<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('place_bets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('transaction_id')->unique();
            $table->string('member_account');
            $table->integer('product_code');
            $table->decimal('amount', 20, 4);
            $table->string('action');
            $table->string('status')->default('pending');
            $table->json('meta')->nullable();
            $table->string('wager_status')->nullable();
            $table->string('round_id')->nullable();
            $table->string('game_type')->nullable();
            $table->string('channel_code')->nullable();
            $table->bigInteger('settled_at')->nullable();
            $table->bigInteger('created_at_provider')->nullable();
            $table->string('currency')->nullable();
            $table->string('game_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_bets');
    }
}; 