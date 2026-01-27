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
        Schema::create('user_stakes', function (Blueprint $table) {
            $table->id();
            $table->string('stake_id', 20)->unique();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('pool_id')->nullable();

            $table->decimal('stake_amount', 20, 8);
            $table->decimal('current_balance', 20, 8);
            $table->decimal('total_rewards', 20, 8)->default(0);

            $table->decimal('apy_rate', 8, 4);
            $table->timestamp('staked_at');
            $table->timestamp('unlock_at')->nullable();

            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->boolean('auto_compound')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['pool_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stakes');
    }
};
