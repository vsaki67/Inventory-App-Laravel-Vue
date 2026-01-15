<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\StockMovementController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/inventory/{inventoryItem}/history', [InventoryItemController::class, 'history'])->name('inventory.history');
});

Route::get('/inventory', [InventoryItemController::class, 'index'])->name('inventory.index');
Route::post('/inventory/add', [StockMovementController::class, 'add'])->name('inventory.add');
Route::post('/inventory/deduct', [StockMovementController::class, 'deduct'])->name('inventory.deduct');
Route::post('/inventory/items', [InventoryItemController::class, 'store'])->name('inventory.store');

require __DIR__.'/auth.php';
