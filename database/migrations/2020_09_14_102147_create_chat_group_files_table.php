<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatGroupFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_group_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('group_id')->nullable()->comment('群ID');
            $table->string('name')->nullable()->default('')->comment('文件名');
            $table->string('file_url')->nullable()->default('')->comment('文件地址');
            $table->integer('user_id')->nullable()->comment('上传者ID');
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
        Schema::dropIfExists('chat_group_files');
    }
}
