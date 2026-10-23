<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_devices', function (Blueprint $table) {
            $table->id();
            $table->string('mac_address', 17)->unique()->comment('Alamat MAC ESP32, format AA:BB:CC:DD:EE:FF');
            $table->string('device_id', 120)->unique()->comment('Label unik perangkat, dikirim ke kolom device_id telemetri');
            $table->string('machine_code', 50)->nullable()->comment('FK ke machines.code, nullable');
            $table->unsignedInteger('send_interval_ms')->default(30000)->comment('Interval kirim data dalam milidetik');
            $table->string('notes')->nullable()->comment('Keterangan bebas');
            $table->timestamp('last_seen_at')->nullable()->comment('Waktu terakhir perangkat fetch config');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('machine_code')->references('code')->on('machines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_devices');
    }
};
