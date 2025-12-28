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
        Schema::table('payment_instructions', function (Blueprint $table) {
            $table->boolean('is_copyable')->default('false')->after('color');
            $table->string('placeholder')->nullable()->after('is_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_instructions', function (Blueprint $table) {
            $table->dropColumn('placeholder');
            $table->dropColumn('is_copyable');
        });
    }
};
