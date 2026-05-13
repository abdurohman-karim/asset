<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramBotSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = (string) config('services.telegram.webhook_secret');
        $providedSecret = (string) $request->header('X-Telegram-Bot-Secret', '');

        if ($expectedSecret === '' || $providedSecret === '') {
            return response()->json([
                'status' => 'error',
                'code' => 'unauthorized',
                'message' => 'Unauthorized',
            ], 401);
        }

        if (!hash_equals($expectedSecret, $providedSecret)) {
            return response()->json([
                'status' => 'error',
                'code' => 'forbidden',
                'message' => 'Forbidden',
            ], 403);
        }

        return $next($request);
    }
}
