<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:patient,doctor'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user = $request->user();
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        AuditLog::create([
            'user_id' => $user->id,
            'user_role' => $user->role ?? 'unknown',
            'action_type' => 'login',
            'resource_type' => 'auth',
            'resource_id' => $user->id,
            'success' => true,
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'session_id' => $request->hasSession() ? $request->session()->getId() : Str::uuid()->toString(),
            'phi_accessed' => false,
            'accessed_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        AuditLog::create([
            'user_id' => $user->id,
            'user_role' => $user->role ?? 'unknown',
            'action_type' => 'logout',
            'resource_type' => 'auth',
            'resource_id' => $user->id,
            'success' => true,
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'session_id' => $request->hasSession() ? $request->session()->getId() : Str::uuid()->toString(),
            'phi_accessed' => false,
            'accessed_at' => now(),
        ]);

        $token = $user->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'data' => ['message' => 'Logged out successfully.'],
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load(['patient', 'practitioner']);

        $data = $user->toArray();
        $data['photo_url'] = $this->resolvePhotoUrl($user);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Resolve photo URL for demo scenario users.
     */
    private function resolvePhotoUrl(User $user): ?string
    {
        $key = $user->demo_scenario_key;
        if (! $key) {
            return null;
        }

        $scenario = config("demo-scenarios.scenarios.{$key}");
        if (! $scenario) {
            return null;
        }

        $dir = $scenario['source_dir'] ?? $scenario['photo_dir'] ?? null;
        if (! $dir) {
            return null;
        }

        if (! file_exists(base_path($dir.'/patient-photo.png'))) {
            return null;
        }

        return "/api/v1/demo/scenarios/{$key}/photo";
    }
}
