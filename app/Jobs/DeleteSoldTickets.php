<?php

namespace App\Jobs;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteSoldTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $deletedCount = Ticket::where('is_sold', true)->delete();
        
        \Log::info('Sold tickets deleted successfully', [
            'deleted_count' => $deletedCount,
            'timestamp' => now()
        ]);
    }
}