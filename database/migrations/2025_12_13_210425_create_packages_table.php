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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->text('description_ar')->nullable();
            
            // Pricing type: 'fixed' or 'percentage'
            $table->enum('pricing_type', ['fixed', 'percentage'])->default('fixed');
            
            // For fixed: monthly subscription fee
            // For percentage: percentage from each order
            $table->decimal('price', 10, 2)->default(0);
            
            // Duration in days (30 = monthly, 90 = quarterly, 365 = yearly)
            $table->integer('duration_days')->default(30);
            
            // Features/benefits (JSON array)
            $table->json('features')->nullable();
            
            // Limits
            $table->integer('max_products')->nullable(); // null = unlimited
            $table->integer('max_orders_per_month')->nullable(); // null = unlimited
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('is_active');
            $table->index('pricing_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
