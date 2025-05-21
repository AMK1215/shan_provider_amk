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
        Schema::create('game_lists', function (Blueprint $table) {
            $table->id();
            $table->string('game_code');
            $table->string('game_name');
            $table->string('game_type');
            $table->string('image_url');
            $table->unsignedBigInteger('product_id');
            $table->integer('product_code');
            $table->string('support_currency');
            $table->string('status');
            $table->boolean('is_active')->default(true);
            $table->string('provider')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_lists');
    }
}; 