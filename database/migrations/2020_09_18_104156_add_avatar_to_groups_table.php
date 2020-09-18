<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAvatarToGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chat_groups', function (Blueprint $table) {
            $table->string('avatar')->nullable(false)->default('')->comment('群图标')->after('name');
            $table->integer('cert_id')->nullable()->comment('证书ID')->after('avatar');
            $table->integer('province_id')->nullable()->comment('省ID')->after('cert_id');
            $table->integer('city_id')->nullable()->comment('市ID')->after('province_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chat_groups', function (Blueprint $table) {
            $table->dropColumn('avatar');
            $table->dropColumn('cert_id');
        });
    }
}
