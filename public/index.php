<?php
defined('APP_ROOT') or define('APP_ROOT', dirname(__DIR__));
defined('SRC_ROOT') or define('SRC_ROOT', APP_ROOT . '/src');
if (true) { //使用压缩文件？
    defined('MINFILE') or define('MINFILE', SRC_ROOT . '/cutelib.php');
    require_once (is_readable(MINFILE) ? MINFILE : SRC_ROOT . '/bootstrap.php');
} else {
    require_once (SRC_ROOT . '/bootstrap.php');
}


$app = app(APP_ROOT . '/protected/settings.php');
$app->route('/', function() {
    echo "Hello World!\n";
});

$app->mount(__DIR__, '*.php');
$app->mount(__DIR__, '*/*.php');
$app->run();
