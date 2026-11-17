<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load('ticketType', 'event');

        return view('orders.show', compact('order'));
    }
}
