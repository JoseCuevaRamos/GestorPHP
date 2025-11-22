<?php
    require_once __DIR__ . '/database/generator/BaseMigration.php';

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/Seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => 'db',
            'name' => 'conduit',
            'user' => 'root',
            'pass' => 'secret',
            'port' => '3306',
            'charset' => 'utf8',
        ],
        'production' => [
            'adapter' => 'mysql',
            'host' => 'db',
            'name' => 'conduit',
            'user' => 'root',
            'pass' => 'secret',
            'port' => '3306',
            'charset' => 'utf8',
            'options' => [
                PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
            ],
        ],
    ],
    'version_order' => 'creation'
];
