<?php

return [
    'repositories' => '/local/repositories',
    'db_main' => [
        'user' => 'gitphp',
        'password' => 'gitphp',
        'host' => 'mysql',
    ],
    Gitphp\Session::class => [
        'db_conf' => 'db_main',
        'db' => 'gitphp',
    ],
    Gitphp\User::class => [
        'db_conf' => 'db_main',
        'db' => 'gitphp',
    ],
];
