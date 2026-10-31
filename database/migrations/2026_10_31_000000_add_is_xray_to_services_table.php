<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('is_xray')->default(false)->index();
        });

        DB::table('services')
            ->join('categories', 'services.category_id', '=', 'categories.id')
            ->select('services.id', 'services.name as service_name', 'categories.name as category_name')
            ->orderBy('services.id')
            ->chunk(200, function ($services): void {
                foreach ($services as $service) {
                    $isXray = preg_match('/\bx[\s-]?rays?\b/i', $service->service_name)
                        || preg_match('/\bx[\s-]?rays?\b/i', $service->category_name);

                    if ($isXray) {
                        DB::table('services')->where('id', $service->id)->update(['is_xray' => true]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['is_xray']);
            $table->dropColumn('is_xray');
        });
    }
};
