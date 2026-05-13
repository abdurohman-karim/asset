<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateRpcRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();

        if ($user) {
            $request->setUserResolver(static fn () => $user);
            return $next($request);
        }

        $expectedSecret = (string) config('services.telegram.webhook_secret');
        $providedSecret = (string) $request->header('X-Telegram-Bot-Secret', '');

        if ($expectedSecret === '' || $providedSecret === '') {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32001,
                    'message' => 'Unauthenticated',
                ],
                'id' => $request->input('id'),
            ], 401);
        }

        if (!hash_equals($expectedSecret, $providedSecret)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32003,
                    'message' => 'Forbidden',
                ],
                'id' => $request->input('id'),
            ], 403);
        }

        $request->attributes->set('telegram_secret_verified', true);

        return $next($request);
    }
}
