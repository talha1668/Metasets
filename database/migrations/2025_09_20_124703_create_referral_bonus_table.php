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
        Schema::create('referral_bonus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description');
            $table->enum('type', ['milestone', 'achievement', 'loyalty']);

            $table->integer('min_active_referrals')->default(0);
            $table->decimal('min_team_investment', 20, 8)->default(0);
            $table->integer('time_frame_days')->default(0);

            $table->enum('reward_type', ['fixed_amount', 'percentage', 'upgrade']);
            $table->decimal('reward_amount', 20, 8)->default(0);
            $table->string('reward_details')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_recurring')->default(false);
            $table->integer('max_claims_per_user')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_bonus');
    }
};
