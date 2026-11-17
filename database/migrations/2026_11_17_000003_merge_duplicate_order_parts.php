<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('order_parts')
            ->select(['work_order_id', 'product_id', 'unit_price'])
            ->whereNotNull('product_id')
            ->groupBy('work_order_id', 'product_id', 'unit_price')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $parts = DB::table('order_parts')
                ->where('work_order_id', $duplicate->work_order_id)
                ->where('product_id', $duplicate->product_id)
                ->where('unit_price', $duplicate->unit_price)
                ->orderBy('id')
                ->get();
            $primary = $parts->first();

            DB::table('order_parts')->where('id', $primary->id)->update([
                'quantity' => $parts->sum('quantity'),
                'updated_at' => now(),
            ]);
            DB::table('order_parts')->whereIn('id', $parts->skip(1)->pluck('id'))->delete();
        }

        Schema::table('order_parts', function (Blueprint $table) {
            $table->unique(
                ['work_order_id', 'product_id', 'unit_price'],
                'order_parts_order_product_price_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('order_parts', function (Blueprint $table) {
            $table->dropUnique('order_parts_order_product_price_unique');
        });
    }
};
