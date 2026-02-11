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
use Carbon\Carbon;
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
        // If source_dir exists, load data from JSON and merge with scenario config
        if (isset($scenario['source_dir'])) {
            $loaded = $this->loadFromSourceDir(base_path($scenario['source_dir']));
            $scenario = array_merge($scenario, $loaded);
            $scenario['transcript_file'] = $scenario['source_dir'].'/raw-transcript.txt';
        }

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

    /**
     * Load patient data from a source directory's patient-profile.json.
     *
     * Transforms the simplified JSON format into the full format expected
     * by the existing seeder methods.
     *
     * @return array{patient: array, conditions: array, medications: array, observations: array}
     */
    public function loadFromSourceDir(string $sourceDir): array
    {
        $jsonPath = $sourceDir.'/patient-profile.json';
        if (! file_exists($jsonPath)) {
            throw new \RuntimeException("Patient profile not found: {$jsonPath}");
        }

        $json = json_decode(file_get_contents($jsonPath), true);

        return [
            'patient' => $this->transformPatient($json),
            'conditions' => $this->transformConditions($json['conditions'] ?? []),
            'medications' => $this->transformMedications($json['medications'] ?? []),
            'observations' => $this->transformObservations($json),
        ];
    }

    /**
     * Transform JSON demographics into the flat patient array.
     *
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function transformPatient(array $json): array
    {
        $d = $json['demographics'];

        return [
            'first_name' => $d['first_name'],
            'last_name' => $d['last_name'],
            'dob' => $d['dob'],
            'gender' => $d['gender'],
            'phone' => $d['phone'],
            'preferred_language' => $d['preferred_language'],
            'timezone' => 'Europe/Brussels',
            'mrn' => $d['mrn'],
            'height_cm' => $d['height_cm'],
            'weight_kg' => $d['weight_kg'],
            'blood_type' => $d['blood_type'] ?? null,
            'allergies' => $json['allergies'] ?? [],
            'emergency_contact_name' => $json['emergency_contact']['name'] ?? null,
            'emergency_contact_phone' => $json['emergency_contact']['phone'] ?? null,
            'emergency_contact_relationship' => $json['emergency_contact']['relationship'] ?? null,
        ];
    }

    /**
     * Transform simplified conditions into FHIR-like format.
     *
     * @param  array<int, array<string, mixed>>  $conditions
     * @return array<int, array<string, mixed>>
     */
    private function transformConditions(array $conditions): array
    {
        return array_map(function (array $c) {
            $result = [
                'code_system' => 'ICD-10-CM',
                'code' => $c['icd10'],
                'code_display' => $c['name'],
                'category' => 'encounter-diagnosis',
                'clinical_status' => $c['status'],
                'verification_status' => 'confirmed',
                'severity' => null,
            ];

            // Parse onset string (e.g. "2020-06") to calculate onset_years_ago
            if (isset($c['onset'])) {
                $onsetDate = Carbon::parse($c['onset'].'-01');
                $yearsAgo = (int) $onsetDate->diffInYears(now());
                if ($yearsAgo > 0) {
                    $result['onset_years_ago'] = $yearsAgo;
                } else {
                    $weeksAgo = (int) $onsetDate->diffInWeeks(now());
                    $result['onset_weeks_ago'] = max(1, $weeksAgo);
                }
            }

            return $result;
        }, $conditions);
    }

    /**
     * Transform simplified medications into RxNorm-like format.
     *
     * @param  array<int, array<string, mixed>>  $medications
     * @return array<int, array<string, mixed>>
     */
    private function transformMedications(array $medications): array
    {
        return array_values(array_filter(array_map(function (array $m) {
            // Skip discontinued medications
            if (($m['status'] ?? 'active') === 'discontinued') {
                return null;
            }

            $parsed = $this->parseDoseString($m['dose'] ?? '');

            return [
                'rxnorm_code' => (string) abs(crc32($m['generic_name'])),
                'generic_name' => $m['generic_name'],
                'display_name' => $m['generic_name'].' '.$m['dose'],
                'form' => 'tablet',
                'strength_value' => $parsed['value'],
                'strength_unit' => $parsed['unit'],
                'prescription' => [
                    'dose_quantity' => $parsed['value'],
                    'dose_unit' => $parsed['unit'],
                    'frequency' => $m['frequency'],
                    'route' => 'oral',
                    'special_instructions' => $m['reason'] ?? null,
                ],
            ];
        }, $medications)));
    }

    /**
     * Parse a dose string like "400mg" or "50mcg" into value and unit.
     *
     * @return array{value: float, unit: string}
     */
    private function parseDoseString(string $dose): array
    {
        if (preg_match('/^([\d.]+)\s*(.+)$/', trim($dose), $matches)) {
            return [
                'value' => (float) $matches[1],
                'unit' => $matches[2],
            ];
        }

        return ['value' => 0, 'unit' => 'mg'];
    }

    /**
     * Transform lab results and vitals into LOINC observations.
     *
     * @param  array<string, mixed>  $json
     * @return array<int, array<string, mixed>>
     */
    private function transformObservations(array $json): array
    {
        $observations = [];

        // Lab results
        foreach ($json['lab_results'] ?? [] as $lab) {
            // Skip projected/future lab results
            $note = $lab['note'] ?? '';
            if (stripos($note, 'projected') !== false) {
                continue;
            }

            $observations[] = [
                'code_system' => 'LOINC',
                'code' => $lab['loinc'],
                'code_display' => $lab['test'],
                'category' => 'laboratory',
                'value_type' => 'quantity',
                'value_quantity' => $lab['value'],
                'value_unit' => $lab['unit'],
                'reference_range_low' => $lab['reference_range_low'],
                'reference_range_high' => $lab['reference_range_high'],
                'reference_range_text' => $lab['reference_text'] ?? null,
                'interpretation' => $lab['interpretation'],
            ];
        }

        // Vitals from the last entry in vitals_timeline
        $vitalsTimeline = $json['vitals_timeline'] ?? [];
        if (! empty($vitalsTimeline)) {
            $lastVitals = end($vitalsTimeline);

            // Blood pressure panel
            $observations[] = [
                'code_system' => 'LOINC',
                'code' => '85354-9',
                'code_display' => 'Blood pressure panel',
                'category' => 'vital-signs',
                'value_type' => 'string',
                'value_string' => $lastVitals['systolic'].'/'.$lastVitals['diastolic'].' mmHg',
                'interpretation' => 'N',
                'specialty_data' => [
                    'systolic' => ['value' => $lastVitals['systolic'], 'unit' => 'mmHg', 'code' => '8480-6'],
                    'diastolic' => ['value' => $lastVitals['diastolic'], 'unit' => 'mmHg', 'code' => '8462-4'],
                ],
            ];

            // Heart rate
            if (isset($lastVitals['heart_rate'])) {
                $observations[] = [
                    'code_system' => 'LOINC',
                    'code' => '8867-4',
                    'code_display' => 'Heart rate',
                    'category' => 'vital-signs',
                    'value_type' => 'quantity',
                    'value_quantity' => $lastVitals['heart_rate'],
                    'value_unit' => 'bpm',
                    'reference_range_low' => 60,
                    'reference_range_high' => 100,
                    'interpretation' => 'N',
                ];
            }

            // SpO2
            if (isset($lastVitals['spo2'])) {
                $observations[] = [
                    'code_system' => 'LOINC',
                    'code' => '2708-6',
                    'code_display' => 'Oxygen saturation',
                    'category' => 'vital-signs',
                    'value_type' => 'quantity',
                    'value_quantity' => $lastVitals['spo2'],
                    'value_unit' => '%',
                    'reference_range_low' => 95,
                    'reference_range_high' => 100,
                    'interpretation' => $lastVitals['spo2'] >= 95 ? 'N' : 'L',
                ];
            }
        }

        return $observations;
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
        $namePart = strtolower($data['first_name']).'.'.strtolower($data['last_name']);
        $suffix = substr(md5(Str::uuid()->toString()), 0, 3);

        return Patient::create([
            'fhir_patient_id' => 'patient-'.Str::uuid(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'dob' => $data['dob'],
            'gender' => $data['gender'],
            'email' => "{$namePart}.{$suffix}@demo.postvisit.ai",
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
        $suffix = substr(md5(Str::uuid()->toString()), 0, 3);

        return User::create([
            'name' => $patient->first_name.' '.$patient->last_name,
            'email' => "{$namePart}.{$suffix}@demo.postvisit.ai",
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
