<?php

//todo: a terme mettre en place ici le choix du stockage
//todo mettre les annotation
//? durée de rentention ou max rentention
return [
    'tag' => env('APP_NAME', null),

    'header-severite-activation' => env( 'HEADER-SEVERITE-ACTIVATION', 'X-trace-severite'),

    'path' => env('SEVERITE_PATH', 'severite'),

    'activate' => env('SEVERITE_PATH', false),

    'xhprof-lib-url' => env('XHPROF-LIB-URL', '/usr/src/xhprof/xhprof_lib/utils/')
];
