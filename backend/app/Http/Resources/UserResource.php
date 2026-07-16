<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * §5.2 kontrak — GET /api/me. Format persis:
 * { id, username, email, role, profile: { full_name, phone, avatar_path } }
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'profile' => [
                'full_name' => $this->profile?->full_name,
                'phone' => $this->profile?->phone,
                'avatar_path' => $this->profile?->avatar_path,
            ],
        ];
    }
}
