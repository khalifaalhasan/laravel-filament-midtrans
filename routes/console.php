<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Grace period lets a last-second payment webhook land before the seats are released.
Artisan::command('orders:expire {--grace=5 : Minutes to wait past expires_at}', function (OrderService $orders) {
    $expired = 0;

    Order::where('status', OrderStatus::Pending)
        ->where('expires_at', '<', now()->subMinutes((int) $this->option('grace')))
        ->each(function (Order $order) use ($orders, &$expired) {
            $expired += (int) $orders->transition($order, OrderStatus::Expired);
        });

    $this->info("Expired {$expired} order(s).");
})->purpose('Expire unpaid orders and release their seats');

Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();
