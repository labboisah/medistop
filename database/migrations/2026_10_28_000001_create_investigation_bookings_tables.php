<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->string('patient_name');
            $table->string('phone', 30);
            $table->string('gender')->nullable();
            $table->string('age')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('bill_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('investigation_booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('investigation_bookings')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_booking_items');
        Schema::dropIfExists('investigation_bookings');
    }
};
