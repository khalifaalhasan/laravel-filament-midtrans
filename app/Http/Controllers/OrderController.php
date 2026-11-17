<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

    public function show(Order $order, OrderService $orders): View
    {
        Gate::authorize('view', $order);

        $orders->syncWithGateway($order);

        $order->load('items.ticketType', 'event', 'tickets.ticketType');

        return view('orders.show', compact('order'));
    }
}
