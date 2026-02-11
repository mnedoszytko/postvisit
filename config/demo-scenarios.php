<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Scenarios
    |--------------------------------------------------------------------------
    |
    | Each scenario defines a complete patient profile with clinical data
    | that gets seeded when a demo user selects it. The doctor user is
    | shared across all scenarios.
    |
    */

    'doctor' => [
        'name' => 'Dr. Nedo',
        'email' => 'doctor@demo.postvisit.ai',
        'first_name' => 'Michael',
        'last_name' => 'Nedoszytko',
        'npi' => '1234567890',
        'license_number' => 'CA-CARD-2024-001',
        'medical_degree' => 'MD, PhD',
        'primary_specialty' => 'cardiology',
        'secondary_specialties' => ['internal_medicine'],
    ],

    'organization' => [
        'name' => 'City Heart Clinic',
        'type' => 'cardiology',
        'address' => '123 Heart Street, San Francisco, CA',
        'phone' => '+1-415-555-0100',
        'email' => 'info@cityheartclinic.com',
    ],

    'scenarios' => [

        'pvcs' => [
            'key' => 'pvcs',
            'name' => 'PVCs / Palpitations',
            'description' => 'Heart palpitations and irregular heartbeat for 3 weeks. EKG shows PVCs. Started on Propranolol.',
            'icon' => 'heart-pulse',
            'color' => 'emerald',

            'patient' => [
                'first_name' => 'Alex',
                'last_name' => 'Johnson',
                'dob' => '1985-03-15',
                'gender' => 'male',
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
            ],

            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'cardiology_consultation',
                'reason_for_visit' => 'Heart palpitations and irregular heartbeat',
                'reason_codes' => [
                    ['system' => 'ICD-10-CM', 'code' => 'R00.2', 'display' => 'Palpitations'],
                    ['system' => 'ICD-10-CM', 'code' => 'R00.1', 'display' => 'Bradycardia, unspecified'],
                ],
                'summary' => 'Patient presents with 3-week history of heart palpitations. EKG shows PVCs. Started on Propranolol 40mg BID.',
                'duration_minutes' => 45,
                'days_ago' => 1,
                'provider_notes_followup' => 'Follow up in 2 weeks for BP check, 3 months for repeat Holter.',
            ],

            'conditions' => [
                [
                    'code_system' => 'ICD-10-CM',
                    'code' => 'I49.3',
                    'code_display' => 'Premature ventricular contractions',
                    'category' => 'encounter-diagnosis',
                    'clinical_status' => 'active',
                    'verification_status' => 'confirmed',
                    'severity' => 'moderate',
                    'onset_weeks_ago' => 3,
                    'clinical_notes' => 'Patient reports frequent palpitations for 3 weeks. EKG confirms frequent PVCs. Echocardiogram shows preserved EF. Starting beta-blocker therapy.',
                ],
            ],

            'medications' => [
                [
                    'rxnorm_code' => '8787',
                    'atc_code' => 'C07AA05',
                    'generic_name' => 'Propranolol Hydrochloride',
                    'brand_names' => ['Inderal', 'Inderal LA'],
                    'display_name' => 'Propranolol 40mg tablet',
                    'form' => 'tablet',
                    'strength_value' => 40,
                    'strength_unit' => 'mg',
                    'ingredients' => ['propranolol hydrochloride'],
                    'pregnancy_category' => 'C',
                    'prescription' => [
                        'dose_quantity' => 40,
                        'dose_unit' => 'mg',
                        'frequency' => 'BID',
                        'frequency_text' => 'Every 12 hours with meals',
                        'route' => 'oral',
                        'duration_days' => 30,
                        'number_of_refills' => 3,
                        'special_instructions' => 'Take with food. Do not stop abruptly. Monitor heart rate and blood pressure.',
                        'indication' => 'Premature ventricular contractions',
                        'indication_code' => 'I49.3',
                        'substitution_allowed' => true,
                    ],
                ],
            ],

            'observations' => [
                [
                    'code_system' => 'LOINC', 'code' => '8867-4', 'code_display' => 'Heart rate',
                    'category' => 'vital-signs', 'value_type' => 'quantity',
                    'value_quantity' => 78, 'value_unit' => 'bpm',
                    'reference_range_low' => 60, 'reference_range_high' => 100,
                    'interpretation' => 'N',
                ],
                [
                    'code_system' => 'LOINC', 'code' => '85354-9', 'code_display' => 'Blood pressure panel',
                    'category' => 'vital-signs', 'value_type' => 'string',
                    'value_string' => '128/82 mmHg', 'interpretation' => 'N',
                    'specialty_data' => [
                        'systolic' => ['value' => 128, 'unit' => 'mmHg', 'code' => '8480-6'],
                        'diastolic' => ['value' => 82, 'unit' => 'mmHg', 'code' => '8462-4'],
                    ],
                ],
                [
                    'code_system' => 'SNOMED-CT', 'code' => '29303005', 'code_display' => '12-Lead electrocardiogram',
                    'category' => 'exam', 'value_type' => 'string',
                    'value_string' => 'Normal sinus rhythm with frequent PVCs. No ST-segment changes. Normal axis.',
                    'specialty_data' => [
                        'rhythm' => 'Normal sinus rhythm', 'rate' => 78, 'axis' => 'Normal',
                        'intervals' => ['PR' => 160, 'QRS' => 88, 'QTc' => 420],
                        'findings' => [
                            'Frequent premature ventricular complexes (PVCs)',
                            'No ST-segment elevation or depression',
                            'Normal P-wave morphology',
                            'No bundle branch block pattern',
                        ],
                        'interpretation' => 'Abnormal - frequent PVCs noted',
                    ],
                ],
                [
                    'code_system' => 'SNOMED-CT', 'code' => '40701008', 'code_display' => 'Echocardiography',
                    'category' => 'exam', 'value_type' => 'string',
                    'value_string' => 'EF 55%, normal wall motion, no valvular abnormalities.',
                    'specialty_data' => [
                        'ejection_fraction' => 55, 'wall_motion' => 'Normal',
                        'lv_size' => 'Normal', 'rv_function' => 'Normal',
                        'valves' => [
                            'mitral' => 'No regurgitation', 'aortic' => 'No stenosis or regurgitation',
                            'tricuspid' => 'Trace regurgitation', 'pulmonic' => 'Normal',
                        ],
                        'pericardium' => 'No effusion',
                        'conclusion' => 'Normal echocardiogram with preserved ejection fraction',
                    ],
                ],
                [
                    'code_system' => 'LOINC', 'code' => '2093-3', 'code_display' => 'Total cholesterol',
                    'category' => 'laboratory', 'value_type' => 'quantity',
                    'value_quantity' => 215, 'value_unit' => 'mg/dL',
                    'reference_range_low' => 0, 'reference_range_high' => 200,
                    'reference_range_text' => 'Desirable: <200 mg/dL', 'interpretation' => 'H',
                ],
                [
                    'code_system' => 'LOINC', 'code' => '2823-3', 'code_display' => 'Potassium [Moles/volume] in Serum or Plasma',
                    'category' => 'laboratory', 'value_type' => 'quantity',
                    'value_quantity' => 4.2, 'value_unit' => 'mEq/L',
                    'reference_range_low' => 3.5, 'reference_range_high' => 5.0,
                    'reference_range_text' => '3.5-5.0 mEq/L', 'interpretation' => 'N',
                ],
                [
                    'code_system' => 'LOINC', 'code' => '3016-3', 'code_display' => 'Thyrotropin [Units/volume] in Serum or Plasma',
                    'category' => 'laboratory', 'value_type' => 'quantity',
                    'value_quantity' => 2.1, 'value_unit' => 'mIU/L',
                    'reference_range_low' => 0.4, 'reference_range_high' => 4.0,
                    'reference_range_text' => '0.4-4.0 mIU/L', 'interpretation' => 'N',
                ],
            ],

            'visit_note' => [
                'composition_type' => 'progress_note',
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
                'medical_terms' => [
                    'chief_complaint' => [
                        ['term' => 'palpitations', 'start' => 6, 'end' => 18, 'definition' => 'A sensation of your heart beating rapidly, fluttering, or pounding. In your case, these are caused by extra heartbeats called PVCs.'],
                        ['term' => 'irregular heartbeat', 'start' => 23, 'end' => 42, 'definition' => 'When the heart doesn\'t beat in its normal steady rhythm. Your EKG showed extra beats (PVCs) causing this irregularity.'],
                    ],
                    'history_of_present_illness' => [
                        ['term' => 'intermittent', 'start' => 70, 'end' => 82, 'definition' => 'Coming and going, not constant. Your palpitations happen on and off throughout the day.'],
                        ['term' => 'heart palpitations', 'start' => 83, 'end' => 101, 'definition' => 'The feeling of your heart racing, fluttering, or skipping beats. You described these as a "fluttering" sensation.'],
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
                        ['term' => 'SpO2', 'start' => 84, 'end' => 88, 'definition' => 'Oxygen saturation — how much oxygen your blood is carrying. 99% is excellent (95% or above is normal).'],
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
                    ],
                    'plan' => [
                        ['term' => 'Propranolol', 'start' => 9, 'end' => 20, 'definition' => 'A beta-blocker medication that slows your heart rate and reduces the force of contractions. It helps suppress PVCs and reduce palpitation symptoms.'],
                        ['term' => 'BID', 'start' => 29, 'end' => 32, 'definition' => '"Bis in die" — Latin for "twice a day." Take one pill in the morning and one in the evening, with meals.'],
                        ['term' => 'PVC suppression', 'start' => 60, 'end' => 75, 'definition' => 'Reducing the number of extra heartbeats. The Propranolol aims to make PVCs less frequent and less noticeable.'],
                        ['term' => 'Holter monitor', 'start' => 334, 'end' => 348, 'definition' => 'A portable device you wear for 24 hours that continuously records your heart rhythm. This will show exactly how many PVCs you\'re having and whether the medication is working.'],
                        ['term' => 'PVC burden', 'start' => 373, 'end' => 383, 'definition' => 'The percentage of your total heartbeats that are PVCs. A burden under 10% is generally considered safe. The Holter monitor will measure this.'],
                    ],
                ],
            ],

            'transcript_file' => 'demo/transcript.txt',

            'chat_session' => [
                'topic' => 'Post-visit follow-up: PVCs and Propranolol',
            ],
        ],

        'heart-failure' => [
            'key' => 'heart-failure',
            'name' => 'Heart Failure',
            'description' => 'Chronic heart failure management with recent decompensation. Weight monitoring and medication adjustment.',
            'icon' => 'activity',
            'color' => 'rose',

            'patient' => [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'dob' => '1958-11-22',
                'gender' => 'female',
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
            ],

            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'cardiology_consultation',
                'reason_for_visit' => 'Heart failure follow-up — worsening shortness of breath and weight gain',
                'summary' => 'HFrEF patient with recent decompensation. Weight up 4 kg in 2 weeks. Adjusted diuretics, added SGLT2i.',
                'duration_minutes' => 35,
                'days_ago' => 3,
            ],

            'conditions' => [
                [
                    'code_system' => 'ICD-10-CM',
                    'code' => 'I50.22',
                    'code_display' => 'Chronic systolic heart failure',
                    'category' => 'encounter-diagnosis',
                    'clinical_status' => 'active',
                    'verification_status' => 'confirmed',
                    'severity' => 'moderate',
                    'onset_years_ago' => 2,
                    'clinical_notes' => 'HFrEF with LVEF 35%. Recent decompensation with fluid overload.',
                ],
                [
                    'code_system' => 'ICD-10-CM',
                    'code' => 'I10',
                    'code_display' => 'Essential hypertension',
                    'category' => 'encounter-diagnosis',
                    'clinical_status' => 'active',
                    'verification_status' => 'confirmed',
                    'severity' => 'mild',
                    'onset_years_ago' => 10,
                ],
            ],

            'medications' => [
                [
                    'rxnorm_code' => '4603', 'generic_name' => 'Furosemide',
                    'display_name' => 'Furosemide 40mg tablet',
                    'form' => 'tablet', 'strength_value' => 40, 'strength_unit' => 'mg',
                    'prescription' => [
                        'dose_quantity' => 40, 'dose_unit' => 'mg', 'frequency' => 'BID',
                        'route' => 'oral',
                        'special_instructions' => 'Increase from 20mg QD due to fluid overload',
                    ],
                ],
                [
                    'rxnorm_code' => '29046', 'generic_name' => 'Lisinopril',
                    'display_name' => 'Lisinopril 20mg tablet',
                    'form' => 'tablet', 'strength_value' => 20, 'strength_unit' => 'mg',
                    'prescription' => [
                        'dose_quantity' => 20, 'dose_unit' => 'mg', 'frequency' => 'QD',
                        'route' => 'oral',
                        'special_instructions' => 'Continued — titrated to max tolerated dose',
                    ],
                ],
                [
                    'rxnorm_code' => '20352', 'generic_name' => 'Carvedilol',
                    'display_name' => 'Carvedilol 25mg tablet',
                    'form' => 'tablet', 'strength_value' => 25, 'strength_unit' => 'mg',
                    'prescription' => [
                        'dose_quantity' => 25, 'dose_unit' => 'mg', 'frequency' => 'BID',
                        'route' => 'oral',
                        'special_instructions' => 'Continued — stable on current dose',
                    ],
                ],
                [
                    'rxnorm_code' => '1488564', 'generic_name' => 'Dapagliflozin',
                    'display_name' => 'Dapagliflozin 10mg tablet',
                    'form' => 'tablet', 'strength_value' => 10, 'strength_unit' => 'mg',
                    'prescription' => [
                        'dose_quantity' => 10, 'dose_unit' => 'mg', 'frequency' => 'QD',
                        'route' => 'oral',
                        'special_instructions' => 'NEW — added for HFrEF mortality benefit per ESC 2023',
                    ],
                ],
            ],

            'observations' => [
                [
                    'code_system' => 'LOINC', 'code' => '30934-4', 'code_display' => 'BNP',
                    'category' => 'laboratory', 'value_type' => 'quantity',
                    'value_quantity' => 820, 'value_unit' => 'pg/mL',
                    'reference_range_low' => 0, 'reference_range_high' => 100,
                    'interpretation' => 'H',
                ],
                [
                    'code_system' => 'LOINC', 'code' => '2160-0', 'code_display' => 'Creatinine',
                    'category' => 'laboratory', 'value_type' => 'quantity',
                    'value_quantity' => 1.6, 'value_unit' => 'mg/dL',
                    'reference_range_low' => 0.6, 'reference_range_high' => 1.1,
                    'interpretation' => 'H',
                ],
            ],

            'weight_series' => [
                ['day' => -28, 'kg' => 73.5], ['day' => -25, 'kg' => 73.8], ['day' => -21, 'kg' => 74.0],
                ['day' => -18, 'kg' => 74.2], ['day' => -14, 'kg' => 74.5], ['day' => -11, 'kg' => 75.2],
                ['day' => -8, 'kg' => 76.0], ['day' => -6, 'kg' => 76.8], ['day' => -4, 'kg' => 77.5],
                ['day' => -3, 'kg' => 78.0], ['day' => -2, 'kg' => 77.2], ['day' => -1, 'kg' => 76.5],
                ['day' => 0, 'kg' => 75.8],
            ],

            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Worsening shortness of breath and weight gain over the past 2 weeks',
                'history_of_present_illness' => 'Mrs. Santos is a 67-year-old female with known HFrEF (LVEF 35%) presenting with progressive dyspnea on exertion and 4 kg weight gain over 2 weeks.',
                'physical_exam' => "General: Alert, mildly dyspneic at rest. Vitals: HR 88, BP 142/88, RR 22, SpO2 94% on RA, Weight 78 kg.\nCardiovascular: S3 gallop present, JVP elevated to 12 cm.\nLungs: Bibasilar crackles.\nExtremities: 2+ pitting edema bilaterally.",
                'assessment' => 'Acute-on-chronic heart failure decompensation (HFrEF, LVEF 35%) — NYHA Class III. Precipitated by dietary sodium indiscretion.',
                'plan' => "1. Increase Furosemide to 40mg BID\n2. Add Dapagliflozin 10mg QD (DAPA-HF trial)\n3. Continue Lisinopril 20mg QD and Carvedilol 25mg BID\n4. Daily weight monitoring\n5. Strict 1.5L/day fluid restriction\n6. Recheck labs in 1 week\n7. Follow-up in 2 weeks",
                'follow_up' => '2 weeks for volume status reassessment.',
                'follow_up_timeframe' => '2 weeks',
            ],

            'chat_session' => [
                'topic' => 'Post-visit follow-up: Heart failure management',
            ],
        ],

    ],

];
