<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'currency_code',
        'category',
        'description',
        'datetime',
        'raw'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'datetime' => 'datetime',
        'raw' => 'array', // jsonb
    ];

    /* Relations */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* Accessors */

    public function isIncome(): bool
    {
        return $this->amount > 0;
    }

    public function isExpense(): bool
    {
        return $this->amount < 0;
    }
}
