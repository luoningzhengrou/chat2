<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatIpHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_ip_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('client_id')->nullable()->comment('客户端 ID');
            $table->integer('user_id')->unsigned()->comment('用户 ID');
            $table->integer('ip')->unsigned()->comment('IP');
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
        Schema::dropIfExists('chat_ip_histories');
    }
}
