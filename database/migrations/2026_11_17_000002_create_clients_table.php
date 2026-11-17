<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->index();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        DB::table('work_orders')
            ->select(['id', 'client', 'phone'])
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    $clientId = DB::table('clients')->where('phone', $order->phone)->value('id');
                    if (! $clientId) {
                        $clientId = DB::table('clients')->insertGetId([
                            'name' => $order->client,
                            'phone' => $order->phone,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('work_orders')->where('id', $order->id)->update(['client_id' => $clientId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
        Schema::dropIfExists('clients');
    }
};
