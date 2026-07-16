<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * §5.6 kontrak — GET/POST/PUT /api/admin/bands item:
 * { id, name, genre, description, logo_path }
 */
class BandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'genre' => $this->genre,
            'description' => $this->description,
            'logo_path' => $this->logo_path,
        ];
    }
}
