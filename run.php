#!/usr/bin/env php
<?php
defined('APP_ROOT') or define('APP_ROOT', __DIR__);
defined('SRC_ROOT') or define('SRC_ROOT', APP_ROOT . '/src');
defined('APP_CLASS') or define('APP_CLASS', '\\Cute\\Console');
//defined('CUTELIB_FILE') or define('CUTELIB_FILE', SRC_ROOT . '/cutelib.php');
//require_once CUTELIB_FILE;
require_once SRC_ROOT . '/bootstrap.php';

$app = app(APP_ROOT . '/protected/settings.php');
$app->mount(APP_ROOT . '/protected/commands');
$app->run();
