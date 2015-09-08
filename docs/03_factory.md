
## Factory  对象工厂

可以根据配置文件生产或者缓存对象


```php
//Storage为配置容器，接受PHP配置文件作为参数
$settings_file = APP_ROOT . '/protected/settings.php';
$storage = \Cute\Base\Storage::newInstance($settings_file);
$factory = new \Cute\Factory($storage);
//生成PDO对象，使用default参数组
$pdo = $factory->create('\\PDO', 'default');
//加载PDO对象，如果对象尚不存在，就会调用上面的方法先生成一个
$pdo = $factory->load('\\PDO', 'default');
```

配置文件的例子 settings.php

```php
<?php
defined('APP_ROOT') or die('Illeigal access'); // 禁止非法访问

return array(
    'configs' => array(  //使用$app->getConfig()可以获取的参数
        'site_name' => 'Demo',
        'locale_dir' => APP_ROOT . '/protected/locales',
        'route_key' => 's',
        'url_prefix' => '/index.php',
        'asset_url' => '/assets',
        'login_url' => '/sign/in/',
        'logout_url' => '/sign/out/',
    ),
    '\\PDO' => array(
        'default' => array( //MySQL数据库连接
            'dsn' => 'mysql:host=127.0.0.1;charset=utf8',
            'user' => 'dba',
            'password' => '',
            'options' => array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
            ),
        ),
    ),
);
```