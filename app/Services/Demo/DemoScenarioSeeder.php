<?php

namespace App\Services\Demo;

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
use Illuminate\Support\Str;

class DemoScenarioSeeder
{
    /**
     * Seed a complete demo scenario and return the logged-in user.
     *
     * @param  array<string, mixed>  $scenario
     */
    public function seed(array $scenario): User
    {
        $org = $this->findOrCreateOrganization();
        $doctorUser = $this->findOrCreateDoctor($org);
        $practitioner = $doctorUser->practitioner;

        $patient = $this->createPatient($scenario['patient']);
        $user = $this->createPatientUser($patient, $scenario['key']);

        $visit = $this->createVisit($scenario['visit'], $patient, $practitioner, $org, $doctorUser);

        $this->createConditions($scenario['conditions'] ?? [], $patient, $visit, $doctorUser);
        $this->createMedicationsAndPrescriptions($scenario['medications'] ?? [], $patient, $practitioner, $visit, $doctorUser);
        $this->createObservations($scenario['observations'] ?? [], $patient, $visit, $practitioner, $doctorUser);
        $this->createWeightSeries($scenario['weight_series'] ?? [], $patient, $practitioner, $doctorUser);
        $this->createVisitNote($scenario['visit_note'] ?? [], $visit, $patient, $practitioner);
        $this->createTranscript($scenario['transcript_file'] ?? null, $visit, $patient);
        $this->createChatSession($scenario['chat_session'] ?? [], $patient, $visit);

        return $user;
    }

    private function findOrCreateOrganization(): Organization
    {
        $config = config('demo-scenarios.organization');

        return Organization::firstOrCreate(
            ['email' => $config['email']],
            $config,
        );
    }

    private function findOrCreateDoctor(Organization $org): User
    {
        $config = config('demo-scenarios.doctor');
        $email = $config['email'];

        $user = User::where('email', $email)->first();
        if ($user) {
            return $user;
        }

        $practitioner = Practitioner::create([
            'fhir_practitioner_id' => 'practitioner-'.Str::uuid(),
            'first_name' => $config['first_name'],
            'last_name' => $config['last_name'],
            'email' => $email,
            'npi' => $config['npi'],
            'license_number' => $config['license_number'],
            'medical_degree' => $config['medical_degree'],
            'primary_specialty' => $config['primary_specialty'],
            'secondary_specialties' => $config['secondary_specialties'],
            'organization_id' => $org->id,
        ]);

        return User::create([
            'name' => $config['name'],
            'email' => $email,
            'password' => 'password',
            'role' => 'doctor',
            'practitioner_id' => $practitioner->id,
            'is_active' => true,
        ]);
    }

    private function createPatient(array $data): Patient
    {
        return Patient::create([
            'fhir_patient_id' => 'patient-'.Str::uuid(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'dob' => $data['dob'],
            'gender' => $data['gender'],
            'email' => 'demo-'.Str::random(8).'@postvisit.ai',
            'phone' => $data['phone'],
            'preferred_language' => $data['preferred_language'],
            'timezone' => $data['timezone'],
            'mrn' => $data['mrn'].'-'.Str::random(4),
            'height_cm' => $data['height_cm'],
            'weight_kg' => $data['weight_kg'],
            'blood_type' => $data['blood_type'] ?? null,
            'allergies' => $data['allergies'] ?? [],
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null,
            'consent_given' => true,
            'consent_date' => now(),
            'data_sharing_consent' => true,
        ]);
    }

    private function createPatientUser(Patient $patient, string $scenarioKey): User
    {
        $namePart = strtolower($patient->first_name).'.'.strtolower($patient->last_name);
        $random = Str::random(6);

        return User::create([
            'name' => $patient->first_name.' '.$patient->last_name,
            'email' => "{$namePart}.{$random}@demo.postvisit.ai",
            'password' => 'password',
            'role' => 'patient',
            'patient_id' => $patient->id,
            'is_active' => true,
        ]);
    }

    private function createVisit(array $data, Patient $patient, Practitioner $practitioner, Organization $org, User $doctor): Visit
    {
        $daysAgo = $data['days_ago'] ?? 1;
        $start = now()->subDays($daysAgo);

        return Visit::create([
            'fhir_encounter_id' => 'encounter-'.Str::uuid(),
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'organization_id' => $org->id,
            'visit_type' => $data['visit_type'],
            'class' => $data['class'],
            'visit_status' => 'completed',
            'service_type' => $data['service_type'],
            'reason_for_visit' => $data['reason_for_visit'],
            'reason_codes' => $data['reason_codes'] ?? null,
            'summary' => $data['summary'],
            'started_at' => $start,
            'ended_at' => $start->copy()->addMinutes($data['duration_minutes']),
            'duration_minutes' => $data['duration_minutes'],
            'provider_notes_followup' => $data['provider_notes_followup'] ?? null,
            'created_by' => $doctor->id,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $conditions
     */
    private function createConditions(array $conditions, Patient $patient, Visit $visit, User $doctor): void
    {
        foreach ($conditions as $c) {
            $onsetDate = null;
            if (isset($c['onset_weeks_ago'])) {
                $onsetDate = now()->subWeeks($c['onset_weeks_ago'])->toDateString();
            } elseif (isset($c['onset_years_ago'])) {
                $onsetDate = now()->subYears($c['onset_years_ago'])->toDateString();
            }

            Condition::create([
                'fhir_condition_id' => 'condition-'.Str::uuid(),
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'code_system' => $c['code_system'],
                'code' => $c['code'],
                'code_display' => $c['code_display'],
                'category' => $c['category'],
                'clinical_status' => $c['clinical_status'],
                'verification_status' => $c['verification_status'],
                'severity' => $c['severity'] ?? null,
                'onset_date' => $onsetDate,
                'clinical_notes' => $c['clinical_notes'] ?? null,
                'created_by' => $doctor->id,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $medications
     */
    private function createMedicationsAndPrescriptions(array $medications, Patient $patient, Practitioner $practitioner, Visit $visit, User $doctor): void
    {
        foreach ($medications as $m) {
            $medication = Medication::firstOrCreate(
                ['rxnorm_code' => $m['rxnorm_code']],
                [
                    'generic_name' => $m['generic_name'],
                    'display_name' => $m['display_name'],
                    'form' => $m['form'],
                    'strength_value' => $m['strength_value'],
                    'strength_unit' => $m['strength_unit'],
                    'atc_code' => $m['atc_code'] ?? null,
                    'brand_names' => $m['brand_names'] ?? null,
                    'ingredients' => $m['ingredients'] ?? null,
                    'pregnancy_category' => $m['pregnancy_category'] ?? null,
                    'black_box_warning' => $m['black_box_warning'] ?? false,
                    'source' => 'rxnorm',
                    'source_last_updated' => now(),
                    'is_active' => true,
                ],
            );

            if (isset($m['prescription'])) {
                $rx = $m['prescription'];
                Prescription::create([
                    'fhir_medication_request_id' => 'rx-'.Str::uuid(),
                    'patient_id' => $patient->id,
                    'practitioner_id' => $practitioner->id,
                    'visit_id' => $visit->id,
                    'medication_id' => $medication->id,
                    'status' => 'active',
                    'intent' => 'order',
                    'dose_quantity' => $rx['dose_quantity'],
                    'dose_unit' => $rx['dose_unit'],
                    'frequency' => $rx['frequency'],
                    'frequency_text' => $rx['frequency_text'] ?? null,
                    'route' => $rx['route'],
                    'start_date' => now()->toDateString(),
                    'duration_days' => $rx['duration_days'] ?? null,
                    'number_of_refills' => $rx['number_of_refills'] ?? 0,
                    'refills_remaining' => $rx['number_of_refills'] ?? 0,
                    'special_instructions' => $rx['special_instructions'] ?? null,
                    'indication' => $rx['indication'] ?? null,
                    'indication_code' => $rx['indication_code'] ?? null,
                    'substitution_allowed' => $rx['substitution_allowed'] ?? true,
                    'created_by' => $doctor->id,
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $observations
     */
    private function createObservations(array $observations, Patient $patient, Visit $visit, Practitioner $practitioner, User $doctor): void
    {
        $yesterday = now()->subDay()->toDateString();

        foreach ($observations as $obs) {
            Observation::create([
                'fhir_observation_id' => 'obs-'.Str::random(6).'-'.Str::uuid(),
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'practitioner_id' => $practitioner->id,
                'code_system' => $obs['code_system'],
                'code' => $obs['code'],
                'code_display' => $obs['code_display'],
                'category' => $obs['category'],
                'status' => 'final',
                'value_type' => $obs['value_type'],
                'value_quantity' => $obs['value_quantity'] ?? null,
                'value_unit' => $obs['value_unit'] ?? null,
                'value_string' => $obs['value_string'] ?? null,
                'reference_range_low' => $obs['reference_range_low'] ?? null,
                'reference_range_high' => $obs['reference_range_high'] ?? null,
                'reference_range_text' => $obs['reference_range_text'] ?? null,
                'interpretation' => $obs['interpretation'] ?? null,
                'specialty_data' => $obs['specialty_data'] ?? null,
                'effective_date' => $yesterday,
                'issued_at' => now()->subDay(),
                'created_by' => $doctor->id,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $weightSeries
     */
    private function createWeightSeries(array $weightSeries, Patient $patient, Practitioner $practitioner, User $doctor): void
    {
        foreach ($weightSeries as $w) {
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
    }

    private function createVisitNote(array $data, Visit $visit, Patient $patient, Practitioner $practitioner): void
    {
        if (empty($data)) {
            return;
        }

        VisitNote::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'author_practitioner_id' => $practitioner->id,
            'composition_type' => $data['composition_type'],
            'status' => 'final',
            'chief_complaint' => $data['chief_complaint'],
            'history_of_present_illness' => $data['history_of_present_illness'] ?? null,
            'review_of_systems' => $data['review_of_systems'] ?? null,
            'physical_exam' => $data['physical_exam'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'assessment_codes' => $data['assessment_codes'] ?? null,
            'plan' => $data['plan'] ?? null,
            'medical_terms' => $data['medical_terms'] ?? null,
            'follow_up' => $data['follow_up'] ?? null,
            'follow_up_timeframe' => $data['follow_up_timeframe'] ?? null,
            'is_signed' => true,
            'signed_at' => now()->subDay(),
        ]);
    }

    private function createTranscript(?string $file, Visit $visit, Patient $patient): void
    {
        if (! $file) {
            return;
        }

        $path = base_path($file);
        if (! file_exists($path)) {
            return;
        }

        Transcript::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'source_type' => 'ambient_device',
            'stt_provider' => 'whisper',
            'audio_duration_seconds' => 1590,
            'raw_transcript' => file_get_contents($path),
            'processing_status' => 'completed',
            'patient_consent_given' => true,
            'consent_timestamp' => now()->subDay(),
        ]);
    }

    private function createChatSession(array $data, Patient $patient, Visit $visit): void
    {
        if (empty($data)) {
            return;
        }

        ChatSession::create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'topic' => $data['topic'],
            'status' => 'active',
            'initiated_at' => now(),
        ]);
    }
}
