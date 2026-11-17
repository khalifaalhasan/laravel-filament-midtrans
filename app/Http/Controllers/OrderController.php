<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Ticket;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'riwayat' ? 'riwayat' : 'aktif';

        // Aktif: paid for an event that has not ended, or still payable. Riwayat: everything else.
        $active = fn (Builder $q) => $q
            ->where(fn (Builder $q) => $q->where('status', OrderStatus::Paid)->whereHas('event', fn (Builder $e) => $e->where('ends_at', '>', now())))
            ->orWhere(fn (Builder $q) => $q->where('status', OrderStatus::Pending)->where('expires_at', '>', now()));

        $orders = $request->user()->orders()
            ->when($tab === 'aktif', fn (Builder $q) => $q->where($active), fn (Builder $q) => $q->whereNot($active))
            ->with('event', 'items.ticketType')
            ->withCount('tickets')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', compact('orders', 'tab'));
    }

    /** Single e-ticket, print-ready. Same access as the order it belongs to. */
    public function ticket(Ticket $ticket): View
    {
        Gate::authorize('view', $ticket->order);

        $ticket->load('order', 'event', 'ticketType');

        return view('orders.ticket', compact('ticket'));
    }

    public function show(Order $order, OrderService $orders): View
    {
        Gate::authorize('view', $order);

        $orders->syncWithGateway($order);

        $order->load('items.ticketType', 'event', 'tickets.ticketType');

        return view('orders.show', compact('order'));
    }
}
