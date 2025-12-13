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
        Schema::table('packages', function (Blueprint $table) {
            // Percentage tiers for percentage-based packages
            // Example: [{"min": 0, "max": 1000, "percentage": 5}, {"min": 1000, "max": 3000, "percentage": 3}, {"min": 3000, "max": null, "percentage": 1}]
            $table->json('percentage_tiers')->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('percentage_tiers');
        });
    }
};
