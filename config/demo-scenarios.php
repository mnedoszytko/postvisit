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
        'name' => 'Dr. Michael Nedoszytko',
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
            'specialty' => 'cardiology',
            'featured' => true,
            'photo_dir' => 'demo/visits/visit-00-pvcs-palpitations',
            'transcript_file' => 'demo/visits/visit-00-pvcs-palpitations/raw-transcript.txt',
            'notes_dir' => 'demo/visits/visit-00-pvcs-palpitations',

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
                'bmi' => 25.2,
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

            'weight_series' => [
                ['day' => -29, 'kg' => 80.2],
                ['day' => -28, 'kg' => 80.5],
                ['day' => -27, 'kg' => 80.6],
                ['day' => -26, 'kg' => 80.6],
                ['day' => -21, 'kg' => 80.5],
                ['day' => -19, 'kg' => 81.0],
                ['day' => -17, 'kg' => 80.8],
                ['day' => -16, 'kg' => 81.3],
                ['day' => -12, 'kg' => 81.0],
                ['day' => -11, 'kg' => 81.3],
                ['day' => -10, 'kg' => 81.5],
                ['day' => -8, 'kg' => 82.0],
                ['day' => -5, 'kg' => 82.2],
                ['day' => -3, 'kg' => 82.5],
                ['day' => -2, 'kg' => 83.8],
                ['day' => -1, 'kg' => 85.3],
            ],
        ],

        'coronarography' => [
            'key' => 'coronarography',
            'name' => 'Coronarography / Stenosis',
            'description' => 'Post-coronarography follow-up for LAD stenosis, fibromyalgia management, and medication review.',
            'icon' => 'heart',
            'color' => 'red',
            'specialty' => 'cardiology',
            'featured' => true,
            'source_dir' => 'demo/visits/visit-01-coronarography-stenosis',
            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'cardiology_consultation',
                'reason_for_visit' => 'Post-coronarography follow-up for LAD stenosis',
                'summary' => 'Post-coronarography follow-up. LAD stenosis confirmed. Reviewing medications and managing fibromyalgia symptoms.',
                'duration_minutes' => 35,
                'days_ago' => 1,
            ],
            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Post-coronarography follow-up for LAD stenosis',
            ],
            'chat_session' => [
                'topic' => 'Post-visit follow-up: Coronarography and stenosis',
            ],

            'weight_series' => [
                ['day' => -29, 'kg' => 68.2],
                ['day' => -28, 'kg' => 67.9],
                ['day' => -23, 'kg' => 68.0],
                ['day' => -21, 'kg' => 68.4],
                ['day' => -20, 'kg' => 68.1],
                ['day' => -19, 'kg' => 68.4],
                ['day' => -16, 'kg' => 68.0],
                ['day' => -14, 'kg' => 67.9],
                ['day' => -13, 'kg' => 68.0],
                ['day' => -11, 'kg' => 68.4],
                ['day' => -10, 'kg' => 68.2],
                ['day' => -7, 'kg' => 68.3],
                ['day' => -5, 'kg' => 67.5],
                ['day' => -4, 'kg' => 68.0],
                ['day' => -2, 'kg' => 67.7],
            ],
        ],

        'gastric-bypass' => [
            'key' => 'gastric-bypass',
            'name' => 'Gastric Bypass Pre-op',
            'description' => 'Pre-operative cardiac clearance for gastric bypass surgery. Echocardiogram and stress test normal.',
            'icon' => 'scissors',
            'color' => 'blue',
            'specialty' => 'cardiology',
            'featured' => true,
            'source_dir' => 'demo/visits/visit-02-gastric-bypass-preop',
            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'cardiology_consultation',
                'reason_for_visit' => 'Pre-operative cardiac clearance for gastric bypass surgery',
                'summary' => 'Cardiac clearance for gastric bypass. Echo and stress test normal. No contraindication for surgery. BMI 33.1.',
                'duration_minutes' => 25,
                'days_ago' => 1,
            ],
            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Pre-operative cardiac clearance for gastric bypass surgery',
            ],
            'chat_session' => [
                'topic' => 'Post-visit follow-up: Gastric bypass cardiac clearance',
            ],

            'weight_series' => [
                ['day' => -29, 'kg' => 90.5],
                ['day' => -28, 'kg' => 90.6],
                ['day' => -27, 'kg' => 89.9],
                ['day' => -23, 'kg' => 90.0],
                ['day' => -19, 'kg' => 90.4],
                ['day' => -18, 'kg' => 89.5],
                ['day' => -17, 'kg' => 89.7],
                ['day' => -15, 'kg' => 90.0],
                ['day' => -12, 'kg' => 90.3],
                ['day' => -11, 'kg' => 90.3],
                ['day' => -6, 'kg' => 90.1],
                ['day' => -5, 'kg' => 90.6],
                ['day' => -4, 'kg' => 89.6],
                ['day' => -2, 'kg' => 90.0],
                ['day' => -1, 'kg' => 89.9],
            ],
        ],

        'hypertension-followup' => [
            'key' => 'hypertension-followup',
            'name' => 'Hypertension Follow-up',
            'description' => 'Routine hypertension follow-up with medication adjustment and lifestyle counseling.',
            'icon' => 'gauge',
            'color' => 'amber',
            'specialty' => 'cardiology',
            'featured' => true,
            'source_dir' => 'demo/visits/visit-03-hypertension-followup',
            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'cardiology_consultation',
                'reason_for_visit' => 'Hypertension follow-up and medication review',
                'summary' => 'Hypertension follow-up. Blood pressure controlled on current regimen. Continuing Amlodipine 5mg and monitoring.',
                'duration_minutes' => 20,
                'days_ago' => 1,
            ],
            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Hypertension follow-up and medication review',
            ],
            'chat_session' => [
                'topic' => 'Post-visit follow-up: Hypertension management',
            ],

            'weight_series' => [
                ['day' => -29, 'kg' => 81.5],
                ['day' => -28, 'kg' => 81.8],
                ['day' => -26, 'kg' => 81.5],
                ['day' => -25, 'kg' => 81.9],
                ['day' => -23, 'kg' => 81.4],
                ['day' => -21, 'kg' => 82.0],
                ['day' => -18, 'kg' => 81.5],
                ['day' => -16, 'kg' => 82.2],
                ['day' => -15, 'kg' => 81.9],
                ['day' => -11, 'kg' => 82.6],
                ['day' => -9, 'kg' => 81.7],
                ['day' => -8, 'kg' => 82.0],
                ['day' => -7, 'kg' => 82.3],
                ['day' => -3, 'kg' => 81.8],
                ['day' => -1, 'kg' => 81.8],
            ],
        ],

        'chest-pain' => [
            'key' => 'chest-pain',
            'name' => 'Chest Pain / Carotid',
            'description' => 'Evaluation of atypical chest pain with carotid stenosis workup. Echo and carotid ultrasound performed.',
            'icon' => 'alert-triangle',
            'color' => 'orange',
            'specialty' => 'cardiology',
            'source_dir' => 'demo/visits/visit-04-chest-pain-carotid',
            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'cardiology_consultation',
                'reason_for_visit' => 'Evaluation of chest pain and carotid stenosis',
                'summary' => 'Chest pain evaluation with carotid ultrasound. Mild carotid stenosis noted. Echo shows preserved EF. Conservative management.',
                'duration_minutes' => 35,
                'days_ago' => 1,
            ],
            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Evaluation of chest pain and carotid stenosis',
            ],
            'chat_session' => [
                'topic' => 'Post-visit follow-up: Chest pain and carotid assessment',
            ],

            'weight_series' => [
                ['day' => -29, 'kg' => 95.0],
                ['day' => -28, 'kg' => 94.6],
                ['day' => -27, 'kg' => 95.2],
                ['day' => -25, 'kg' => 95.5],
                ['day' => -24, 'kg' => 95.3],
                ['day' => -22, 'kg' => 94.9],
                ['day' => -18, 'kg' => 94.6],
                ['day' => -17, 'kg' => 95.6],
                ['day' => -16, 'kg' => 95.0],
                ['day' => -13, 'kg' => 95.2],
                ['day' => -9, 'kg' => 95.4],
                ['day' => -8, 'kg' => 94.9],
                ['day' => -7, 'kg' => 94.5],
                ['day' => -5, 'kg' => 94.6],
                ['day' => -1, 'kg' => 95.2],
            ],
        ],

        'fibromyalgia' => [
            'key' => 'fibromyalgia',
            'name' => 'Arm Pain / Fibromyalgia',
            'description' => 'Evaluation of arm pain with possible fibromyalgia diagnosis. Cardiac workup to rule out cardiac origin.',
            'icon' => 'hand',
            'color' => 'purple',
            'specialty' => 'cardiology',
            'source_dir' => 'demo/visits/visit-05-arm-pain-fibromyalgia',
            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'cardiology_consultation',
                'reason_for_visit' => 'Evaluation of arm pain — rule out cardiac origin',
                'summary' => 'Arm pain evaluation. Cardiac workup negative. Likely fibromyalgia or musculoskeletal origin. Referral to rheumatology.',
                'duration_minutes' => 20,
                'days_ago' => 1,
            ],
            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Evaluation of arm pain — rule out cardiac origin',
            ],
            'chat_session' => [
                'topic' => 'Post-visit follow-up: Arm pain assessment',
            ],

            'weight_series' => [
                ['day' => -29, 'kg' => 72.0],
                ['day' => -24, 'kg' => 72.1],
                ['day' => -20, 'kg' => 71.8],
                ['day' => -19, 'kg' => 71.5],
                ['day' => -18, 'kg' => 72.3],
                ['day' => -16, 'kg' => 71.7],
                ['day' => -15, 'kg' => 72.1],
                ['day' => -13, 'kg' => 71.5],
                ['day' => -8, 'kg' => 72.5],
                ['day' => -7, 'kg' => 71.7],
                ['day' => -6, 'kg' => 72.5],
                ['day' => -4, 'kg' => 71.7],
                ['day' => -3, 'kg' => 71.5],
                ['day' => -2, 'kg' => 72.0],
                ['day' => -1, 'kg' => 72.5],
            ],
        ],

        'aortic-aneurysm' => [
            'key' => 'aortic-aneurysm',
            'name' => 'Aortic Aneurysm / Smoking',
            'description' => 'Follow-up for thoracic aortic aneurysm with smoking cessation counseling and monitoring.',
            'icon' => 'wind',
            'color' => 'slate',
            'specialty' => 'cardiology',
            'source_dir' => 'demo/visits/visit-06-aortic-aneurysm-smoking',
            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'cardiology_consultation',
                'reason_for_visit' => 'Aortic aneurysm follow-up and smoking cessation counseling',
                'summary' => 'Aortic aneurysm monitoring. Stable size on imaging. Smoking cessation discussed. Continuing conservative management.',
                'duration_minutes' => 25,
                'days_ago' => 1,
            ],
            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Aortic aneurysm follow-up and smoking cessation counseling',
            ],
            'chat_session' => [
                'topic' => 'Post-visit follow-up: Aortic aneurysm management',
            ],

            'weight_series' => [
                ['day' => -29, 'kg' => 85.5],
                ['day' => -27, 'kg' => 84.6],
                ['day' => -25, 'kg' => 85.5],
                ['day' => -23, 'kg' => 85.0],
                ['day' => -22, 'kg' => 84.9],
                ['day' => -21, 'kg' => 84.4],
                ['day' => -19, 'kg' => 85.3],
                ['day' => -13, 'kg' => 85.4],
                ['day' => -11, 'kg' => 85.4],
                ['day' => -10, 'kg' => 84.8],
                ['day' => -9, 'kg' => 85.5],
                ['day' => -7, 'kg' => 85.4],
                ['day' => -5, 'kg' => 85.5],
                ['day' => -3, 'kg' => 85.1],
                ['day' => -2, 'kg' => 84.4],
            ],
        ],

        'preop-stent' => [
            'key' => 'preop-stent',
            'name' => 'Pre-op Stent / Statin',
            'description' => 'Pre-operative cardiac assessment for shoulder surgery. Post-MI with 2 stents. Statin intolerance investigation.',
            'icon' => 'pill',
            'color' => 'indigo',
            'specialty' => 'cardiology',
            'source_dir' => 'demo/visits/visit-07-preop-stent-statin',
            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'cardiology_consultation',
                'reason_for_visit' => 'Pre-operative cardiac assessment for shoulder surgery — post-MI with stents',
                'summary' => 'Pre-op clearance for shoulder arthroscopy. Post-MI with 2 RCA stents (2020). Statin stopped 2 months ago due to myalgia. Cardiac clearance approved. Restart statin, recheck labs in 4 weeks.',
                'duration_minutes' => 30,
                'days_ago' => 1,
            ],
            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Pre-operative cardiac assessment for shoulder surgery — post-MI with stents',
            ],
            'chat_session' => [
                'topic' => 'Post-visit follow-up: Pre-op clearance and statin management',
            ],

            'weight_series' => [
                ['day' => -29, 'kg' => 118.8],
                ['day' => -28, 'kg' => 118.7],
                ['day' => -26, 'kg' => 118.9],
                ['day' => -19, 'kg' => 119.4],
                ['day' => -18, 'kg' => 118.6],
                ['day' => -16, 'kg' => 119.6],
                ['day' => -15, 'kg' => 118.9],
                ['day' => -14, 'kg' => 119.4],
                ['day' => -11, 'kg' => 119.4],
                ['day' => -10, 'kg' => 118.9],
                ['day' => -8, 'kg' => 118.7],
                ['day' => -7, 'kg' => 119.5],
                ['day' => -4, 'kg' => 119.4],
                ['day' => -3, 'kg' => 118.9],
                ['day' => -1, 'kg' => 118.5],
            ],
        ],

        'bp-monitoring' => [
            'key' => 'bp-monitoring',
            'name' => 'BP Monitoring',
            'description' => 'Hypertension management with 24-hour ambulatory blood pressure monitoring referral.',
            'icon' => 'activity',
            'color' => 'teal',
            'specialty' => 'cardiology',
            'source_dir' => 'demo/visits/visit-08-hypertension-bp-monitoring',
            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'cardiology_consultation',
                'reason_for_visit' => 'Hypertension follow-up — elevated readings, referral for 24h BP monitoring',
                'summary' => 'Hypertension assessment. In-office BP 170/96, home readings 130-148. Echo normal. Referred for 24h ambulatory BP monitoring. Continue Amlodipine 5mg.',
                'duration_minutes' => 25,
                'days_ago' => 1,
            ],
            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Hypertension follow-up — elevated readings, referral for 24h BP monitoring',
            ],
            'chat_session' => [
                'topic' => 'Post-visit follow-up: Blood pressure monitoring',
            ],

            'weight_series' => [
                ['day' => -29, 'kg' => 88.1],
                ['day' => -28, 'kg' => 88.6],
                ['day' => -26, 'kg' => 87.8],
                ['day' => -21, 'kg' => 88.2],
                ['day' => -20, 'kg' => 87.7],
                ['day' => -19, 'kg' => 87.9],
                ['day' => -17, 'kg' => 88.1],
                ['day' => -15, 'kg' => 87.7],
                ['day' => -13, 'kg' => 88.2],
                ['day' => -11, 'kg' => 88.0],
                ['day' => -10, 'kg' => 88.5],
                ['day' => -8, 'kg' => 87.7],
                ['day' => -7, 'kg' => 88.5],
                ['day' => -2, 'kg' => 87.6],
                ['day' => -1, 'kg' => 87.4],
            ],
        ],

        'diabetes-management' => [
            'key' => 'diabetes-management',
            'name' => 'Type 2 Diabetes / HbA1c',
            'description' => 'Diabetes follow-up with elevated HbA1c at 8.2%. Adding empagliflozin, adjusting insulin. Dietary counseling.',
            'icon' => 'droplets',
            'color' => 'yellow',
            'specialty' => 'endocrinology',
            'practitioner' => 'endocrinologist',
            'source_dir' => 'demo/visits/visit-09-diabetes-management',
            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'endocrinology_consultation',
                'reason_for_visit' => 'Diabetes follow-up — poor blood sugar control, HbA1c 8.2%',
                'summary' => 'Diabetes follow-up. HbA1c elevated at 8.2%. Added empagliflozin, adjusted insulin dose. Dietary counseling and exercise recommendations. Follow-up in 3 months with labs.',
                'duration_minutes' => 30,
                'days_ago' => 1,
            ],
            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Diabetes follow-up — poor blood sugar control, HbA1c 8.2%',
            ],
            'chat_session' => [
                'topic' => 'Post-visit follow-up: Diabetes management and new medication',
            ],
        ],

        'crohns-flare' => [
            'key' => 'crohns-flare',
            'name' => "Crohn's Disease Flare",
            'description' => 'Active Crohn\'s flare with bloody diarrhea. Elevated CRP, anemia. Prednisone taper started, colonoscopy scheduled.',
            'icon' => 'flame',
            'color' => 'pink',
            'specialty' => 'gastroenterology',
            'practitioner' => 'gastroenterologist',
            'source_dir' => 'demo/visits/visit-10-crohns-flare',
            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'gastroenterology_consultation',
                'reason_for_visit' => 'Abdominal pain and bloody diarrhea — Crohn\'s disease flare',
                'summary' => 'Crohn\'s flare with bloody diarrhea. Labs show elevated inflammatory markers and anemia. Started prednisone taper, continuing Humira. Colonoscopy scheduled in 2 weeks. Iron supplementation added.',
                'duration_minutes' => 35,
                'days_ago' => 1,
            ],
            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Abdominal pain and bloody diarrhea — Crohn\'s disease flare',
            ],
            'chat_session' => [
                'topic' => 'Post-visit follow-up: Crohn\'s disease flare management',
            ],
        ],

        'copd-exacerbation' => [
            'key' => 'copd-exacerbation',
            'name' => 'COPD Exacerbation',
            'description' => 'COPD exacerbation with productive cough. Spirometry shows severe obstruction (FEV1 42%). Antibiotics and pulmonary rehab referral.',
            'icon' => 'wind',
            'color' => 'cyan',
            'specialty' => 'pulmonology',
            'practitioner' => 'pulmonologist',
            'source_dir' => 'demo/visits/visit-11-copd-exacerbation',
            'visit' => [
                'visit_type' => 'office_visit',
                'class' => 'AMB',
                'service_type' => 'pulmonology_consultation',
                'reason_for_visit' => 'Worsening shortness of breath and productive cough — COPD exacerbation',
                'summary' => 'COPD exacerbation with productive cough. Spirometry shows severe obstruction (FEV1 42%). Started Z-pack for bacterial component. Adjusted maintenance inhalers. Pulmonary rehabilitation referral. Follow-up in 2 weeks.',
                'duration_minutes' => 30,
                'days_ago' => 1,
            ],
            'visit_note' => [
                'composition_type' => 'progress_note',
                'chief_complaint' => 'Worsening shortness of breath and productive cough — COPD exacerbation',
            ],
            'chat_session' => [
                'topic' => 'Post-visit follow-up: COPD exacerbation and pulmonary rehab',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Practitioners (per-specialty doctors)
    |--------------------------------------------------------------------------
    |
    | Scenarios can reference a practitioner key via the 'practitioner' field.
    | If no key is set, the default 'doctor' config is used (Dr. Nedo, cardiology).
    |
    */

    'practitioners' => [

        'endocrinologist' => [
            'name' => 'Dr. Patel',
            'email' => 'dr.patel@demo.postvisit.ai',
            'first_name' => 'Anita',
            'last_name' => 'Patel',
            'npi' => '2345678901',
            'license_number' => 'CA-ENDO-2024-001',
            'medical_degree' => 'MD',
            'primary_specialty' => 'endocrinology',
            'secondary_specialties' => ['internal_medicine'],
        ],

        'gastroenterologist' => [
            'name' => 'Dr. Chen',
            'email' => 'dr.chen@demo.postvisit.ai',
            'first_name' => 'Lisa',
            'last_name' => 'Chen',
            'npi' => '3456789012',
            'license_number' => 'CA-GI-2024-001',
            'medical_degree' => 'MD',
            'primary_specialty' => 'gastroenterology',
            'secondary_specialties' => ['internal_medicine'],
        ],

        'pulmonologist' => [
            'name' => 'Dr. Okafor',
            'email' => 'dr.okafor@demo.postvisit.ai',
            'first_name' => 'Chukwuemeka',
            'last_name' => 'Okafor',
            'npi' => '4567890123',
            'license_number' => 'CA-PULM-2024-001',
            'medical_degree' => 'MD, FCCP',
            'primary_specialty' => 'pulmonology',
            'secondary_specialties' => ['internal_medicine', 'critical_care'],
        ],

    ],

];
