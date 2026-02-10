<?php

namespace Database\Seeders;

use App\Models\ChatSession;
use App\Models\Condition;
use App\Models\Medication;
use App\Models\Observation;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Prescription;
use App\Models\Transcript;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitNote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    /**
     * Seed the database with a complete cardiology demo scenario.
     *
     * Visit transcript and discharge notes BLOCKED -- awaiting Dr. Nedo input (Feb 11)
     */
    public function run(): void
    {
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
            'fhir_practitioner_id' => 'practitioner-' . Str::uuid(),
            'first_name' => 'Nedo',
            'last_name' => 'Dr.',
            'email' => 'doctor@demo.postvisit.ai',
            'npi' => '1234567890',
            'license_number' => 'CA-CARD-2024-001',
            'medical_degree' => 'MD',
            'primary_specialty' => 'cardiology',
            'secondary_specialties' => ['internal_medicine'],
            'organization_id' => $org->id,
        ]);

        // 3. Patient
        $patient = Patient::create([
            'fhir_patient_id' => 'patient-' . Str::uuid(),
            'first_name' => 'Alex',
            'last_name' => 'Johnson',
            'dob' => '1985-03-15',
            'gender' => 'male',
            'email' => 'patient@demo.postvisit.ai',
            'phone' => '+1-555-0123',
            'preferred_language' => 'en',
            'timezone' => 'America/New_York',
            'mrn' => 'MRN-001',
            'consent_given' => true,
            'consent_date' => now(),
            'data_sharing_consent' => true,
        ]);

        // 4. Users
        $doctorUser = User::create([
            'name' => 'Dr. Nedo',
            'email' => 'doctor@demo.postvisit.ai',
            'password' => 'password',
            'role' => 'doctor',
            'practitioner_id' => $practitioner->id,
            'is_active' => true,
        ]);

        $patientUser = User::create([
            'name' => 'Alex Johnson',
            'email' => 'patient@demo.postvisit.ai',
            'password' => 'password',
            'role' => 'patient',
            'patient_id' => $patient->id,
            'is_active' => true,
        ]);

        // 5. Visit
        $visitStart = now()->subDay();
        $visit = Visit::create([
            'fhir_encounter_id' => 'encounter-' . Str::uuid(),
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
            'fhir_observation_id' => 'obs-hr-' . Str::uuid(),
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
            'fhir_observation_id' => 'obs-bp-' . Str::uuid(),
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
            'fhir_observation_id' => 'obs-ekg-' . Str::uuid(),
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
            'fhir_observation_id' => 'obs-echo-' . Str::uuid(),
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
            'fhir_observation_id' => 'obs-chol-' . Str::uuid(),
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
            'fhir_observation_id' => 'obs-k-' . Str::uuid(),
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
            'fhir_observation_id' => 'obs-tsh-' . Str::uuid(),
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

        // 7. Condition: PVCs
        Condition::create([
            'fhir_condition_id' => 'condition-' . Str::uuid(),
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
            'fhir_medication_request_id' => 'rx-' . Str::uuid(),
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
            'follow_up' => '2 weeks for blood pressure check and medication tolerance assessment. 3 months for repeat Holter monitor and cardiology follow-up.',
            'follow_up_timeframe' => '2 weeks',
            'is_signed' => true,
            'signed_at' => now()->subDay(),
        ]);

        // 11. Transcript (placeholder - awaiting Dr. Nedo input)
        Transcript::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'source_type' => 'ambient_device',
            'stt_provider' => 'placeholder',
            'audio_duration_seconds' => 0,
            'raw_transcript' => 'PLACEHOLDER - Awaiting real transcript from Dr. Nedo',
            'processing_status' => 'pending',
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
    }
}
