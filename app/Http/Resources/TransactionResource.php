<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_user_id' => $this->from_user_id,
            'to_user_id' => $this->to_user_id,
            'amount' => (float) $this->amount,
            'created_at' => $this->created_at?->toIso8601String(),
            'from_user' => $this->whenLoaded('fromUser', fn () => [
                'id' => $this->fromUser->id,
                'name' => $this->fromUser->name,
                'email' => $this->fromUser->email,
            ]),
            'to_user' => $this->whenLoaded('toUser', fn () => [
                'id' => $this->toUser->id,
                'name' => $this->toUser->name,
                'email' => $this->toUser->email,
            ]),
        ];
    }
}
