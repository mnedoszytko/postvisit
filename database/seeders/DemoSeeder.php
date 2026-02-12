<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Condition;
use App\Models\Consent;
use App\Models\Document;
use App\Models\LibraryItem;
use App\Models\MedicalReference;
use App\Models\Medication;
use App\Models\Notification;
use App\Models\Observation;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Prescription;
use App\Models\Transcript;
use App\Models\UploadToken;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitNote;
use App\Services\AI\TermExtractor;
use App\Services\Medications\OpenFdaClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    /**
     * Seed the database with a complete cardiology demo scenario.
     *
     * Seeds complete cardiology demo with 3 patient scenarios.
     */
    public function run(): void
    {
        // Idempotent: clean up previous demo data before re-seeding
        $this->cleanupExistingDemoData();

        // 1. Organization
        $org = Organization::create([
            'name' => 'City Heart Clinic',
            'type' => 'cardiology',
            'address' => '123 Heart Street, San Francisco, CA',
            'phone' => '+1-415-555-0100',
            'email' => 'info@cityheartclinic.com',
        ]);

        // 2. Practitioner
        $practitioner = Practitioner::create([
            'fhir_practitioner_id' => 'practitioner-'.Str::uuid(),
            'first_name' => 'Michael',
            'last_name' => 'Nedoszytko',
            'email' => 'doctor@demo.postvisit.ai',
            'npi' => '1234567890',
            'license_number' => 'CA-CARD-2024-001',
            'medical_degree' => 'MD, PhD',
            'primary_specialty' => 'cardiology',
            'secondary_specialties' => ['internal_medicine'],
            'organization_id' => $org->id,
        ]);

        // 3. Patient
        $patient = Patient::create([
            'fhir_patient_id' => 'patient-'.Str::uuid(),
            'first_name' => 'Alex',
            'last_name' => 'Johnson',
            'dob' => '1985-03-15',
            'gender' => 'male',
            'email' => 'patient@demo.postvisit.ai',
            'phone' => '+1-555-0123',
            'preferred_language' => 'en',
            'timezone' => 'America/New_York',
            'mrn' => 'MRN-001',
            'height_cm' => 180.3,
            'weight_kg' => 82.0,
            'blood_type' => 'A+',
            'allergies' => [
                ['name' => 'Penicillin', 'severity' => 'severe', 'reaction' => 'Anaphylaxis'],
                ['name' => 'Sulfa drugs', 'severity' => 'moderate', 'reaction' => 'Skin rash'],
            ],
            'emergency_contact_name' => 'Sarah Johnson',
            'emergency_contact_phone' => '+1-555-0124',
            'emergency_contact_relationship' => 'Spouse',
            'consent_given' => true,
            'consent_date' => now(),
            'data_sharing_consent' => true,
        ]);

        // 4. Users
        $doctorUser = User::create([
            'name' => 'Dr. Michael Nedoszytko',
            'email' => 'doctor@demo.postvisit.ai',
            'password' => 'password',
            'role' => 'doctor',
            'practitioner_id' => $practitioner->id,
            'is_active' => true,
        ]);

        $patientUser = User::create([
            'name' => 'Alex Johnson',
            'email' => 'alex.johnson.pvcs@demo.postvisit.ai',
            'password' => 'password',
            'role' => 'patient',
            'patient_id' => $patient->id,
            'is_active' => true,
            'demo_scenario_key' => 'pvcs',
        ]);

        // 5. Visit
        $visitStart = now()->subDay();
        $visit = Visit::create([
            'fhir_encounter_id' => 'encounter-'.Str::uuid(),
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'organization_id' => $org->id,
            'visit_type' => 'office_visit',
            'class' => 'AMB',
            'visit_status' => 'completed',
            'service_type' => 'cardiology_consultation',
            'reason_for_visit' => 'Heart palpitations and irregular heartbeat',
            'reason_codes' => [
                ['system' => 'ICD-10-CM', 'code' => 'R00.2', 'display' => 'Palpitations'],
                ['system' => 'ICD-10-CM', 'code' => 'R00.1', 'display' => 'Bradycardia, unspecified'],
            ],
            'summary' => 'Patient presents with 3-week history of heart palpitations. EKG shows PVCs. Started on Propranolol 40mg BID.',
            'started_at' => $visitStart,
            'ended_at' => $visitStart->copy()->addMinutes(45),
            'duration_minutes' => 45,
            'provider_notes_followup' => 'Follow up in 2 weeks for BP check, 3 months for repeat Holter.',
            'created_by' => $doctorUser->id,
        ]);

        // 6. Observations
        $yesterday = now()->subDay()->toDateString();

        // 6a. Heart Rate
        Observation::create([
            'fhir_observation_id' => 'obs-hr-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '8867-4',
            'code_display' => 'Heart rate',
            'category' => 'vital-signs',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 78,
            'value_unit' => 'bpm',
            'reference_range_low' => 60,
            'reference_range_high' => 100,
            'interpretation' => 'N',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'created_by' => $doctorUser->id,
        ]);

        // 6b. Blood Pressure (systolic/diastolic as string with components in specialty_data)
        Observation::create([
            'fhir_observation_id' => 'obs-bp-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '85354-9',
            'code_display' => 'Blood pressure panel',
            'category' => 'vital-signs',
            'status' => 'final',
            'value_type' => 'string',
            'value_string' => '128/82 mmHg',
            'interpretation' => 'N',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'specialty_data' => [
                'systolic' => ['value' => 128, 'unit' => 'mmHg', 'code' => '8480-6'],
                'diastolic' => ['value' => 82, 'unit' => 'mmHg', 'code' => '8462-4'],
            ],
            'created_by' => $doctorUser->id,
        ]);

        // 6c. 12-Lead EKG
        Observation::create([
            'fhir_observation_id' => 'obs-ekg-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'SNOMED-CT',
            'code' => '29303005',
            'code_display' => '12-Lead electrocardiogram',
            'category' => 'exam',
            'status' => 'final',
            'value_type' => 'string',
            'value_string' => 'Normal sinus rhythm with frequent PVCs. No ST-segment changes. Normal axis.',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'specialty_data' => [
                'rhythm' => 'Normal sinus rhythm',
                'rate' => 78,
                'axis' => 'Normal',
                'intervals' => ['PR' => 160, 'QRS' => 88, 'QTc' => 420],
                'findings' => [
                    'Frequent premature ventricular complexes (PVCs)',
                    'No ST-segment elevation or depression',
                    'Normal P-wave morphology',
                    'No bundle branch block pattern',
                ],
                'interpretation' => 'Abnormal - frequent PVCs noted',
            ],
            'created_by' => $doctorUser->id,
        ]);

        // 6d. Echocardiogram
        Observation::create([
            'fhir_observation_id' => 'obs-echo-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'SNOMED-CT',
            'code' => '40701008',
            'code_display' => 'Echocardiography',
            'category' => 'exam',
            'status' => 'final',
            'value_type' => 'string',
            'value_string' => 'EF 55%, normal wall motion, no valvular abnormalities.',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'specialty_data' => [
                'ejection_fraction' => 55,
                'wall_motion' => 'Normal',
                'lv_size' => 'Normal',
                'rv_function' => 'Normal',
                'valves' => [
                    'mitral' => 'No regurgitation',
                    'aortic' => 'No stenosis or regurgitation',
                    'tricuspid' => 'Trace regurgitation',
                    'pulmonic' => 'Normal',
                ],
                'pericardium' => 'No effusion',
                'conclusion' => 'Normal echocardiogram with preserved ejection fraction',
            ],
            'created_by' => $doctorUser->id,
        ]);

        // 6e. Total Cholesterol
        Observation::create([
            'fhir_observation_id' => 'obs-chol-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '2093-3',
            'code_display' => 'Total cholesterol',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 215,
            'value_unit' => 'mg/dL',
            'reference_range_low' => 0,
            'reference_range_high' => 200,
            'reference_range_text' => 'Desirable: <200 mg/dL',
            'interpretation' => 'H',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'created_by' => $doctorUser->id,
        ]);

        // 6f. Potassium
        Observation::create([
            'fhir_observation_id' => 'obs-k-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '2823-3',
            'code_display' => 'Potassium [Moles/volume] in Serum or Plasma',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 4.2,
            'value_unit' => 'mEq/L',
            'reference_range_low' => 3.5,
            'reference_range_high' => 5.0,
            'reference_range_text' => '3.5-5.0 mEq/L',
            'interpretation' => 'N',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'created_by' => $doctorUser->id,
        ]);

        // 6g. TSH
        Observation::create([
            'fhir_observation_id' => 'obs-tsh-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '3016-3',
            'code_display' => 'Thyrotropin [Units/volume] in Serum or Plasma',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 2.1,
            'value_unit' => 'mIU/L',
            'reference_range_low' => 0.4,
            'reference_range_high' => 4.0,
            'reference_range_text' => '0.4-4.0 mIU/L',
            'interpretation' => 'N',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'created_by' => $doctorUser->id,
        ]);

        // 6h. BNP (Brain Natriuretic Peptide) — elevated in heart failure
        Observation::create([
            'fhir_observation_id' => 'obs-bnp-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '30934-4',
            'code_display' => 'Natriuretic peptide B [Mass/volume] in Serum or Plasma',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 450,
            'value_unit' => 'pg/mL',
            'reference_range_low' => 0,
            'reference_range_high' => 100,
            'reference_range_text' => 'Normal: <100 pg/mL; HF likely: >400 pg/mL',
            'interpretation' => 'H',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'specialty_data' => [
                'clinical_significance' => 'Elevated BNP supports diagnosis of heart failure',
                'hf_threshold' => 400,
                'gray_zone' => '100-400 pg/mL',
            ],
            'created_by' => $doctorUser->id,
        ]);

        // 6i. NT-proBNP — more sensitive HF biomarker
        Observation::create([
            'fhir_observation_id' => 'obs-ntprobnp-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '33762-6',
            'code_display' => 'NT-proBNP [Mass/volume] in Serum or Plasma',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 1850,
            'value_unit' => 'pg/mL',
            'reference_range_low' => 0,
            'reference_range_high' => 300,
            'reference_range_text' => 'Normal <50y: <300 pg/mL; HF likely: >900 pg/mL',
            'interpretation' => 'H',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'specialty_data' => [
                'clinical_significance' => 'Markedly elevated NT-proBNP consistent with decompensated heart failure',
                'age_adjusted_cutoff' => 300,
            ],
            'created_by' => $doctorUser->id,
        ]);

        // 6j. Creatinine — renal function monitoring in HF
        Observation::create([
            'fhir_observation_id' => 'obs-creat-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '2160-0',
            'code_display' => 'Creatinine [Mass/volume] in Serum or Plasma',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 1.4,
            'value_unit' => 'mg/dL',
            'reference_range_low' => 0.7,
            'reference_range_high' => 1.3,
            'reference_range_text' => '0.7-1.3 mg/dL',
            'interpretation' => 'H',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'specialty_data' => [
                'clinical_significance' => 'Mildly elevated creatinine — monitor for cardiorenal syndrome',
                'egfr_estimated' => 58,
            ],
            'created_by' => $doctorUser->id,
        ]);

        // 6k. BUN (Blood Urea Nitrogen) — renal monitoring
        Observation::create([
            'fhir_observation_id' => 'obs-bun-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '3094-0',
            'code_display' => 'Urea nitrogen [Mass/volume] in Serum or Plasma',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 28,
            'value_unit' => 'mg/dL',
            'reference_range_low' => 7,
            'reference_range_high' => 20,
            'reference_range_text' => '7-20 mg/dL',
            'interpretation' => 'H',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'created_by' => $doctorUser->id,
        ]);

        // 6l. Sodium — electrolyte monitoring in HF
        Observation::create([
            'fhir_observation_id' => 'obs-na-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '2951-2',
            'code_display' => 'Sodium [Moles/volume] in Serum or Plasma',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 134,
            'value_unit' => 'mEq/L',
            'reference_range_low' => 136,
            'reference_range_high' => 145,
            'reference_range_text' => '136-145 mEq/L',
            'interpretation' => 'L',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'specialty_data' => [
                'clinical_significance' => 'Mild hyponatremia — common in HF due to fluid overload and neurohormonal activation',
            ],
            'created_by' => $doctorUser->id,
        ]);

        // 6m. LDL Cholesterol
        Observation::create([
            'fhir_observation_id' => 'obs-ldl-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '2089-1',
            'code_display' => 'LDL Cholesterol [Mass/volume] in Serum or Plasma',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 142,
            'value_unit' => 'mg/dL',
            'reference_range_low' => 0,
            'reference_range_high' => 100,
            'reference_range_text' => 'Optimal: <100 mg/dL; Near optimal: 100-129',
            'interpretation' => 'H',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'created_by' => $doctorUser->id,
        ]);

        // 6n. HDL Cholesterol
        Observation::create([
            'fhir_observation_id' => 'obs-hdl-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '2085-9',
            'code_display' => 'HDL Cholesterol [Mass/volume] in Serum or Plasma',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 38,
            'value_unit' => 'mg/dL',
            'reference_range_low' => 40,
            'reference_range_high' => 60,
            'reference_range_text' => 'Desirable: >40 mg/dL (men), >50 mg/dL (women)',
            'interpretation' => 'L',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'created_by' => $doctorUser->id,
        ]);

        // 6o. Triglycerides
        Observation::create([
            'fhir_observation_id' => 'obs-trig-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '2571-8',
            'code_display' => 'Triglycerides [Mass/volume] in Serum or Plasma',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 178,
            'value_unit' => 'mg/dL',
            'reference_range_low' => 0,
            'reference_range_high' => 150,
            'reference_range_text' => 'Normal: <150 mg/dL; Borderline: 150-199',
            'interpretation' => 'H',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'created_by' => $doctorUser->id,
        ]);

        // 6p. Hemoglobin — anemia screening in HF
        Observation::create([
            'fhir_observation_id' => 'obs-hgb-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC',
            'code' => '718-7',
            'code_display' => 'Hemoglobin [Mass/volume] in Blood',
            'category' => 'laboratory',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => 12.8,
            'value_unit' => 'g/dL',
            'reference_range_low' => 13.5,
            'reference_range_high' => 17.5,
            'reference_range_text' => '13.5-17.5 g/dL (men)',
            'interpretation' => 'L',
            'effective_date' => $yesterday,
            'issued_at' => now()->subDay(),
            'specialty_data' => [
                'clinical_significance' => 'Mild anemia — common in HF, worsens symptoms and prognosis',
            ],
            'created_by' => $doctorUser->id,
        ]);

        // 6h. Body Weight series (HF weight monitoring scenario)
        $weightData = [
            ['days_ago' => 10, 'weight' => 82.0],
            ['days_ago' => 9, 'weight' => 82.1],
            ['days_ago' => 8, 'weight' => 82.3],
            ['days_ago' => 7, 'weight' => 82.5],
            ['days_ago' => 6, 'weight' => 82.8],
            ['days_ago' => 5, 'weight' => 83.0],
            ['days_ago' => 4, 'weight' => 83.1],
            ['days_ago' => 3, 'weight' => 83.0],
            ['days_ago' => 2, 'weight' => 84.2],
            ['days_ago' => 1, 'weight' => 85.3],
        ];

        foreach ($weightData as $w) {
            Observation::create([
                'fhir_observation_id' => 'obs-wt-'.Str::uuid(),
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'practitioner_id' => $practitioner->id,
                'code_system' => 'LOINC',
                'code' => '29463-7',
                'code_display' => 'Body weight',
                'category' => 'vital-signs',
                'status' => 'final',
                'value_type' => 'quantity',
                'value_quantity' => $w['weight'],
                'value_unit' => 'kg',
                'reference_range_low' => 75,
                'reference_range_high' => 90,
                'reference_range_text' => 'Target: 80-84 kg',
                'interpretation' => $w['weight'] > 84 ? 'H' : 'N',
                'effective_date' => now()->subDays($w['days_ago'])->toDateString(),
                'issued_at' => now()->subDays($w['days_ago']),
                'specialty_data' => [
                    'monitoring_context' => 'heart_failure',
                    'dry_weight' => 82.0,
                    'alert_threshold_kg' => 2.0,
                    'alert_threshold_days' => 3,
                ],
                'created_by' => $doctorUser->id,
            ]);
        }

        // 6q. Blood Pressure series (HTN monitoring scenario)
        $bpSeries = [
            ['days_ago' => 9, 'sys' => 142, 'dia' => 90, 'interp' => 'H', 'stage' => 'stage_2'],
            ['days_ago' => 8, 'sys' => 138, 'dia' => 88, 'interp' => 'H', 'stage' => 'stage_1'],
            ['days_ago' => 7, 'sys' => 135, 'dia' => 86, 'interp' => 'H', 'stage' => 'stage_1'],
            ['days_ago' => 6, 'sys' => 132, 'dia' => 84, 'interp' => 'H', 'stage' => 'stage_1'],
            ['days_ago' => 5, 'sys' => 128, 'dia' => 82, 'interp' => 'N', 'stage' => 'elevated'],
            ['days_ago' => 4, 'sys' => 136, 'dia' => 87, 'interp' => 'H', 'stage' => 'stage_1'],
            ['days_ago' => 3, 'sys' => 125, 'dia' => 80, 'interp' => 'N', 'stage' => 'elevated'],
            ['days_ago' => 2, 'sys' => 122, 'dia' => 78, 'interp' => 'N', 'stage' => 'normal'],
            ['days_ago' => 1, 'sys' => 130, 'dia' => 84, 'interp' => 'H', 'stage' => 'stage_1'],
        ];

        foreach ($bpSeries as $bp) {
            Observation::create([
                'fhir_observation_id' => 'obs-bp-'.Str::uuid(),
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'practitioner_id' => $practitioner->id,
                'code_system' => 'LOINC',
                'code' => '85354-9',
                'code_display' => 'Blood pressure panel',
                'category' => 'vital-signs',
                'status' => 'final',
                'value_type' => 'string',
                'value_string' => "{$bp['sys']}/{$bp['dia']} mmHg",
                'interpretation' => $bp['interp'],
                'effective_date' => now()->subDays($bp['days_ago'])->toDateString(),
                'issued_at' => now()->subDays($bp['days_ago']),
                'specialty_data' => [
                    'systolic' => ['value' => $bp['sys'], 'unit' => 'mmHg', 'code' => '8480-6'],
                    'diastolic' => ['value' => $bp['dia'], 'unit' => 'mmHg', 'code' => '8462-4'],
                    'htn_stage' => $bp['stage'],
                ],
                'created_by' => $doctorUser->id,
            ]);
        }

        // 7. Condition: PVCs
        Condition::create([
            'fhir_condition_id' => 'condition-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'code_system' => 'ICD-10-CM',
            'code' => 'I49.3',
            'code_display' => 'Premature ventricular contractions',
            'category' => 'encounter-diagnosis',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'severity' => 'moderate',
            'onset_date' => now()->subWeeks(3)->toDateString(),
            'clinical_notes' => 'Patient reports frequent palpitations for 3 weeks. EKG confirms frequent PVCs. Echocardiogram shows preserved EF. Starting beta-blocker therapy.',
            'created_by' => $doctorUser->id,
        ]);

        // 8. Medication: Propranolol
        $medication = Medication::create([
            'rxnorm_code' => '8787',
            'atc_code' => 'C07AA05',
            'generic_name' => 'Propranolol Hydrochloride',
            'brand_names' => ['Inderal', 'Inderal LA'],
            'display_name' => 'Propranolol 40mg tablet',
            'form' => 'tablet',
            'strength_value' => 40,
            'strength_unit' => 'mg',
            'ingredients' => ['propranolol hydrochloride'],
            'black_box_warning' => false,
            'pregnancy_category' => 'C',
            'source' => 'rxnorm',
            'source_last_updated' => now(),
            'is_active' => true,
        ]);

        // 9. Prescription: Propranolol 40mg BID
        Prescription::create([
            'fhir_medication_request_id' => 'rx-'.Str::uuid(),
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'visit_id' => $visit->id,
            'medication_id' => $medication->id,
            'status' => 'active',
            'intent' => 'order',
            'dose_quantity' => 40,
            'dose_unit' => 'mg',
            'frequency' => 'BID',
            'frequency_text' => 'Every 12 hours with meals',
            'route' => 'oral',
            'start_date' => now()->toDateString(),
            'duration_days' => 30,
            'number_of_refills' => 3,
            'refills_remaining' => 3,
            'special_instructions' => 'Take with food. Do not stop abruptly. Monitor heart rate and blood pressure.',
            'indication' => 'Premature ventricular contractions',
            'indication_code' => 'I49.3',
            'substitution_allowed' => true,
            'created_by' => $doctorUser->id,
        ]);

        // 10. Visit Note (SOAP)
        VisitNote::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'author_practitioner_id' => $practitioner->id,
            'composition_type' => 'progress_note',
            'status' => 'final',
            'chief_complaint' => 'Heart palpitations and irregular heartbeat for 3 weeks',
            'history_of_present_illness' => 'Mr. Johnson is a 40-year-old male presenting with a 3-week history of intermittent heart palpitations described as a "fluttering" sensation in the chest. Episodes occur 5-10 times daily, lasting seconds to minutes, and are not associated with syncope, chest pain, or dyspnea. He reports increased stress at work and higher caffeine intake recently (4-5 cups of coffee/day). No prior cardiac history. No family history of sudden cardiac death. Non-smoker, occasional alcohol use.',
            'review_of_systems' => 'Constitutional: No fever, weight changes. Cardiovascular: Palpitations as noted, no chest pain, no edema. Respiratory: No dyspnea, no cough. GI: No nausea, normal appetite. Neurological: No dizziness, no syncope.',
            'physical_exam' => 'General: Well-appearing male in no acute distress. Vitals: HR 78, BP 128/82, RR 16, SpO2 99% on RA. Cardiovascular: Regular rate with occasional ectopy, no murmurs, no gallops. Lungs: Clear bilaterally. Extremities: No edema, pulses 2+ bilaterally.',
            'assessment' => 'Premature ventricular contractions (PVCs) - frequent, symptomatic. Likely benign given preserved ejection fraction and normal echocardiogram. Contributing factors include elevated caffeine intake and psychosocial stress.',
            'assessment_codes' => [
                ['system' => 'ICD-10-CM', 'code' => 'I49.3', 'display' => 'Premature ventricular contractions'],
            ],
            'plan' => "1. Start Propranolol 40mg PO BID with meals for symptomatic PVC suppression.\n2. Reduce caffeine intake to 1 cup/day maximum.\n3. Lifestyle modifications: regular exercise, stress management techniques.\n4. Patient education on PVCs provided - reassurance that condition is typically benign with normal heart structure.\n5. Order 24-hour Holter monitor in 3 months to quantify PVC burden on therapy.",
            'medical_terms' => [
                'chief_complaint' => [
                    ['term' => 'palpitations', 'start' => 6, 'end' => 18, 'definition' => 'A sensation of your heart beating rapidly, fluttering, or pounding. In your case, these are caused by extra heartbeats called PVCs.'],
                    ['term' => 'irregular heartbeat', 'start' => 23, 'end' => 42, 'definition' => 'When the heart doesn\'t beat in its normal steady rhythm. Your EKG showed extra beats (PVCs) causing this irregularity.'],
                ],
                'history_of_present_illness' => [
                    ['term' => 'intermittent', 'start' => 70, 'end' => 82, 'definition' => 'Coming and going, not constant. Your palpitations happen on and off throughout the day.'],
                    ['term' => 'heart palpitations', 'start' => 83, 'end' => 101, 'definition' => 'The feeling of your heart racing, fluttering, or skipping beats. You described these as a "fluttering" sensation.'],
                    ['term' => 'fluttering', 'start' => 118, 'end' => 128, 'definition' => 'A common way patients describe palpitations — a light, rapid vibrating feeling in the chest caused by extra heartbeats.'],
                    ['term' => 'syncope', 'start' => 243, 'end' => 250, 'definition' => 'Fainting or passing out. Good news — you have NOT experienced this, which suggests your PVCs are not dangerous.'],
                    ['term' => 'chest pain', 'start' => 252, 'end' => 262, 'definition' => 'Pain or discomfort in the chest area. You reported no chest pain, which is reassuring alongside your normal heart structure.'],
                    ['term' => 'dyspnea', 'start' => 267, 'end' => 274, 'definition' => 'Medical term for shortness of breath or difficulty breathing. You do not have this symptom.'],
                    ['term' => 'caffeine', 'start' => 323, 'end' => 331, 'definition' => 'A stimulant found in coffee, tea, and energy drinks. Your intake of 4-5 cups/day may be triggering extra heartbeats. Reducing to 1 cup/day is recommended.'],
                    ['term' => 'cardiac', 'start' => 383, 'end' => 390, 'definition' => 'Relating to the heart. "No prior cardiac history" means you haven\'t had heart problems before.'],
                    ['term' => 'sudden cardiac death', 'start' => 421, 'end' => 441, 'definition' => 'An unexpected death caused by a sudden heart malfunction. No family history of this is a positive sign for your prognosis.'],
                ],
                'review_of_systems' => [
                    ['term' => 'Palpitations', 'start' => 58, 'end' => 70, 'definition' => 'The sensation of feeling your heartbeat — racing, fluttering, or pounding. This is your main symptom.'],
                    ['term' => 'chest pain', 'start' => 84, 'end' => 94, 'definition' => 'Pain or discomfort in the chest. You denied having chest pain, which is reassuring.'],
                    ['term' => 'edema', 'start' => 99, 'end' => 104, 'definition' => 'Swelling caused by fluid buildup, usually in legs or ankles. No edema means your heart is pumping effectively.'],
                    ['term' => 'dyspnea', 'start' => 122, 'end' => 129, 'definition' => 'Shortness of breath. You do not have this, which supports a benign cause for your palpitations.'],
                    ['term' => 'nausea', 'start' => 148, 'end' => 154, 'definition' => 'A feeling of sickness with an urge to vomit. You do not have this symptom.'],
                    ['term' => 'dizziness', 'start' => 190, 'end' => 199, 'definition' => 'Feeling lightheaded or unsteady. You denied dizziness, which is a good sign that your heart is maintaining adequate blood flow.'],
                    ['term' => 'syncope', 'start' => 204, 'end' => 211, 'definition' => 'Fainting or temporary loss of consciousness. You have not experienced this.'],
                ],
                'physical_exam' => [
                    ['term' => 'acute distress', 'start' => 35, 'end' => 49, 'definition' => 'Signs of serious discomfort or illness. "No acute distress" means you appeared comfortable during the exam.'],
                    ['term' => 'HR', 'start' => 59, 'end' => 61, 'definition' => 'Heart Rate — the number of heartbeats per minute. Your HR of 78 is normal (60-100 bpm is the healthy range).'],
                    ['term' => 'BP', 'start' => 66, 'end' => 68, 'definition' => 'Blood Pressure — the force of blood against artery walls. Your BP of 128/82 is slightly elevated but near normal.'],
                    ['term' => 'RR', 'start' => 77, 'end' => 79, 'definition' => 'Respiratory Rate — breaths per minute. Your RR of 16 is normal (12-20 is typical).'],
                    ['term' => 'SpO2', 'start' => 84, 'end' => 88, 'definition' => 'Oxygen saturation — how much oxygen your blood is carrying. 99% is excellent (95% or above is normal).'],
                    ['term' => 'RA', 'start' => 96, 'end' => 98, 'definition' => 'Room Air — meaning you were breathing normally without supplemental oxygen when this was measured.'],
                    ['term' => 'ectopy', 'start' => 145, 'end' => 151, 'definition' => 'Extra heartbeats originating from an abnormal location in the heart. In your case, these are the PVCs causing your palpitations.'],
                    ['term' => 'murmurs', 'start' => 156, 'end' => 163, 'definition' => 'Abnormal heart sounds caused by turbulent blood flow. No murmurs means your heart valves are working normally.'],
                    ['term' => 'gallops', 'start' => 168, 'end' => 175, 'definition' => 'Extra heart sounds that can indicate heart failure. No gallops is a positive finding.'],
                    ['term' => 'edema', 'start' => 219, 'end' => 224, 'definition' => 'Swelling from fluid buildup. No edema in your legs means your heart is pumping well.'],
                ],
                'assessment' => [
                    ['term' => 'Premature ventricular contractions', 'start' => 0, 'end' => 34, 'definition' => 'Extra heartbeats that start in the lower chambers (ventricles) of your heart. They happen before the regular beat, causing that "skipping" sensation you feel.'],
                    ['term' => 'PVCs', 'start' => 36, 'end' => 40, 'definition' => 'Short for Premature Ventricular Contractions — the extra heartbeats causing your palpitations.'],
                    ['term' => 'benign', 'start' => 74, 'end' => 80, 'definition' => 'Not harmful or dangerous. Your PVCs are likely benign because your heart structure and pumping function are normal.'],
                    ['term' => 'ejection fraction', 'start' => 97, 'end' => 114, 'definition' => 'The percentage of blood pumped out with each heartbeat. Your EF of 55% is normal (50-70% is healthy), meaning your heart is pumping well.'],
                    ['term' => 'echocardiogram', 'start' => 126, 'end' => 140, 'definition' => 'An ultrasound of your heart that shows its structure and how well it pumps. Yours was normal — no valve problems or structural issues.'],
                    ['term' => 'caffeine', 'start' => 180, 'end' => 188, 'definition' => 'A stimulant that can trigger extra heartbeats. Reducing your intake from 4-5 cups to 1 cup/day should help reduce PVC frequency.'],
                    ['term' => 'psychosocial stress', 'start' => 200, 'end' => 219, 'definition' => 'Stress from work, relationships, or life situations. Stress increases adrenaline, which can trigger more PVCs. Stress management is part of your treatment plan.'],
                ],
                'plan' => [
                    ['term' => 'Propranolol', 'start' => 9, 'end' => 20, 'definition' => 'A beta-blocker medication that slows your heart rate and reduces the force of contractions. It helps suppress PVCs and reduce palpitation symptoms.'],
                    ['term' => '40mg', 'start' => 21, 'end' => 25, 'definition' => 'The dose of Propranolol prescribed — 40 milligrams per tablet, taken twice daily.'],
                    ['term' => 'PO', 'start' => 26, 'end' => 28, 'definition' => '"Per os" — Latin for "by mouth." This means the medication is taken as a pill, not injected.'],
                    ['term' => 'BID', 'start' => 29, 'end' => 32, 'definition' => '"Bis in die" — Latin for "twice a day." Take one pill in the morning and one in the evening, with meals.'],
                    ['term' => 'PVC suppression', 'start' => 60, 'end' => 75, 'definition' => 'Reducing the number of extra heartbeats. The Propranolol aims to make PVCs less frequent and less noticeable.'],
                    ['term' => 'caffeine', 'start' => 87, 'end' => 95, 'definition' => 'Limit to 1 cup of coffee per day. Caffeine is a known trigger for PVCs in many patients.'],
                    ['term' => 'PVCs', 'start' => 225, 'end' => 229, 'definition' => 'Premature Ventricular Contractions — the extra heartbeats causing your symptoms. Education about PVCs helps reduce anxiety, which itself can trigger more PVCs.'],
                    ['term' => 'benign', 'start' => 281, 'end' => 287, 'definition' => 'Not dangerous. With a normal heart structure, PVCs are typically harmless even though they feel uncomfortable.'],
                    ['term' => 'Holter monitor', 'start' => 334, 'end' => 348, 'definition' => 'A portable device you wear for 24 hours that continuously records your heart rhythm. This will show exactly how many PVCs you\'re having and whether the medication is working.'],
                    ['term' => 'PVC burden', 'start' => 373, 'end' => 383, 'definition' => 'The percentage of your total heartbeats that are PVCs. A burden under 10% is generally considered safe. The Holter monitor will measure this.'],
                ],
            ],
            'follow_up' => '2 weeks for blood pressure check and medication tolerance assessment. 3 months for repeat Holter monitor and cardiology follow-up.',
            'follow_up_timeframe' => '2 weeks',
            'is_signed' => true,
            'signed_at' => now()->subDay(),
        ]);

        // 11. Transcript
        Transcript::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'source_type' => 'ambient_device',
            'stt_provider' => 'whisper',
            'audio_duration_seconds' => 1590,
            'raw_transcript' => file_get_contents(database_path('../demo/transcript.txt')),
            'processing_status' => 'completed',
            'patient_consent_given' => true,
            'consent_timestamp' => now()->subDay(),
        ]);

        // 12. Chat Session
        ChatSession::create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'topic' => 'Post-visit follow-up: PVCs and Propranolol',
            'status' => 'active',
            'initiated_at' => now(),
        ]);

        // 13. Longitudinal vitals for Alex (home readings over 2 weeks)
        $this->seedAlexLongitudinalVitals($patient, $practitioner, $doctorUser);

        // 14. Historical lab data for trend charts
        $this->seedLabHistory($patient, $visit, $practitioner, $doctorUser);

        // 15. Medical References (real, verified PMIDs/DOIs)
        $this->seedMedicalReferences();

        // ====================================================================
        // SCENARIO 2: Heart Failure — Maria Santos
        // ====================================================================
        $this->seedHeartFailureScenario($org, $practitioner, $doctorUser);

        // ====================================================================
        // SCENARIO 3: Hypertension — James Williams
        // ====================================================================
        $this->seedHypertensionScenario($org, $practitioner, $doctorUser);

        // Seed demo notifications and audit logs for the doctor dashboard
        $this->seedDemoNotifications($doctorUser);
        $this->seedDemoAuditLogs($doctorUser);

        // Extract medical terms for visit notes that don't have them yet
        // (PVC visit has hardcoded terms; HF and HTN need AI extraction)
        $this->extractMissingMedicalTerms();

        // Pre-warm FDA safety cache for demo medications
        $this->warmFdaCache();
    }

    /**
     * Run TermExtractor on visit notes that have SOAP content but no medical_terms.
     */
    private function extractMissingMedicalTerms(): void
    {
        $notes = VisitNote::where(function ($q) {
            $q->whereNull('medical_terms')
                ->orWhere('medical_terms', '[]')
                ->orWhere('medical_terms', '{}');
        })->whereNotNull('chief_complaint')->get();

        if ($notes->isEmpty()) {
            return;
        }

        $extractor = app(TermExtractor::class);

        foreach ($notes as $note) {
            try {
                $result = $extractor->extract($note);
                $count = collect($result)->flatten(1)->count();
                Log::info("DemoSeeder: extracted {$count} medical terms", ['visit_id' => $note->visit_id]);
            } catch (\Throwable $e) {
                Log::warning('DemoSeeder: term extraction failed (non-fatal)', [
                    'visit_id' => $note->visit_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Pre-warm the FDA safety cache for all demo medications.
     *
     * This prevents 5-15s blocking calls to the FDA API on the first chat
     * request for each medication. The cache key mirrors ContextAssembler::getFdaSafetyForMedication().
     */
    private function warmFdaCache(): void
    {
        $openFda = app(OpenFdaClient::class);
        $medications = Medication::all();

        foreach ($medications as $med) {
            if (! $med->generic_name) {
                continue;
            }

            $cacheKey = 'fda_safety:'.mb_strtolower($med->generic_name);

            if (Cache::has($cacheKey)) {
                continue;
            }

            try {
                $parts = [];

                $adverse = $openFda->getAdverseEvents($med->generic_name, 5);
                if (! empty($adverse['events'])) {
                    $parts[] = "\nFDA Adverse Event Reports for {$med->generic_name}:";
                    foreach ($adverse['events'] as $event) {
                        $parts[] = "- {$event['reaction']}: {$event['count']} reports";
                    }
                }

                $label = $openFda->getDrugLabel($med->generic_name);
                if (! empty($label)) {
                    if (! empty($label['boxed_warning'])) {
                        $parts[] = "\nBOXED WARNING: ".mb_substr($label['boxed_warning'], 0, 500);
                    }
                    if (! empty($label['information_for_patients'])) {
                        $parts[] = "\nPatient Info: ".mb_substr($label['information_for_patients'], 0, 500);
                    }
                }

                $value = $parts !== [] ? implode("\n", $parts) : null;
                Cache::put($cacheKey, $value, 86400);

                Log::info("DemoSeeder: warmed FDA cache for {$med->generic_name}");
            } catch (\Throwable $e) {
                // Non-fatal — cache miss just means slower first chat request
                Log::warning('DemoSeeder: FDA cache warm failed (non-fatal)', [
                    'medication' => $med->generic_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Alex Johnson: home BP and HR readings over 14 days (post-propranolol start).
     */
    private function seedAlexLongitudinalVitals(Patient $patient, Practitioner $practitioner, User $doctor): void
    {
        $baseDate = now()->subDays(14);

        $readings = [
            ['day' => 0, 'hr' => 82, 'sys' => 130, 'dia' => 84],
            ['day' => 1, 'hr' => 80, 'sys' => 128, 'dia' => 82],
            ['day' => 2, 'hr' => 78, 'sys' => 126, 'dia' => 80],
            ['day' => 3, 'hr' => 76, 'sys' => 125, 'dia' => 79],
            ['day' => 5, 'hr' => 74, 'sys' => 124, 'dia' => 78],
            ['day' => 7, 'hr' => 72, 'sys' => 122, 'dia' => 76],
            ['day' => 9, 'hr' => 70, 'sys' => 120, 'dia' => 75],
            ['day' => 11, 'hr' => 68, 'sys' => 118, 'dia' => 74],
            ['day' => 13, 'hr' => 66, 'sys' => 116, 'dia' => 72],
        ];

        foreach ($readings as $r) {
            $date = $baseDate->copy()->addDays($r['day']);

            Observation::create([
                'fhir_observation_id' => 'obs-hr-home-'.Str::uuid(),
                'patient_id' => $patient->id,
                'practitioner_id' => $practitioner->id,
                'code_system' => 'LOINC',
                'code' => '8867-4',
                'code_display' => 'Heart rate',
                'category' => 'vital-signs',
                'status' => 'final',
                'value_type' => 'quantity',
                'value_quantity' => $r['hr'],
                'value_unit' => 'bpm',
                'reference_range_low' => 60,
                'reference_range_high' => 100,
                'interpretation' => 'N',
                'effective_date' => $date->toDateString(),
                'issued_at' => $date,
                'specialty_data' => ['source' => 'apple_watch', 'context' => 'resting'],
                'created_by' => $doctor->id,
            ]);

            Observation::create([
                'fhir_observation_id' => 'obs-bp-home-'.Str::uuid(),
                'patient_id' => $patient->id,
                'practitioner_id' => $practitioner->id,
                'code_system' => 'LOINC',
                'code' => '85354-9',
                'code_display' => 'Blood pressure panel',
                'category' => 'vital-signs',
                'status' => 'final',
                'value_type' => 'string',
                'value_string' => $r['sys'].'/'.$r['dia'].' mmHg',
                'interpretation' => $r['sys'] > 130 ? 'H' : 'N',
                'effective_date' => $date->toDateString(),
                'issued_at' => $date,
                'specialty_data' => [
                    'systolic' => ['value' => $r['sys'], 'unit' => 'mmHg'],
                    'diastolic' => ['value' => $r['dia'], 'unit' => 'mmHg'],
                    'source' => 'home_monitor',
                ],
                'created_by' => $doctor->id,
            ]);
        }
    }

    /**
     * Seed historical lab readings for Alex Johnson to enable trend charts.
     *
     * Adds 3-4 older readings per key marker, creating realistic clinical trends.
     * The "latest" values already seeded above remain as the most recent data points.
     */
    private function seedLabHistory(Patient $patient, Visit $visit, Practitioner $practitioner, User $doctor): void
    {
        // Each entry: [code, code_display, category, unit, ref_low, ref_high, ref_text, readings[]]
        // readings: [months_ago => value, interpretation]
        $markers = [
            [
                'code' => '2093-3',
                'code_display' => 'Total cholesterol',
                'unit' => 'mg/dL',
                'ref_low' => 0, 'ref_high' => 200,
                'ref_text' => 'Desirable: <200 mg/dL',
                'readings' => [
                    [6, 248, 'H'], [4, 238, 'H'], [2, 225, 'H'],
                ],
            ],
            [
                'code' => '2089-1',
                'code_display' => 'LDL Cholesterol [Mass/volume] in Serum or Plasma',
                'unit' => 'mg/dL',
                'ref_low' => 0, 'ref_high' => 100,
                'ref_text' => 'Optimal: <100 mg/dL; Near optimal: 100-129',
                'readings' => [
                    [6, 168, 'H'], [4, 160, 'H'], [2, 152, 'H'],
                ],
            ],
            [
                'code' => '2085-9',
                'code_display' => 'HDL Cholesterol [Mass/volume] in Serum or Plasma',
                'unit' => 'mg/dL',
                'ref_low' => 40, 'ref_high' => 60,
                'ref_text' => 'Desirable: >40 mg/dL (men), >50 mg/dL (women)',
                'readings' => [
                    [6, 35, 'L'], [4, 36, 'L'], [2, 37, 'L'],
                ],
            ],
            [
                'code' => '2571-8',
                'code_display' => 'Triglycerides [Mass/volume] in Serum or Plasma',
                'unit' => 'mg/dL',
                'ref_low' => 0, 'ref_high' => 150,
                'ref_text' => 'Normal: <150 mg/dL; Borderline: 150-199',
                'readings' => [
                    [6, 195, 'H'], [4, 210, 'H'], [2, 185, 'H'],
                ],
            ],
            [
                'code' => '2823-3',
                'code_display' => 'Potassium [Moles/volume] in Serum or Plasma',
                'unit' => 'mEq/L',
                'ref_low' => 3.5, 'ref_high' => 5.0,
                'ref_text' => '3.5-5.0 mEq/L',
                'readings' => [
                    [6, 4.0, 'N'], [4, 4.1, 'N'], [2, 4.3, 'N'],
                ],
            ],
            [
                'code' => '3016-3',
                'code_display' => 'Thyrotropin [Units/volume] in Serum or Plasma',
                'unit' => 'mIU/L',
                'ref_low' => 0.4, 'ref_high' => 4.0,
                'ref_text' => '0.4-4.0 mIU/L',
                'readings' => [
                    [6, 1.8, 'N'], [4, 2.0, 'N'], [2, 2.3, 'N'],
                ],
            ],
            [
                'code' => '2160-0',
                'code_display' => 'Creatinine [Mass/volume] in Serum or Plasma',
                'unit' => 'mg/dL',
                'ref_low' => 0.7, 'ref_high' => 1.3,
                'ref_text' => '0.7-1.3 mg/dL',
                'readings' => [
                    [6, 1.1, 'N'], [4, 1.2, 'N'], [2, 1.3, 'N'],
                ],
            ],
            [
                'code' => '718-7',
                'code_display' => 'Hemoglobin [Mass/volume] in Blood',
                'unit' => 'g/dL',
                'ref_low' => 13.5, 'ref_high' => 17.5,
                'ref_text' => '13.5-17.5 g/dL (men)',
                'readings' => [
                    [6, 14.2, 'N'], [4, 13.8, 'N'], [2, 13.2, 'L'],
                ],
            ],
            [
                'code' => '30934-4',
                'code_display' => 'Natriuretic peptide B [Mass/volume] in Serum or Plasma',
                'unit' => 'pg/mL',
                'ref_low' => 0, 'ref_high' => 100,
                'ref_text' => 'Normal: <100 pg/mL; HF likely: >400 pg/mL',
                'readings' => [
                    [6, 180, 'H'], [4, 250, 'H'], [2, 350, 'H'],
                ],
            ],
        ];

        foreach ($markers as $marker) {
            foreach ($marker['readings'] as [$monthsAgo, $value, $interp]) {
                $date = now()->subMonths($monthsAgo);
                Observation::create([
                    'fhir_observation_id' => 'obs-hist-'.Str::uuid(),
                    'patient_id' => $patient->id,
                    'visit_id' => $visit->id,
                    'practitioner_id' => $practitioner->id,
                    'code_system' => 'LOINC',
                    'code' => $marker['code'],
                    'code_display' => $marker['code_display'],
                    'category' => 'laboratory',
                    'status' => 'final',
                    'value_type' => 'quantity',
                    'value_quantity' => $value,
                    'value_unit' => $marker['unit'],
                    'reference_range_low' => $marker['ref_low'],
                    'reference_range_high' => $marker['ref_high'],
                    'reference_range_text' => $marker['ref_text'],
                    'interpretation' => $interp,
                    'effective_date' => $date->toDateString(),
                    'issued_at' => $date,
                    'created_by' => $doctor->id,
                ]);
            }
        }
    }

    /**
     * Scenario 2: Heart failure patient — Maria Santos (HFrEF).
     */
    private function seedHeartFailureScenario(Organization $org, Practitioner $practitioner, User $doctor): void
    {
        $patient = Patient::create([
            'fhir_patient_id' => 'patient-'.Str::uuid(),
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'dob' => '1958-11-22',
            'gender' => 'female',
            'email' => 'maria@demo.postvisit.ai',
            'phone' => '+1-555-0456',
            'preferred_language' => 'en',
            'timezone' => 'America/New_York',
            'mrn' => 'MRN-002',
            'height_cm' => 162.5,
            'weight_kg' => 73.5,
            'blood_type' => 'O+',
            'allergies' => [
                ['name' => 'Aspirin', 'severity' => 'mild', 'reaction' => 'Stomach upset'],
            ],
            'emergency_contact_name' => 'Carlos Santos',
            'emergency_contact_phone' => '+1-555-0457',
            'emergency_contact_relationship' => 'Husband',
            'consent_given' => true,
            'consent_date' => now(),
            'data_sharing_consent' => true,
        ]);

        User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@demo.postvisit.ai',
            'password' => 'password',
            'role' => 'patient',
            'patient_id' => $patient->id,
            'is_active' => true,
        ]);

        $visitStart = now()->subDays(3);
        $visit = Visit::create([
            'fhir_encounter_id' => 'encounter-'.Str::uuid(),
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'organization_id' => $org->id,
            'visit_type' => 'office_visit',
            'class' => 'AMB',
            'visit_status' => 'completed',
            'service_type' => 'cardiology_consultation',
            'reason_for_visit' => 'Heart failure follow-up — worsening shortness of breath and weight gain',
            'summary' => 'HFrEF patient with recent decompensation. Weight up 4 kg in 2 weeks. Adjusted diuretics, added SGLT2i.',
            'started_at' => $visitStart,
            'ended_at' => $visitStart->copy()->addMinutes(35),
            'duration_minutes' => 35,
            'created_by' => $doctor->id,
        ]);

        Condition::create([
            'fhir_condition_id' => 'condition-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'code_system' => 'ICD-10-CM',
            'code' => 'I50.22',
            'code_display' => 'Chronic systolic heart failure',
            'category' => 'encounter-diagnosis',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'severity' => 'moderate',
            'onset_date' => now()->subYears(2)->toDateString(),
            'clinical_notes' => 'HFrEF with LVEF 35%. Recent decompensation with fluid overload.',
            'created_by' => $doctor->id,
        ]);

        Condition::create([
            'fhir_condition_id' => 'condition-'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'code_system' => 'ICD-10-CM',
            'code' => 'I10',
            'code_display' => 'Essential hypertension',
            'category' => 'encounter-diagnosis',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'severity' => 'mild',
            'onset_date' => now()->subYears(10)->toDateString(),
            'created_by' => $doctor->id,
        ]);

        $furosemide = Medication::firstOrCreate(['rxnorm_code' => '4603'], [
            'generic_name' => 'Furosemide', 'display_name' => 'Furosemide 40mg tablet',
            'form' => 'tablet', 'strength_value' => 40, 'strength_unit' => 'mg', 'source' => 'rxnorm', 'is_active' => true,
        ]);
        $lisinopril = Medication::firstOrCreate(['rxnorm_code' => '29046'], [
            'generic_name' => 'Lisinopril', 'display_name' => 'Lisinopril 20mg tablet',
            'form' => 'tablet', 'strength_value' => 20, 'strength_unit' => 'mg', 'source' => 'rxnorm', 'is_active' => true,
        ]);
        $carvedilol = Medication::firstOrCreate(['rxnorm_code' => '20352'], [
            'generic_name' => 'Carvedilol', 'display_name' => 'Carvedilol 25mg tablet',
            'form' => 'tablet', 'strength_value' => 25, 'strength_unit' => 'mg', 'source' => 'rxnorm', 'is_active' => true,
        ]);
        $dapagliflozin = Medication::firstOrCreate(['rxnorm_code' => '1488564'], [
            'generic_name' => 'Dapagliflozin', 'display_name' => 'Dapagliflozin 10mg tablet',
            'form' => 'tablet', 'strength_value' => 10, 'strength_unit' => 'mg', 'source' => 'rxnorm', 'is_active' => true,
        ]);

        foreach ([
            [$furosemide, 40, 'BID', 'Increase from 20mg QD due to fluid overload'],
            [$lisinopril, 20, 'QD', 'Continued — titrated to max tolerated dose'],
            [$carvedilol, 25, 'BID', 'Continued — stable on current dose'],
            [$dapagliflozin, 10, 'QD', 'NEW — added for HFrEF mortality benefit per ESC 2023'],
        ] as [$med, $dose, $freq, $note]) {
            Prescription::create([
                'fhir_medication_request_id' => 'rx-'.Str::uuid(),
                'patient_id' => $patient->id,
                'practitioner_id' => $practitioner->id,
                'visit_id' => $visit->id,
                'medication_id' => $med->id,
                'status' => 'active',
                'intent' => 'order',
                'dose_quantity' => $dose,
                'dose_unit' => 'mg',
                'frequency' => $freq,
                'route' => 'oral',
                'start_date' => now()->toDateString(),
                'special_instructions' => $note,
                'created_by' => $doctor->id,
            ]);
        }

        VisitNote::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'author_practitioner_id' => $practitioner->id,
            'composition_type' => 'progress_note',
            'status' => 'final',
            'chief_complaint' => 'Worsening shortness of breath and weight gain over the past 2 weeks',
            'history_of_present_illness' => 'Mrs. Santos is a 67-year-old female with known HFrEF (LVEF 35%) presenting with progressive dyspnea on exertion and 4 kg weight gain over 2 weeks.',
            'physical_exam' => "General: Alert, mildly dyspneic at rest. Vitals: HR 88, BP 142/88, RR 22, SpO2 94% on RA, Weight 78 kg.\nCardiovascular: S3 gallop present, JVP elevated to 12 cm.\nLungs: Bibasilar crackles.\nExtremities: 2+ pitting edema bilaterally.",
            'assessment' => 'Acute-on-chronic heart failure decompensation (HFrEF, LVEF 35%) — NYHA Class III. Precipitated by dietary sodium indiscretion.',
            'plan' => "1. Increase Furosemide to 40mg BID\n2. Add Dapagliflozin 10mg QD (DAPA-HF trial)\n3. Continue Lisinopril 20mg QD and Carvedilol 25mg BID\n4. Daily weight monitoring\n5. Strict 1.5L/day fluid restriction\n6. Recheck labs in 1 week\n7. Follow-up in 2 weeks",
            'follow_up' => '2 weeks for volume status reassessment.',
            'follow_up_timeframe' => '2 weeks',
            'is_signed' => true,
            'signed_at' => now()->subDays(3),
        ]);

        // Weight data (trending up — red flag)
        $weights = [
            ['day' => -28, 'kg' => 73.5], ['day' => -25, 'kg' => 73.8], ['day' => -21, 'kg' => 74.0],
            ['day' => -18, 'kg' => 74.2], ['day' => -14, 'kg' => 74.5], ['day' => -11, 'kg' => 75.2],
            ['day' => -8, 'kg' => 76.0], ['day' => -6, 'kg' => 76.8], ['day' => -4, 'kg' => 77.5],
            ['day' => -3, 'kg' => 75.5], ['day' => -2, 'kg' => 76.8], ['day' => -1, 'kg' => 77.8],
            ['day' => 0, 'kg' => 78.0],
        ];

        foreach ($weights as $w) {
            $date = now()->addDays($w['day']);
            Observation::create([
                'fhir_observation_id' => 'obs-wt-'.Str::uuid(),
                'patient_id' => $patient->id,
                'practitioner_id' => $practitioner->id,
                'code_system' => 'LOINC',
                'code' => '29463-7',
                'code_display' => 'Body weight',
                'category' => 'vital-signs',
                'status' => 'final',
                'value_type' => 'quantity',
                'value_quantity' => $w['kg'],
                'value_unit' => 'kg',
                'interpretation' => $w['kg'] > 76 ? 'H' : 'N',
                'effective_date' => $date->toDateString(),
                'issued_at' => $date,
                'specialty_data' => ['source' => 'home_scale', 'dry_weight_target' => 73.0],
                'created_by' => $doctor->id,
            ]);
        }

        // BNP + Creatinine at visit
        Observation::create([
            'fhir_observation_id' => 'obs-bnp-maria-'.Str::uuid(),
            'patient_id' => $patient->id, 'visit_id' => $visit->id, 'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC', 'code' => '30934-4', 'code_display' => 'BNP',
            'category' => 'laboratory', 'status' => 'final', 'value_type' => 'quantity',
            'value_quantity' => 820, 'value_unit' => 'pg/mL',
            'reference_range_low' => 0, 'reference_range_high' => 100,
            'interpretation' => 'H', 'effective_date' => now()->subDays(3)->toDateString(),
            'issued_at' => now()->subDays(3), 'created_by' => $doctor->id,
        ]);

        Observation::create([
            'fhir_observation_id' => 'obs-creat-maria-'.Str::uuid(),
            'patient_id' => $patient->id, 'visit_id' => $visit->id, 'practitioner_id' => $practitioner->id,
            'code_system' => 'LOINC', 'code' => '2160-0', 'code_display' => 'Creatinine',
            'category' => 'laboratory', 'status' => 'final', 'value_type' => 'quantity',
            'value_quantity' => 1.6, 'value_unit' => 'mg/dL',
            'reference_range_low' => 0.6, 'reference_range_high' => 1.1,
            'interpretation' => 'H', 'effective_date' => now()->subDays(3)->toDateString(),
            'issued_at' => now()->subDays(3), 'created_by' => $doctor->id,
        ]);

        // Historical lab data for Maria (HF trending)
        $mariaLabHistory = [
            ['code' => '30934-4', 'display' => 'BNP', 'unit' => 'pg/mL', 'ref_low' => 0, 'ref_high' => 100,
                'readings' => [[6, 350, 'H'], [4, 520, 'H'], [2, 680, 'H']]],
            ['code' => '2160-0', 'display' => 'Creatinine', 'unit' => 'mg/dL', 'ref_low' => 0.6, 'ref_high' => 1.1,
                'readings' => [[6, 1.2, 'H'], [4, 1.3, 'H'], [2, 1.4, 'H']]],
            ['code' => '2823-3', 'display' => 'Potassium', 'unit' => 'mEq/L', 'ref_low' => 3.5, 'ref_high' => 5.0,
                'readings' => [[6, 3.8, 'N'], [4, 4.0, 'N'], [2, 4.2, 'N']]],
            ['code' => '2951-2', 'display' => 'Sodium', 'unit' => 'mEq/L', 'ref_low' => 136, 'ref_high' => 145,
                'readings' => [[6, 140, 'N'], [4, 138, 'N'], [2, 136, 'N']]],
            ['code' => '718-7', 'display' => 'Hemoglobin', 'unit' => 'g/dL', 'ref_low' => 12.0, 'ref_high' => 16.0,
                'readings' => [[6, 12.5, 'N'], [4, 12.2, 'N'], [2, 11.8, 'L']]],
        ];

        foreach ($mariaLabHistory as $marker) {
            foreach ($marker['readings'] as [$mo, $val, $interp]) {
                $date = now()->subMonths($mo);
                Observation::create([
                    'fhir_observation_id' => 'obs-hist-'.Str::uuid(),
                    'patient_id' => $patient->id, 'visit_id' => $visit->id, 'practitioner_id' => $practitioner->id,
                    'code_system' => 'LOINC', 'code' => $marker['code'], 'code_display' => $marker['display'],
                    'category' => 'laboratory', 'status' => 'final', 'value_type' => 'quantity',
                    'value_quantity' => $val, 'value_unit' => $marker['unit'],
                    'reference_range_low' => $marker['ref_low'], 'reference_range_high' => $marker['ref_high'],
                    'interpretation' => $interp,
                    'effective_date' => $date->toDateString(), 'issued_at' => $date,
                    'created_by' => $doctor->id,
                ]);
            }
        }

        ChatSession::create([
            'patient_id' => $patient->id, 'visit_id' => $visit->id,
            'topic' => 'Post-visit follow-up: Heart failure management',
            'status' => 'active', 'initiated_at' => now(),
        ]);
    }

    /**
     * Scenario 3: Hypertension patient — James Williams.
     */
    private function seedHypertensionScenario(Organization $org, Practitioner $practitioner, User $doctor): void
    {
        $patient = Patient::create([
            'fhir_patient_id' => 'patient-'.Str::uuid(),
            'first_name' => 'James', 'last_name' => 'Williams',
            'dob' => '1972-06-08', 'gender' => 'male',
            'email' => 'james@demo.postvisit.ai', 'phone' => '+1-555-0789',
            'preferred_language' => 'en', 'timezone' => 'America/New_York',
            'mrn' => 'MRN-003',
            'height_cm' => 175.0,
            'weight_kg' => 92.5,
            'blood_type' => 'B+',
            'allergies' => [],
            'emergency_contact_name' => 'Linda Williams',
            'emergency_contact_phone' => '+1-555-0790',
            'emergency_contact_relationship' => 'Wife',
            'consent_given' => true, 'consent_date' => now(), 'data_sharing_consent' => true,
        ]);

        User::create([
            'name' => 'James Williams', 'email' => 'james@demo.postvisit.ai',
            'password' => 'password', 'role' => 'patient', 'patient_id' => $patient->id, 'is_active' => true,
        ]);

        $visitStart = now()->subDays(5);
        $visit = Visit::create([
            'fhir_encounter_id' => 'encounter-'.Str::uuid(),
            'patient_id' => $patient->id, 'practitioner_id' => $practitioner->id,
            'organization_id' => $org->id, 'visit_type' => 'office_visit', 'class' => 'AMB',
            'visit_status' => 'completed', 'service_type' => 'cardiology_consultation',
            'reason_for_visit' => 'Hypertension follow-up — blood pressure not at target despite medication',
            'summary' => 'Stage 2 hypertension, inadequately controlled on amlodipine 5mg. Adding HCTZ, uptitrating amlodipine to 10mg.',
            'started_at' => $visitStart, 'ended_at' => $visitStart->copy()->addMinutes(25),
            'duration_minutes' => 25, 'created_by' => $doctor->id,
        ]);

        Condition::create([
            'fhir_condition_id' => 'condition-'.Str::uuid(),
            'patient_id' => $patient->id, 'visit_id' => $visit->id,
            'code_system' => 'ICD-10-CM', 'code' => 'I11.9',
            'code_display' => 'Hypertensive heart disease without heart failure',
            'category' => 'encounter-diagnosis', 'clinical_status' => 'active',
            'verification_status' => 'confirmed', 'severity' => 'moderate',
            'onset_date' => now()->subYears(5)->toDateString(),
            'clinical_notes' => 'Stage 2 hypertension. Target organ assessment negative.',
            'created_by' => $doctor->id,
        ]);

        $amlodipine = Medication::firstOrCreate(['rxnorm_code' => '17767'], [
            'generic_name' => 'Amlodipine', 'display_name' => 'Amlodipine 10mg tablet',
            'form' => 'tablet', 'strength_value' => 10, 'strength_unit' => 'mg', 'source' => 'rxnorm', 'is_active' => true,
        ]);
        $hctz = Medication::firstOrCreate(['rxnorm_code' => '5487'], [
            'generic_name' => 'Hydrochlorothiazide', 'display_name' => 'Hydrochlorothiazide 25mg tablet',
            'form' => 'tablet', 'strength_value' => 25, 'strength_unit' => 'mg', 'source' => 'rxnorm', 'is_active' => true,
        ]);

        foreach ([
            [$amlodipine, 10, 'QD', 'Uptitrated from 5mg to 10mg for better BP control.'],
            [$hctz, 25, 'QD', 'NEW — added for combination therapy. Monitor potassium.'],
        ] as [$med, $dose, $freq, $note]) {
            Prescription::create([
                'fhir_medication_request_id' => 'rx-'.Str::uuid(),
                'patient_id' => $patient->id, 'practitioner_id' => $practitioner->id,
                'visit_id' => $visit->id, 'medication_id' => $med->id,
                'status' => 'active', 'intent' => 'order',
                'dose_quantity' => $dose, 'dose_unit' => 'mg', 'frequency' => $freq,
                'route' => 'oral', 'start_date' => now()->toDateString(),
                'special_instructions' => $note, 'created_by' => $doctor->id,
            ]);
        }

        VisitNote::create([
            'visit_id' => $visit->id, 'patient_id' => $patient->id,
            'author_practitioner_id' => $practitioner->id,
            'composition_type' => 'progress_note', 'status' => 'final',
            'chief_complaint' => 'Elevated blood pressure readings at home despite medication',
            'history_of_present_illness' => 'Mr. Williams is a 53-year-old male with a 5-year history of essential hypertension. Home BP readings consistently 150-160/95-100 mmHg despite Amlodipine 5mg daily for 3 months.',
            'physical_exam' => "General: Well-appearing. Vitals: HR 76, BP 156/98, RR 14, SpO2 99% on RA.\nCardiovascular: Regular rate and rhythm, no murmurs.\nExtremities: No edema.",
            'assessment' => 'Stage 2 hypertension, inadequately controlled on monotherapy. No target organ damage.',
            'plan' => "1. Uptitrate Amlodipine to 10mg QD\n2. Add HCTZ 25mg QD\n3. Continue home BP monitoring\n4. Target: <130/80 mmHg\n5. Check BMP in 2 weeks\n6. Follow-up in 4 weeks",
            'follow_up' => '4 weeks for BP reassessment on dual therapy.',
            'follow_up_timeframe' => '4 weeks',
            'is_signed' => true, 'signed_at' => now()->subDays(5),
        ]);

        // Lab results + history for James (HTN with metabolic workup)
        $jamesLabs = [
            ['code' => '2093-3', 'display' => 'Total cholesterol', 'unit' => 'mg/dL',
                'ref_low' => 0, 'ref_high' => 200, 'ref_text' => 'Desirable: <200 mg/dL',
                'history' => [[6, 232, 'H'], [4, 220, 'H'], [2, 210, 'H']],
                'current' => [205, 'H']],
            ['code' => '2089-1', 'display' => 'LDL Cholesterol', 'unit' => 'mg/dL',
                'ref_low' => 0, 'ref_high' => 100, 'ref_text' => 'Optimal: <100 mg/dL',
                'history' => [[6, 148, 'H'], [4, 138, 'H'], [2, 128, 'H']],
                'current' => [118, 'H']],
            ['code' => '2160-0', 'display' => 'Creatinine', 'unit' => 'mg/dL',
                'ref_low' => 0.7, 'ref_high' => 1.3, 'ref_text' => '0.7-1.3 mg/dL',
                'history' => [[6, 1.0, 'N'], [4, 1.0, 'N'], [2, 1.1, 'N']],
                'current' => [1.1, 'N']],
            ['code' => '2823-3', 'display' => 'Potassium', 'unit' => 'mEq/L',
                'ref_low' => 3.5, 'ref_high' => 5.0, 'ref_text' => '3.5-5.0 mEq/L',
                'history' => [[6, 4.1, 'N'], [4, 4.0, 'N'], [2, 4.2, 'N']],
                'current' => [4.3, 'N']],
            ['code' => '2345-7', 'display' => 'Fasting Glucose', 'unit' => 'mg/dL',
                'ref_low' => 70, 'ref_high' => 100, 'ref_text' => '70-100 mg/dL',
                'history' => [[6, 108, 'H'], [4, 105, 'H'], [2, 102, 'H']],
                'current' => [98, 'N']],
        ];

        $yesterday = now()->subDays(5)->toDateString();
        foreach ($jamesLabs as $lab) {
            // Current value
            Observation::create([
                'fhir_observation_id' => 'obs-lab-james-'.Str::uuid(),
                'patient_id' => $patient->id, 'visit_id' => $visit->id, 'practitioner_id' => $practitioner->id,
                'code_system' => 'LOINC', 'code' => $lab['code'], 'code_display' => $lab['display'],
                'category' => 'laboratory', 'status' => 'final', 'value_type' => 'quantity',
                'value_quantity' => $lab['current'][0], 'value_unit' => $lab['unit'],
                'reference_range_low' => $lab['ref_low'], 'reference_range_high' => $lab['ref_high'],
                'reference_range_text' => $lab['ref_text'], 'interpretation' => $lab['current'][1],
                'effective_date' => $yesterday, 'issued_at' => now()->subDays(5),
                'created_by' => $doctor->id,
            ]);
            // Historical
            foreach ($lab['history'] as [$mo, $val, $interp]) {
                $date = now()->subMonths($mo);
                Observation::create([
                    'fhir_observation_id' => 'obs-hist-'.Str::uuid(),
                    'patient_id' => $patient->id, 'visit_id' => $visit->id, 'practitioner_id' => $practitioner->id,
                    'code_system' => 'LOINC', 'code' => $lab['code'], 'code_display' => $lab['display'],
                    'category' => 'laboratory', 'status' => 'final', 'value_type' => 'quantity',
                    'value_quantity' => $val, 'value_unit' => $lab['unit'],
                    'reference_range_low' => $lab['ref_low'], 'reference_range_high' => $lab['ref_high'],
                    'reference_range_text' => $lab['ref_text'], 'interpretation' => $interp,
                    'effective_date' => $date->toDateString(), 'issued_at' => $date,
                    'created_by' => $doctor->id,
                ]);
            }
        }

        // Longitudinal BP: before and after medication change
        $bpReadings = [
            ['day' => -21, 'sys' => 158, 'dia' => 98, 'hr' => 78],
            ['day' => -18, 'sys' => 152, 'dia' => 96, 'hr' => 76],
            ['day' => -15, 'sys' => 160, 'dia' => 100, 'hr' => 80],
            ['day' => -12, 'sys' => 155, 'dia' => 97, 'hr' => 74],
            ['day' => -9, 'sys' => 162, 'dia' => 102, 'hr' => 78],
            ['day' => -7, 'sys' => 158, 'dia' => 98, 'hr' => 76],
            ['day' => -5, 'sys' => 156, 'dia' => 98, 'hr' => 76],
            ['day' => -4, 'sys' => 150, 'dia' => 94, 'hr' => 74],
            ['day' => -3, 'sys' => 146, 'dia' => 92, 'hr' => 72],
            ['day' => -2, 'sys' => 142, 'dia' => 88, 'hr' => 74],
            ['day' => -1, 'sys' => 138, 'dia' => 86, 'hr' => 72],
            ['day' => 0, 'sys' => 134, 'dia' => 84, 'hr' => 70],
        ];

        foreach ($bpReadings as $r) {
            $date = now()->addDays($r['day']);
            Observation::create([
                'fhir_observation_id' => 'obs-bp-james-'.Str::uuid(),
                'patient_id' => $patient->id, 'practitioner_id' => $practitioner->id,
                'code_system' => 'LOINC', 'code' => '85354-9', 'code_display' => 'Blood pressure panel',
                'category' => 'vital-signs', 'status' => 'final', 'value_type' => 'string',
                'value_string' => $r['sys'].'/'.$r['dia'].' mmHg',
                'interpretation' => $r['sys'] > 140 ? 'H' : 'N',
                'effective_date' => $date->toDateString(), 'issued_at' => $date,
                'specialty_data' => [
                    'systolic' => ['value' => $r['sys'], 'unit' => 'mmHg'],
                    'diastolic' => ['value' => $r['dia'], 'unit' => 'mmHg'],
                    'source' => 'home_monitor',
                ],
                'created_by' => $doctor->id,
            ]);
            Observation::create([
                'fhir_observation_id' => 'obs-hr-james-'.Str::uuid(),
                'patient_id' => $patient->id, 'practitioner_id' => $practitioner->id,
                'code_system' => 'LOINC', 'code' => '8867-4', 'code_display' => 'Heart rate',
                'category' => 'vital-signs', 'status' => 'final', 'value_type' => 'quantity',
                'value_quantity' => $r['hr'], 'value_unit' => 'bpm',
                'reference_range_low' => 60, 'reference_range_high' => 100,
                'interpretation' => 'N', 'effective_date' => $date->toDateString(),
                'issued_at' => $date, 'specialty_data' => ['source' => 'home_monitor'],
                'created_by' => $doctor->id,
            ]);
        }

        ChatSession::create([
            'patient_id' => $patient->id, 'visit_id' => $visit->id,
            'topic' => 'Post-visit follow-up: Blood pressure management',
            'status' => 'active', 'initiated_at' => now(),
        ]);
    }

    private function seedMedicalReferences(): void
    {
        $references = [
            [
                'title' => '2023 Focused Update of the 2021 ESC Guidelines for the diagnosis and treatment of acute and chronic heart failure',
                'authors' => 'McDonagh TA, Metra M, Adamo M, et al.',
                'journal' => 'European Heart Journal',
                'year' => 2023,
                'doi' => '10.1093/eurheartj/ehad195',
                'pmid' => '37622666',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/37622666/',
                'source_organization' => 'ESC',
                'category' => 'guideline',
                'specialty' => 'cardiology',
                'summary' => 'Comprehensive ESC guidelines for HF diagnosis and management including pharmacotherapy, devices, and comorbidities.',
                'verified' => true,
                'verified_at' => now(),
            ],
            [
                'title' => '2022 AHA/ACC/HFSA Guideline for the Management of Heart Failure',
                'authors' => 'Heidenreich PA, Bozkurt B, Aguilar D, et al.',
                'journal' => 'Circulation',
                'year' => 2022,
                'doi' => '10.1161/CIR.0000000000001063',
                'pmid' => '35363499',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/35363499/',
                'source_organization' => 'AHA/ACC',
                'category' => 'guideline',
                'specialty' => 'cardiology',
                'summary' => 'AHA/ACC/HFSA guideline covering classification, biomarkers, imaging, pharmacologic and device therapy for heart failure.',
                'verified' => true,
                'verified_at' => now(),
            ],
            [
                'title' => '2023 ESC Guidelines for the management of cardiovascular disease in patients with diabetes',
                'authors' => 'Marx N, Federici M, Schutt K, et al.',
                'journal' => 'European Heart Journal',
                'year' => 2023,
                'doi' => '10.1093/eurheartj/ehad192',
                'pmid' => '37622663',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/37622663/',
                'source_organization' => 'ESC',
                'category' => 'guideline',
                'specialty' => 'cardiology',
                'summary' => 'Guidelines for CVD management in diabetic patients, including risk assessment and treatment targets.',
                'verified' => true,
                'verified_at' => now(),
            ],
            [
                'title' => '2021 ESC Guidelines on cardiovascular disease prevention in clinical practice',
                'authors' => 'Visseren FLJ, Mach F, Smulders R, et al.',
                'journal' => 'European Heart Journal',
                'year' => 2021,
                'doi' => '10.1093/eurheartj/ehab484',
                'pmid' => '34458905',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/34458905/',
                'source_organization' => 'ESC',
                'category' => 'guideline',
                'specialty' => 'cardiology',
                'summary' => 'Prevention guidelines covering risk factors, lifestyle modification, and pharmacological interventions.',
                'verified' => true,
                'verified_at' => now(),
            ],
            [
                'title' => '2022 ESC Guidelines on ventricular arrhythmias and the prevention of sudden cardiac death',
                'authors' => 'Zeppenfeld K, Tfelt-Hansen J, de Riva M, et al.',
                'journal' => 'European Heart Journal',
                'year' => 2022,
                'doi' => '10.1093/eurheartj/ehac262',
                'pmid' => '36017572',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/36017572/',
                'source_organization' => 'ESC',
                'category' => 'guideline',
                'specialty' => 'cardiology',
                'summary' => 'Guidelines on evaluation and management of ventricular arrhythmias and prevention of sudden cardiac death, including PVCs.',
                'verified' => true,
                'verified_at' => now(),
            ],
            [
                'title' => '2019 ESC/EAS Guidelines for the management of dyslipidaemias',
                'authors' => 'Mach F, Baigent C, Catapano AL, et al.',
                'journal' => 'European Heart Journal',
                'year' => 2020,
                'doi' => '10.1093/eurheartj/ehz455',
                'pmid' => '31504418',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/31504418/',
                'source_organization' => 'ESC/EAS',
                'category' => 'guideline',
                'specialty' => 'cardiology',
                'summary' => 'Dyslipidemia management guidelines including LDL targets, statin therapy, and combination treatments.',
                'verified' => true,
                'verified_at' => now(),
            ],
            [
                'title' => 'Beta-Blockers for the Treatment of Premature Ventricular Contractions: A Meta-Analysis',
                'authors' => 'Ling Y, Wan Q, Chen Q, Zhu W.',
                'journal' => 'Journal of Cardiovascular Pharmacology',
                'year' => 2023,
                'doi' => '10.1097/FJC.0000000000001387',
                'pmid' => '36729885',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/36729885/',
                'source_organization' => null,
                'category' => 'meta_analysis',
                'specialty' => 'cardiology',
                'summary' => 'Meta-analysis of beta-blocker efficacy for PVC suppression, relevant to patient scenario (Propranolol for PVCs).',
                'verified' => true,
                'verified_at' => now(),
            ],
            [
                'title' => '2023 AHA/ACC/ACCP/ASPC/NLA/PCNA Guideline for the Management of Patients With Chronic Coronary Disease',
                'authors' => 'Virani SS, Newby LK, Arnold SV, et al.',
                'journal' => 'Circulation',
                'year' => 2023,
                'doi' => '10.1161/CIR.0000000000001168',
                'pmid' => '37471501',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/37471501/',
                'source_organization' => 'AHA/ACC',
                'category' => 'guideline',
                'specialty' => 'cardiology',
                'summary' => 'Comprehensive guideline for chronic coronary disease management including medical therapy, revascularization, and risk reduction.',
                'verified' => true,
                'verified_at' => now(),
            ],
        ];

        foreach ($references as $ref) {
            $doi = $ref['doi'] ?? null;
            if ($doi) {
                MedicalReference::firstOrCreate(['doi' => $doi], $ref);
            } else {
                MedicalReference::create($ref);
            }
        }
    }

    /**
     * Seed demo notifications (patient messages to doctor).
     */
    private function seedDemoNotifications(User $doctorUser): void
    {
        $alexVisitId = Visit::whereHas('patient', fn ($q) => $q->where('first_name', 'Alex')->where('last_name', 'Johnson'))
            ->orderByDesc('started_at')->value('id');
        $mariaVisitId = Visit::whereHas('patient', fn ($q) => $q->where('first_name', 'Maria')->where('last_name', 'Santos'))
            ->orderByDesc('started_at')->value('id');
        $jamesVisitId = Visit::whereHas('patient', fn ($q) => $q->where('first_name', 'James')->where('last_name', 'Williams'))
            ->orderByDesc('started_at')->value('id');

        $notifications = [
            [
                'user_id' => $doctorUser->id,
                'visit_id' => $alexVisitId,
                'type' => 'patient_feedback',
                'title' => 'Question about Propranolol',
                'body' => "I've been taking Propranolol for 3 days now. Is it normal to feel a bit more tired than usual? Also, should I still avoid coffee completely or is one cup okay?",
                'created_at' => now()->subHours(2),
            ],
            [
                'user_id' => $doctorUser->id,
                'visit_id' => $alexVisitId,
                'type' => 'patient_feedback',
                'title' => 'Coffee and PVCs',
                'body' => 'Had a cup of coffee today and noticed a few more palpitations than usual this evening. Is this something I should worry about?',
                'created_at' => now()->subHours(5),
            ],
            [
                'user_id' => $doctorUser->id,
                'visit_id' => $mariaVisitId,
                'type' => 'patient_feedback',
                'title' => 'Weight tracking question',
                'body' => "My weight went up to 78 kg this morning. Should I be concerned? I've been following the fluid restriction but had a slightly saltier meal yesterday.",
                'created_at' => now()->subHours(8),
            ],
            [
                'user_id' => $doctorUser->id,
                'visit_id' => $jamesVisitId,
                'type' => 'patient_feedback',
                'title' => 'Medication side effects (Amlodipine)',
                'body' => 'I noticed some ankle swelling since starting the new medication. Is this a normal side effect of Amlodipine? Should I continue taking it?',
                'created_at' => now()->subHours(12),
            ],
        ];

        foreach ($notifications as $n) {
            Notification::create($n);
        }
    }

    /**
     * Seed demo audit log entries for the doctor dashboard.
     */
    private function seedDemoAuditLogs(User $doctorUser): void
    {
        $sessionId = (string) Str::uuid();
        $alexSessionId = (string) Str::uuid();
        $mariaSessionId = (string) Str::uuid();
        $jamesSessionId = (string) Str::uuid();
        $ip = '192.168.1.100';

        // Find patient users
        $alexUser = User::where('email', 'alex.johnson.pvcs@demo.postvisit.ai')->first();
        $mariaUser = User::where('email', 'maria@demo.postvisit.ai')->first();
        $jamesUser = User::where('email', 'james@demo.postvisit.ai')->first();

        // Find patient IDs
        $alexPatientId = $alexUser?->patient_id;
        $mariaPatientId = $mariaUser?->patient_id;
        $jamesPatientId = $jamesUser?->patient_id;

        // Find visit IDs
        $alexVisitId = Visit::where('patient_id', $alexPatientId)->orderByDesc('started_at')->value('id');
        $mariaVisitId = Visit::where('patient_id', $mariaPatientId)->orderByDesc('started_at')->value('id');
        $jamesVisitId = Visit::where('patient_id', $jamesPatientId)->orderByDesc('started_at')->value('id');

        // Find visit note IDs
        $alexNoteId = VisitNote::where('visit_id', $alexVisitId)->value('id');
        $mariaNoteId = VisitNote::where('visit_id', $mariaVisitId)->value('id');
        $jamesNoteId = VisitNote::where('visit_id', $jamesVisitId)->value('id');

        $entries = [
            // Doctor login
            ['user_id' => $doctorUser->id, 'user_role' => 'doctor', 'action_type' => 'login', 'resource_type' => 'Session', 'resource_id' => $sessionId, 'success' => true, 'ip_address' => $ip, 'session_id' => $sessionId, 'phi_accessed' => false, 'accessed_at' => now()->subHours(1)],

            // Patient logins
            ['user_id' => $alexUser?->id, 'user_role' => 'patient', 'action_type' => 'login', 'resource_type' => 'Session', 'resource_id' => $alexSessionId, 'success' => true, 'ip_address' => '10.0.0.50', 'session_id' => $alexSessionId, 'phi_accessed' => false, 'accessed_at' => now()->subHours(3)],
            ['user_id' => $mariaUser?->id, 'user_role' => 'patient', 'action_type' => 'login', 'resource_type' => 'Session', 'resource_id' => $mariaSessionId, 'success' => true, 'ip_address' => '10.0.0.51', 'session_id' => $mariaSessionId, 'phi_accessed' => false, 'accessed_at' => now()->subHours(5)],
            ['user_id' => $jamesUser?->id, 'user_role' => 'patient', 'action_type' => 'login', 'resource_type' => 'Session', 'resource_id' => $jamesSessionId, 'success' => true, 'ip_address' => '10.0.0.52', 'session_id' => $jamesSessionId, 'phi_accessed' => false, 'accessed_at' => now()->subHours(8)],

            // Patient visit reads
            ['user_id' => $alexUser?->id, 'user_role' => 'patient', 'action_type' => 'read', 'resource_type' => 'Visit', 'resource_id' => $alexVisitId, 'success' => true, 'ip_address' => '10.0.0.50', 'session_id' => $alexSessionId, 'phi_accessed' => true, 'phi_elements' => ['visit_summary', 'medications'], 'accessed_at' => now()->subHours(3)->addMinutes(2)],
            ['user_id' => $mariaUser?->id, 'user_role' => 'patient', 'action_type' => 'read', 'resource_type' => 'Visit', 'resource_id' => $mariaVisitId, 'success' => true, 'ip_address' => '10.0.0.51', 'session_id' => $mariaSessionId, 'phi_accessed' => true, 'phi_elements' => ['visit_summary', 'medications'], 'accessed_at' => now()->subHours(5)->addMinutes(1)],
            ['user_id' => $jamesUser?->id, 'user_role' => 'patient', 'action_type' => 'read', 'resource_type' => 'Visit', 'resource_id' => $jamesVisitId, 'success' => true, 'ip_address' => '10.0.0.52', 'session_id' => $jamesSessionId, 'phi_accessed' => true, 'phi_elements' => ['visit_summary', 'medications'], 'accessed_at' => now()->subHours(8)->addMinutes(3)],

            // Patient SOAP reads
            ['user_id' => $alexUser?->id, 'user_role' => 'patient', 'action_type' => 'read', 'resource_type' => 'VisitNote', 'resource_id' => $alexNoteId, 'success' => true, 'ip_address' => '10.0.0.50', 'session_id' => $alexSessionId, 'phi_accessed' => true, 'phi_elements' => ['soap_note', 'medical_terms'], 'accessed_at' => now()->subHours(3)->addMinutes(5)],
            ['user_id' => $mariaUser?->id, 'user_role' => 'patient', 'action_type' => 'read', 'resource_type' => 'VisitNote', 'resource_id' => $mariaNoteId, 'success' => true, 'ip_address' => '10.0.0.51', 'session_id' => $mariaSessionId, 'phi_accessed' => true, 'phi_elements' => ['soap_note', 'medical_terms'], 'accessed_at' => now()->subHours(5)->addMinutes(4)],
            ['user_id' => $jamesUser?->id, 'user_role' => 'patient', 'action_type' => 'read', 'resource_type' => 'VisitNote', 'resource_id' => $jamesNoteId, 'success' => true, 'ip_address' => '10.0.0.52', 'session_id' => $jamesSessionId, 'phi_accessed' => true, 'phi_elements' => ['soap_note', 'medical_terms'], 'accessed_at' => now()->subHours(8)->addMinutes(6)],

            // Doctor patient reviews
            ['user_id' => $doctorUser->id, 'user_role' => 'doctor', 'action_type' => 'read', 'resource_type' => 'Patient', 'resource_id' => $alexPatientId, 'success' => true, 'ip_address' => $ip, 'session_id' => $sessionId, 'phi_accessed' => true, 'phi_elements' => ['patient_profile', 'conditions', 'prescriptions'], 'accessed_at' => now()->subMinutes(45)],
            ['user_id' => $doctorUser->id, 'user_role' => 'doctor', 'action_type' => 'read', 'resource_type' => 'Patient', 'resource_id' => $mariaPatientId, 'success' => true, 'ip_address' => $ip, 'session_id' => $sessionId, 'phi_accessed' => true, 'phi_elements' => ['patient_profile', 'conditions', 'prescriptions'], 'accessed_at' => now()->subMinutes(30)],
            ['user_id' => $doctorUser->id, 'user_role' => 'doctor', 'action_type' => 'read', 'resource_type' => 'Patient', 'resource_id' => $jamesPatientId, 'success' => true, 'ip_address' => $ip, 'session_id' => $sessionId, 'phi_accessed' => true, 'phi_elements' => ['patient_profile', 'conditions', 'prescriptions'], 'accessed_at' => now()->subMinutes(15)],

            // Doctor audit log view
            ['user_id' => $doctorUser->id, 'user_role' => 'doctor', 'action_type' => 'read', 'resource_type' => 'AuditLog', 'resource_id' => $sessionId, 'success' => true, 'ip_address' => $ip, 'session_id' => $sessionId, 'phi_accessed' => false, 'accessed_at' => now()->subMinutes(5)],
        ];

        foreach ($entries as $entry) {
            if (! empty($entry['user_id'])) {
                AuditLog::create($entry);
            }
        }
    }

    /**
     * Remove existing demo data so the seeder is idempotent on re-run.
     *
     * Covers records from both DemoSeeder and DemoScenarioSeeder.
     * Deletes in reverse-dependency order to respect FK constraints.
     */
    private function cleanupExistingDemoData(): void
    {
        $practitioner = Practitioner::where('email', 'doctor@demo.postvisit.ai')->first();
        $org = Organization::where('email', 'info@cityheartclinic.com')->first();

        // Collect ALL demo patients: by email pattern and by DemoScenarioSeeder users
        $demoPatientIds = Patient::where('email', 'like', '%@demo.postvisit.ai')->pluck('id');
        $scenarioPatientIds = User::whereNotNull('demo_scenario_key')
            ->whereNotNull('patient_id')
            ->pluck('patient_id');
        $allPatientIds = $demoPatientIds->merge($scenarioPatientIds)->unique();

        // Collect ALL demo users: by email pattern + scenario key
        $allUserIds = User::where('email', 'like', '%@demo.postvisit.ai')
            ->orWhereNotNull('demo_scenario_key')
            ->pluck('id');

        if ($allPatientIds->isEmpty() && ! $practitioner && ! $org) {
            return;
        }

        // Find ALL demo visits (by patient OR by practitioner)
        $visitQuery = Visit::query();
        if ($allPatientIds->isNotEmpty()) {
            $visitQuery->whereIn('patient_id', $allPatientIds);
        }
        if ($practitioner) {
            $visitQuery->orWhere('practitioner_id', $practitioner->id);
        }
        $allVisitIds = $visitQuery->pluck('id');

        if ($allVisitIds->isNotEmpty()) {
            // Chat messages → sessions
            $chatSessionIds = ChatSession::whereIn('visit_id', $allVisitIds)->pluck('id');
            if ($chatSessionIds->isNotEmpty()) {
                ChatMessage::whereIn('session_id', $chatSessionIds)->delete();
                ChatSession::whereIn('id', $chatSessionIds)->delete();
            }

            // Visit-level children
            UploadToken::whereIn('visit_id', $allVisitIds)->delete();
            Document::whereIn('visit_id', $allVisitIds)->delete();
            Transcript::whereIn('visit_id', $allVisitIds)->delete();
            VisitNote::whereIn('visit_id', $allVisitIds)->delete();
            Observation::whereIn('visit_id', $allVisitIds)->delete();
            Prescription::whereIn('visit_id', $allVisitIds)->delete();
            Condition::whereIn('visit_id', $allVisitIds)->delete();

            Visit::whereIn('id', $allVisitIds)->delete();
        }

        // Patient-level children (not linked to visits)
        if ($allPatientIds->isNotEmpty()) {
            Observation::whereIn('patient_id', $allPatientIds)->delete();
            Condition::whereIn('patient_id', $allPatientIds)->delete();
            Prescription::whereIn('patient_id', $allPatientIds)->delete();
            Consent::whereIn('patient_id', $allPatientIds)->delete();
            Document::whereIn('patient_id', $allPatientIds)->delete();
        }

        // User-level children (audit logs, notifications, library items)
        if ($allUserIds->isNotEmpty()) {
            LibraryItem::whereIn('user_id', $allUserIds)->delete();
            Notification::whereIn('user_id', $allUserIds)->delete();
            AuditLog::whereIn('user_id', $allUserIds)->delete();
        }

        // Nullify created_by on patients and visits to break circular FKs
        if ($allPatientIds->isNotEmpty()) {
            Patient::whereIn('id', $allPatientIds)->update(['created_by' => null]);
        }

        // Delete users → patients → practitioner → organization
        if ($allUserIds->isNotEmpty()) {
            // Nullify created_by FKs that reference demo users
            Observation::whereIn('created_by', $allUserIds)->update(['created_by' => null]);
            Condition::whereIn('created_by', $allUserIds)->update(['created_by' => null]);
            Prescription::whereIn('created_by', $allUserIds)->update(['created_by' => null]);
            Document::whereIn('created_by', $allUserIds)->update(['created_by' => null]);
            Visit::whereIn('created_by', $allUserIds)->update(['created_by' => null]);
            User::whereIn('id', $allUserIds)->delete();
        }

        if ($allPatientIds->isNotEmpty()) {
            Patient::whereIn('id', $allPatientIds)->delete();
        }

        if ($practitioner) {
            // Nullify any remaining FK references to this practitioner
            Observation::where('practitioner_id', $practitioner->id)->update(['practitioner_id' => null]);
            VisitNote::where('author_practitioner_id', $practitioner->id)->delete();
            $practitioner->delete();
        }

        $org?->delete();

        Log::info('DemoSeeder: cleaned up existing demo data for re-seeding.');
    }
}
