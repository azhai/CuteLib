<?php
use \Cute\Base\IP;
use \Cute\Contrib\GEO\QQWry;
use \Cute\Contrib\GEO\IPCountry;
use \Cute\Contrib\GEO\PhoneLoc;

app()->route('/', function() {
    $ipaddr = IP::getServerIP();
    $dat = new IPCountry(APP_ROOT . '/misc/ipcountry.dat');
    $country = $dat->search($ipaddr);
    $dat = new QQWry(APP_ROOT . '/misc/qqwry.dat');
    $result = $dat->search($ipaddr);
    var_dump($ipaddr);
    var_dump($country);
    var_dump(implode(' ', $result));

    $dat = new PhoneLoc(APP_ROOT . '/misc/phoneloc.dat');
    $result = $dat->search('0035818');
    var_dump($result);
    $result = $dat->search('028');
    var_dump($result);
    $result = $dat->search('1378742');
    var_dump($result);
});



