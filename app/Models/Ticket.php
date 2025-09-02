<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'prize_value', 'is_winner', 'is_sold'];

    protected $casts = [
        'is_winner' => 'boolean',
        'is_sold' => 'boolean',
        'prize_value' => 'decimal:2'
    ];

    public function results()
    {
        return $this->hasMany(TicketResult::class);
    }
}