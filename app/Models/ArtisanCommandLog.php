<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtisanCommandLog extends Model
{
    protected $fillable = [
        'user_id',
        'command',
        'parameters',
        'status',
        'output',
        'execution_time',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'execution_time' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
