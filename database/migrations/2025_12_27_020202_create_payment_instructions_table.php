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
        Schema::create('payment_instructions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->text('instruction_en');
            $table->text('instruction_ar');
            $table->integer('font_size')->default(14); // in px
            $table->boolean('is_bold')->default(false);
            $table->string('color')->default('#000000'); // hex color
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_instructions');
    }
};
