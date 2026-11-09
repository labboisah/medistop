<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('settled_amount', 12, 2)->nullable();
            $table->string('status')->default('pending');
            $table->text('staff_note')->nullable();
            $table->text('settlement_note')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'approver_id']);
            $table->index('settled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_claims');
    }
};
