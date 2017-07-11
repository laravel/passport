<?php

return [
    /*
    |--------------------------------------------------------------------------
    | clients
    |--------------------------------------------------------------------------
    |
    | Please provide the clients model used in Passport.
    |
    */

    'clients' => [

        'model'                     =>  'Laravel\Passport\Client',

        'personal_access_client'    =>  'Laravel\Passport\PersonalAccessClient'

    ],

    /*
    |--------------------------------------------------------------------------
    | Tokens
    |--------------------------------------------------------------------------
    |
    | Please provide the token model used in Passport.
    |
    */

    'tokens' => [

        'model'                         => 'Laravel\Passport\Token',

    ]
];
