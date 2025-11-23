<?php
    require_once __DIR__ . '/database/generator/BaseMigration.php';

// phinx configuration: prefer environment variables so CI/containers and the app use the
// same database settings. Fallback to the original defaults for local dev.
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
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'name' => getenv('DB_DATABASE') ?: 'conduit',
            'user' => getenv('DB_USERNAME') ?: 'root',
            'pass' => getenv('DB_PASSWORD') ?: 'secret',
            'port' => getenv('DB_PORT') ?: '3306',
            'charset' => 'utf8',
        ],
        'production' => [
            'adapter' => 'mysql',
            'host' => getenv('DB_HOST') ?: 'db',
            'name' => getenv('DB_DATABASE') ?: 'conduit',
            'user' => getenv('DB_USERNAME') ?: 'root',
            'pass' => getenv('DB_PASSWORD') ?: 'secret',
            'port' => getenv('DB_PORT') ?: '3306',
            'charset' => 'utf8',
            'options' => (function () {
                $opts = [];
                // Always use 1009 for SSL CA option to avoid ambiguity
                $ca = getenv('DB_SSL_CA');
                if ($ca) {
                    $opts[1009] = $ca;
                }
                return $opts;
            })(),
        ],
    ],
    'version_order' => 'creation'
];
