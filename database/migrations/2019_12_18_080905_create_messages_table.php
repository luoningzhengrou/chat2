<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->unsigned()->comment('发送人ID');
            $table->integer('to_user_id')->unsigned()->comment('接收人ID');
            $table->string('content')->default('')->comment('消息');
            $table->tinyInteger('is_send')->unsigned()->default(0)->comment('是否推送到');
            $table->tinyInteger('is_read')->unsigned()->default(0)->comment('是否已读');
            $table->tinyInteger('is_show')->unsigned()->default(1)->comment('0 删除');
            $table->tinyInteger('to_is_show')->unsigned()->default(1)->comment('to 0 删除');
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
        Schema::dropIfExists('messages');
    }
}
