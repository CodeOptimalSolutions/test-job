<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('export_list_id');
            $table->string('c_name');
            $table->integer('c_id');
            $table->string('c_ref')->nullable();
            $table->string('cost_place')->nullable();
            $table->string('language');
            $table->string('status');
            $table->string('phone');
            $table->string('physic');
            $table->timestamp('due');
            $table->string('session_length');
            $table->string('rounded_session_length');
            $table->string('due_within24');
            $table->string('by_admin');
            $table->string('online');
            $table->string('t_name');
            $table->integer('t_id');
            $table->string('t_dob');
            $table->string('compensation');
            $table->string('withdrawn_late');
            $table->string('c_not_call');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('exports');
    }
}
