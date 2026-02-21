<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Usuario Registrado',
            'email' => 'registro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'initial_balance' => 50,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Usuario Registrado');
        $response->assertJsonPath('data.email', 'registro@example.com');
        $response->assertJsonPath('data.balance', 50);
        $this->assertDatabaseHas('users', ['email' => 'registro@example.com']);
    }

    public function test_login_returns_token_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
            'type',
        ]);
        $response->assertJsonPath('type', 'Bearer');
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'Las credenciales proporcionadas no son correctas.',
        ]);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Yo']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/me', [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $user->id);
        $response->assertJsonPath('data.name', 'Yo');
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/me', [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function test_logout_succeeds_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Sesión cerrada correctamente.']);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/logout', [], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }
}
