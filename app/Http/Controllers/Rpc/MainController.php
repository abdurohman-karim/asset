<?php

namespace App\Http\Controllers\Rpc;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Services\GoalService;
use App\Services\SmartSaveService;
use App\Services\BudgetService;
use App\Services\TransactionService;
use App\Services\AIService;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MainController extends Controller
{
    public function index(
        Request $request,
        UserService $userService,
        GoalService $goalService,
        SmartSaveService $smartSaveService,
        BudgetService $budgetService,
        TransactionService $transactionService,
        AIService $aiService,
        CurrencyService $currencyService,
    ) {
        $jsonRpcVersion = $request->input('jsonrpc');
        $id = $request->input('id');
        $method = $request->input('method');
        $params = $request->input('params', []);

        if (!$jsonRpcVersion && !$method) {
            return response()->json([
                'jsonrpc' => '2.0',
                'result' => [
                    'status' => 'ok',
                    'service' => 'AI Dream Saver API',
                ],
                'id' => null,
            ]);
        }

        if ($jsonRpcVersion !== '2.0' || !$method) {
            return $this->error(-32600, 'Invalid Request', $id, Response::HTTP_BAD_REQUEST);
        }

        $user = $request->user();
        $telegramRequest = (bool) $request->attributes->get('telegram_secret_verified');

        if (!$user && $telegramRequest) {
            $user = $userService->resolveTelegramUser($params);
        }

        if (!$user && !($telegramRequest && $method === 'user.register')) {
            return $this->error(-32001, 'Unauthenticated', $id, Response::HTTP_UNAUTHORIZED);
        }

        try {
            $result = match ($method) {
                'user.register' => $telegramRequest
                    ? $userService->register($params)
                    : $this->error(-32001, 'Unauthenticated', $id, Response::HTTP_UNAUTHORIZED),

                'goal.create' => $goalService->create($params, $user),
                'goal.get' => $goalService->get($params, $user),
                'goal.list' => $goalService->list($params, $user),
                'goal.deposit' => $goalService->deposit($params, $user),
                'goal.setPrimary' => $goalService->setPrimary($params, $user),
                'goal.priority.up' => $goalService->priorityUp($params, $user),
                'goal.priority.down' => $goalService->priorityDown($params, $user),
                'goal.close' => $goalService->close($params, $user),
                'goal.reopen' => $goalService->reopen($params, $user),

                'smart.save.run' => $smartSaveService->run($params, $user),

                'budget.getMonth' => $budgetService->getMonth($params, $user),
                'budget.recalculate' => $budgetService->recalculate($params, $user),

                'transaction.import' => $transactionService->import($params, $user),
                'transaction.getDaily' => $transactionService->getDaily($params, $user),

                'currency.list' => [
                    'success' => true,
                    'data' => array_map(
                        fn (array $currency) => $currencyService->serialize($currency),
                        $currencyService->listActive()
                    ),
                ],
                'currency.get' => [
                    'success' => true,
                    'data' => $currencyService->serialize($currencyService->preferredCurrency($user)),
                ],
                'currency.set' => [
                    'success' => true,
                    'message' => 'Currency updated successfully',
                    'data' => $currencyService->serialize(
                        $currencyService->setPreferredCurrency($user, $currencyService->resolveSelection($params, $user))
                    ),
                ],

                'ai.insight.daily' => $aiService->daily($params, $user),
                'ai.goal.analysis' => $aiService->goalAnalysis($params, $user),
                'ai.transaction.analysis' => $aiService->transactionAnalysis($params, $user),
                'ai.weekly_review' => $aiService->weeklyReview($params, $user),
                'ai.risk_detection' => $aiService->riskDetection($params, $user),
                'ai.savings_projection' => $aiService->savingsProjection($params, $user),
                'ai.predictive_balance' => $aiService->predictiveBalance($params, $user),
                'ai.behavioral_profile' => $aiService->behavioralProfile($params, $user),

                default => $this->error(-32601, 'Method not found', $id)
            };

            if ($result instanceof JsonResponse) {
                return $result;
            }

            if (str_starts_with($method, 'ai.')) {
                Log::info('AI RPC response', [
                    'method' => $method,
                    'user_id' => $user?->id,
                    'result' => $result,
                ]);
            }

            return response()->json([
                'jsonrpc' => '2.0',
                'result' => $result,
                'id' => $id,
            ]);

        } catch (\Throwable $e) {
            $error = [
                'code' => -32603,
                'message' => $e->getMessage(),
            ];

            if (config('app.debug')) {
                $error['file'] = $e->getFile();
                $error['line'] = $e->getLine();
            }

            return response()->json([
                'jsonrpc' => '2.0',
                'error' => $error,
                'id' => $id,
            ], 500);
        }
    }

    protected function error(int $code, string $message, $id = null, int $httpCode = 200)
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
            'id' => $id,
        ], $httpCode);
    }
}
