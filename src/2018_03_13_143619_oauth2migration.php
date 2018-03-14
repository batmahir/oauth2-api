<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Oauth2migration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $hasTable = Schema::hasTable('oauth_access_tokens');
        if(!$hasTable)
        {
            throw new \Exception('oauth_access_tokens table does not exist');
        }

        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->longText('access_token')->nullable()->after('client_id');
            $table->longText('refresh_token')->nullable()->after('access_token');
            $table->string('token_type')->nullable()->after('refresh_token');
            $table->integer('expires_in')->nullable()->after('token_type');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->dropColumn('access_token');
            $table->dropColumn('refresh_token');
            $table->dropColumn('token_type');
            $table->dropColumn('expires_in');
        });
    }
}
