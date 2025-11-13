<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'insight',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /* Relations */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
