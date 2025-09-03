<?php

namespace App\Jobs;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReseedTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {            
        // Check if we already have enough unsold tickets.
        $availableTickets = Ticket::where('is_sold', false)->count();
        
        // Don't reseed if there are already a sufficient number of tickets.
        if ($availableTickets >= 3000) {
            \Log::info('Reseed skipped: sufficient tickets available', ['available' => $availableTickets]);
            return;
        }

        // Get all existing codes to avoid duplicates.
        $existingCodes = Ticket::pluck('code')->flip()->toArray();

        DB::transaction(function() use ($existingCodes) {

            // Generate 500 new tickets (10 batches of 50).
            $batchSize = 50;
            $totalCreated = 0;
            
            for ($batch = 0; $batch < 10; $batch++) {
                \Log::info("Starting batch {$batch}");
                $batchTickets = [];
                
                for ($i = 0; $i < $batchSize; $i++) {
                    $code = $this->generateUniqueCode($existingCodes, $batch, $i);
                    $isWinner = mt_rand(1, 500000) === 1;
                    $prizeValue = $isWinner ? $this->getRandomPrize() : 0;
                    
                    $batchTickets[] = [
                        'code' => $code,
                        'prize_value' => $prizeValue,
                        'is_winner' => $isWinner,
                        'is_sold' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    // Add generated code to existing codes array immediately.
                    $existingCodes[$code] = true;
                }
                
                // Insert each batch of ticket purchases into the tickets table.
                Ticket::insert($batchTickets);
                $totalCreated += count($batchTickets);
            }

            // Add some guaranteed winners.
            $guaranteedWinners = [];
            
            // 10 x £10 winners
            for ($i = 0; $i < 10; $i++) {
                $code = $this->generateUniqueCode($existingCodes, 'winner10', $i);
                $guaranteedWinners[] = [
                    'code' => $code,
                    'prize_value' => 10.00,
                    'is_winner' => true,
                    'is_sold' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $existingCodes[$code] = true;
            }
            
            // 5 x £100 winners
            for ($i = 0; $i < 5; $i++) {
                $code = $this->generateUniqueCode($existingCodes, 'winner100', $i);
                $guaranteedWinners[] = [
                    'code' => $code,
                    'prize_value' => 100.00,
                    'is_winner' => true,
                    'is_sold' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $existingCodes[$code] = true;
            }
            
            Ticket::insert($guaranteedWinners);
            \Log::info('Guaranteed winners created successfully', ['count' => count($guaranteedWinners)]);
            
            
            \Log::info('Transaction completed', ['total_tickets_created' => $totalCreated + count($guaranteedWinners)]);
        });
    }

    private function generateUniqueCode(&$existingCodes, $batch = null, $index = null)
    {
        $maxAttempts = 100;
        $attempts = 0;
        
        do {
            $attempts++;
            
            $random1 = strtoupper(Str::random(4));
            $random2 = strtoupper(Str::random(3));
            
            $code = 'Ticket-' . $random1 . $random2;
        
            
            // Double-check the code doesn't already exist in database.
            if (!isset($existingCodes[$code])) {
                $dbCheck = Ticket::where('code', $code)->exists();
                if ($dbCheck) {
                    $existingCodes[$code] = true;
                    continue;
                }
            }
            
        } while (isset($existingCodes[$code]) && $attempts <= $maxAttempts);
        
        // Mark this code as used.
        $existingCodes[$code] = true;
            
        return $code;
    }

    private function getRandomPrize()
    {
        // Todo: Have this method and TicketFactory call the same abstracted method to randomise prize values.
        $prizes = [1.00, 5.00, 10.00, 25.00, 100.00];
        return $prizes[array_rand($prizes)];
    }
}