
## Logging  日志

FileLogger 文件日志是线程安全的

```php
$logger = new \Cute\Logging\FileLogger(
    'notice',                      //文件名类似 notice-20150901.log
    APP_ROOT . '/runtime/logs',    //日志存放目录
    \Cute\Logging\Logger::NOTICE   //过滤级别，低于本级别的不过滤
);
//参数替换
$logger->warning('This is a {level} message.', array('level' => 'WARNING'));
//低级别不会记录
$logger->info('DONT write the info message!');
```

日志级别，按照PSR-2标准从高到低为

* EMERGENCY
* ALERT
* CRITICAL
* ERROR
* WARNING
* NOTICE
* INFO
* DEBUG