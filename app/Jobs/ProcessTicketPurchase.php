<?php

namespace App\Jobs;

use App\Models\Purchase;
use App\Models\Ticket;
use App\Models\TicketResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTicketPurchase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Purchase $purchase
    ) {}

    public function handle()
    {
        try {
            sleep(2);

            $tickets = Ticket::where('is_sold', false)
                ->inRandomOrder()
                ->limit($this->purchase->quantity)
                ->get();

            // Check if we have enough tickets.
            if ($tickets->count() < $this->purchase->quantity) {
                $this->purchase->update(['status' => 'failed']);
                return;
            }

            $ticketsData = [];

            foreach ($tickets as $ticket) {
                $ticket->update(['is_sold' => true]);

                $ticketsData[] = [
                    'ticket_id' => $ticket->id,
                    'code' => $ticket->code,
                    'prize_won' => $ticket->prize_value,
                    'is_winner' => $ticket->is_winner,
                ];
            }

            // Ensure we always have valid data.
            if (empty($ticketsData)) {
                $ticketsData = [];
            }

            TicketResult::create([
                'purchase_id' => $this->purchase->id,
                'tickets' => $ticketsData,
                'total_prize_won' => collect($ticketsData)->sum('prize_won'),
            ]);

            // The ticket purchase has been successfully completed.
            $this->purchase->update(['status' => 'completed']);

        } catch (Exception $e) {
            Log::error('Ticket purchase processing failed', [
                'purchase_id' => $this->purchase->id,
                'error' => $e->getMessage()
            ]);
            
            $this->purchase->update(['status' => 'failed']);
        }
    }
}