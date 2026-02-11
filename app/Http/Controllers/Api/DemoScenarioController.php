<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Demo\DemoScenarioSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoScenarioController extends Controller
{
    public function __construct(private DemoScenarioSeeder $seeder) {}

    /**
     * List available demo scenarios.
     */
    public function index(): JsonResponse
    {
        $scenarios = collect(config('demo-scenarios.scenarios'))
            ->map(fn (array $s) => [
                'key' => $s['key'],
                'name' => $s['name'],
                'description' => $s['description'],
                'icon' => $s['icon'],
                'color' => $s['color'],
                'patient_name' => $s['patient']['first_name'].' '.$s['patient']['last_name'],
                'patient_age' => now()->diffInYears($s['patient']['dob']),
                'patient_gender' => $s['patient']['gender'],
                'condition' => $s['conditions'][0]['code_display'] ?? null,
            ])
            ->values();

        return response()->json(['data' => $scenarios]);
    }

    /**
     * Create a fresh demo session for the selected scenario.
     */
    public function startScenario(Request $request): JsonResponse
    {
        $request->validate([
            'scenario' => 'required|string',
        ]);

        $scenarioKey = $request->input('scenario');
        $scenarios = config('demo-scenarios.scenarios');

        if (! isset($scenarios[$scenarioKey])) {
            return response()->json([
                'error' => ['message' => 'Unknown scenario: '.$scenarioKey],
            ], 422);
        }

        $user = $this->seeder->seed($scenarios[$scenarioKey]);

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        $token = $user->createToken('demo-token')->plainTextToken;

        $visit = $user->patient->visits()
            ->with(['patient:id,first_name,last_name', 'practitioner:id,first_name,last_name'])
            ->latest('started_at')
            ->first();

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
                'visit' => $visit,
                'scenario' => $scenarioKey,
            ],
        ]);
    }
}
