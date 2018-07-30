<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnToFeeInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->enum('charge_fee', ['yes', 'no'])->after('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropColumn('charge_fee');
        });
    }
}
