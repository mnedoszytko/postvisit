<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Demo\DemoScenarioSeeder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DemoScenarioController extends Controller
{
    public function __construct(private DemoScenarioSeeder $seeder) {}

    /**
     * List available demo scenarios.
     */
    public function index(): JsonResponse
    {
        $scenarios = collect(config('demo-scenarios.scenarios'))
            ->map(function (array $s) {
                // For source_dir scenarios, read patient data from JSON
                if (isset($s['source_dir'])) {
                    $jsonPath = base_path($s['source_dir'].'/patient-profile.json');
                    if (file_exists($jsonPath)) {
                        $json = json_decode(file_get_contents($jsonPath), true);
                        $demographics = $json['demographics'];
                        $hasPhoto = file_exists(base_path($s['source_dir'].'/patient-photo.png'));

                        return [
                            'key' => $s['key'],
                            'name' => $s['name'],
                            'description' => $s['description'],
                            'icon' => $s['icon'],
                            'color' => $s['color'],
                            'patient_name' => $demographics['first_name'].' '.$demographics['last_name'],
                            'patient_age' => Carbon::parse($demographics['dob'])->age,
                            'patient_gender' => $demographics['gender'],
                            'condition' => $json['conditions'][0]['name'] ?? null,
                            'language' => $demographics['preferred_language'],
                            'bmi' => $demographics['bmi'] ?? null,
                            'photo_url' => $hasPhoto ? "/api/v1/demo/scenarios/{$s['key']}/photo" : null,
                            'has_audio' => file_exists(base_path($s['source_dir'].'/dialogue-tts.mp3')),
                        ];
                    }
                }

                // Existing inline format
                $photoDir = $s['photo_dir'] ?? null;
                $hasPhoto = $photoDir && file_exists(base_path($photoDir.'/patient-photo.png'));

                return [
                    'key' => $s['key'],
                    'name' => $s['name'],
                    'description' => $s['description'],
                    'icon' => $s['icon'],
                    'color' => $s['color'],
                    'patient_name' => $s['patient']['first_name'].' '.$s['patient']['last_name'],
                    'patient_age' => Carbon::parse($s['patient']['dob'])->age,
                    'patient_gender' => $s['patient']['gender'],
                    'condition' => $s['conditions'][0]['code_display'] ?? null,
                    'language' => $s['patient']['preferred_language'] ?? 'en',
                    'bmi' => $s['patient']['bmi'] ?? null,
                    'photo_url' => $hasPhoto ? "/api/v1/demo/scenarios/{$s['key']}/photo" : null,
                    'has_audio' => false,
                ];
            })
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

    /**
     * Switch current demo session to the shared doctor account.
     */
    public function switchToDoctor(Request $request): JsonResponse
    {
        $doctorEmail = config('demo-scenarios.doctor.email');
        $doctor = \App\Models\User::where('email', $doctorEmail)->first();

        if (! $doctor) {
            return response()->json([
                'error' => ['message' => 'Demo doctor not found. Run a scenario first.'],
            ], 422);
        }

        Auth::login($doctor);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        $token = $doctor->createToken('demo-doctor-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $doctor,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Serve patient photo for a scenario.
     */
    public function photo(string $scenario): BinaryFileResponse
    {
        $scenarios = config('demo-scenarios.scenarios');

        if (! isset($scenarios[$scenario])) {
            abort(404);
        }

        $s = $scenarios[$scenario];
        $dir = $s['source_dir'] ?? $s['photo_dir'] ?? null;

        if (! $dir) {
            abort(404);
        }

        $path = base_path($dir.'/patient-photo.png');

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
