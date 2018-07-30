<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LoginLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('login_logs')) {
            Schema::create('login_logs', function (Blueprint $table) {
                $table->increments('id')->unsigned(false);
                $table->integer('user_id')->unsigned(false);
                $table->string('ip', 255);
                $table->text('useragent');
                $table->string('msg', 255);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at', '0000-00-00 00:00:00');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('login_logs');
    }
}
