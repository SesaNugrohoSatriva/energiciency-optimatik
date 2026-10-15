<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('component_sku', 100)->nullable();
            $table->string('component_name', 180);
            $table->string('unit', 30);
            $table->decimal('required_quantity', 16, 5);
            $table->decimal('reserved_quantity', 16, 5)->default(0);
            $table->decimal('consumed_quantity', 16, 5)->default(0);
            $table->decimal('unit_cost_snapshot', 14, 2)->default(0);
            $table->string('status', 20)->default('unmapped');
            $table->timestamps();
            $table->index(['production_batch_id', 'status']);
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('production_batch_id')->nullable()->constrained('production_batches')->nullOnDelete();
            $table->foreignId('batch_material_id')->nullable()->constrained('batch_materials')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_type', 20);
            $table->decimal('quantity', 16, 5);
            $table->decimal('unit_cost_snapshot', 14, 2);
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['inventory_item_id', 'created_at']);
            $table->index(['production_batch_id', 'transaction_type']);
        });

        $now = now();
        foreach (DB::table('inventory_items')->orderBy('id')->get(['id', 'quantity', 'unit_cost']) as $inventory) {
            DB::table('inventory_transactions')->insert([
                'inventory_item_id' => $inventory->id,
                'transaction_type' => 'opening_balance',
                'quantity' => $inventory->quantity,
                'unit_cost_snapshot' => $inventory->unit_cost,
                'reference' => 'Migration opening balance',
                'created_at' => $now,
            ]);
        }

        Schema::table('energy_records', function (Blueprint $table) {
            $table->foreignId('production_batch_id')->nullable()->constrained('production_batches')->nullOnDelete();
            $table->foreignId('routing_operation_id')->nullable()->constrained('routing_operations')->nullOnDelete();
            $table->index(['production_batch_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('energy_records', function (Blueprint $table) {
            $table->dropIndex(['production_batch_id', 'recorded_at']);
            $table->dropConstrainedForeignId('routing_operation_id');
            $table->dropConstrainedForeignId('production_batch_id');
        });
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('batch_materials');
    }
};
