<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'amount_total',
        'amount_saved',
        'currency_code',
        'deadline',
        'priority',
        'status',
        'is_primary'
    ];

    protected $casts = [
        'deadline' => 'date',
        'amount_total' => 'decimal:2',
        'amount_saved' => 'decimal:2',
    ];

    /* Relations */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(GoalPayment::class);
    }

    /* Useful Accessors */

    public function getProgressAttribute(): float
    {
        if ($this->amount_total == 0) return 0;
        return round(($this->amount_saved / $this->amount_total) * 100, 2);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
