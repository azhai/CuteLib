<?php
use \Cute\Widget\Counter;
use \Cute\Contrib\Shop\Amount;
use \Cute\Utility\Calendar;

app()->route('/', function() {
    $counter = new Counter('test_val');
    $counter->setCache('\\Cute\\Cache\\RedisCache');
    $val = $counter->increase();
    var_dump($val);
    
    $rmb = new Amount(120015.30);
    $dollar = $rmb->toCurrency('USD');
    var_dump($rmb->toCapital());
    var_dump($rmb, $dollar);
    
    $cal = new Calendar();
    $cal->setDate(2015, 2, 3);
    var_dump($cal->speak('%F 星期%v %R %z'));
    var_dump($cal->getBirthAnimalIndex());
    var_dump($cal->getHoroscopeIndex());
});
