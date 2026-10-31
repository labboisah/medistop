<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('route_name', 160)->default('unmatched');
            $table->string('method', 10);
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration_ms');
            $table->unsignedSmallInteger('query_count')->default(0);
            $table->unsignedInteger('database_time_ms')->default(0);
            $table->unsignedSmallInteger('slow_query_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['route_name', 'created_at']);
            $table->index('duration_ms');
            $table->index('status_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
    }
};
