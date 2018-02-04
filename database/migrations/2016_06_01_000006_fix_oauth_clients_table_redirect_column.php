<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixOauthClientsTableRedirectColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('oauth_clients')) {
            $clients = DB::select('SELECT id, redirect FROM oauth_clients');

            foreach($clients as $client) {
                if (is_null(json_decode($client->redirect, true))) {
                    $affected = DB::update('UPDATE oauth_clients SET redirect = :redirect WHERE id = :id', [
                        'id' => $client->id,
                        'redirect' => json_encode(explode(',', $client->redirect)),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('oauth_clients')) {
            $clients = DB::select('SELECT id, redirect FROM oauth_clients');

            foreach($clients as $client) {
                if (!is_null($redirect = json_decode($client->redirect, true))) {
                    $affected = DB::update('UPDATE oauth_clients SET redirect = :redirect WHERE id = :id', [
                        'id' => $client->id,
                        'redirect' => implode(',', $redirect),
                    ]);
                }
            }
        }
    }
}
