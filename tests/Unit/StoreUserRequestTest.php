<?php

namespace Tests\Unit;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreUserRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validates_name_required(): void
    {
        $rules = (new StoreUserRequest)->rules();
        $validator = Validator::make([
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'initial_balance' => 0,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validates_email_required(): void
    {
        $rules = (new StoreUserRequest)->rules();
        $validator = Validator::make([
            'name' => 'Test User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'initial_balance' => 0,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_validates_email_format(): void
    {
        $rules = (new StoreUserRequest)->rules();
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'initial_balance' => 0,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_validates_email_unique(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $rules = (new StoreUserRequest)->rules();
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'initial_balance' => 0,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_validates_password_required(): void
    {
        $rules = (new StoreUserRequest)->rules();
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'initial_balance' => 0,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_validates_password_confirmed(): void
    {
        $rules = (new StoreUserRequest)->rules();
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
            'initial_balance' => 0,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_passes_with_valid_data(): void
    {
        $rules = (new StoreUserRequest)->rules();
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'unique@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'initial_balance' => 100,
        ], $rules);
        $this->assertFalse($validator->fails());
    }

    public function test_validates_initial_balance_non_negative(): void
    {
        $rules = (new StoreUserRequest)->rules();
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'initial_balance' => -100,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('initial_balance', $validator->errors()->toArray());
    }

    public function test_validates_initial_balance_required(): void
    {
        $rules = (new StoreUserRequest)->rules();
        $validator = Validator::make([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('initial_balance', $validator->errors()->toArray());
    }

    public function test_custom_message_for_initial_balance_min(): void
    {
        $request = new StoreUserRequest;
        $messages = $request->messages();
        $this->assertArrayHasKey('initial_balance.min', $messages);
        $this->assertSame('El saldo inicial no puede ser negativo.', $messages['initial_balance.min']);
    }

    public function test_custom_message_for_email_unique(): void
    {
        $request = new StoreUserRequest;
        $messages = $request->messages();
        $this->assertArrayHasKey('email.unique', $messages);
        $this->assertSame('Ya existe un usuario con este correo electrónico.', $messages['email.unique']);
    }
}
