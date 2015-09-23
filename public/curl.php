<?php
use \Cute\Commun\cURL;
use \Cute\Logging\FileLogger;


app()->route('/', function() {
    $logger = new FileLogger('curl', APP_ROOT . '/runtime/logs');
    $client = new cURL('https://raw.githubusercontent.com/Mashape/unirest-php', $logger);
    $result = $client->post('/master/LICENSE', array(), array('x'=>rand(0, 100), 'y'=>'yes'));
    var_dump($result->code, $result->body);
});



