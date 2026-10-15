<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oee_records', function (Blueprint $table) {
            $table->foreignId('production_batch_id')->nullable()->constrained('production_batches')->nullOnDelete();
            $table->foreignId('routing_operation_id')->nullable()->constrained('routing_operations')->nullOnDelete();
            $table->index(['production_batch_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::table('oee_records', function (Blueprint $table) {
            $table->dropIndex(['production_batch_id', 'recorded_on']);
            $table->dropConstrainedForeignId('routing_operation_id');
            $table->dropConstrainedForeignId('production_batch_id');
        });
    }
};
