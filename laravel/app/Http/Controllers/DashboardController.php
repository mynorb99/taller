<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\Product;
use App\Models\WorkOrder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();
        $orders = WorkOrder::with(['parts', 'laborItems', 'payments'])->latest('received_at')->get();
        $activeOrders = $orders->where('status', '!=', 'completed');
        $movementsToday = CashMovement::whereDate('occurred_at', $today)->get();

        return view('dashboard', [
            'activeOrders' => $activeOrders,
            'orders' => $orders,
            'recentOrders' => $orders->take(6),
            'criticalProducts' => Product::whereColumn('stock', '<=', 'minimum_stock')->orderBy('stock')->get(),
            'receivables' => $activeOrders
                ->whereNotIn('status', ['reception', 'quoted'])
                ->sum(fn (WorkOrder $order) => max($order->balance, 0)),
            'todayIncome' => $movementsToday->where('type', 'income')->sum('amount'),
            'todayExpenses' => $movementsToday->where('type', 'expense')->sum('amount'),
            'readyOrders' => $activeOrders->where('status', 'ready')->count(),
            'repairingOrders' => $activeOrders->where('status', 'repairing')->count(),
        ]);
    }
}
