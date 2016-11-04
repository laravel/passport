<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Laravel\Passport\DataTypeIdSelector;

class CreateOauthPersonalAccessClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $dataType = DataTypeIdSelector::readConfigFile();

        Schema::create('oauth_personal_access_clients', function (Blueprint $table) use ($dataType) {
            //ID filed as datatype selection
            eval("\$table->".$dataType."('id');");
            //Client ID filed as datatype selection
            eval("\$table->".$dataType."('client_id')->index();");
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
        Schema::drop('oauth_personal_access_clients');
    }
}
