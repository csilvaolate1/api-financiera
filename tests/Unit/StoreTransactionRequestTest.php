<?php

namespace Tests\Unit;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTransactionRequestTest extends TestCase
{
    use RefreshDatabase;
    public function test_validates_amount_required(): void
    {
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => 1,
            'to_user_id' => 2,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_validates_amount_min(): void
    {
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => 1,
            'to_user_id' => 2,
            'amount' => 0,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_validates_to_user_different_from_from_user(): void
    {
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => 1,
            'to_user_id' => 1,
            'amount' => 10,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('to_user_id', $validator->errors()->toArray());
    }

    public function test_passes_with_valid_data(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => $u1->id,
            'to_user_id' => $u2->id,
            'amount' => 50.25,
        ], $rules);
        $this->assertFalse($validator->fails());
    }

    public function test_custom_message_for_amount_min(): void
    {
        $request = new StoreTransactionRequest();
        $messages = $request->messages();
        $this->assertArrayHasKey('amount.min', $messages);
        $this->assertSame('El monto debe ser mayor a cero.', $messages['amount.min']);
    }

    public function test_custom_message_for_to_user_different(): void
    {
        $request = new StoreTransactionRequest();
        $messages = $request->messages();
        $this->assertArrayHasKey('to_user_id.different', $messages);
        $this->assertSame('No se puede transferir al mismo usuario.', $messages['to_user_id.different']);
    }
}
