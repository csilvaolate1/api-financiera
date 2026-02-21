<?php

namespace Tests\Unit;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateUserRequestTest extends TestCase
{
    use RefreshDatabase;

    private function createRequest(User $user, array $data = []): UpdateUserRequest
    {
        $request = UpdateUserRequest::create('/api/users/' . $user->id, 'PUT', $data);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setRouteResolver(function () use ($user) {
            $route = $this->createMock(Route::class);
            $route->method('parameter')->with('user')->willReturn($user);

            return $route;
        });

        return $request;
    }

    public function test_validates_initial_balance_min_when_provided(): void
    {
        $user = User::factory()->create();
        $request = $this->createRequest($user);
        $rules = $request->rules();

        $validator = Validator::make(['initial_balance' => -50], $rules, $request->messages());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('initial_balance', $validator->errors()->toArray());
    }

    public function test_allows_own_email_on_update(): void
    {
        $user = User::factory()->create(['email' => 'own@example.com']);
        $request = $this->createRequest($user, ['email' => 'own@example.com']);
        $rules = $request->rules();

        $validator = Validator::make(
            ['email' => 'own@example.com'],
            ['email' => $rules['email']],
            $request->messages()
        );
        $this->assertFalse($validator->fails());
    }

    public function test_rejects_other_users_email(): void
    {
        $user1 = User::factory()->create(['email' => 'a@example.com']);
        User::factory()->create(['email' => 'b@example.com']);
        $request = $this->createRequest($user1);
        $rules = $request->rules();

        $validator = Validator::make(
            ['email' => 'b@example.com'],
            ['email' => $rules['email']],
            $request->messages()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_custom_message_for_initial_balance_min(): void
    {
        $user = User::factory()->create();
        $request = $this->createRequest($user);
        $messages = $request->messages();
        $this->assertArrayHasKey('initial_balance.min', $messages);
        $this->assertSame('El saldo inicial no puede ser negativo.', $messages['initial_balance.min']);
    }

    public function test_custom_message_for_email_unique(): void
    {
        $user = User::factory()->create();
        $request = $this->createRequest($user);
        $messages = $request->messages();
        $this->assertArrayHasKey('email.unique', $messages);
        $this->assertSame('Ya existe otro usuario con este correo electrónico.', $messages['email.unique']);
    }
}
