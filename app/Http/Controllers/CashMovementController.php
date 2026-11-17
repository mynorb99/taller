<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashMovementController extends Controller
{
    private const INCOME_CATEGORIES = ['Abono de cliente', 'Venta directa', 'Otro ingreso'];

    private const EXPENSE_CATEGORIES = ['Compra de repuestos', 'Herramientas', 'Alquiler', 'Sueldos', 'Combustible', 'Electricidad', 'Otro gasto'];

    public function index(Request $request): View
    {
        $month = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? now()->format('Y-m');
        $date = Carbon::createFromFormat('Y-m', $month);
        $movements = CashMovement::with('workOrder')
            ->whereBetween('occurred_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
            ->latest('occurred_at')->get();
        $today = CashMovement::whereDate('occurred_at', today())->get();

        return view('cash.index', [
            'month' => $month,
            'movements' => $movements,
            'monthlyIncome' => $movements->where('type', 'income')->sum('amount'),
            'monthlyExpenses' => $movements->where('type', 'expense')->sum('amount'),
            'todayIncome' => $today->where('type', 'income')->sum('amount'),
            'todayExpenses' => $today->where('type', 'expense')->sum('amount'),
        ]);
    }

    public function create(): View
    {
        return view('cash.create', [
            'incomeCategories' => self::INCOME_CATEGORIES,
            'expenseCategories' => self::EXPENSE_CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'occurred_at' => ['required', 'date'],
        ]);
        $categories = $data['type'] === 'income' ? self::INCOME_CATEGORIES : self::EXPENSE_CATEGORIES;
        if (! in_array($data['category'], $categories, true)) {
            throw ValidationException::withMessages(['category' => 'La categoría no coincide con el tipo de movimiento.']);
        }

        CashMovement::create($data + ['origin' => 'manual']);

        return redirect()->route('cash.index', ['month' => substr($data['occurred_at'], 0, 7)])
            ->with('success', 'Movimiento registrado.');
    }

    public function destroy(CashMovement $movement): RedirectResponse
    {
        abort_if($movement->origin !== 'manual', 422, 'Los abonos de órdenes no se pueden eliminar desde caja.');
        $month = $movement->occurred_at->format('Y-m');
        $movement->delete();

        return redirect()->route('cash.index', ['month' => $month])->with('success', 'Movimiento eliminado.');
    }
}
