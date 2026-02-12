<?php

namespace App\Http\Middleware;

use App\Models\Visit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVisitAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Authentication required.'],
            ], 401);
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        $visit = $request->route('visit');
        if (! $visit instanceof Visit) {
            return $next($request);
        }

        if (method_exists($user, 'isPatient') && $user->isPatient()) {
            if ($user->patient_id !== $visit->patient_id) {
                return response()->json([
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have access to this visit.'],
                ], 403);
            }

            return $next($request);
        }

        if (method_exists($user, 'isDoctor') && $user->isDoctor()) {
            if (! $user->practitioner_id || $visit->practitioner_id !== $user->practitioner_id) {
                return response()->json([
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have access to this visit.'],
                ], 403);
            }

            return $next($request);
        }

        return response()->json([
            'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have access to this resource.'],
        ], 403);
    }
}

