<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTicketPurchase;
use App\Models\Purchase;
use App\Models\TicketResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function index()
    {
        // Keep tallying the total winnings over all purchase requests from a user.
        $totalWinnings = TicketResult::whereHas('purchase', function($q) {
            $q->where('user_id', Auth::id());
        })->sum('total_prize_won');

        return Inertia::render('Tickets/Index', [
            'totalWinnings' => $totalWinnings
        ]);
    }

    /*
    * Called by user when purchasing ticket to dispatch the ProcessTicketPurchase job..
    */
    public function purchase(Request $request)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1000|max:10000'
        ]);

        $purchase = Purchase::create([
            'user_id' => Auth::id(),
            'quantity' => $request->quantity,
            'total_spent' => $request->quantity * 0.10, // 10p per ticket.
            'status' => 'processing'
        ]);

        ProcessTicketPurchase::dispatch($purchase);

        return response()->json(['purchase_id' => $purchase->id]);
    }

    /*
    * Called after ticket purchase to display one aggregate row of all purchased tickets.
    */
    public function status($purchaseId)
    {
        $purchase = Purchase::with('results')->findOrFail($purchaseId);

        $results = [];
        $totalResults = 0;

        if ($purchase->status === 'completed' && $purchase->results->isNotEmpty()) {
            
            // Just display all tickets stored within the json tickets array in ticket_results.
            $ticketResult = $purchase->results->first();
            $allTickets = $ticketResult->tickets; // Already an array due to model casting.
            
            $totalResults = count($allTickets);
            
            // Sort winners first, then display only the first 50.
            $sortedTickets = collect($allTickets)
                ->sortByDesc('is_winner')
                ->take(50)
                ->values()
                ->toArray();

            $results = array_map(function($ticket) {
                return [
                    'code' => $ticket['code'],
                    'prize_won' => $ticket['prize_won'],
                    'is_winner' => $ticket['is_winner'],
                ];
            }, $sortedTickets);
        }

        return response()->json([
            'status' => $purchase->status,
            'results' => $results,
            'total_results' => $totalResults
        ]);
    }
}