<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserBuddiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_buddies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->unsigned()->comment('用户ID');
            $table->integer('to_user_id')->unsigned()->comment('好友ID');
            $table->tinyInteger('status')->unsigned()->comment('1 正常   2 拉黑');
            $table->tinyInteger('is_show_phone')->unsigned()->comment('是否展示手机号码 1 Yes 0 No');
            $table->longText('buddy')->comment('好友');
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
        Schema::dropIfExists('user_buddies');
    }
}
