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
        Schema::table('areas', function (Blueprint $table) {
            $table->decimal('from_latitude', 10, 8)->nullable()->after('delivery_fee');
            $table->decimal('to_latitude', 10, 8)->nullable()->after('from_latitude');
            $table->decimal('from_longitude', 11, 8)->nullable()->after('to_latitude');
            $table->decimal('to_longitude', 11, 8)->nullable()->after('from_longitude');
            
            $table->index(['from_latitude', 'to_latitude', 'from_longitude', 'to_longitude'], 'area_boundaries_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropIndex('area_boundaries_index');
            $table->dropColumn(['from_latitude', 'to_latitude', 'from_longitude', 'to_longitude']);
        });
    }
};
