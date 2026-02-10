<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        $role = $request->input('role', 'patient');

        $email = $role === 'doctor'
            ? 'doctor@demo.postvisit.ai'
            : 'patient@demo.postvisit.ai';

        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'error' => ['message' => 'Demo data not seeded. Run: php artisan db:seed --class=DemoSeeder'],
            ], 404);
        }

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        $token = $user->createToken('demo-token')->plainTextToken;

        $visit = Visit::where('patient_id', $user->patient_id)
            ->orWhere('created_by', $user->id)
            ->with(['patient:id,first_name,last_name', 'practitioner:id,first_name,last_name'])
            ->latest('started_at')
            ->first();

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
                'visit' => $visit,
                'role' => $role,
            ],
        ]);
    }

    public function status(): JsonResponse
    {
        $hasDemoData = User::where('email', 'patient@demo.postvisit.ai')->exists();

        return response()->json([
            'data' => [
                'seeded' => $hasDemoData,
                'patient_email' => 'patient@demo.postvisit.ai',
                'doctor_email' => 'doctor@demo.postvisit.ai',
                'password' => 'password',
            ],
        ]);
    }

    public function reset(): JsonResponse
    {
        Artisan::call('migrate:fresh');
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoSeeder']);

        return response()->json([
            'data' => ['message' => 'Demo data has been reset successfully.'],
        ]);
    }

    public function simulateAlert(): JsonResponse
    {
        $doctorUser = User::where('email', 'doctor@demo.postvisit.ai')->first();

        if (! $doctorUser) {
            return response()->json(['error' => ['message' => 'Demo data not seeded']], 404);
        }

        $visit = Visit::latest('started_at')->first();

        $doctorUser->notifications()->create([
            'visit_id' => $visit?->id,
            'type' => 'escalation_alert',
            'title' => 'Patient Escalation Alert',
            'body' => 'Patient reported concerning symptoms that may require immediate attention: chest pain and shortness of breath since starting medication.',
            'data' => [
                'severity' => 'high',
                'trigger' => 'simulated',
            ],
        ]);

        return response()->json([
            'data' => ['message' => 'Escalation alert simulated successfully.'],
        ]);
    }
}
