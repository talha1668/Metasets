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
        Schema::create('investment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description');
            $table->json('features')->nullable();

            $table->decimal('min_amount', 20, 8);
            $table->decimal('max_amount', 20, 8);
            $table->decimal('interest_rate', 8, 4);
            $table->enum('interest_type', ['fixed', 'compound'])->default('fixed');
            $table->enum('return_type', ['hourly', 'daily', 'weekly', 'monthly'])->default('daily');
            $table->integer('duration_days');
            $table->enum('principal_return', ['yes', 'no', 'after_maturity'])->default('yes');

            $table->boolean('auto_reinvest_available')->default(false);
            $table->decimal('auto_reinvest_rate', 5, 2)->default(0);
            $table->boolean('partial_withdrawal_allowed')->default(false);
            $table->decimal('early_withdrawal_fee', 5, 2)->default(0);

            $table->integer('max_investors')->default(0);
            $table->integer('current_investors')->default(0);
            $table->decimal('total_cap', 20, 8)->default(0);
            $table->decimal('current_invested', 20, 8)->default(0);

            $table->enum('risk_level', ['low', 'medium', 'high'])->default('medium');
            $table->json('ai_recommendation_score')->nullable();
            $table->decimal('success_rate', 5, 2)->default(0);

            $table->string('color_scheme', 7)->default('#3b82f6');
            $table->json('badges')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('featured')->default(false);

            $table->enum('status', ['active', 'inactive', 'coming_soon'])->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->decimal('total_profit_paid', 20, 8)->default(0);
            $table->integer('total_completed_investments')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_plans');
    }
};
