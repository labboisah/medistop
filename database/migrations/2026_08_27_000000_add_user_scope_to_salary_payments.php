<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropUnique('salary_payments_salary_month_unique');
            $table->unique(['salary_month', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropUnique('salary_payments_salary_month_user_id_unique');
            $table->unique('salary_month');
        });
    }
};
