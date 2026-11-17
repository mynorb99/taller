@extends('layouts.app')
@section('title', 'Registrar movimiento')
@section('heading', 'Movimiento de caja')
@section('subtitle', 'Registrar un ingreso o un gasto')

@section('content')
    <form class="form-card stack" method="POST" action="{{ route('cash.store') }}">
        @csrf
        <div class="form-grid">
            <label>Tipo *
                <select class="input" name="type" id="movementType">
                    <option value="income">Ingreso (+)</option><option value="expense" @selected(old('type') === 'expense')>Gasto (−)</option>
                </select>
            </label>
            <label>Categoría *
                <select class="input" name="category" id="movementCategory"></select>
            </label>
            <label>Concepto *
                <input class="input" name="description" value="{{ old('description') }}" required maxlength="255">
            </label>
            <label>Monto (Q) *
                <input class="input" type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required>
            </label>
            <label>Fecha *
                <input class="input" type="datetime-local" name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" required>
            </label>
        </div>
        <div class="form-actions"><button class="button primary" type="submit">Guardar movimiento</button><a class="button" href="{{ route('cash.index') }}">Cancelar</a></div>
    </form>
@endsection

@push('scripts')
<script>
    const categories = {
        income: @json($incomeCategories),
        expense: @json($expenseCategories)
    };
    const typeInput = document.getElementById('movementType');
    const categoryInput = document.getElementById('movementCategory');
    function updateCategories() {
        const selected = @json(old('category'));
        categoryInput.replaceChildren(...categories[typeInput.value].map((category) => {
            const option = new Option(category, category);
            if (category === selected) option.selected = true;
            return option;
        }));
    }
    typeInput.addEventListener('change', updateCategories);
    updateCategories();
</script>
@endpush
