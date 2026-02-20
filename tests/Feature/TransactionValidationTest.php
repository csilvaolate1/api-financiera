<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithBalance(float $balance): User
    {
        return User::factory()->create([
            'initial_balance' => $balance,
            'balance' => $balance,
        ]);
    }

    private function getAuthToken(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_rejects_transfer_above_sender_balance(): void
    {
        $sender = $this->createUserWithBalance(100);
        $receiver = $this->createUserWithBalance(50);
        $token = $this->getAuthToken($sender);

        $response = $this->postJson('/api/transactions', [
            'from_user_id' => $sender->id,
            'to_user_id' => $receiver->id,
            'amount' => 150,
        ], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Saldo insuficiente. No se puede transferir un monto superior al saldo disponible del emisor.',
        ]);
        $response->assertJsonPath('balance', 100);
    }

    public function test_rejects_same_user_as_sender_and_receiver(): void
    {
        $user = $this->createUserWithBalance(100);
        $token = $this->getAuthToken($user);

        $response = $this->postJson('/api/transactions', [
            'from_user_id' => $user->id,
            'to_user_id' => $user->id,
            'amount' => 10,
        ], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors['to_user_id'] ?? []);
    }

    public function test_idempotency_returns_existing_transaction(): void
    {
        $sender = $this->createUserWithBalance(500);
        $receiver = $this->createUserWithBalance(0);
        $token = $this->getAuthToken($sender);
        $key = 'idem-' . uniqid();

        $r1 = $this->postJson('/api/transactions', [
            'from_user_id' => $sender->id,
            'to_user_id' => $receiver->id,
            'amount' => 50,
            'idempotency_key' => $key,
        ], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);
        $r1->assertStatus(201);
        $id = $r1->json('data.id');

        $r2 = $this->postJson('/api/transactions', [
            'from_user_id' => $sender->id,
            'to_user_id' => $receiver->id,
            'amount' => 50,
            'idempotency_key' => $key,
        ], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);
        $r2->assertStatus(200);
        $r2->assertJsonPath('data.id', $id);
        $this->assertSame(1, Transaction::count());
    }

    public function test_rejects_when_daily_limit_exceeded(): void
    {
        $sender = $this->createUserWithBalance(10000);
        $receiver = $this->createUserWithBalance(0);
        $token = $this->getAuthToken($sender);
        // Límite diario 5000 USD. Hacer una de 4000 y otra de 1500 = 5500, la segunda debe fallar.
        Transaction::create([
            'from_user_id' => $sender->id,
            'to_user_id' => $receiver->id,
            'amount' => 4000,
        ]);
        $sender->decrement('balance', 4000);
        $receiver->increment('balance', 4000);

        $response = $this->postJson('/api/transactions', [
            'from_user_id' => $sender->id,
            'to_user_id' => $receiver->id,
            'amount' => 1500,
        ], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Límite diario de transferencia excedido. El límite es de 5000 USD por día.',
        ]);
        $response->assertJsonPath('daily_limit', 5000);
    }

    /**
     * Varias transferencias que en conjunto superan 5000 USD: el total persistido
     * del día para el emisor nunca debe exceder 5000 (regresión de race condition).
     */
    public function test_daily_total_never_exceeds_limit_with_multiple_attempts(): void
    {
        $sender = $this->createUserWithBalance(20000);
        $receiver = $this->createUserWithBalance(0);
        $token = $this->getAuthToken($sender);

        // Intentar 4 transferencias de 2000 = 8000 USD; solo deben persistir hasta 5000.
        $amountPerTransfer = 2000;
        $attempts = 4;
        $successCount = 0;
        $limitExceededCount = 0;

        for ($i = 0; $i < $attempts; $i++) {
            $response = $this->postJson('/api/transactions', [
                'from_user_id' => $sender->id,
                'to_user_id' => $receiver->id,
                'amount' => $amountPerTransfer,
            ], [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ]);

            if ($response->status() === 201) {
                $successCount++;
            } elseif ($response->status() === 422) {
                $body = $response->json();
                if (isset($body['daily_limit']) && (int) $body['daily_limit'] === 5000) {
                    $limitExceededCount++;
                }
            }
        }

        $todayStart = Carbon::today();
        $totalStoredToday = (float) Transaction::query()
            ->where('from_user_id', $sender->id)
            ->where('created_at', '>=', $todayStart)
            ->sum('amount');

        $this->assertLessThanOrEqual(5000, $totalStoredToday,
            'El total transferido hoy no debe superar 5000 USD (evitar race condition).');
        $this->assertGreaterThanOrEqual(1, $limitExceededCount,
            'Al menos una petición debe ser rechazada por límite diario.');
        $this->assertLessThanOrEqual(2, $successCount,
            'Como máximo 2 transferencias de 2000 + 1 de 1000 caben en 5000; con 2000 solo 2.');
    }
}
