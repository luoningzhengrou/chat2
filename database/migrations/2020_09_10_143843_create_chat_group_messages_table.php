<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatGroupMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_group_messages', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->integer('group_id')->nullable()->comment('群ID');
            $table->integer('user_id')->nullable()->comment('用户ID');
            $table->longText('content')->nullable()->comment('消息内容');
            $table->tinyInteger('type')->nullable(false)->default(1)->comment('消息类型 1 文本 2 图片');
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
        Schema::dropIfExists('chat_group_messages');
    }
}
