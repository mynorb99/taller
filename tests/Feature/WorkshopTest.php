<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_the_login_page(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_sign_in_with_username_and_password(): void
    {
        User::create([
            'name' => 'Taller Admin',
            'username' => 'admin',
            'email' => 'admin@taller.local',
            'role' => 'Jefe de Taller',
            'password' => 'taller123',
        ]);

        $this->post(route('login.store'), [
            'username' => 'admin',
            'password' => 'taller123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_order_part_stock_and_payment_are_persisted_together(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::create([
            'name' => 'Filtro hidráulico',
            'category' => 'Filtros',
            'cost' => 10,
            'price' => 25,
            'stock' => 5,
            'minimum_stock' => 1,
        ]);
        $order = WorkOrder::create([
            'code' => 'OT-0001',
            'client' => 'Cliente de prueba',
            'phone' => '5551234',
            'equipment' => 'Excavadora',
            'repair_type' => 'Diagnóstico',
            'notes' => 'No enciende',
            'status' => 'reception',
            'received_at' => now(),
        ]);

        $this->post(route('orders.parts.store', $order), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertSessionHasNoErrors();
        $this->post(route('orders.parts.store', $order), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertSessionHasNoErrors();
        $this->post(route('orders.labor.store', $order), [
            'description' => 'Instalación',
            'amount' => 30,
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, $product->fresh()->stock);
        $this->patch(route('orders.status', $order), ['status' => 'repairing'])
            ->assertSessionHasErrors('status');
        $this->post(route('orders.quote.store', $order))
            ->assertSessionHasNoErrors();
        $this->assertSame('quoted', $order->fresh()->status);
        $this->assertEquals(105, $order->fresh()->quote_data['total']);
        $this->assertSame('Excavadora', $order->fresh()->quote_data['equipment']);
        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Cotización · OT-0001')
            ->assertSee('No enciende')
            ->assertSee('Total cotizado');

        $this->patch(route('orders.status', $order), ['status' => 'repairing'])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, $product->fresh()->stock);
        $this->post(route('orders.payments.store', $order), [
            'amount' => 105,
            'method' => 'Efectivo',
        ])->assertSessionHasNoErrors();

        $this->assertSame('repairing', $order->fresh()->status);
        $this->patch(route('orders.status', $order), ['status' => 'ready'])
            ->assertSessionHasNoErrors();
        $this->patch(route('orders.status', $order), ['status' => 'completed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertDatabaseCount('order_parts', 1);
        $this->assertDatabaseHas('order_parts', [
            'work_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 25,
            'inventory_deducted_quantity' => 3,
        ]);
        $this->assertDatabaseHas('payments', ['work_order_id' => $order->id, 'amount' => 105]);
        $this->assertDatabaseHas('cash_movements', [
            'work_order_id' => $order->id,
            'type' => 'income',
            'amount' => 105,
            'origin' => 'payment',
        ]);
    }

    public function test_order_cannot_start_repair_when_quoted_parts_exceed_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::create([
            'name' => 'Filtro hidráulico',
            'category' => 'Filtros',
            'cost' => 10,
            'price' => 25,
            'stock' => 1,
            'minimum_stock' => 1,
        ]);
        $order = WorkOrder::create([
            'code' => 'OT-0001',
            'client' => 'Cliente de prueba',
            'phone' => '5551234',
            'repair_type' => 'Diagnóstico',
            'status' => 'reception',
            'received_at' => now(),
        ]);

        $this->post(route('orders.parts.store', $order), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertSessionHasNoErrors();
        $this->post(route('orders.quote.store', $order))
            ->assertSessionHasNoErrors();

        $this->from(route('orders.show', $order))
            ->patch(route('orders.status', $order), ['status' => 'repairing'])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors('status');

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame('quoted', $order->fresh()->status);
        $this->assertDatabaseCount('order_parts', 1);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_creating_an_order_for_a_new_client_registers_the_client(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('orders.store'), [
            'client_mode' => 'new',
            'client_name' => 'Cliente nuevo',
            'client_phone' => '5559000',
            'repair_type' => 'Diagnóstico',
            'equipment' => 'Compresor',
        ]);

        $client = Client::where('phone', '5559000')->firstOrFail();
        $order = WorkOrder::where('client_id', $client->id)->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame('Cliente nuevo', $client->name);
        $this->assertSame($client->id, $order->registeredClient->id);
    }

    public function test_existing_client_orders_show_the_customer_balance_and_history(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::create([
            'name' => 'Cliente existente',
            'phone' => '5557000',
        ]);
        $firstOrder = WorkOrder::create([
            'client_id' => $client->id,
            'code' => 'OT-0101',
            'client' => $client->name,
            'phone' => $client->phone,
            'repair_type' => 'Diagnóstico',
            'status' => 'ready',
            'received_at' => now()->subDay(),
        ]);
        $firstOrder->laborItems()->create(['description' => 'Reparación parcial', 'amount' => 100]);
        $firstOrder->payments()->create(['method' => 'Efectivo', 'amount' => 25, 'paid_at' => now()]);

        $secondOrder = WorkOrder::create([
            'client_id' => $client->id,
            'code' => 'OT-0102',
            'client' => $client->name,
            'phone' => $client->phone,
            'repair_type' => 'Soldadura',
            'status' => 'repairing',
            'received_at' => now(),
        ]);
        $secondOrder->laborItems()->create(['description' => 'Soldadura', 'amount' => 50]);

        $this->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('Q 125.00')
            ->assertSee('OT-0101')
            ->assertSee('OT-0102');

        $this->post(route('orders.store'), [
            'client_mode' => 'existing',
            'client_id' => $client->id,
            'repair_type' => 'Mantenimiento preventivo',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Client::where('phone', '5557000')->count());
        $this->assertSame(3, $client->workOrders()->count());
    }

    public function test_order_row_points_to_its_detail_page(): void
    {
        $this->actingAs(User::factory()->create());
        $order = WorkOrder::create([
            'code' => 'OT-0001',
            'client' => 'Cliente de prueba',
            'phone' => '5551234',
            'repair_type' => 'Diagnóstico',
            'status' => 'reception',
            'received_at' => now(),
        ]);

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertSee('class="clickable-row"', false)
            ->assertSee('data-order-url="'.route('orders.show', $order).'"', false);
    }

    public function test_quotes_page_lists_drafts_and_created_quotes_but_not_repairs(): void
    {
        $this->actingAs(User::factory()->create());
        $draft = WorkOrder::create([
            'code' => 'OT-0201',
            'client' => 'Cotización pendiente',
            'phone' => '5551201',
            'repair_type' => 'Diagnóstico',
            'status' => 'reception',
            'received_at' => now(),
        ]);
        $quoted = WorkOrder::create([
            'code' => 'OT-0202',
            'client' => 'Cotización creada',
            'phone' => '5551202',
            'repair_type' => 'Soldadura',
            'status' => 'quoted',
            'received_at' => now(),
            'quoted_at' => now(),
            'quote_data' => ['total' => 80],
        ]);
        WorkOrder::create([
            'code' => 'OT-0203',
            'client' => 'Reparación iniciada',
            'phone' => '5551203',
            'repair_type' => 'Cambio de piezas',
            'status' => 'repairing',
            'received_at' => now(),
        ]);

        $this->get(route('quotes.index'))
            ->assertOk()
            ->assertSee('Cotizaciones')
            ->assertSee($draft->code)
            ->assertSee($quoted->code)
            ->assertDontSee('OT-0203');
    }
}
