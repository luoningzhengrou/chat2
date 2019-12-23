<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAddFriendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_add_friends', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->unsigned()->comment('申请好友ID');
            $table->integer('to_user_id')->unsigned()->comment('被申请好友ID');
            $table->string('info')->default('')->comment('验证信息');
            $table->string('r_info')->default('')->comment('拒绝信息');
            $table->tinyInteger('is_handle')->unsigned()->default(0)->comment('是否已处理');
            $table->tinyInteger('status')->unsigned()->default(0)->comment('处理结果 1 同意  2 拒绝');
            $table->timestamp('verified_at');
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
        Schema::dropIfExists('user_add_friends');
    }
}
