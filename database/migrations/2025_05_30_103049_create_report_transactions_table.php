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
        Schema::create('report_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('game_type_id');
            $table->decimal('rate')->default(0);
            $table->decimal('transaction_amount', 12);
            $table->decimal('bet_amount', 12)->nullable();
            $table->decimal('valid_amount', 12)->nullable();
            $table->string('status')->default('0');
            $table->string('final_turn')->default('0');
            $table->string('banker')->default('0');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('game_type_id')->references('id')->on('game_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_transactions');
    }
};
