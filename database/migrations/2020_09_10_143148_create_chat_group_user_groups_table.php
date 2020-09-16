<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatGroupUserGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_group_user_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->nullable()->comment('用户ID');
            $table->integer('group_id')->nullable()->comment('群ID');
            $table->integer('is_top')->nullable(false)->default(0)->comment('是否置顶 1 是 0 否');
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
        Schema::dropIfExists('chat_group_user_groups');
    }
}
