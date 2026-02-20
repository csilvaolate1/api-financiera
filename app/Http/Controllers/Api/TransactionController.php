<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransactionController extends Controller
{
    private const DAILY_LIMIT_USD = 5000;

    public function store(StoreTransactionRequest $request): JsonResponse|TransactionResource
    {
        $data = $request->validated();
        $fromUser = User::query()->findOrFail($data['from_user_id']);
        $toUser = User::query()->findOrFail($data['to_user_id']);
        $amount = (float) $data['amount'];

        if ($fromUser->balance < $amount) {
            return response()->json([
                'message' => 'Saldo insuficiente. No se puede transferir un monto superior al saldo disponible del emisor.',
                'balance' => (float) $fromUser->balance,
            ], 422);
        }

        $idempotencyKey = $data['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existing = Transaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                return (new TransactionResource($existing))
                    ->response()
                    ->setStatusCode(200);
            }
        }

        $fromId = $fromUser->id;
        $toId = $toUser->id;
        $todayStart = Carbon::today();

        $transaction = DB::transaction(function () use ($fromId, $toId, $amount, $idempotencyKey, $todayStart) {
            $from = User::query()->where('id', $fromId)->lockForUpdate()->firstOrFail();
            $to = User::query()->where('id', $toId)->lockForUpdate()->firstOrFail();

            if ($from->balance < $amount) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Saldo insuficiente. No se puede transferir un monto superior al saldo disponible del emisor.',
                    'balance' => (float) $from->balance,
                ], 422));
            }

            $todayTotal = (float) Transaction::query()
                ->where('from_user_id', $fromId)
                ->where('created_at', '>=', $todayStart)
                ->sum('amount');
            if ($todayTotal + $amount > self::DAILY_LIMIT_USD) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Límite diario de transferencia excedido. El límite es de ' . self::DAILY_LIMIT_USD . ' USD por día.',
                    'daily_limit' => self::DAILY_LIMIT_USD,
                    'used_today' => $todayTotal,
                ], 422));
            }

            $from->decrement('balance', $amount);
            $to->increment('balance', $amount);
            return Transaction::query()->create([
                'from_user_id' => $fromId,
                'to_user_id' => $toId,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $transactions = Transaction::query()
            ->with(['fromUser:id,name,email', 'toUser:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));
        return TransactionResource::collection($transactions);
    }

    /**
     * Exportar transacciones en CSV (delimitador: punto y coma).
     */
    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="transacciones_' . date('Y-m-d_His') . '.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($handle, ['id', 'from_user_id', 'to_user_id', 'amount', 'created_at'], ';');
            Transaction::query()
                ->orderBy('id')
                ->cursor()
                ->each(function (Transaction $t) use ($handle) {
                    fputcsv($handle, [
                        $t->id,
                        $t->from_user_id,
                        $t->to_user_id,
                        $t->amount,
                        $t->created_at?->toIso8601String(),
                    ], ';');
                });
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Total transferido por cada usuario emisor.
     */
    public function statsTotalBySender(): JsonResponse
    {
        $totals = Transaction::query()
            ->select('from_user_id')
            ->selectRaw('SUM(amount) as total_transferred')
            ->groupBy('from_user_id')
            ->with('fromUser:id,name,email')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->from_user_id,
                'user' => $row->fromUser ? [
                    'name' => $row->fromUser->name,
                    'email' => $row->fromUser->email,
                ] : null,
                'total_transferred' => (float) $row->total_transferred,
            ]);
        return response()->json(['data' => $totals]);
    }

    /**
     * Promedio de monto por usuario (emisor).
     */
    public function statsAverageByUser(): JsonResponse
    {
        $averages = Transaction::query()
            ->select('from_user_id')
            ->selectRaw('AVG(amount) as average_amount')
            ->selectRaw('COUNT(*) as transaction_count')
            ->groupBy('from_user_id')
            ->with('fromUser:id,name,email')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->from_user_id,
                'user' => $row->fromUser ? [
                    'name' => $row->fromUser->name,
                    'email' => $row->fromUser->email,
                ] : null,
                'average_amount' => (float) $row->average_amount,
                'transaction_count' => (int) $row->transaction_count,
            ]);
        return response()->json(['data' => $averages]);
    }
}
