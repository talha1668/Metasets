<?php

use App\Enums\Wallet\Status;
use App\Enums\Wallet\Type;
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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('address')->unique();
            $table->decimal('balance', 20, 8)->default(0);
            $table->decimal('bonus_balance', 20, 8)->default(0);
            $table->decimal('total_invested', 20, 8)->default(0);
            $table->decimal('total_withdrawn', 20, 8)->default(0);
            $table->decimal('total_earnings', 20, 8)->default(0);
            $table->decimal('total_referral_bonus', 20, 8)->default(0);
            $table->enum('status', ['inactive', 'active', 'locked'])->default('active')->index();
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
