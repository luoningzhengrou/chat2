<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatGroupComplaintReasonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_group_complaint_reason', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('info')->nullable()->comment('信息');
            $table->unsignedInteger('status')->default(1)->comment('状态 1 启用 0禁用');
            $table->unsignedInteger('home_status')->default(1)->comment('前台状态 1 启用 0禁用');
            $table->unsignedInteger('admin_id')->nullable()->comment('管理员ID');
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
        Schema::dropIfExists('chat_group_complaint_reason');
    }
}
