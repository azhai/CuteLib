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

$job_server = \Cute\Commun\JobServer::getInstance();

//反转字符串或数组
$job_server->reverse = function($job) {
    $data = $job->worknorm();
    if (count($data) === 1 && is_string($data[0])) {
        return strrev($data[0]);
    } else {
        return array_reverse($data);
    }
};

//查找电话号码归属地
$job_server->phone_search_city = function($job) {
    $phones = $job->worknorm();
    $dat = new \Cute\Contrib\GEO\PhoneLoc(APP_ROOT . '/misc/phoneloc.dat');
    $result = array();
    foreach ($phones as $phone) {
        $result[$phone] = $dat->search($phone);
    }
    return $result;
};

//查找IP所在国家代码
$job_server->ip_search_country = function($job) {
    @list($ipaddr) = $job->worknorm();
    $dat = new \Cute\Contrib\GEO\IPCountry(APP_ROOT . '/misc/ipcountry.dat');
    $result = $dat->search($ipaddr);
    return $result;
};

//查找IP所在位置
$job_server->ip_search_address = function($job) {
    @list($ipaddr) = $job->worknorm();
    $dat = new \Cute\Contrib\GEO\QQWry(APP_ROOT . '/misc/qqwry.dat');
    $result = $dat->search($ipaddr);
    return implode(' ', $result);
};

$job_server->run();
?>
