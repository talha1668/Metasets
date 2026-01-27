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
        Schema::create('user_investments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('investment_id', 20)->unique();
            $table->decimal('amount', 20, 8);
            $table->decimal('expected_return', 20, 8);
            $table->decimal('earned_amount', 20, 8)->default(0);
            $table->decimal('withdrawn_amount', 20, 8)->default(0);
            $table->decimal('remaining_principal', 20, 8)->default(0);

            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('next_return_at')->nullable();
            $table->timestamp('maturity_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->integer('total_returns_expected');
            $table->integer('returns_paid')->default(0);
            $table->decimal('return_amount', 20, 8)->default(0);
            $table->json('return_schedule')->nullable();

            $table->boolean('auto_reinvest')->default(false);
            $table->decimal('reinvest_percentage', 5, 2)->default(0);
            $table->decimal('reinvested_amount', 20, 8)->default(0);

            $table->json('ai_prediction_data')->nullable();
            $table->decimal('ai_confidence_score', 5, 2)->default(0);

            $table->text('notes')->nullable();
            $table->string('source', 50)->default('web');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('investment_plans')->onDelete('cascade');

            $table->index(['user_id', 'status']);
            $table->index(['plan_id', 'status']);
            $table->index('investment_id');
            $table->index(['status', 'next_return_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_investments');
    }
};
