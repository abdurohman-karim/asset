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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('month', 7); // YYYY-MM
            $table->string('currency_code', 16)->index();
            $table->decimal('income', 18, 2)->default(0);
            $table->decimal('expenses', 18, 2)->default(0);
            $table->decimal('recommended_daily_limit', 18, 2)->default(0);
            $table->jsonb('categories')->nullable();
            $table->unique(['user_id', 'month', 'currency_code'], 'budgets_user_id_month_currency_code_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
