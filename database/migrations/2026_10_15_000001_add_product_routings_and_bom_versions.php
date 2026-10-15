<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->string('base_unit', 30)->default('pcs');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('estimated_production_minutes')->nullable();
            $table->decimal('estimated_energy_kwh', 14, 5)->nullable();
        });

        Schema::table('machines', function (Blueprint $table) {
            $table->string('location')->nullable();
            $table->decimal('capacity', 14, 3)->nullable();
            $table->string('capacity_unit', 30)->nullable();
        });

        Schema::table('processes', function (Blueprint $table) {
            $table->unsignedInteger('setup_time_minutes')->default(0);
            $table->decimal('ideal_cycle_seconds', 12, 4)->nullable();
            $table->unsignedSmallInteger('labor_required')->default(1);
            $table->decimal('capacity', 14, 3)->nullable();
            $table->string('capacity_unit', 30)->nullable();
            $table->text('work_instructions')->nullable();
            $table->text('quality_criteria')->nullable();
        });

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

        Schema::table('bom_items', function (Blueprint $table) {
            $table->foreignId('bom_id')->nullable()->after('product_id')->constrained('boms')->nullOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->after('bom_id')->constrained('inventory_items')->nullOnDelete();
            $table->decimal('scrap_allowance_percent', 7, 3)->default(0);
        });

        Schema::create('product_routings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('name', 160);
            $table->string('status', 20)->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'version']);
            $table->index(['product_id', 'status']);
        });

        Schema::create('routing_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_routing_id')->constrained('product_routings')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->foreignId('process_id')->constrained()->restrictOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->unsignedInteger('setup_time_minutes')->default(0);
            $table->decimal('ideal_cycle_seconds', 12, 4)->nullable();
            $table->unsignedSmallInteger('labor_required')->default(1);
            $table->decimal('estimated_energy_kwh', 14, 5)->default(0);
            $table->text('work_instructions')->nullable();
            $table->boolean('requires_quality_inspection')->default(false);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
            $table->unique(['product_routing_id', 'sequence']);
            $table->index(['product_routing_id', 'process_id']);
        });

        $now = now();
        foreach (DB::table('products')->orderBy('id')->get(['id', 'sku']) as $product) {
            $bomId = DB::table('boms')->insertGetId([
                'product_id' => $product->id,
                'bom_number' => $product->sku.'-BOM-001',
                'version' => 1,
                'status' => 'active',
                'output_quantity' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('bom_items')->where('product_id', $product->id)->update(['bom_id' => $bomId]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_operations');
        Schema::dropIfExists('product_routings');
        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_item_id');
            $table->dropConstrainedForeignId('bom_id');
            $table->dropColumn('scrap_allowance_percent');
        });
        Schema::dropIfExists('boms');
        Schema::table('processes', function (Blueprint $table) {
            $table->dropColumn(['setup_time_minutes','ideal_cycle_seconds','labor_required','capacity','capacity_unit','work_instructions','quality_criteria']);
        });
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['location','capacity','capacity_unit']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['description','base_unit','is_active','estimated_production_minutes','estimated_energy_kwh']);
        });
    }
};
