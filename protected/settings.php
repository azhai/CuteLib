<?php
defined('APP_ROOT') or die('Illeigal access'); // 禁止非法访问

return array(
    'configs' => array(
        'site_name' => 'Demo',
        'locale_dir' => APP_ROOT . '/protected/locales',
        'route_key' => 's',
        'url_prefix' => '/index.php',
        'asset_url' => '/assets',
        'login_url' => '/sign/in/',
        'logout_url' => '/sign/out/',
    ),
    '\\PDO' => array(
        'default' => array(
            'dsn' => 'mysql:host=127.0.0.1;charset=utf8',
            'user' => 'dba',
            'password' => '',
            'options' => array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
            ),
        ),
    ),
    '\\Cute\\DB\\Database' => array(
        'wordpress' => array(
            '\\PDO' => 'default', 'dbname' => 'db_wordpress', 'tblpre' => 'wp_',
        ),
    ),
    '\\Cute\\DB\\HandlerSocket' => array(
        'default' => array('dbname' => 'db_wordpress', 'host' => '127.0.0.1', 'port' => 9999),
    ),
    '\\Cute\\Cache\\Redis' => array(
        'default' => array('host' => '127.0.0.1', 'port' => 6379),
    ),
    '\\Cute\\Logging\\FileLogger' => array(
        'default' => array('name' => 'php', 'directory' => APP_ROOT . '/runtime/logs'),
        'error' => array('name' => 'error', 'directory' => APP_ROOT . '/runtime/logs', 'ERROR'),
    ),
    '\\Cute\\View\\Templater' => array(
        'default' => array(
            'source_dir' => APP_ROOT . '/protected/templates',
        ),
    ),
);
