<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewUserMetaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_meta', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('consumer_type');
            $table->string('customer_type');
            $table->string('translator_type');
            $table->string('gender');
            $table->string('translator_level');
            $table->string('username');
            $table->string('post_code');
            $table->string('city');
            $table->string('country');
            $table->string('town');
            $table->string('worked_for');
            $table->string('organization_number');
            $table->string('not_get_notification');
            $table->string('not_get_emergency');
            $table->string('not_get_nighttime');
            $table->string('address', 200);
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
        Schema::drop('user_meta');
    }
}
