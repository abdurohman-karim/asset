<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'currency_code')) {
                $table->string('currency_code', 16)->nullable()->after('amount')->index();
            }
        });

        Schema::table('goals', function (Blueprint $table) {
            if (!Schema::hasColumn('goals', 'currency_code')) {
                $table->string('currency_code', 16)->nullable()->after('amount_saved')->index();
            }
        });

        Schema::table('goal_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('goal_payments', 'currency_code')) {
                $table->string('currency_code', 16)->nullable()->after('amount')->index();
            }
        });

        Schema::table('budgets', function (Blueprint $table) {
            if (!Schema::hasColumn('budgets', 'currency_code')) {
                $table->string('currency_code', 16)->nullable()->after('month');
            }
        });

        DB::table('transactions')->whereNull('currency_code')->update(['currency_code' => 'UZS']);
        DB::table('goals')->whereNull('currency_code')->update(['currency_code' => 'UZS']);
        DB::table('goal_payments')->whereNull('currency_code')->update(['currency_code' => 'UZS']);
        DB::table('budgets')->whereNull('currency_code')->update(['currency_code' => 'UZS']);

        Schema::table('budgets', function (Blueprint $table) {
            $table->string('currency_code', 16)->nullable(false)->change();
            $table->dropUnique('budgets_user_id_month_unique');
            $table->unique(['user_id', 'month', 'currency_code'], 'budgets_user_id_month_currency_code_unique');
            $table->index('currency_code');
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique('budgets_user_id_month_currency_code_unique');
            $table->dropIndex(['currency_code']);
            $table->unique(['user_id', 'month'], 'budgets_user_id_month_unique');
            $table->dropColumn('currency_code');
        });

        Schema::table('goal_payments', function (Blueprint $table) {
            $table->dropIndex(['currency_code']);
            $table->dropColumn('currency_code');
        });

        Schema::table('goals', function (Blueprint $table) {
            $table->dropIndex(['currency_code']);
            $table->dropColumn('currency_code');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['currency_code']);
            $table->dropColumn('currency_code');
        });
    }
};
