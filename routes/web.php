<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Webhooks\MidtransWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EventController::class, 'index'])->name('events.index');
Route::get('/jelajah', [EventController::class, 'explore'])->name('events.explore');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('/events/{event}/tiket', [CheckoutController::class, 'tickets'])->name('checkout.tickets');

Route::post('/webhooks/midtrans', MidtransWebhookController::class)->name('webhooks.midtrans');

Route::middleware('auth')->group(function () {
    Route::post('/events/{event}/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/{order}/detail', [CheckoutController::class, 'details'])->name('checkout.details');
    Route::post('/checkout/{order}/detail', [CheckoutController::class, 'saveDetails'])->name('checkout.details.store');
    Route::get('/checkout/{order}/pembayaran', [CheckoutController::class, 'payment'])->name('checkout.payment');
    Route::post('/checkout/{order}/pembayaran', [CheckoutController::class, 'savePayment'])->name('checkout.payment.store');
    Route::get('/checkout/{order}/bayar', [CheckoutController::class, 'pay'])->name('checkout.pay');
    Route::get('/checkout/{order}/status', [CheckoutController::class, 'status'])->name('checkout.status');
    Route::post('/checkout/{order}/batal', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
    Route::get('/my-tickets', [OrderController::class, 'index'])->name('my-tickets');
    Route::get('/e-ticket/{ticket}', [OrderController::class, 'ticket'])->name('tickets.show');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
