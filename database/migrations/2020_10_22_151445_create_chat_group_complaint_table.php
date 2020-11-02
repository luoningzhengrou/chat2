<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatGroupComplaintTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_group_complaint', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('group_id')->nullable()->comment('群ID');
            $table->unsignedInteger('user_id')->nullable()->comment('投诉人ID');
            $table->text('content')->nullable()->comment('投诉内容,图片地址');
            $table->string('reason')->nullable()->comment('投诉理由');
            $table->unsignedInteger('complaint_id')->nullable()->comment('投诉ID');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态 0 未处理 1已处理');
            $table->unsignedInteger('reason_id')->nullable()->comment('处理结果ID');
            $table->unsignedInteger('punish_id')->nullable()->comment('处罚ID');
            $table->unsignedInteger('admin_id')->nullable()->comment('处理人ID');
            $table->string('remark')->nullable()->comment('处理结果备注');
            $table->dateTime('complaint_time')->nullable()->comment('处理时间');
            $table->unsignedInteger('type')->nullable()->comment('封禁状态 0 未封 1 已封');
            $table->dateTime('end_time')->nullable()->comment('解封时间');
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
        Schema::dropIfExists('chat_group_complaint');
    }
}
