<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatGroupReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_group_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->nullable()->comment('举报用户ID');
            $table->integer('group_id')->nullable()->comment('群ID');
            $table->string('message_ids')->nullable(false)->default('')->comment('违规消息ID集合');
            $table->tinyInteger('status')->nullable(false)->default(0)->comment('状态 0 审核中 1 处理完成 2 忽略');
            $table->integer('admin_id')->nullable()->comment('管理员ID');
            $table->string('remark')->nullable()->default('')->comment('备注');
            $table->integer('ban_id')->nullable()->comment('');
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
        Schema::dropIfExists('chat_group_reports');
    }
}
