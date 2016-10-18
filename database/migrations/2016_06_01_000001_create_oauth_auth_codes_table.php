<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Laravel\Passport\DataTypeIdSelector;

class CreateOauthAuthCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $dataType = DataTypeIdSelector::readConfigFile();

        Schema::create('oauth_auth_codes', function (Blueprint $table) use ($dataType) {
            $table->string('id', 100)->primary();
            //User ID filed as datatype selection
            eval("\$table->".$dataType."('user_id');");
            //User ID filed as datatype selection
            eval("\$table->".$dataType."('client_id');");
            $table->text('scopes')->nullable();
            $table->boolean('revoked');
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
        Schema::drop('oauth_auth_codes');
    }
}
