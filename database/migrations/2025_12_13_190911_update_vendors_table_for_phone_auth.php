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
        Schema::table('vendors', function (Blueprint $table) {
            // Make email nullable and remove unique constraint
            $table->dropUnique(['email']);
            $table->string('email')->nullable()->change();
            
            // Make phone unique and not nullable
            $table->string('phone', 20)->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Revert changes
            $table->dropUnique(['phone']);
            $table->string('phone', 20)->nullable()->change();
            
            $table->string('email')->nullable(false)->unique()->change();
        });
    }
};
