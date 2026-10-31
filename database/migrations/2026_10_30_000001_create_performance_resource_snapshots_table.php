<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_resource_snapshots', function (Blueprint $table) {
            $table->id();
            $table->decimal('cpu_load_percent', 6, 2)->nullable();
            $table->unsignedBigInteger('memory_used_bytes')->nullable();
            $table->unsignedBigInteger('memory_total_bytes')->nullable();
            $table->decimal('network_in_bytes_per_second', 14, 2)->nullable();
            $table->decimal('network_out_bytes_per_second', 14, 2)->nullable();
            $table->unsignedBigInteger('disk_free_bytes')->nullable();
            $table->unsignedBigInteger('disk_total_bytes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_resource_snapshots');
    }
};
