<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnsFeeMaxTravelTimeToUserMeta extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_meta', function (Blueprint $table) {
            $table->enum('fee', ['yes', 'no'])->default('no');
            $table->integer('time_to_charge')->nullable();
            $table->integer('time_to_pay')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_meta', function (Blueprint $table) {
            $table->dropColumn('fee');
            $table->dropColumn('time_to_charge');
            $table->dropColumn('time_to_pay');
        });
    }
}
