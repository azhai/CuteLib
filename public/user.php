<?php


app()->route('/', function() {
    $_SESSION['ymd'] = date('Ymd');
});
