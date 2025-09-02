<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketResult extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_id', 'tickets', 'total_prize_won'];

    protected $casts = [
        'tickets' => 'array',
        'total_prize_won' => 'decimal:2'
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}