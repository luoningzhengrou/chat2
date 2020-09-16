<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('union_id')->nullable()->unique()->comment('群号');
            $table->string('name')->nullable()->comment('群名');
            $table->string('code')->default('')->comment('进群码');
            $table->string('announcement')->default('')->comment('群公告');
            $table->time('start_time')->nullable()->comment('群学习开始时间');
            $table->time('end_time')->nullable()->comment('群学习结束时间');
            $table->tinyInteger('is_only_manage_chat')->default(0)->comment('仅管理员发言');
            $table->tinyInteger('is_only_manage_invite')->default(0)->comment('仅管理员拉人');
            $table->string('prohibit_user_ids')->default('')->comment('踢出群聊禁止加入用户');
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
        Schema::dropIfExists('chat_groups');
    }
}
