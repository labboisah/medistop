<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_claims', function (Blueprint $table) {
            $table->date('commission_date')->nullable()->after('approver_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('commission_claims', function (Blueprint $table) {
            $table->dropIndex(['commission_date']);
            $table->dropColumn('commission_date');
        });
    }
};
