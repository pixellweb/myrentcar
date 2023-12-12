<?php

return [

    'domain' => 'xxxxx.hitech-mysolutions.com',
    'path' => '/myrentcar/api/MyRentcarServices/',


    // On peut voir les identifiants ainsi que le code société au moment de se connecter sur l'interface web Myrentcar
    "dbIdentifiant" => "XXXXXXX",
    "societe" => 120,
    "username" => env('MYRENTCAR_USERNAME', 'xxxxxx'),
    "password" => env('MYRENTCAR_PASSWORD', 'xxxxxx'),


    "mode_reglement" => 32,  // CARTE BANCAIRE WEB, PAYBOX...

    "code_prestation" => 'LOCATION',
    "libelle_prestation" => 'LOCATION',

    "tva" => '8.5',

];
