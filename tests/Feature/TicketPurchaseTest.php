<?php

namespace Tests\Feature;

use App\Jobs\ProcessTicketPurchase;
use App\Models\Purchase;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_purchase_tickets()
    {
        // Setup the job to be queued in this test.
        Queue::fake();
       
        $user = User::factory()->create();
        Ticket::factory(2000)->create();
        
        $response = $this->actingAs($user)->post('/tickets/purchase', [
            'quantity' => 1000
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'quantity' => 1000,
            'total_spent' => 100.00
        ]);
        Queue::assertPushed(ProcessTicketPurchase::class);
    }

    public function test_job_processes_tickets_correctly()
    {
        // Just one user in the seeder currently.
        $user = User::factory()->create();
        $tickets = Ticket::factory(1000)->create();
       
        $purchase = Purchase::create([
            'user_id' => $user->id,
            'quantity' => 100,
            'total_spent' => 10.00,
            'status' => 'processing'
        ]);

        $job = new ProcessTicketPurchase($purchase);
        $job->handle();

        $this->assertEquals('completed', $purchase->fresh()->status);
        
        // Check for only 1 TicketResult record.
        $this->assertEquals(1, $purchase->results()->count());
        
        // Additional assertions to verify the ticket data.
        $result = $purchase->results()->first();
        $this->assertCount(100, $result->tickets);
        $this->assertIsArray($result->tickets);
    }
}