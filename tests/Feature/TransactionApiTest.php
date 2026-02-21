<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private function getAuthToken(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_index_returns_paginated_transactions(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        foreach (range(1, 3) as $i) {
            Transaction::create([
                'from_user_id' => $sender->id,
                'to_user_id' => $receiver->id,
                'amount' => 10 * $i,
            ]);
        }
        $token = $this->getAuthToken($sender);

        $response = $this->getJson('/api/transactions', [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'from_user_id', 'to_user_id', 'amount', 'created_at'],
            ],
            'links',
            'meta',
        ]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/transactions', [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }
}
