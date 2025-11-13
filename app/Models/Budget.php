<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'income',
        'expenses',
        'recommended_daily_limit',
        'categories',
    ];

    protected $casts = [
        'income' => 'decimal:2',
        'expenses' => 'decimal:2',
        'recommended_daily_limit' => 'decimal:2',
        'categories' => 'array', // JSONB
    ];

    /* Relations */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* Helpers */

    public function getYearAttribute(): int
    {
        return intval(substr($this->month, 0, 4));
    }

    public function getMonthNumberAttribute(): int
    {
        return intval(substr($this->month, 5, 2));
    }
}
