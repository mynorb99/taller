<?php

namespace Database\Seeders;

use App\Models\CashMovement;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = Str::random(32);
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador',
                'email' => 'admin@taller.local',
                'role' => 'Jefe de Taller',
                'password' => Hash::make($adminPassword),
            ],
        );
        if ($admin->wasRecentlyCreated) {
            $this->command?->info("Contraseña inicial de admin (guárdala ahora): {$adminPassword}");
        }

        foreach ([
            ['Engranaje helicoidal 24T', 'Engranajes', 120, 185, 8, 3],
            ['Manguera hidráulica 1/2" × 2 m', 'Hidráulica', 45, 78, 14, 5],
            ['Filtro de aceite hidráulico', 'Filtros', 28, 52, 4, 6],
            ['Rodamiento SKF 6205', 'Rodamientos', 15, 32, 22, 8],
            ['Kit de sellos para cilindro', 'Sellos', 38, 65, 2, 4],
            ['Aceite hidráulico ISO 68 (20 L)', 'Lubricantes', 95, 140, 6, 2],
            ['Correa en V B-58', 'Transmisión', 9, 18, 0, 5],
            ['Electrodo E6011 (kg)', 'Consumibles', 6, 12, 11, 4],
        ] as [$name, $category, $cost, $price, $stock, $minimumStock]) {
            Product::firstOrCreate(
                ['name' => $name],
                compact('category', 'cost', 'price', 'stock') + ['minimum_stock' => $minimumStock],
            );
        }

        $products = Product::all()->keyBy('name');
        $samples = [
            [
                'order' => [
                    'code' => 'OT-0001', 'client' => 'Carlos Mendoza · Constructora Del Valle',
                    'phone' => '987 445 210', 'equipment' => 'Excavadora CAT 320D',
                    'repair_type' => 'Mantenimiento correctivo',
                    'notes' => 'Bomba hidráulica con pérdida de presión. Pruebas de funcionamiento satisfactorias.',
                    'status' => 'completed', 'received_at' => now()->subDays(34),
                    'ready_at' => now()->subDays(28), 'completed_at' => now()->subDays(27),
                ],
                'parts' => [
                    ['name' => 'Manguera hidráulica 1/2" × 2 m', 'quantity' => 2],
                    ['name' => 'Filtro de aceite hidráulico', 'quantity' => 1],
                ],
                'labor' => [
                    ['description' => 'Cambio de bomba hidráulica', 'amount' => 450],
                    ['description' => 'Pruebas y calibración en campo', 'amount' => 120],
                ],
                'payments' => [
                    ['amount' => 400, 'method' => 'Transferencia', 'days_ago' => 30],
                    ['amount' => 378, 'method' => 'Efectivo', 'days_ago' => 27],
                ],
            ],
            [
                'order' => [
                    'code' => 'OT-0002', 'client' => 'Rosa Quispe · Agrícola San Pedro',
                    'phone' => '955 102 334', 'equipment' => 'Tractor John Deere 5310',
                    'repair_type' => 'Cambio de piezas',
                    'notes' => 'Caja de cambios con ruido en segunda y tercera. Engranaje principal reemplazado.',
                    'status' => 'ready', 'received_at' => now()->subDays(9), 'ready_at' => now()->subDay(),
                ],
                'parts' => [
                    ['name' => 'Engranaje helicoidal 24T', 'quantity' => 1],
                    ['name' => 'Rodamiento SKF 6205', 'quantity' => 2],
                ],
                'labor' => [['description' => 'Desmontaje y montaje de caja de cambios', 'amount' => 380]],
                'payments' => [['amount' => 300, 'method' => 'Billetera digital', 'days_ago' => 2]],
            ],
            [
                'order' => [
                    'code' => 'OT-0003', 'client' => 'Jorge Salas · Minera El Roble',
                    'phone' => '912 778 450', 'equipment' => 'Volquete Volvo FMX',
                    'repair_type' => 'Rectificado', 'notes' => 'Cigüeñal con desgaste en muñones. Rectificado a sobremedida 0.25.',
                    'status' => 'repairing', 'received_at' => now()->subDays(4),
                ],
                'parts' => [],
                'labor' => [['description' => 'Rectificado de cigüeñal', 'amount' => 520]],
                'payments' => [['amount' => 200, 'method' => 'Transferencia', 'days_ago' => 3]],
            ],
            [
                'order' => [
                    'code' => 'OT-0004', 'client' => 'Ana Torres · Panificadora El Trigal',
                    'phone' => '944 310 887', 'equipment' => 'Horno rotatorio industrial',
                    'repair_type' => 'Diagnóstico', 'notes' => 'Ruido en eje de ventilador. Revisar rodamientos y alineación.',
                    'status' => 'reception', 'received_at' => now()->subDay(),
                ],
                'parts' => [],
                'labor' => [],
                'payments' => [],
            ],
        ];

        foreach ($samples as $sample) {
            $client = Client::firstOrCreate(
                ['phone' => $sample['order']['phone']],
                ['name' => $sample['order']['client']],
            );
            $orderData = $sample['order'] + ['client_id' => $client->id];
            $order = WorkOrder::firstOrCreate(['code' => $sample['order']['code']], $orderData);
            if (! $order->wasRecentlyCreated) {
                continue;
            }

            foreach ($sample['parts'] as $part) {
                $product = $products->get($part['name']);
                $order->parts()->create([
                    'product_id' => $product?->id,
                    'name' => $part['name'],
                    'category' => $product?->category,
                    'quantity' => $part['quantity'],
                    'unit_price' => $product?->price ?? 0,
                ]);
            }
            $order->laborItems()->createMany($sample['labor']);

            foreach ($sample['payments'] as $paymentData) {
                $paidAt = Carbon::now()->subDays($paymentData['days_ago']);
                $payment = $order->payments()->create([
                    'amount' => $paymentData['amount'],
                    'method' => $paymentData['method'],
                    'paid_at' => $paidAt,
                ]);
                CashMovement::create([
                    'work_order_id' => $order->id,
                    'type' => 'income',
                    'category' => 'Abono de cliente',
                    'description' => 'Abono de '.$order->client,
                    'amount' => $payment->amount,
                    'origin' => 'payment',
                    'occurred_at' => $paidAt,
                ]);
            }
        }

        foreach ([
            ['type' => 'expense', 'category' => 'Compra de repuestos', 'description' => 'Compra de repuestos a proveedor', 'amount' => 1250, 'days_ago' => 12],
            ['type' => 'expense', 'category' => 'Alquiler', 'description' => 'Alquiler del taller (mes)', 'amount' => 1800, 'days_ago' => 10],
            ['type' => 'expense', 'category' => 'Electricidad', 'description' => 'Recibo de energía eléctrica', 'amount' => 240, 'days_ago' => 5],
            ['type' => 'expense', 'category' => 'Combustible', 'description' => 'Diésel para pruebas de maquinaria', 'amount' => 180, 'days_ago' => 1],
            ['type' => 'income', 'category' => 'Venta directa', 'description' => 'Venta de aceite usado', 'amount' => 90, 'days_ago' => 1],
        ] as $movement) {
            $daysAgo = $movement['days_ago'];
            unset($movement['days_ago']);
            CashMovement::firstOrCreate(
                ['description' => $movement['description'], 'origin' => 'manual'],
                $movement + ['occurred_at' => now()->subDays($daysAgo)],
            );
        }
    }
}
