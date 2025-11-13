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
        Schema::create('goal_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goal_id')->index();
            $table->decimal('amount', 18, 2);
            $table->string('method')->default('manual');
            // manual | smart_save | auto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_payments');
    }
};
