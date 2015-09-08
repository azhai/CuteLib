
## Application 应用程序

应用程序Application有两个子类Website和Console，分别对应Web和命令行

可在Application上安装插件，默认安装了Storage->getConfig()和Factory->load()

```php
//Storage为配置容器，接受PHP配置文件作为参数
$settings_file = APP_ROOT . '/protected/settings.php';
$storage = \Cute\Base\Storage::newInstance($settings_file);
$app = new \Cute\Application($storage);
//子类Website在初始化时，安装了Router和Input，并注册了若干方法
$router = \Cute\Context\Router::getCurrent();
$app->install($router, array(
    'dispatch', 'abort', 'redirect'
));
//使用别名，Input的静态方法getInstance()变成$app的实例方法input()
$app->install('\\Cute\\Context\\Input', array(
    'getClientIP', 'input' => 'getInstance',
));
```

