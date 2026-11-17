<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->timestamp('quoted_at')->nullable();
            $table->json('quote_data')->nullable();
        });

        Schema::table('order_parts', function (Blueprint $table) {
            $table->unsignedInteger('inventory_deducted_quantity')->default(0);
        });

        DB::table('order_parts')->update([
            'inventory_deducted_quantity' => DB::raw('quantity'),
        ]);
    }

    public function down(): void
    {
        Schema::table('order_parts', function (Blueprint $table) {
            $table->dropColumn('inventory_deducted_quantity');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['quoted_at', 'quote_data']);
        });
    }
};
