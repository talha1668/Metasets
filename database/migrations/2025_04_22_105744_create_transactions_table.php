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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 50)->unique()->index();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', [
                'deposit',
                'withdrawal',
                'transfer',
                'credit',
                'debit',
                'bonus',
                'stake',
                'unstake',
                'staking_reward',
            ]);
            $table->decimal('amount', 20, 8);
            $table->decimal('fee', 20, 8)->default(0);
            $table->decimal('post_balance', 20, 8);
            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'cancelled'
            ])->default('pending');
            $table->text('details')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
