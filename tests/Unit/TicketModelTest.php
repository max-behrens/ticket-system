<?php

namespace Tests\Unit;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_can_be_created()
    {
        $ticket = Ticket::create([
            'code' => 'TEST-12345',
            'prize_value' => 10.00,
            'is_winner' => true,
            'is_sold' => false
        ]);

        $this->assertDatabaseHas('tickets', [
            'code' => 'TEST-12345',
            'prize_value' => 10.00,
            'is_winner' => true
        ]);
    }

    public function test_winner_probability_distribution()
    {
        // Create more tickets to get meaningful statistics at 0.002% rate.
        $tickets = Ticket::factory(50000)->create();
        $winners = $tickets->where('is_winner', true)->count();
       
        // With 0.002% rate, expect roughly 1 winner per 50,000 tickets.
        $this->assertGreaterThanOrEqual(0, $winners);
        $this->assertLessThanOrEqual(5, $winners);
    }
}