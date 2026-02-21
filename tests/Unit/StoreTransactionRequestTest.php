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
    public function test_validates_from_user_id_required(): void
    {
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'to_user_id' => 1,
            'amount' => 10,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('from_user_id', $validator->errors()->toArray());
    }

    public function test_validates_to_user_id_required(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => $u->id,
            'amount' => 10,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('to_user_id', $validator->errors()->toArray());
    }

    public function test_validates_from_user_id_exists(): void
    {
        $u = User::factory()->create();
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => 99999,
            'to_user_id' => $u->id,
            'amount' => 10,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('from_user_id', $validator->errors()->toArray());
    }

    public function test_validates_to_user_id_exists(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => $u->id,
            'to_user_id' => 99999,
            'amount' => 10,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('to_user_id', $validator->errors()->toArray());
    }

    public function test_accepts_valid_idempotency_key(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->actingAs($u1);
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => $u1->id,
            'to_user_id' => $u2->id,
            'amount' => 50,
            'idempotency_key' => 'key-' . str_repeat('a', 60),
        ], $rules);
        $this->assertFalse($validator->fails());
    }

    public function test_validates_idempotency_key_max_length(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->actingAs($u1);
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => $u1->id,
            'to_user_id' => $u2->id,
            'amount' => 50,
            'idempotency_key' => str_repeat('x', 65),
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('idempotency_key', $validator->errors()->toArray());
    }

    public function test_validates_amount_required(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->actingAs($u1);
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => $u1->id,
            'to_user_id' => $u2->id,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_validates_amount_min(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->actingAs($u1);
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => $u1->id,
            'to_user_id' => $u2->id,
            'amount' => 0,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_validates_to_user_different_from_from_user(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => $u->id,
            'to_user_id' => $u->id,
            'amount' => 10,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('to_user_id', $validator->errors()->toArray());
    }

    public function test_passes_with_valid_data(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->actingAs($u1);
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

    public function test_rejects_from_user_id_when_not_own_account(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->actingAs($u1);
        $rules = (new StoreTransactionRequest)->rules();
        $validator = Validator::make([
            'from_user_id' => $u2->id,
            'to_user_id' => $u1->id,
            'amount' => 10,
        ], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('from_user_id', $validator->errors()->toArray());
        $this->assertStringContainsString('propia cuenta', $validator->errors()->first('from_user_id'));
    }

    public function test_custom_message_for_from_user_id_exists(): void
    {
        $request = new StoreTransactionRequest();
        $messages = $request->messages();
        $this->assertArrayHasKey('from_user_id.exists', $messages);
        $this->assertSame('El usuario emisor no existe.', $messages['from_user_id.exists']);
    }

    public function test_custom_message_for_to_user_id_exists(): void
    {
        $request = new StoreTransactionRequest();
        $messages = $request->messages();
        $this->assertArrayHasKey('to_user_id.exists', $messages);
        $this->assertSame('El usuario receptor no existe.', $messages['to_user_id.exists']);
    }
}
