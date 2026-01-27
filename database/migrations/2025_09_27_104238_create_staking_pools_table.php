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
        Schema::create('staking_pools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token_symbol', 10);
            $table->text('description');

            $table->decimal('min_stake_amount', 20, 8);
            $table->decimal('max_stake_amount', 20, 8)->nullable();
            $table->decimal('apy_rate', 8, 4);
            $table->integer('lock_days');

            $table->decimal('total_pool_size', 20, 8)->default(0);
            $table->decimal('current_staked', 20, 8)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('auto_compound')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staking_pools');
    }
};
