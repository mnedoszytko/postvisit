<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->withCount('visits');

        if ($search = $request->query('search')) {
            $term = '%'.strtolower($search).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(first_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$term]);
            });
        }

        $patients = $query->orderBy('last_name')->get();

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
}
