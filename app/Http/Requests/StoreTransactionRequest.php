<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_user_id' => ['required', 'exists:users,id'],
            'to_user_id' => ['required', 'exists:users,id', 'different:from_user_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_user_id.required' => 'El usuario emisor es obligatorio.',
            'from_user_id.exists' => 'El usuario emisor no existe.',
            'to_user_id.required' => 'El usuario receptor es obligatorio.',
            'to_user_id.exists' => 'El usuario receptor no existe.',
            'to_user_id.different' => 'No se puede transferir al mismo usuario.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.min' => 'El monto debe ser mayor a cero.',
        ];
    }
}
