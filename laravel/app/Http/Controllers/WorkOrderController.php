<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\Client;
use App\Models\OrderPart;
use App\Models\Product;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    private const REPAIR_TYPES = [
        'Mantenimiento preventivo', 'Mantenimiento correctivo', 'Cambio de piezas',
        'Rectificado', 'Soldadura', 'Diagnóstico', 'Otro',
    ];

    public function index(Request $request): View
    {
        $orders = WorkOrder::with(['parts', 'laborItems', 'payments'])
            ->when($request->filled('status') && $request->input('status') !== 'all',
                fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner->where('code', 'like', $term)
                    ->orWhere('client', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('equipment', 'like', $term)
                    ->orWhere('repair_type', 'like', $term));
            })
            ->latest('received_at')->get();

        return view('orders.index', [
            'orders' => $orders,
            'status' => $request->input('status', 'all'),
            'search' => $request->input('q', ''),
        ]);
    }

    public function quotes(): View
    {
        return view('quotes.index', [
            'orders' => WorkOrder::with(['parts', 'laborItems', 'payments'])
                ->whereIn('status', ['reception', 'quoted'])
                ->latest('received_at')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $clients = Client::with(['workOrders.parts', 'workOrders.laborItems', 'workOrders.payments'])
            ->orderBy('name')->get();

        return view('orders.create', [
            'repairTypes' => self::REPAIR_TYPES,
            'clients' => $clients,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_mode' => ['required', 'in:new,existing'],
            'client_id' => ['required_if:client_mode,existing', 'nullable', 'integer', 'exists:clients,id'],
            'client_name' => ['required_if:client_mode,new', 'nullable', 'string', 'max:255'],
            'client_phone' => ['required_if:client_mode,new', 'nullable', 'string', 'max:50'],
            'equipment' => ['nullable', 'string', 'max:255'],
            'repair_type' => ['required', 'in:'.implode(',', self::REPAIR_TYPES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $order = DB::transaction(function () use ($data) {
            $client = $data['client_mode'] === 'new'
                ? Client::create(['name' => $data['client_name'], 'phone' => $data['client_phone']])
                : Client::whereKey($data['client_id'])->lockForUpdate()->firstOrFail();

            $order = WorkOrder::create([
                'client_id' => $client->id,
                'client' => $client->name,
                'phone' => $client->phone,
                'equipment' => $data['equipment'] ?? null,
                'repair_type' => $data['repair_type'],
                'notes' => $data['notes'] ?? null,
                'status' => 'reception',
                'received_at' => now(),
                'code' => 'PENDING',
            ]);
            $order->update(['code' => 'OT-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT)]);

            return $order;
        });

        return redirect()->route('orders.show', $order)
            ->with('success', 'Ingreso registrado. Agrega los costos para crear la cotización.');
    }

    public function createQuote(WorkOrder $order): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = WorkOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($lockedOrder->status !== 'reception') {
                throw ValidationException::withMessages(['status' => 'Solo se puede crear una cotización desde la recepción.']);
            }

            $parts = $lockedOrder->parts()->orderBy('id')->get()->map(fn (OrderPart $part) => [
                'name' => $part->name,
                'category' => $part->category,
                'quantity' => $part->quantity,
                'unit_price' => (float) $part->unit_price,
                'total' => round($part->quantity * (float) $part->unit_price, 2),
            ])->all();
            $laborItems = $lockedOrder->laborItems()->orderBy('id')->get()->map(fn ($labor) => [
                'description' => $labor->description,
                'amount' => (float) $labor->amount,
            ])->all();
            $partsTotal = array_sum(array_column($parts, 'total'));
            $laborTotal = array_sum(array_column($laborItems, 'amount'));

            $lockedOrder->update([
                'status' => 'quoted',
                'quoted_at' => now(),
                'quote_data' => [
                    'code' => $lockedOrder->code,
                    'client' => $lockedOrder->client,
                    'phone' => $lockedOrder->phone,
                    'equipment' => $lockedOrder->equipment,
                    'repair_type' => $lockedOrder->repair_type,
                    'notes' => $lockedOrder->notes,
                    'parts' => $parts,
                    'labor_items' => $laborItems,
                    'parts_total' => round($partsTotal, 2),
                    'labor_total' => round($laborTotal, 2),
                    'total' => round($partsTotal + $laborTotal, 2),
                ],
            ]);
        });

        return redirect()->route('orders.show', $order)
            ->with('success', 'Cotización creada y guardada. El stock se descontará al iniciar la reparación.');
    }

    public function show(WorkOrder $order): View
    {
        $order->load(['registeredClient', 'parts.product', 'laborItems', 'payments']);

        return view('orders.show', [
            'order' => $order,
            'products' => Product::orderBy('name')->get(),
            'repairTypes' => self::REPAIR_TYPES,
        ]);
    }

    public function warranties(): View
    {
        return view('warranties.index', [
            'orders' => WorkOrder::with(['parts', 'laborItems', 'payments'])
                ->where('status', 'completed')->latest('completed_at')->get(),
        ]);
    }

    public function updateStatus(Request $request, WorkOrder $order): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:repairing,ready,completed']]);
        $next = $data['status'];

        DB::transaction(function () use ($order, $next) {
            $lockedOrder = WorkOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $allowed = match ($lockedOrder->status) {
                'quoted' => ['repairing'],
                'repairing' => ['ready'],
                'ready' => ['completed'],
                default => [],
            };

            if (! in_array($next, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'Ese cambio de estado no está permitido.']);
            }

            if ($next === 'completed') {
                $lockedOrder->load(['parts', 'laborItems', 'payments']);
                if ($lockedOrder->balance > 0.001) {
                    throw ValidationException::withMessages(['status' => 'Registra el pago completo antes de entregar la orden.']);
                }
            }

            if ($next === 'repairing') {
                if (! $lockedOrder->quote_data) {
                    throw ValidationException::withMessages(['status' => 'Crea la cotización antes de iniciar la reparación.']);
                }

                $partsToDeduct = $lockedOrder->parts()
                    ->orderBy('product_id')
                    ->get()
                    ->filter(fn (OrderPart $part) => $part->quantity > $part->inventory_deducted_quantity);

                foreach ($partsToDeduct->groupBy('product_id') as $productId => $parts) {
                    if (! $productId) {
                        throw ValidationException::withMessages(['status' => 'Hay un repuesto de la cotización que ya no existe en el inventario.']);
                    }

                    $product = Product::whereKey($productId)->lockForUpdate()->first();
                    if (! $product) {
                        throw ValidationException::withMessages(['status' => 'Hay un repuesto de la cotización que ya no existe en el inventario.']);
                    }

                    $needed = $parts->sum(fn (OrderPart $part) => $part->quantity - $part->inventory_deducted_quantity);
                    if ($product->stock < $needed) {
                        throw ValidationException::withMessages([
                            'status' => "Stock insuficiente para {$product->name}; hay {$product->stock} y se necesitan {$needed}.",
                        ]);
                    }

                    $product->decrement('stock', $needed);
                    $parts->each(fn (OrderPart $part) => $part->update([
                        'inventory_deducted_quantity' => $part->quantity,
                    ]));
                }
            }

            $timestamps = match ($next) {
                'ready' => ['ready_at' => now()],
                'completed' => ['completed_at' => now()],
                default => [],
            };
            $lockedOrder->update(['status' => $next] + $timestamps);
        });

        return back()->with('success', $next === 'repairing'
            ? 'Reparación iniciada y repuestos descontados del inventario.'
            : 'Estado de la orden actualizado.');
    }

    public function addPart(Request $request, WorkOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        DB::transaction(function () use ($data, $order) {
            $lockedOrder = WorkOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $product = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            if (! in_array($lockedOrder->status, ['reception', 'repairing', 'ready'], true)) {
                throw ValidationException::withMessages(['product_id' => 'No se pueden modificar los repuestos en este estado.']);
            }
            $deductStock = $lockedOrder->status !== 'reception';
            if ($deductStock && $product->stock < $data['quantity']) {
                throw ValidationException::withMessages(['quantity' => 'No hay stock suficiente.']);
            }

            if ($deductStock) {
                $product->decrement('stock', $data['quantity']);
            }
            $samePriceParts = $lockedOrder->parts()
                ->where('product_id', $product->id)
                ->where('unit_price', $product->price)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($samePriceParts->isNotEmpty()) {
                $primaryPart = $samePriceParts->first();
                $primaryPart->update([
                    'quantity' => $samePriceParts->sum('quantity') + $data['quantity'],
                    'inventory_deducted_quantity' => $samePriceParts->sum('inventory_deducted_quantity')
                        + ($deductStock ? $data['quantity'] : 0),
                ]);
                $samePriceParts->skip(1)->each->delete();
            } else {
                $lockedOrder->parts()->create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category,
                    'quantity' => $data['quantity'],
                    'unit_price' => $product->price,
                    'inventory_deducted_quantity' => $deductStock ? $data['quantity'] : 0,
                ]);
            }
        });

        return back()->with('success', $order->status === 'reception'
            ? 'Repuesto agregado a la cotización; el stock se descontará al iniciar la reparación.'
            : 'Repuesto agregado y stock descontado.');
    }

    public function removePart(WorkOrder $order, OrderPart $part): RedirectResponse
    {
        abort_unless($part->work_order_id === $order->id, 404);
        DB::transaction(function () use ($part, $order) {
            $lockedOrder = WorkOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($lockedOrder->status, ['reception', 'repairing', 'ready'], true)) {
                throw ValidationException::withMessages(['status' => 'No se pueden modificar los repuestos en este estado.']);
            }
            $lockedPart = $lockedOrder->parts()->whereKey($part->id)->lockForUpdate()->firstOrFail();
            if ($lockedPart->product_id && $lockedPart->inventory_deducted_quantity > 0) {
                Product::whereKey($lockedPart->product_id)->lockForUpdate()->first()
                    ?->increment('stock', $lockedPart->inventory_deducted_quantity);
            }
            $lockedPart->delete();
        });

        return back()->with('success', 'Repuesto retirado; se devolvió al inventario el stock que ya se había descontado.');
    }

    public function addLabor(Request $request, WorkOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
        ]);

        DB::transaction(function () use ($data, $order) {
            $lockedOrder = WorkOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($lockedOrder->status, ['reception', 'repairing', 'ready'], true)) {
                throw ValidationException::withMessages(['status' => 'No se puede modificar la cotización en este estado.']);
            }
            $lockedOrder->laborItems()->create($data);
        });

        return back()->with('success', 'Mano de obra agregada.');
    }

    public function removeLabor(WorkOrder $order, int $labor): RedirectResponse
    {
        DB::transaction(function () use ($order, $labor) {
            $lockedOrder = WorkOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($lockedOrder->status, ['reception', 'repairing', 'ready'], true)) {
                throw ValidationException::withMessages(['status' => 'No se puede modificar la cotización en este estado.']);
            }
            $lockedOrder->laborItems()->findOrFail($labor)->delete();
        });

        return back()->with('success', 'Mano de obra eliminada.');
    }

    public function storePayment(Request $request, WorkOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'method' => ['required', 'in:Efectivo,Transferencia,Tarjeta,Billetera digital'],
        ]);

        DB::transaction(function () use ($data, $order) {
            $lockedOrder = WorkOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($lockedOrder->status, ['repairing', 'ready'], true)) {
                throw ValidationException::withMessages(['amount' => 'Registra abonos cuando la reparación haya iniciado.']);
            }
            if ((float) $data['amount'] > $lockedOrder->balance + 0.001) {
                throw ValidationException::withMessages(['amount' => 'El abono supera el saldo pendiente.']);
            }

            $payment = $lockedOrder->payments()->create([
                'amount' => $data['amount'],
                'method' => $data['method'],
                'paid_at' => now(),
            ]);
            CashMovement::create([
                'work_order_id' => $lockedOrder->id,
                'type' => 'income',
                'category' => 'Abono de cliente',
                'description' => 'Abono de '.$lockedOrder->client,
                'amount' => $payment->amount,
                'origin' => 'payment',
                'occurred_at' => $payment->paid_at,
            ]);
            $lockedOrder->load(['parts', 'laborItems', 'payments']);
            if ($lockedOrder->status === 'ready' && $lockedOrder->balance <= 0.001) {
                $lockedOrder->update(['status' => 'completed', 'completed_at' => now()]);
            }
        });

        return back()->with('success', 'Abono registrado en caja.');
    }
}
