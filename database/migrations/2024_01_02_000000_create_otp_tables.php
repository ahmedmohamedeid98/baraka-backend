<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('code', 10);
            $table->enum('method', ['whatsapp', 'sms'])->default('whatsapp');
            $table->boolean('verified')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            $table->index(['phone', 'code']);
            $table->index('expires_at');
        });

        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->enum('type', ['otp', 'order_notification', 'other'])->default('other');
            $table->text('message');
            $table->string('status')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
            
            $table->index('phone');
            $table->index('type');
        });

        Schema::create('firebase_sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('verification_id')->nullable();
            $table->string('status')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
            
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firebase_sms_logs');
        Schema::dropIfExists('whatsapp_logs');
        Schema::dropIfExists('otp_verifications');
    }
};
