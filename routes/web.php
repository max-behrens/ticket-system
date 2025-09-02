<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    // Custom routes:
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets/purchase', [TicketController::class, 'purchase'])->name('tickets.purchase');
    Route::get('/tickets/status/{purchase}', [TicketController::class, 'status'])->name('tickets.status');
    Route::get('/tickets/latest-purchase', [TicketController::class, 'latestPurchase'])->name('tickets.latest-purchase');
    Route::get('/tickets/all/{purchaseId}', [TicketController::class, 'allTickets'])->name('tickets.all');
    Route::get('/tickets/all-user-tickets', [TicketController::class, 'allUserTickets'])->middleware('auth');

    // Default laravel project routes:
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
