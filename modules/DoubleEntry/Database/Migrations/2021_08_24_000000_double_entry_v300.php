<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DoubleEntryV300 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('double_entry_accounts', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->nullable()->after('type_id');

            $table->foreign('account_id')->references('id')->on('double_entry_accounts');
        });

        Schema::table('double_entry_journals', function (Blueprint $table) {
            $table->string('journal_number')->nullable()->after('reference');
            $table->string('basis')->nullable()->after('journal_number');
            $table->string('currency_code')->nullable()->after('basis');
            $table->double('currency_rate', 15, 8)->nullable()->after('currency_code');
        });

        Schema::table('double_entry_ledger', function (Blueprint $table) {
            $table->string('notes')->nullable()->after('reference');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('double_entry_accounts', function (Blueprint $table) {
            /** Make sure to put this condition to check if driver is SQLite */
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('double_entry_accounts_account_id_foreign');
            }

            $table->dropColumn('account_id');
        });

        Schema::table('double_entry_journals', function (Blueprint $table) {
            $table->dropColumn([
                'journal_number',
                'basis',
                'currency_code',
                'currency_rate',
            ]);
        });

        Schema::table('double_entry_ledger', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
}
