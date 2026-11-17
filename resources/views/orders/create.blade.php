@extends('layouts.app')
@section('title', 'Nueva orden')
@section('heading', 'Nueva orden de trabajo')
@section('subtitle', 'Registra el cliente, equipo y diagnóstico; después completarás la cotización')

@section('content')
    <form class="form-card stack" method="POST" action="{{ route('orders.store') }}">
        @csrf
        <label>Tipo de cliente *
            <select class="input" name="client_mode" id="clientMode" required>
                <option value="">Selecciona una opción</option>
                <option value="new" @selected(old('client_mode') === 'new')>Cliente nuevo</option>
                <option value="existing" @selected(old('client_mode') === 'existing')>Cliente registrado</option>
            </select>
        </label>

        <div id="newClientFields" class="stack" hidden>
            <h2 class="section-title">Datos del cliente nuevo</h2>
            <div class="form-grid">
                <label>Nombre del cliente *
                    <input class="input" name="client_name" value="{{ old('client_name') }}" maxlength="255" data-required>
                </label>
                <label>Celular *
                    <input class="input" name="client_phone" value="{{ old('client_phone') }}" maxlength="50" data-required>
                </label>
            </div>
        </div>

        <div id="existingClientFields" class="stack" hidden>
            <h2 class="section-title">Busca al cliente registrado</h2>
            <label for="clientSearch">Escribe el nombre o celular para filtrar *
                <input class="input" type="search" id="clientSearch" placeholder="Ej.: Rosa Quispe o 955 102 334"
                    autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false"
                    aria-controls="clientResults" value="{{ old('client_id') ? $clients->firstWhere('id', old('client_id'))?->name : '' }}">
                <input type="hidden" name="client_id" id="clientId" value="{{ old('client_id') }}">
            </label>
            <p class="subtle" id="clientSearchHint">Empieza a escribir para ver coincidencias.</p>
            <div class="client-results" id="clientResults" role="listbox" aria-label="Clientes coincidentes" hidden>
                @foreach ($clients as $client)
                    <button class="client-option" type="button" role="option"
                        data-client-id="{{ $client->id }}"
                        data-name="{{ $client->name }}"
                        data-phone="{{ $client->phone }}"
                        data-balance="{{ number_format($client->outstanding_balance, 2, '.', '') }}"
                        data-search="{{ $client->name }} {{ $client->phone }} {{ $client->email }}">
                        <span><b>{{ $client->name }}</b><small>{{ $client->phone }}@if ($client->email) · {{ $client->email }}@endif</small></span>
                        <span class="client-option-balance">Pendiente Q {{ number_format($client->outstanding_balance, 2) }}</span>
                    </button>
                @endforeach
                <p class="client-no-results" id="clientNoResults" hidden>No hay clientes con ese nombre o celular.</p>
            </div>
            <p class="client-selected" id="clientSelected" role="status" hidden></p>
            @if ($clients->isEmpty())
                <p class="subtle">Todavía no hay clientes registrados. Selecciona “Cliente nuevo” para continuar.</p>
            @endif
        </div>

        <hr style="width:100%;border-color:var(--line)">
        <div class="form-grid">
            <label>Maquinaria / equipo
                <input class="input" name="equipment" value="{{ old('equipment') }}" maxlength="255">
            </label>
            <label>Tipo de reparación *
                <select class="input" name="repair_type" required>
                    @foreach ($repairTypes as $type)<option @selected(old('repair_type') === $type)>{{ $type }}</option>@endforeach
                </select>
            </label>
        </div>
        <label>Notas / diagnóstico
            <textarea class="input" name="notes" rows="4" maxlength="5000">{{ old('notes') }}</textarea>
        </label>
        <div class="form-actions"><button class="button primary" type="submit">Continuar a la cotización</button><a class="button" href="{{ route('orders.index') }}">Cancelar</a></div>
    </form>
@endsection

@push('scripts')
<script>
    const clientMode = document.getElementById('clientMode');
    const newClientFields = document.getElementById('newClientFields');
    const existingClientFields = document.getElementById('existingClientFields');
    const clientSearch = document.getElementById('clientSearch');
    const clientId = document.getElementById('clientId');
    const clientResults = document.getElementById('clientResults');
    const clientSearchHint = document.getElementById('clientSearchHint');
    const clientSelected = document.getElementById('clientSelected');
    const clientNoResults = document.getElementById('clientNoResults');
    const clientOptions = [...clientResults.querySelectorAll('.client-option')];
    const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    function clearClientSelection() {
        clientId.value = '';
        clientSelected.hidden = true;
        clientSelected.textContent = '';
        clientSearch.setCustomValidity('');
    }

    function filterClients() {
        clearClientSelection();
        const query = normalize(clientSearch.value.trim());
        let matches = 0;
        clientOptions.forEach((option) => {
            const match = query.length > 0 && normalize(option.dataset.search).includes(query);
            option.hidden = !match;
            if (match) matches++;
        });
        clientResults.hidden = query.length === 0;
        clientNoResults.hidden = query.length === 0 || matches > 0;
        clientSearchHint.hidden = query.length > 0;
        clientSearch.setAttribute('aria-expanded', String(query.length > 0 && matches > 0));
    }

    function chooseClient(option) {
        clientId.value = option.dataset.clientId;
        clientSearch.value = `${option.dataset.name} · ${option.dataset.phone}`;
        clientSelected.textContent = `Cliente seleccionado · ${option.dataset.name} · ${option.dataset.phone} · saldo pendiente Q ${Number(option.dataset.balance).toFixed(2)}`;
        clientSelected.hidden = false;
        clientResults.hidden = true;
        clientSearchHint.hidden = true;
        clientSearch.setAttribute('aria-expanded', 'false');
        clientSearch.setCustomValidity('');
        clientSearch.focus();
    }

    function updateClientMode() {
        const isNew = clientMode.value === 'new';
        const isExisting = clientMode.value === 'existing';
        newClientFields.hidden = !isNew;
        existingClientFields.hidden = !isExisting;
        newClientFields.querySelectorAll('input').forEach((input) => {
            input.disabled = !isNew;
            input.required = isNew && input.hasAttribute('data-required');
        });
        clientSearch.disabled = !isExisting;
        clientSearch.required = isExisting;
        clientId.disabled = !isExisting;
        if (!isExisting) {
            clientSearch.value = '';
            clearClientSelection();
            clientResults.hidden = true;
            clientSearchHint.hidden = false;
        }
    }

    clientMode.addEventListener('change', updateClientMode);
    clientSearch.addEventListener('input', filterClients);
    clientOptions.forEach((option) => option.addEventListener('click', () => chooseClient(option)));
    clientSearch.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            clientResults.hidden = true;
            clientSearch.setAttribute('aria-expanded', 'false');
        }
        if (event.key === 'ArrowDown') {
            clientResults.querySelector('.client-option:not([hidden])')?.focus();
        }
    });
    clientResults.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            clientResults.hidden = true;
            clientSearch.focus();
            clientSearch.setAttribute('aria-expanded', 'false');
        }
    });
    clientMode.closest('form').addEventListener('submit', (event) => {
        if (clientMode.value === 'existing' && !clientId.value) {
            event.preventDefault();
            clientSearch.setCustomValidity('Escribe y selecciona un cliente de la lista.');
            clientSearch.reportValidity();
        }
    });

    clientOptions.forEach((option) => {
        if (option.dataset.clientId === clientId.value) chooseClient(option);
    });
    updateClientMode();
</script>
@endpush
