<?php

namespace App\Http\Controllers\Rpc;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Services\GoalService;
use App\Services\SmartSaveService;
use App\Services\BudgetService;
use App\Services\TransactionService;
use App\Services\AIService;
use Illuminate\Http\Request;
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
    ) {
        $jsonRpcVersion = $request->input('jsonrpc');
        $id     = $request->input('id');
        $method = $request->input('method');
        $params = $request->input('params', []);

        // health check
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

        // invalid RPC
        if ($jsonRpcVersion !== '2.0' || !$method) {
            return $this->error(-32600, 'Invalid Request', $id, Response::HTTP_BAD_REQUEST);
        }

        // 🔥 Resolve user (главная часть!)
        $user = $userService->resolveUser($params, $request->user());

        try {
            $result = match ($method) {

                // USER
                'user.register' => $userService->register($params),

                // GOALS
                'goal.create'  => $goalService->create($params, $user),
                'goal.get'     => $goalService->get($params, $user),
                'goal.list'    => $goalService->list($params, $user),
                'goal.deposit' => $goalService->deposit($params, $user),

                // SMART SAVE
                'smart.save.run' => $smartSaveService->run($params, $user),

                // BUDGET
                'budget.getMonth' => $budgetService->getMonth($params, $user),
                'budget.recalculate' => $budgetService->recalculate($params, $user),

                // TRANSACTIONS
                'transaction.import' => $transactionService->import($params, $user),
                'transaction.getDaily' => $transactionService->getDaily($params, $user),

                // AI
                'ai.insight.daily'         => $aiService->daily($params, $user),
                'ai.goal.analysis'         => $aiService->goalAnalysis($params, $user),
                'ai.transaction.analysis'   => $aiService->transactionAnalysis($params, $user),

                // default
                default => $this->error(-32601, 'Method not found', $id)
            };

            return response()->json([
                'jsonrpc' => '2.0',
                'result'  => $result,
                'id'      => $id,
            ]);

        } catch (\Throwable $e) {

            // detailed debug output
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code'    => -32603,
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ],
                'id' => $id,
            ], 500);
        }
    }

    protected function error(int $code, string $message, $id = null, int $httpCode = 200)
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'error' => [
                'code'    => $code,
                'message' => $message,
            ],
            'id' => $id,
        ], $httpCode);
    }
}
