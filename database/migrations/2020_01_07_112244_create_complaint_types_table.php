<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComplaintTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complaint_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type')->comment('封禁理由');
            $table->integer('time')->unsigned()->comment('封禁时间');
            $table->tinyInteger('status')->unsigned()->comment('状态 1 正常 0 禁用');
            $table->integer('admin_id')->unsigned()->comment('管理员ID');
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
        Schema::dropIfExists('complaint_types');
    }
}
