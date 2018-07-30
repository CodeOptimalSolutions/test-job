<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnsToExportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->string('after_minimum_time')->after('rounded_session_length');
            $table->string('minimum_time')->after('rounded_session_length');
            $table->timestamp('created_date')->after('due');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->dropColumn('after_minimum_time');
            $table->dropColumn('minimum_time');
            $table->dropColumn('created_date');
        });
    }
}
