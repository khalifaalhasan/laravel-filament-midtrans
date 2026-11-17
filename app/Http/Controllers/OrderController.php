<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()
            ->with('event', 'items.ticketType')
            ->withCount('tickets')
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

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

        $order->load('items.ticketType', 'event', 'tickets.ticketType');

        return view('orders.show', compact('order'));
    }
}
