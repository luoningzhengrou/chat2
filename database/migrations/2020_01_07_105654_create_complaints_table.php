<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComplaintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->unsigned()->comment('投诉人ID');
            $table->integer('c_user_id')->unsigned()->comment('被投诉人ID');
            $table->integer('ban_id')->unsigned()->comment('投诉类型');
            $table->string('info')->default('')->comment('投诉理由');
            $table->string('picture')->comment('图片');
            $table->integer('c_ban_id')->unsigned()->comment('处理类型');
            $table->integer('ban_type_id')->unsigned()->comment('处理原因');
            $table->tinyInteger('status')->unsigned()->default(0)->comment('状态 0 未处理 1 禁言中 2 已忽略 4 已解封');
            $table->integer('admin_id')->unsigned()->comment('处理管理员ID');
            $table->timestamp('p_time')->nullable()->comment('处理时间');
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
        Schema::dropIfExists('complaints');
    }
}
