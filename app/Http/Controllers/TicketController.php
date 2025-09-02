<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTicketPurchase;
use App\Jobs\ReseedTickets;
use App\Models\Purchase;
use App\Models\TicketResult;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function index()
    {
        $totalWinnings = TicketResult::whereHas('purchase', function($q) {
            $q->where('user_id', Auth::id());
        })->sum('total_prize_won');

        return Inertia::render('Tickets/Index', [
            'totalWinnings' => $totalWinnings
        ]);
    }

    public function purchase(Request $request)
    {
        $validation = $request->validate([
            'quantity' => 'required|integer|min:1000|max:10000'
        ]);

        $purchase = Purchase::create([
            'user_id' => Auth::id(),
            'quantity' => $request->quantity,
            'total_spent' => $request->quantity * 0.10,
            'status' => 'processing'
        ]);

        ProcessTicketPurchase::dispatch($purchase);

        return response()->json([
            'success' => true,
            'purchase_id' => $purchase->id
        ]);

    }

    public function status($purchaseId, Request $request)
    {
        $purchase = Purchase::with('results')->findOrFail($purchaseId);
        
        // Ensure this purchase belongs to the authenticated user.
        if ($purchase->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $results = [];
        $totalResults = 0;

        if ($purchase->status === 'completed' && $purchase->results->isNotEmpty()) {
            $ticketResult = $purchase->results->first();
            $allTickets = $ticketResult->tickets;
            
            $totalResults = count($allTickets);
            
            // Pagination parameters
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 50);
            $offset = ($page - 1) * $perPage;

            // Sort winners first, then paginate.
            $sortedTickets = collect($allTickets)
                ->sortByDesc('is_winner')
                ->slice($offset, $perPage)
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
            'total_results' => $totalResults,
            'current_page' => (int) $request->get('page', 1),
            'per_page' => (int) $request->get('per_page', 50)
        ]);

    }

    public function latestPurchase()
    {
        $purchase = Purchase::where('user_id', Auth::id())
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (!$purchase) {
            return response()->json(['error' => 'No completed purchases found'], 404);
        }

        return response()->json(['purchase_id' => $purchase->id]);
    }

    public function allTickets($purchaseId)
    {
        $purchase = Purchase::with('results')->findOrFail($purchaseId);
        
        // Ensure this purchase belongs to the authenticated user.
        if ($purchase->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($purchase->status !== 'completed' || $purchase->results->isEmpty()) {
            return response()->json(['error' => 'Purchase not completed or no results'], 404);
        }

        $ticketResult = $purchase->results->first();
        $allTickets = collect($ticketResult->tickets)
            ->sortByDesc('is_winner')
            ->map(function($ticket) {
                return [
                    'code' => $ticket['code'],
                    'prize_won' => $ticket['prize_won'],
                    'is_winner' => $ticket['is_winner'],
                ];
            })
            ->values()
            ->toArray();

        return response()->json(['tickets' => $allTickets]);

    }

    public function allUserTickets()
    {
        // Get all ticket results for the authenticated user.
        $ticketResults = TicketResult::whereHas('purchase', function($q) {
            $q->where('user_id', Auth::id());
        })->with('purchase')->get();

        $allTickets = [];
        
        foreach ($ticketResults as $ticketResult) {
            $tickets = $ticketResult->tickets;
            
            foreach ($tickets as $ticket) {
                $allTickets[] = [
                    'code' => $ticket['code'],
                    'prize_won' => $ticket['prize_won'] ?? 0,
                    'is_winner' => $ticket['is_winner'] ?? false,
                    'purchase_date' => $ticketResult->purchase->created_at->toDateString(),
                    'purchase_id' => $ticketResult->purchase->id
                ];
            }
        }

        // Sort by winners first, then by purchase date.
        $sortedTickets = collect($allTickets)
            ->sortByDesc(function($ticket) {
                return ($ticket['is_winner'] ? 1000000 : 0) + strtotime($ticket['purchase_date']);
            })
            ->values()
            ->toArray();

        return response()->json(['tickets' => $sortedTickets]);

    }
}