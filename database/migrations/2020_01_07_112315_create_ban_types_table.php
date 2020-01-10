<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBanTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ban_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('info')->comment('处理结果');
            $table->tinyInteger('status')->unsigned()->default(0)->comment('状态 1 正常 0 禁用');
            $table->tinyInteger('is_home')->unsigned()->default(0)->comment('前台是否展示');
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
        Schema::dropIfExists('ban_types');
    }
}
