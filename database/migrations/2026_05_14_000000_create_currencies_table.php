<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();
                $table->string('name');
                $table->string('symbol', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->integer('sort_order')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('currencies', function (Blueprint $table) {
                if (!Schema::hasColumn('currencies', 'code')) {
                    $table->string('code', 10)->nullable();
                }
                if (!Schema::hasColumn('currencies', 'name')) {
                    $table->string('name')->nullable();
                }
                if (!Schema::hasColumn('currencies', 'symbol')) {
                    $table->string('symbol', 20)->nullable();
                }
                if (!Schema::hasColumn('currencies', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (!Schema::hasColumn('currencies', 'is_default')) {
                    $table->boolean('is_default')->default(false);
                }
                if (!Schema::hasColumn('currencies', 'sort_order')) {
                    $table->integer('sort_order')->nullable();
                }
                if (!Schema::hasColumn('currencies', 'created_at') || !Schema::hasColumn('currencies', 'updated_at')) {
                    $table->timestamps();
                }
            });
        }

        if (Schema::hasTable('currencies') && Schema::hasColumn('currencies', 'code')) {
            $now = now();

            $uzs = DB::table('currencies')->where('code', 'UZS')->first();

            if (!$uzs) {
                DB::table('currencies')->insert([
                    'code' => 'UZS',
                    'name' => 'Uzbekistani Som',
                    'symbol' => 'сум',
                    'is_active' => true,
                    'is_default' => true,
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('currencies')
                    ->where('code', 'UZS')
                    ->update([
                        'name' => DB::raw("COALESCE(name, 'Uzbekistani Som')"),
                        'symbol' => DB::raw("COALESCE(symbol, 'сум')"),
                        'is_active' => true,
                        'is_default' => true,
                        'updated_at' => $now,
                    ]);
            }

            DB::table('currencies')
                ->where('code', '!=', 'UZS')
                ->where('is_default', true)
                ->update([
                    'is_default' => false,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('currencies')) {
            Schema::dropIfExists('currencies');
        }
    }
};
