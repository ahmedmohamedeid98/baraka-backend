<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->string('image');
            $table->enum('type', ['slider', 'promotional', 'category', 'vendor'])->default('slider');
            $table->string('link_type')->nullable(); // product, category, vendor, url
            $table->unsignedBigInteger('link_id')->nullable();
            $table->string('link_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('is_active');
            $table->index('type');
            $table->index('sort_order');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('body_ar');
            $table->text('body_en')->nullable();
            $table->string('type')->nullable(); // order, promotion, general
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('banners');
    }
};
