<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterIgnoreColumnsToJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->tinyInteger('ignore_flagged');
            $table->tinyInteger('ignore_physical_phone');
            $table->tinyInteger('ignore_physical');
            $table->tinyInteger('ignore_no_salary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('ignore_flagged');
            $table->dropColumn('ignore_physical_phone');
            $table->dropColumn('ignore_physical');
            $table->dropColumn('ignore_no_salary');
        });
    }
}
