<?php
use \Cute\Widget\Counter;
use \Cute\Contrib\Shop\Amount;

app()->route('/', function() {
    $counter = new Counter('test_val');
    $counter->setCache('\\Cute\\Cache\\RedisCache');
    $val = $counter->increase();
    var_dump($val);
    $usd = new Amount(15);
    $cny = $usd->toCurrency('USD');
    var_dump($usd, $cny);
});
