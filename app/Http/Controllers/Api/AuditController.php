<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
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

        $logs = $query->paginate($request->input('per_page', 50));

        return response()->json(['data' => $logs]);
    }
}
