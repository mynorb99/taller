<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Client::with(['workOrders.parts', 'workOrders.laborItems', 'workOrders.payments'])
            ->withCount('workOrders')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('clients.index', [
            'clients' => $clients,
            'search' => $request->input('q', ''),
        ]);
    }

    public function create(): View
    {
        return view('clients.form', ['client' => new Client]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Client::create($this->validated($request));

        return redirect()->route('clients.show', $client)->with('success', 'Cliente registrado.');
    }

    public function show(Client $client): View
    {
        $client->load(['workOrders.parts', 'workOrders.laborItems', 'workOrders.payments']);

        return view('clients.show', [
            'client' => $client,
            'orders' => $client->workOrders->sortByDesc('received_at'),
            'pendingOrders' => $client->pending_orders,
        ]);
    }

    public function edit(Client $client): View
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validated($request));

        return redirect()->route('clients.show', $client)->with('success', 'Datos del cliente actualizados.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
