<?php

namespace App\Http\Middleware;

use App\Models\Notification;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotificationAccess
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

        $message = $request->route('message');
        if (! $message instanceof Notification) {
            return $next($request);
        }

        if ($message->user_id !== $user->id) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'You do not have access to this message.'],
            ], 403);
        }

        return $next($request);
    }
}

