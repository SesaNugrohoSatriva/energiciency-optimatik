<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('bom_id')->nullable()->constrained('boms')->nullOnDelete();
            $table->foreignId('product_routing_id')->nullable()->constrained('product_routings')->nullOnDelete();
            $table->json('bom_snapshot')->nullable();
            $table->json('routing_snapshot')->nullable();
            $table->unsignedInteger('baseline_actual_quantity')->default(0);
            $table->unsignedInteger('baseline_good_quantity')->default(0);
        });

        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->restrictOnDelete();
            $table->string('batch_number', 100)->unique();
            $table->unsignedInteger('planned_quantity');
            $table->unsignedInteger('actual_quantity')->default(0);
            $table->unsignedInteger('good_quantity')->default(0);
            $table->unsignedInteger('rejected_quantity')->default(0);
            $table->string('status', 20)->default('planned');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('bom_snapshot')->nullable();
            $table->json('routing_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['production_order_id', 'status']);
        });

        Schema::create('production_batch_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 30);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['production_batch_id', 'created_at']);
        });

        foreach (DB::table('production_orders')->orderBy('id')->get(['id', 'product_id', 'actual_quantity', 'good_quantity']) as $order) {
            $bom = DB::table('boms')
                ->where('product_id', $order->product_id)
                ->where('status', 'active')
                ->first();
            $routing = DB::table('product_routings')
                ->where('product_id', $order->product_id)
                ->where('status', 'active')
                ->first();

            $changes = [
                'baseline_actual_quantity' => $order->actual_quantity,
                'baseline_good_quantity' => $order->good_quantity,
            ];
            if ($bom) {
                $changes['bom_id'] = $bom->id;
                $changes['bom_snapshot'] = json_encode([
                    'id' => $bom->id,
                    'bom_number' => $bom->bom_number,
                    'version' => $bom->version,
                    'output_quantity' => $bom->output_quantity,
                    'items' => DB::table('bom_items')->where('bom_id', $bom->id)->orderBy('id')->get()->map(fn ($item) => (array) $item)->all(),
                ], JSON_THROW_ON_ERROR);
            }
            if ($routing) {
                $changes['product_routing_id'] = $routing->id;
                $changes['routing_snapshot'] = json_encode([
                    'id' => $routing->id,
                    'name' => $routing->name,
                    'version' => $routing->version,
                    'operations' => DB::table('routing_operations')->where('product_routing_id', $routing->id)->orderBy('sequence')->get()->map(fn ($operation) => (array) $operation)->all(),
                ], JSON_THROW_ON_ERROR);
            }
            if ($changes !== []) {
                DB::table('production_orders')->where('id', $order->id)->update($changes);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_events');
        Schema::dropIfExists('production_batches');
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_routing_id');
            $table->dropConstrainedForeignId('bom_id');
            $table->dropColumn(['bom_snapshot', 'routing_snapshot', 'baseline_actual_quantity', 'baseline_good_quantity']);
        });
    }
};
