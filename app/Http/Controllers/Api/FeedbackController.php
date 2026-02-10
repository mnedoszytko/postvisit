<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request, Visit $visit): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        // Create a notification for the practitioner
        $practitionerUser = $visit->practitioner?->user ?? null;

        $notification = Notification::create([
            'user_id' => $practitionerUser?->id ?? $request->user()->id,
            'visit_id' => $visit->id,
            'type' => 'patient_feedback',
            'title' => $validated['title'],
            'body' => $validated['body'],
            'data' => [
                'from_user_id' => $request->user()->id,
                'from_name' => $request->user()->name,
            ],
        ]);

        return response()->json(['data' => $notification], 201);
    }

    public function index(Visit $visit): JsonResponse
    {
        $messages = Notification::where('visit_id', $visit->id)
            ->whereIn('type', ['patient_feedback', 'doctor_reply'])
            ->orderBy('created_at')
            ->get();

        return response()->json(['data' => $messages]);
    }

    public function markRead(Notification $message): JsonResponse
    {
        $message->update(['read_at' => now()]);

        return response()->json(['data' => $message]);
    }
}
