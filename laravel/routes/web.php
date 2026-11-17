<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('clients', ClientController::class)->except(['destroy']);

    Route::get('/quotes', [WorkOrderController::class, 'quotes'])->name('quotes.index');
    Route::get('/orders', [WorkOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [WorkOrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [WorkOrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [WorkOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/quote', [WorkOrderController::class, 'createQuote'])->name('orders.quote.store');
    Route::patch('/orders/{order}/status', [WorkOrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{order}/parts', [WorkOrderController::class, 'addPart'])->name('orders.parts.store');
    Route::delete('/orders/{order}/parts/{part}', [WorkOrderController::class, 'removePart'])->name('orders.parts.destroy');
    Route::post('/orders/{order}/labor', [WorkOrderController::class, 'addLabor'])->name('orders.labor.store');
    Route::delete('/orders/{order}/labor/{labor}', [WorkOrderController::class, 'removeLabor'])->name('orders.labor.destroy');
    Route::post('/orders/{order}/payments', [WorkOrderController::class, 'storePayment'])->name('orders.payments.store');
    Route::get('/warranties', [WorkOrderController::class, 'warranties'])->name('warranties.index');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::post('/products/{product}/stock', [ProductController::class, 'addStock'])->name('products.stock');

    Route::get('/cash', [CashMovementController::class, 'index'])->name('cash.index');
    Route::get('/cash/create', [CashMovementController::class, 'create'])->name('cash.create');
    Route::post('/cash', [CashMovementController::class, 'store'])->name('cash.store');
    Route::delete('/cash/{movement}', [CashMovementController::class, 'destroy'])->name('cash.destroy');
});
