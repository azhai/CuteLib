#!/usr/bin/env php
<?php
defined('APP_ROOT') or define('APP_ROOT', __DIR__);
defined('SRC_ROOT') or define('SRC_ROOT', APP_ROOT . '/src');
defined('APP_CLASS') or define('APP_CLASS', '\\Cute\\Console');
if (false) { //使用压缩文件？
    defined('MINFILE') or define('MINFILE', SRC_ROOT . '/cutelib.php');
    require_once (is_readable(MINFILE) ? MINFILE : SRC_ROOT . '/bootstrap.php');
} else {
    require_once (SRC_ROOT . '/bootstrap.php');
}

$app = app(APP_ROOT . '/protected/settings.php');
$app->mount(APP_ROOT . '/protected/commands');
$app->run();
