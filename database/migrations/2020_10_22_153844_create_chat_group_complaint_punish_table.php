<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatGroupComplaintPunishTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_group_complaint_punish', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('description')->nullable()->comment('描述');
            $table->unsignedInteger('day')->nullable()->comment('封禁时长（天）');
            $table->unsignedInteger('status')->nullable()->comment('状态 1 启用 0 禁用');
            $table->unsignedInteger('admin_id')->nullable()->comment('管理员 ID');
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
        Schema::dropIfExists('chat_group_complaint_punish');
    }
}
