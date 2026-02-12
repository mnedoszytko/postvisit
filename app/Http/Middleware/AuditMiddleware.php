<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * HIPAA-inspired audit trail middleware.
 *
 * Logs every authenticated access to PHI-sensitive endpoints.
 * Uses terminable pattern — the audit record is written AFTER
 * the response is sent, so it doesn't slow down requests.
 */
class AuditMiddleware
{
    private const METHOD_TO_ACTION = [
        'GET' => 'read',
        'POST' => 'create',
        'PUT' => 'update',
        'PATCH' => 'update',
        'DELETE' => 'delete',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Runs after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        $user = $request->user();

        if (! $user) {
            return;
        }

        [$resourceType, $resourceId] = $this->extractResource($request);

        AuditLog::create([
            'user_id' => $user->id,
            'user_role' => $user->role ?? 'unknown',
            'action_type' => $this->resolveAction($request),
            'resource_type' => $resourceType,
            'resource_id' => $resourceId ?? Str::uuid()->toString(),
            'success' => $response->getStatusCode() < 400,
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'session_id' => $request->hasSession() ? $request->session()->getId() : Str::uuid()->toString(),
            'phi_accessed' => true,
            'phi_elements' => $this->detectPhiElements($resourceType),
            'accessed_at' => now(),
        ]);
    }

    private function resolveAction(Request $request): string
    {
        // Check for explicit download/export actions
        if ($request->query('download') || $request->query('export')) {
            return 'download';
        }

        return self::METHOD_TO_ACTION[$request->method()] ?? 'read';
    }

    /**
     * Extract resource type and ID from route parameters.
     *
     * @return array{0: string, 1: ?string}
     */
    private function extractResource(Request $request): array
    {
        $route = $request->route();

        if (! $route) {
            return ['unknown', null];
        }

        // Map route parameter names to resource types
        $paramMap = [
            'visit' => 'visit',
            'patient' => 'patient',
            'document' => 'document',
            'message' => 'chat_message',
        ];

        // Find the most specific resource (last matching param)
        $resourceType = 'unknown';
        $resourceId = null;

        foreach ($paramMap as $param => $type) {
            if ($route->parameter($param)) {
                $resourceType = $type;
                $resourceId = $route->parameter($param);

                // If it's an Eloquent model, get its ID
                if (is_object($resourceId) && method_exists($resourceId, 'getKey')) {
                    $resourceId = $resourceId->getKey();
                }
            }
        }

        // Infer from route name/URI if no parameter found
        if ($resourceType === 'unknown') {
            $uri = $route->uri();
            if (Str::contains($uri, 'chat')) {
                $resourceType = 'chat_session';
            } elseif (Str::contains($uri, 'transcript')) {
                $resourceType = 'transcript';
            } elseif (Str::contains($uri, 'notes')) {
                $resourceType = 'visit_note';
            } elseif (Str::contains($uri, 'medical-terms')) {
                $resourceType = 'medical_terms';
            } elseif (Str::contains($uri, 'explain')) {
                $resourceType = 'explanation';
            } elseif (Str::contains($uri, 'observations')) {
                $resourceType = 'observation';
            } elseif (Str::contains($uri, 'conditions')) {
                $resourceType = 'condition';
            } elseif (Str::contains($uri, 'medications') || Str::contains($uri, 'prescriptions')) {
                $resourceType = 'medication';
            } elseif (Str::contains($uri, 'health-summary')) {
                $resourceType = 'health_summary';
            } elseif (Str::contains($uri, 'profile')) {
                $resourceType = 'patient_profile';
            } elseif (Str::contains($uri, 'audit')) {
                $resourceType = 'audit_log';
            }

            // Use visit ID as fallback resource_id
            $resourceId = $route->parameter('visit') ?? $route->parameter('patient');
            if (is_object($resourceId) && method_exists($resourceId, 'getKey')) {
                $resourceId = $resourceId->getKey();
            }
        }

        return [$resourceType, $resourceId];
    }

    /**
     * Determine which PHI elements are accessed based on resource type.
     *
     * @return string[]
     */
    private function detectPhiElements(string $resourceType): array
    {
        return match ($resourceType) {
            'visit' => ['visit_data', 'clinical_notes'],
            'patient', 'patient_profile' => ['demographics', 'contact_info'],
            'document' => ['clinical_documents'],
            'chat_session' => ['patient_questions', 'ai_responses'],
            'transcript' => ['visit_recording', 'spoken_content'],
            'visit_note' => ['soap_notes', 'clinical_assessment'],
            'medical_terms' => ['clinical_terminology'],
            'explanation' => ['medical_explanations'],
            'observation' => ['lab_results', 'vitals'],
            'condition' => ['diagnoses'],
            'medication' => ['prescriptions', 'drug_info'],
            'health_summary' => ['aggregate_health_data'],
            default => [],
        };
    }
}
