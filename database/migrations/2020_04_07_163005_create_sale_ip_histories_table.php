<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleIpHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_ip_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('client_id')->nullable()->comment('客户端 ID');
            $table->integer('sale_id')->unsigned()->comment('销售 ID');
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
        Schema::dropIfExists('sale_ip_histories');
    }
}
