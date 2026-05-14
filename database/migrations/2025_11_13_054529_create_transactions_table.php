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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->decimal('amount', 18, 2); // (+ income, - expense)
            $table->string('currency_code', 16)->nullable()->index();
            $table->string('category')->nullable()->index();
            $table->string('description')->nullable();
            $table->jsonb('raw')->nullable();
            // full raw data from bank API or uploaded file
            $table->timestamp('datetime')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
