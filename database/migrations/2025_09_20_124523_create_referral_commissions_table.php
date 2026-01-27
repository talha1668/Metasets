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
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id');
            $table->unsignedBigInteger('referred_id');
            $table->unsignedBigInteger('investment_id')->nullable();
            $table->integer('level');

            $table->decimal('base_amount', 20, 8);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 20, 8);
            $table->string('currency', 10)->default('USD');

            $table->enum('type', ['deposit', 'investment_profit', 'reinvestment']);
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->text('description')->nullable();
            $table->json('calculation_details')->nullable();
            $table->timestamps();

            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('referred_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('investment_id')->references('id')->on('user_investments')->onDelete('set null');

            $table->index(['referrer_id', 'status']);
            $table->index(['level', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
    }
};
