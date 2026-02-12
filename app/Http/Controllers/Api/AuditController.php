<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $this->buildQuery($request);
        $logs = $query->paginate($request->input('per_page', 50));

        return response()->json(['data' => $logs]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildQuery($request);
        $logs = $query->limit(5000)->get();

        $filename = 'audit-log-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Timestamp',
                'User',
                'Email',
                'Role',
                'Action',
                'Resource Type',
                'Resource ID',
                'Success',
                'IP Address',
                'PHI Accessed',
                'PHI Elements',
            ]);

            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->accessed_at?->toIso8601String(),
                    $log->user?->name ?? 'Unknown',
                    $log->user?->email ?? '',
                    $log->user_role,
                    $log->action_type,
                    $log->resource_type,
                    $log->resource_id,
                    $log->success ? 'Yes' : 'No',
                    $log->ip_address,
                    $log->phi_accessed ? 'Yes' : 'No',
                    $log->phi_elements ? implode(', ', $log->phi_elements) : '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<AuditLog>
     */
    private function buildQuery(Request $request)
    {
        $query = AuditLog::with('user:id,name,email,role')
            ->orderByDesc('accessed_at');

        if ($request->has('resource_type')) {
            $query->where('resource_type', $request->input('resource_type'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        return $query;
    }
}
