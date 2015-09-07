<?php
use \Cute\Widget\Counter;

app()->route('/', function() {
    $counter = Counter::newInstance('test_val');
    $val = $counter->increase();
    var_dump($val);
});
