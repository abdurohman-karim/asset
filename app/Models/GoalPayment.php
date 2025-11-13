<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalPayment extends Model
{
    protected $fillable = [
        'goal_id',
        'amount',
        'method',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /* Relations */

    public function goal()
    {
        return $this->belongsTo(Goal::class);
    }
}