<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnBookedOnlineToFeeInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->double('booking_online')->after('charge_fee')->default(0);
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
            $table->dropColumn('booking_online');
        });
    }
}
