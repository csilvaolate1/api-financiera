<?php

namespace Tests\Unit;

use App\Http\Requests\StoreUserRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreUserRequestTest extends TestCase
{
    use RefreshDatabase;
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
}
