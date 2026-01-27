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
        Schema::create('user_referral_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('bonus_id');
            $table->enum('status', ['earned', 'claimed'])->default('earned');
            $table->decimal('reward_amount', 20, 8);
            $table->timestamp('earned_at');
            $table->timestamp('claimed_at')->nullable();
            $table->json('achievement_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_referral_achievements');
    }
};
