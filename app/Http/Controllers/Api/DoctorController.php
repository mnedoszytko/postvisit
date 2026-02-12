<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\Notification;
use App\Models\Observation;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DoctorController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $practitioner = $request->user()->practitioner;

        if (! $practitioner) {
            return response()->json(['error' => ['message' => 'No practitioner profile linked']], 403);
        }

        $recentVisits = Visit::where('practitioner_id', $practitioner->id)
            ->with(['patient:id,first_name,last_name'])
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();

        $totalPatients = Visit::where('practitioner_id', $practitioner->id)
            ->distinct('patient_id')
            ->count('patient_id');

        $unreadNotifications = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => [
                'recent_visits' => $recentVisits,
                'stats' => [
                    'total_patients' => $totalPatients,
                    'total_visits' => Visit::where('practitioner_id', $practitioner->id)->count(),
                    'unread_notifications' => $unreadNotifications,
                ],
            ],
        ]);
    }

    public function alerts(Request $request): JsonResponse
    {
        $practitioner = $request->user()->practitioner;

        if (! $practitioner) {
            return response()->json(['error' => ['message' => 'No practitioner profile linked']], 403);
        }

        $patientIds = Visit::where('practitioner_id', $practitioner->id)
            ->distinct('patient_id')
            ->pluck('patient_id');

        $alerts = [];

        // Weight gain alerts: >= 2kg in 3 days (code 29463-7)
        $threeDaysAgo = Carbon::now()->subDays(3)->startOfDay();
        $weightObservations = Observation::whereIn('patient_id', $patientIds)
            ->where('code', '29463-7')
            ->where('effective_date', '>=', $threeDaysAgo)
            ->with('patient:id,first_name,last_name')
            ->orderBy('effective_date')
            ->get()
            ->groupBy('patient_id');

        foreach ($weightObservations as $patientId => $observations) {
            if ($observations->count() < 2) {
                continue;
            }

            $earliest = $observations->first();
            $latest = $observations->last();
            $delta = (float) $latest->value_quantity - (float) $earliest->value_quantity;

            if ($delta >= 2.0) {
                $patient = $earliest->patient;
                $alerts[] = [
                    'type' => 'weight_gain',
                    'severity' => 'high',
                    'patient_id' => $patientId,
                    'patient_name' => $patient->first_name.' '.$patient->last_name,
                    'message' => sprintf(
                        'Weight gain of %.1f kg in %d days (%.1f → %.1f kg)',
                        $delta,
                        $earliest->effective_date->diffInDays($latest->effective_date),
                        $earliest->value_quantity,
                        $latest->value_quantity,
                    ),
                    'data' => [
                        'delta_kg' => round($delta, 1),
                        'from' => (float) $earliest->value_quantity,
                        'to' => (float) $latest->value_quantity,
                        'period_days' => $earliest->effective_date->diffInDays($latest->effective_date),
                    ],
                ];
            }
        }

        // BP trend alerts: 3+ consecutive elevated readings (code 85354-9, systolic >= 140 or diastolic >= 90)
        $bpObservations = Observation::whereIn('patient_id', $patientIds)
            ->where('code', '85354-9')
            ->with('patient:id,first_name,last_name')
            ->orderBy('effective_date')
            ->get()
            ->groupBy('patient_id');

        foreach ($bpObservations as $patientId => $observations) {
            $consecutiveElevated = 0;
            $elevatedReadings = [];

            foreach ($observations as $obs) {
                $systolic = null;
                $diastolic = null;

                if (is_array($obs->specialty_data)) {
                    $sysData = $obs->specialty_data['systolic'] ?? null;
                    $diaData = $obs->specialty_data['diastolic'] ?? null;
                    $systolic = is_array($sysData) ? ($sysData['value'] ?? null) : $sysData;
                    $diastolic = is_array($diaData) ? ($diaData['value'] ?? null) : $diaData;
                }

                // Fallback: if no specialty_data components, use value_quantity as systolic
                if ($systolic === null && $obs->value_quantity !== null) {
                    $systolic = (float) $obs->value_quantity;
                }

                $isElevated = ($systolic !== null && $systolic >= 140) || ($diastolic !== null && $diastolic >= 90);

                if ($isElevated) {
                    $consecutiveElevated++;
                    $elevatedReadings[] = [
                        'date' => $obs->effective_date->toDateString(),
                        'systolic' => $systolic,
                        'diastolic' => $diastolic,
                    ];
                } else {
                    $consecutiveElevated = 0;
                    $elevatedReadings = [];
                }
            }

            if ($consecutiveElevated >= 3) {
                $patient = $observations->first()->patient;
                $alerts[] = [
                    'type' => 'elevated_bp',
                    'severity' => 'medium',
                    'patient_id' => $patientId,
                    'patient_name' => $patient->first_name.' '.$patient->last_name,
                    'message' => sprintf(
                        '%d consecutive elevated BP readings',
                        $consecutiveElevated,
                    ),
                    'data' => [
                        'consecutive_count' => $consecutiveElevated,
                        'readings' => array_slice($elevatedReadings, -3),
                    ],
                ];
            }
        }

        // Sort alerts: high severity first
        usort($alerts, fn ($a, $b) => $a['severity'] === 'high' ? -1 : 1);

        return response()->json(['data' => $alerts]);
    }

    public function patients(Request $request): JsonResponse
    {
        $practitioner = $request->user()->practitioner;

        if (! $practitioner) {
            return response()->json(['error' => ['message' => 'No practitioner profile linked']], 403);
        }

        $patientIds = Visit::where('practitioner_id', $practitioner->id)
            ->distinct('patient_id')
            ->pluck('patient_id');

        $query = Patient::whereIn('id', $patientIds)
            ->withCount('visits')
            ->with([
                'conditions' => fn ($q) => $q->where('clinical_status', 'active')
                    ->orderByDesc('onset_date')
                    ->limit(1),
                'visits' => fn ($q) => $q->where('practitioner_id', $practitioner->id)
                    ->orderByDesc('started_at')
                    ->limit(1)
                    ->select('id', 'patient_id', 'started_at', 'visit_status'),
            ]);

        if ($search = $request->query('search')) {
            $term = '%'.strtolower($search).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(first_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$term]);
            });
        }

        $patients = $query->orderBy('last_name')->get();

        // Deduplicate by first_name + last_name — keep the one with the most recent visit
        $patients = $patients->groupBy(fn ($p) => strtolower($p->first_name.'|'.$p->last_name))
            ->map(fn ($group) => $group->sortByDesc(fn ($p) => $p->visits->first()?->started_at)->first())
            ->values();

        // Get alert statuses (tri-state: alert, review, stable)
        $alertStatuses = $this->getPatientAlertStatuses($practitioner);

        // Fetch latest vitals (BP and weight) for all patients in bulk
        $latestVitals = $this->getLatestVitals($patientIds);

        // Append computed fields for the dashboard
        $patients->each(function ($patient) use ($alertStatuses, $latestVitals) {
            $patient->primary_condition = $patient->conditions->first()?->code_display;
            $patient->last_visit_date = $patient->visits->first()?->started_at?->toDateString();
            $patient->last_visit_status = $patient->visits->first()?->visit_status;
            $patient->alert_status = $alertStatuses[$patient->id] ?? 'stable';
            // Keep legacy 'status' field for backward compatibility
            $patient->status = $patient->alert_status === 'stable' ? 'stable' : 'alert';
            $patient->age = $patient->dob ? $patient->dob->age : null;
            $patient->last_vitals = $latestVitals[$patient->id] ?? null;
            unset($patient->conditions, $patient->visits);
        });

        return response()->json(['data' => $patients]);
    }

    public function patientDetail(Patient $patient): JsonResponse
    {
        $patient->load([
            'conditions' => fn ($q) => $q->where('clinical_status', 'active'),
            'prescriptions' => fn ($q) => $q->where('status', 'active')->with('medication'),
        ]);

        return response()->json(['data' => $patient]);
    }

    public function patientVisits(Patient $patient): JsonResponse
    {
        $visits = $patient->visits()
            ->with(['practitioner:id,first_name,last_name', 'organization:id,name'])
            ->orderByDesc('started_at')
            ->get();

        return response()->json(['data' => $visits]);
    }

    public function engagement(Patient $patient): JsonResponse
    {
        $chatSessions = ChatSession::where('patient_id', $patient->id)
            ->withCount('messages')
            ->orderByDesc('initiated_at')
            ->get();

        return response()->json([
            'data' => [
                'chat_sessions' => $chatSessions,
                'total_messages' => $chatSessions->sum('messages_count'),
                'total_sessions' => $chatSessions->count(),
            ],
        ]);
    }

    public function chatAudit(Patient $patient): JsonResponse
    {
        $sessions = ChatSession::where('patient_id', $patient->id)
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->orderByDesc('initiated_at')
            ->get();

        return response()->json(['data' => $sessions]);
    }

    public function patientObservations(Request $request, Patient $patient): JsonResponse
    {
        $query = $patient->observations();

        if ($request->has('code')) {
            $query->where('code', $request->input('code'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        $observations = $query->orderBy('effective_date', 'desc')->get();

        return response()->json(['data' => $observations]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->with('visit:id,started_at,reason_for_visit')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json(['data' => $notifications]);
    }

    public function reply(Request $request, Notification $message): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        // Create a reply notification for the patient
        $reply = Notification::create([
            'user_id' => $message->user_id,
            'visit_id' => $message->visit_id,
            'type' => 'doctor_reply',
            'title' => 'Reply from Dr. '.$request->user()->name,
            'body' => $validated['body'],
            'data' => ['original_notification_id' => $message->id],
        ]);

        return response()->json(['data' => $reply], 201);
    }

    /**
     * Get patient IDs that have active alerts (weight gain or elevated BP).
     *
     * @return array<string>
     */
    private function getAlertPatientIds(\App\Models\Practitioner $practitioner): array
    {
        $patientIds = Visit::where('practitioner_id', $practitioner->id)
            ->distinct('patient_id')
            ->pluck('patient_id');

        $alertIds = [];

        // Weight alerts
        $threeDaysAgo = Carbon::now()->subDays(3)->startOfDay();
        $weightObs = Observation::whereIn('patient_id', $patientIds)
            ->where('code', '29463-7')
            ->where('effective_date', '>=', $threeDaysAgo)
            ->orderBy('effective_date')
            ->get()
            ->groupBy('patient_id');

        foreach ($weightObs as $pid => $obs) {
            if ($obs->count() >= 2) {
                $delta = (float) $obs->last()->value_quantity - (float) $obs->first()->value_quantity;
                if ($delta >= 2.0) {
                    $alertIds[] = $pid;
                }
            }
        }

        // BP alerts
        $bpObs = Observation::whereIn('patient_id', $patientIds)
            ->where('code', '85354-9')
            ->orderBy('effective_date')
            ->get()
            ->groupBy('patient_id');

        foreach ($bpObs as $pid => $obs) {
            $consecutive = 0;
            foreach ($obs as $o) {
                $systolic = $o->specialty_data['systolic'] ?? ((float) $o->value_quantity ?: null);
                $diastolic = $o->specialty_data['diastolic'] ?? null;
                $elevated = ($systolic !== null && $systolic >= 140) || ($diastolic !== null && $diastolic >= 90);
                $consecutive = $elevated ? $consecutive + 1 : 0;
            }
            if ($consecutive >= 3) {
                $alertIds[] = $pid;
            }
        }

        return array_unique($alertIds);
    }

    /**
     * Get alert status per patient: "alert" (weight gain), "review" (elevated BP), or "stable".
     *
     * @return array<string, string> patient_id => status
     */
    private function getPatientAlertStatuses(\App\Models\Practitioner $practitioner): array
    {
        $patientIds = Visit::where('practitioner_id', $practitioner->id)
            ->distinct('patient_id')
            ->pluck('patient_id');

        $statuses = [];

        // Weight alerts => "alert" (highest severity)
        $threeDaysAgo = Carbon::now()->subDays(3)->startOfDay();
        $weightObs = Observation::whereIn('patient_id', $patientIds)
            ->where('code', '29463-7')
            ->where('effective_date', '>=', $threeDaysAgo)
            ->orderBy('effective_date')
            ->get()
            ->groupBy('patient_id');

        foreach ($weightObs as $pid => $obs) {
            if ($obs->count() >= 2) {
                $delta = (float) $obs->last()->value_quantity - (float) $obs->first()->value_quantity;
                if ($delta >= 2.0) {
                    $statuses[$pid] = 'alert';
                }
            }
        }

        // BP alerts => "review" (medium severity, don't overwrite "alert")
        $bpObs = Observation::whereIn('patient_id', $patientIds)
            ->where('code', '85354-9')
            ->orderBy('effective_date')
            ->get()
            ->groupBy('patient_id');

        foreach ($bpObs as $pid => $obs) {
            $consecutive = 0;
            foreach ($obs as $o) {
                $systolic = $o->specialty_data['systolic'] ?? ((float) $o->value_quantity ?: null);
                $diastolic = $o->specialty_data['diastolic'] ?? null;
                $elevated = ($systolic !== null && $systolic >= 140) || ($diastolic !== null && $diastolic >= 90);
                $consecutive = $elevated ? $consecutive + 1 : 0;
            }
            if ($consecutive >= 3 && ! isset($statuses[$pid])) {
                $statuses[$pid] = 'review';
            }
        }

        return $statuses;
    }

    /**
     * Get the latest BP and weight observations per patient.
     *
     * @param  \Illuminate\Support\Collection  $patientIds
     * @return array<string, array> patient_id => ['bp' => '...', 'weight' => '...']
     */
    private function getLatestVitals($patientIds): array
    {
        $vitals = [];

        // Latest weight per patient (code 29463-7)
        $latestWeights = Observation::whereIn('patient_id', $patientIds)
            ->where('code', '29463-7')
            ->orderByDesc('effective_date')
            ->get()
            ->unique('patient_id');

        foreach ($latestWeights as $obs) {
            $vitals[$obs->patient_id]['weight'] = $obs->value_quantity.' '.($obs->value_unit ?? 'kg');
        }

        // Latest BP per patient (code 85354-9)
        $latestBp = Observation::whereIn('patient_id', $patientIds)
            ->where('code', '85354-9')
            ->orderByDesc('effective_date')
            ->get()
            ->unique('patient_id');

        foreach ($latestBp as $obs) {
            $systolic = $obs->specialty_data['systolic'] ?? null;
            $diastolic = $obs->specialty_data['diastolic'] ?? null;

            if (is_array($systolic)) {
                $systolic = $systolic['value'] ?? null;
            }
            if (is_array($diastolic)) {
                $diastolic = $diastolic['value'] ?? null;
            }

            if ($systolic !== null && $diastolic !== null) {
                $vitals[$obs->patient_id]['bp'] = (int) $systolic.'/'.(int) $diastolic.' mmHg';
            } elseif ($obs->value_quantity !== null) {
                $vitals[$obs->patient_id]['bp'] = $obs->value_quantity.' mmHg';
            }
        }

        return $vitals;
    }
}
