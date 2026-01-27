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
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('level');
            $table->decimal('commission_rate', 5, 2);
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('fixed_amount', 10, 2)->default(0);
            $table->enum('applies_to', ['deposit', 'profit', 'both'])->default('deposit');
            $table->integer('min_referrals_required')->default(0);
            $table->decimal('min_investment_required', 20, 8)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_settings');
    }
};
