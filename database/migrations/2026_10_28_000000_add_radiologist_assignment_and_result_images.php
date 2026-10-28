<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_results', function (Blueprint $table) {
            $table->foreignId('radiologist_id')
                ->nullable()
                ->after('reported_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('bill_result_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 127)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_result_images');

        Schema::table('bill_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('radiologist_id');
        });
    }
};
