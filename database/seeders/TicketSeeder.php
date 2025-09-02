<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run()
    {
        // Create 5000 tickets.
        Ticket::factory(5000)->create();
        
        // Ensure some seeded tickets are already winners.
        Ticket::factory(10)->create(['is_winner' => true, 'prize_value' => 10.00]);
        Ticket::factory(5)->create(['is_winner' => true, 'prize_value' => 100.00]);
    }
}