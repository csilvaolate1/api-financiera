<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    private function getAuthToken(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_index_returns_paginated_users(): void
    {
        User::factory()->count(5)->create();
        $admin = User::factory()->create();
        $token = $this->getAuthToken($admin);

        $response = $this->getJson('/api/users', [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'balance', 'initial_balance'],
            ],
            'links',
            'meta',
        ]);
        $this->assertCount(6, $response->json('data'));
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/users', [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function test_store_creates_user_with_balance(): void
    {
        $admin = User::factory()->create();
        $token = $this->getAuthToken($admin);

        $response = $this->postJson('/api/users', [
            'name' => 'Usuario Nuevo',
            'email' => 'nuevo@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'initial_balance' => 150.50,
        ], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Usuario Nuevo');
        $response->assertJsonPath('data.email', 'nuevo@example.com');
        $response->assertJsonPath('data.balance', 150.5);
        $response->assertJsonPath('data.initial_balance', 150.5);
        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'initial_balance' => 0,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function test_show_returns_user(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['name' => 'Usuario Específico']);
        $token = $this->getAuthToken($admin);

        $response = $this->getJson('/api/users/' . $user->id, [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $user->id);
        $response->assertJsonPath('data.name', 'Usuario Específico');
    }

    public function test_show_requires_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson('/api/users/' . $user->id, [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_modifies_user(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['name' => 'Original']);
        $token = $this->getAuthToken($admin);

        $response = $this->putJson('/api/users/' . $user->id, [
            'name' => 'Nombre Actualizado',
        ], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Nombre Actualizado');
        $user->refresh();
        $this->assertSame('Nombre Actualizado', $user->name);
    }

    public function test_destroy_deletes_user(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $token = $this->getAuthToken($admin);

        $response = $this->deleteJson('/api/users/' . $user->id, [], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->deleteJson('/api/users/' . $user->id, [], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }
}
