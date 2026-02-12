<?php

namespace App\Http\Middleware;

use App\Models\Document;
use App\Models\Visit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDocumentAccess
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

        $document = $request->route('document');
        if (! $document instanceof Document) {
            return $next($request);
        }

        if (method_exists($user, 'isPatient') && $user->isPatient()) {
            if ($user->patient_id !== $document->patient_id) {
                return response()->json([
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have access to this document.'],
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

            $hasRelationship = false;

            if ($document->visit_id) {
                $hasRelationship = Visit::query()
                    ->where('id', $document->visit_id)
                    ->where('practitioner_id', $user->practitioner_id)
                    ->exists();
            } else {
                // Fallback: allow if the doctor has any visit with this patient.
                $hasRelationship = Visit::query()
                    ->where('patient_id', $document->patient_id)
                    ->where('practitioner_id', $user->practitioner_id)
                    ->exists();
            }

            if (! $hasRelationship) {
                return response()->json([
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have access to this document.'],
                ], 403);
            }

            return $next($request);
        }

        return response()->json([
            'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have access to this resource.'],
        ], 403);
    }
}

