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
        Schema::create('staking_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('stake_id')->nullable();

            $table->decimal('reward_amount', 20, 8);
            $table->date('reward_date');
            $table->enum('status', ['pending', 'paid', 'compounded'])->default('pending');

            $table->timestamps();

            $table->index(['user_id', 'reward_date']);
            $table->index(['stake_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staking_rewards');
    }
};
