<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Services\OrderService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Order $order, PaymentGateway $gateway, OrderService $orders): View
    {
        Gate::authorize('view', $order);

        // Fallback when the webhook cannot reach us (e.g. local dev without a tunnel): ask the gateway, never the browser.
        if ($order->status === OrderStatus::Pending && $order->snap_token) {
            try {
                if ($update = $gateway->getStatus($order)) {
                    $orders->applyUpdate($update);
                    $order->refresh();
                }
            } catch (PaymentGatewayException $e) {
                Log::warning($e->getMessage(), ['order' => $order->code]);
            }
        }

        $order->load('ticketType', 'event');

        return view('orders.show', compact('order'));
    }
}
