<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupNameToChatGroupUserGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chat_group_user_groups', function (Blueprint $table) {
            $table->string('name_group')->nullable()->default('')->comment('群昵称')->after('group_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chat_group_user_groups', function (Blueprint $table) {
            $table->dropColumn('name_group');
        });
    }
}
