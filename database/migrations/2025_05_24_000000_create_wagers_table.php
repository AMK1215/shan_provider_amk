<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('wagers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('member_account');
            $table->string('round_id');
            $table->string('currency');
            $table->unsignedBigInteger('provider_id')->default(0);
            $table->unsignedBigInteger('provider_line_id')->default(0);
            $table->unsignedBigInteger('provider_product_id')->default(0);
            $table->unsignedBigInteger('provider_product_oid')->default(0);
            $table->string('game_type');
            $table->string('game_code');
            $table->decimal('valid_bet_amount', 16, 4)->default(0.0);
            $table->decimal('bet_amount', 16, 4)->default(0.0);
            $table->decimal('prize_amount', 16, 4)->default(0.0);
            $table->string('status')->default('BET');
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('settled_at')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('wagers');
    }
};
