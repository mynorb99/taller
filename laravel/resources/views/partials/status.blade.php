@php
    $labels = [
        'reception' => ['Cotización pendiente', 'steel'],
        'quoted' => ['Cotización creada', 'amber'],
        'repairing' => ['En reparación', 'blue'],
        'ready' => ['Listo', 'amber'],
        'completed' => ['Finalizada', 'green'],
    ];
    [$statusLabel, $statusColor] = $labels[$status] ?? ['Desconocido', 'steel'];
@endphp
<span class="badge {{ $statusColor }}">{{ $statusLabel }}</span>
