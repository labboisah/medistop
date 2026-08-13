<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('designation')->nullable()->after('role')->constrained('designations')->nullOnDelete();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('patient_name');
            $table->string('age')->nullable()->after('gender');
        });



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('designation');
        });
    }
};
