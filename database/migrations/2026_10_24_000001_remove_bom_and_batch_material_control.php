<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inventory_transactions')->whereNotNull('production_batch_id')->delete();

        $batchIndexes = collect(Schema::getIndexes('inventory_transactions'))
            ->filter(fn (array $index) => in_array('production_batch_id', $index['columns'], true));

        Schema::table('inventory_transactions', function (Blueprint $table) use ($batchIndexes) {
            foreach ($batchIndexes as $index) {
                $table->dropIndex($index['name']);
            }
            $table->dropConstrainedForeignId('batch_material_id');
            $table->dropConstrainedForeignId('production_batch_id');
        });

        Schema::dropIfExists('batch_materials');

        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropColumn('bom_snapshot');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bom_id');
            $table->dropColumn('bom_snapshot');
        });

        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('boms');
    }

    public function down(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('bom_number', 100)->unique();
            $table->unsignedInteger('version');
            $table->string('status', 20)->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->unsignedInteger('output_quantity')->default(1);
            $table->timestamps();
            $table->unique(['product_id', 'version']);
            $table->index(['product_id', 'status']);
        });

        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bom_id')->nullable()->constrained('boms')->nullOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('component_sku');
            $table->string('component_name');
            $table->decimal('quantity_per_unit', 14, 4);
            $table->decimal('scrap_allowance_percent', 7, 3)->default(0);
            $table->string('unit')->default('pcs');
            $table->decimal('unit_cost', 14, 2);
            $table->string('supplier')->nullable();
            $table->timestamps();
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('bom_id')->nullable()->constrained('boms')->nullOnDelete();
            $table->json('bom_snapshot')->nullable();
        });

        Schema::table('production_batches', function (Blueprint $table) {
            $table->json('bom_snapshot')->nullable();
        });

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

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('production_batch_id')->nullable()->constrained('production_batches')->nullOnDelete();
            $table->foreignId('batch_material_id')->nullable()->constrained('batch_materials')->nullOnDelete();
            $table->index(['production_batch_id', 'transaction_type'], 'inv_tx_batch_type_idx');
        });
    }
};
