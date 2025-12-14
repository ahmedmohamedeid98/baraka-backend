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
        Schema::create('smart_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('original_text'); // User's original text input
            $table->json('parsed_items'); // AI parsed items
            $table->decimal('total_price', 10, 2)->default(0);
            $table->integer('total_items')->default(0);
            $table->boolean('is_favorite')->default(false);
            $table->string('name')->nullable(); // User can name the order for quick reuse
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('is_favorite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smart_orders');
    }
};
