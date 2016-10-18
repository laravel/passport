<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Laravel\Passport\DataTypeIdSelector;

class CreateOauthAccessTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $dataType = DataTypeIdSelector::readConfigFile();

        Schema::create('oauth_access_tokens', function (Blueprint $table) use ($dataType) {
            $table->string('id', 100)->primary();
            //User ID filed as datatype selection
            eval("\$table->".$dataType."('user_id')->index()->nullable();");
            //Client ID field as datatype selection
            eval("\$table->".$dataType."('client_id');");
            $table->string('name')->nullable();
            $table->text('scopes')->nullable();
            $table->boolean('revoked');
            $table->timestamps();
            $table->dateTime('expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('oauth_access_tokens');
    }
}
