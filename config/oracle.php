<?php

return [
    'oracle' => [
        'driver'         => 'oracle',
        'tns'            => env('DB_TNS_ORA', ''),
        'host'           => env('DB_HOST_ORA', ''),
        'port'           => env('DB_PORT_ORA', '1521'),
        'database'       => env('DB_DATABASE_ORA', ''),
        'username'       => env('DB_USERNAME_ORA', ''),
        'password'       => env('DB_PASSWORD_ORA', ''),
        'charset'        => env('DB_CHARSET_ORA', 'AL32UTF8'),
        'prefix'         => env('DB_PREFIX_ORA', ''),
        'prefix_schema'  => env('DB_SCHEMA_PREFIX_ORA', ''),
        'server_version' => env('DB_SERVER_VERSION_ORA', '11g'),
    ],
    'clon' => [
        'driver'         => 'oracle',
        'tns'            => env('DB_TNS_ORA', ''),
        'host'           => env('DB_HOST_CLON', ''),
        'port'           => env('DB_PORT_CLON', '1521'),
        'database'       => env('DB_DATABASE_CLON', ''),
        'username'       => env('DB_USERNAME_CLON', ''),
        'password'       => env('DB_PASSWORD_CLON', ''),
        'charset'        => env('DB_CHARSET_CLON', 'AL32UTF8'),
        'prefix'         => env('DB_PREFIX_ORA', ''),
        'prefix_schema'  => env('DB_SCHEMA_PREFIX_ORA', ''),
        'server_version' => env('DB_SERVER_VERSION_ORA', '11g'),
    ],
];
