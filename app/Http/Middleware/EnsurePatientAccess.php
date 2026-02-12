<?php

namespace App\Http\Middleware;

use App\Models\Patient;
use App\Models\Visit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePatientAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Routes using this middleware should already be behind auth:sanctum,
        // but keep the check defensive.
        if (! $user) {
            return response()->json([
                'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Authentication required.'],
            ], 401);
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        $patient = $request->route('patient');
        if (! $patient instanceof Patient) {
            return $next($request);
        }

        if (method_exists($user, 'isPatient') && $user->isPatient()) {
            if ($user->patient_id !== $patient->id) {
                return response()->json([
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have access to this patient.'],
                ], 403);
            }

            return $next($request);
        }

        if (method_exists($user, 'isDoctor') && $user->isDoctor()) {
            if (! $user->practitioner_id) {
                return response()->json([
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'No practitioner profile linked.'],
                ], 403);
            }

            $hasRelationship = Visit::query()
                ->where('patient_id', $patient->id)
                ->where('practitioner_id', $user->practitioner_id)
                ->exists();

            if (! $hasRelationship) {
                return response()->json([
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have access to this patient.'],
                ], 403);
            }

            return $next($request);
        }

        return response()->json([
            'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have access to this resource.'],
        ], 403);
    }
}

