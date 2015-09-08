<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */
 
// 要求PHP5.4以上版本
if (version_compare(PHP_VERSION, '5.4.0') < 0) {
    die('PHP最低要求5.4版本');
}

defined('APP_ROOT') or define('APP_ROOT', __DIR__);
defined('SRC_ROOT') or define('SRC_ROOT', APP_ROOT . '/src');
defined('MINFILE') or define('MINFILE', false);


//将框架打包为单个文件
if (MINFILE) {
    //Importer.php文件压缩后，大小在1.5KB以上
    if (! is_readable(MINFILE) || filesize(MINFILE) < 1024) {
        require_once SRC_ROOT . '/Cute/Bale.php';
        $files = glob(SRC_ROOT . '/Cute/*.php');
        //排除Compressor.php文件
        $key = array_search(SRC_ROOT . '/Cute/Bale.php', $files);
        if ($key !== false) {
            unset($files[$key]);
        }
        //使用压缩
        $bale = new \Cute\Bale();
        $bale->prepend(SRC_ROOT . '/common.php');
        $bale->minify(MINFILE, $files,
                glob(SRC_ROOT . '/Cute/*/*.php'));
    }
    require_once MINFILE; // 使用压缩后的文件
} else {
    require_once SRC_ROOT . '/common.php';
}
