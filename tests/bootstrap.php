#!/usr/bin/env phpunit
<?php
defined('TEST_ROOT') or define('TEST_ROOT', __DIR__);
defined('APP_ROOT') or define('APP_ROOT', dirname(TEST_ROOT));
defined('SRC_ROOT') or define('SRC_ROOT', APP_ROOT . '/src');
defined('MINFILE') or define('MINFILE', SRC_ROOT . '/cutelib.php');
require_once (is_readable(MINFILE) ? MINFILE : SRC_ROOT . '/bootstrap.php');

$app = app(APP_ROOT . '/protected/settings.php');
$app->import('Cute', SRC_ROOT);
$app->import('Cutest', TEST_ROOT);
