<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatGroupUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_group_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('group_id')->nullable()->comment('群ID');
            $table->integer('user_id')->nullable()->comment('用户ID');
            $table->tinyinteger('is_owner')->default(0)->comment('是否群主 1 是 0 否');
            $table->tinyinteger('is_manager')->default(0)->comment('是否管理员 1 是 0 否');
            $table->tinyinteger('type')->default(0)->comment('加入类型 0 邀请 1 申请');
            $table->integer('from_user_id')->nullable()->comment('邀请人ID');
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
        Schema::dropIfExists('chat_group_users');
    }
}
