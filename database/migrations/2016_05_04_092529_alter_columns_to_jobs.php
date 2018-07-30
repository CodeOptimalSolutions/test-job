<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterColumnsToJobs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function ($table) {
            $table->timestamp('expired_at');
            $table->tinyInteger('ignore')->default('0');
            $table->tinyInteger('ignore_expired')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function ($table) {
            $table->dropColumn('expired_at');
            $table->dropColumn('ignore');
            $table->dropColumn('ignore_expired');
        });
    }
}
