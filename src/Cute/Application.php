<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute;
use \Cute\Importer;
use \Cute\Factory;
use \Cute\Base\Storage;


/**
 * 应用程序
 */
abstract class Application
{
    protected $shortcuts = array(); // 快捷方式

    /**
     * 构造函数
     */
    public function __construct(Storage& $storage)
    {
        $this->install($storage, array('getConfig'));
        $factory = new Factory($storage);
        $this->install($factory, array('load'));
    }

    /**
     * 初始化环境
     */
    public function initialize()
    {
        return $this;
    }
    
    /**
     * 安装插件，并注册插件的一些方法
     */
    public function install($plugin, array $methods)
    {
        foreach ($methods as $alias => $method) {
            //省略别名时，使用同名方法。PHP的方法名内部都是小写？
            $alias = strtolower(is_numeric($alias) ? $method : $alias);
            $this->shortcuts[$alias] = array($plugin, $method);
        }
        return $this;
    }
    
    /**
     * 使用已定义的插件
     */
    public function __call($name, $args)
    {
        $name = strtolower($name); //PHP的方法名内部都是小写？
        if (isset($this->shortcuts[$name])) {
            list($plugin, $method) = $this->shortcuts[$name];
            return exec_method_array($plugin, $method, $args);
        }
    }
    
    abstract public function run();
}
