<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batch_cost_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 20);
            $table->decimal('amount', 14, 2);
            $table->string('description', 180);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['production_batch_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_cost_entries');
    }
};
