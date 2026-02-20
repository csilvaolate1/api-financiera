<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('initial_balance', 15, 2)->default(0)->after('email');
            $table->decimal('balance', 15, 2)->default(0)->after('initial_balance');
        });

        DB::table('users')->update(['balance' => DB::raw('initial_balance')]);

        // CHECK constraint: saldo inicial y balance no negativos (MySQL 8.0.16+)
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users ADD CONSTRAINT users_initial_balance_non_negative CHECK (initial_balance >= 0)');
            DB::statement('ALTER TABLE users ADD CONSTRAINT users_balance_non_negative CHECK (balance >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users DROP CHECK users_initial_balance_non_negative');
            DB::statement('ALTER TABLE users DROP CHECK users_balance_non_negative');
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['initial_balance', 'balance']);
        });
    }
};
