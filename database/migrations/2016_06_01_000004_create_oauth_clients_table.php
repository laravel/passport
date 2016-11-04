<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Laravel\Passport\DataTypeIdSelector;

class CreateOauthClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $dataType = DataTypeIdSelector::readConfigFile();

        Schema::create('oauth_clients', function (Blueprint $table) use ($dataType) {
            //ID filed as datatype selection
            eval("\$table->".$dataType."('id');");
            //User ID filed as datatype selection
            eval("\$table->".$dataType."('user_id')->index()->nullable();");
            $table->string('name');
            $table->string('secret', 100);
            $table->text('redirect');
            $table->boolean('personal_access_client');
            $table->boolean('password_client');
            $table->boolean('revoked');
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
        Schema::drop('oauth_clients');
    }
}
