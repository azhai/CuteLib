<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute;


if (! function_exists('trait_exists')) {
    function trait_exists($class, $autoload)
    {
        return false;
    }
}


/**
 * 类加载器
 *
 * USAGE:
 * defined('APP_ROOT') or define('APP_ROOT', dirname(__DIR__));
 * defined('VENDOR_ROOT') or define('VENDOR_ROOT', APP_ROOT . '/vendor');
 * require_once APP_ROOT . '/src/Cute/Importer.php';
 * $importer = \Cute\Importer::getInstance();
 * $importer->addNamespace('NotORM', VENDOR_ROOT . '/notorm');
 * //OR
 * $importer->addClass(VENDOR_ROOT . '/notorm/NotORM.php',
 *         'NotORM', 'NotORM_Result', 'NotORM_Row', 'NotORM_Literal', 'NotORM_Structure');
 */
final class Importer
{

    private static $instance = null; //实例
    private $classes = array(); // 已注册的class/interface/trait对应的文件
    private $namespaces = array(); // 已注册的namespace对用的起始目录

    /**
     * 私有构造函数，防止在类外创建对象
     */
    private function __construct()
    {
        // 加载基本的命令空间
    }

    /**
     * Importer单例
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->register();
        }
        return self::$instance;
    }

    /**
     * 检查指定class/interface/trait是否已存在
     *
     * @param string $class
     *            要检查的完整class/interface/trait名称
     * @param bool $autoload
     *            如果当前不存在，是否尝试PHP的自动加载功能
     * @return bool
     */
    public static function exists($class, $autoload = true)
    {
        return class_exists($class, $autoload)
                || interface_exists($class, $autoload)
                || trait_exists($class, $autoload);
    }

    /**
     * 自动加载方法，用于spl_autoload_register注册
     *
     * @param string $class
     *            要寻找的完整class/interface/trait名称
     * @return bool
     */
    public function autoload($class)
    {
        $class = trim($class, '\\_');
        if (isset($this->classes[$class])) { // 在已知类中查找
            require_once $this->classes[$class];
            return self::exists($class, false);
        }
        $ns_check = $this->checkNamespace($class); // 在已知域名中查找
        return ($ns_check === true);
    }

    /**
     * 将对象的autoload方法注册到PHP系统
     * 在这之后往对象中添加的class和namespace也起作用
     *
     * @return bool
     */
    public function register()
    {
        return spl_autoload_register(array(
            $this, 'autoload'
        ));
    }

    /**
     * 当自动加载class,class2,class3,...时，将filename文件包含进来
     *
     * @param string $filename
     *            这些class/interface/trait所在的文件或入口文件
     * @param string $class
     *            完整class/interface/trait名称
     * @param
     *            ... 其他class/interface/trait名称
     * @return this
     */
    public function addClass($filename, $class)
    {
        $classes = func_get_args();
        $filename = array_shift($classes);
        if (is_readable($filename)) {
            foreach ($classes as $class) {
                $this->classes[trim($class, '\\')] = $filename;
            }
        }
        ksort($this->classes);
        return $this;
    }

    /**
     * 当自动加载的namespace/class以某个词ns开头时，尝试在dir目录寻找匹配文件
     *
     * @param string $ns
     *            包前缀
     * @param string $dir
     *            包所在最顶层目录
     * @return this
     */
    public function addNamespaceStrip($ns, $dir)
    {
        $ns = trim($ns, '\\');
        $dir = rtrim($dir, '\\/');
        $this->namespaces[$ns] = $dir;
        ksort($this->namespaces);
        return $this;
    }

    /**
     * 同上，但$dir为包所在的父目录
     * @return this
     */
    public function addNamespace($ns, $dir)
    {
        $ns = trim($ns, '\\');
        $tok = strtok($ns, '\\_');
        $dir = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $tok;
        return $this->addNamespaceStrip($ns, $dir);
    }

    /**
     * Namespace/class自动加载时，寻找匹配文件的方式
     *
     * @param string $class
     *            要寻找的完整class/interface/trait名称
     * @return bool
     */
    public function checkNamespace($class)
    {
        $tok = strtok($class, '\\_');
        $length = strlen($tok) + 1;
        if (isset($this->namespaces[$tok])) {
            $path = $this->namespaces[$tok];
        } else if (isset($this->namespaces[''])) {
            $path = $this->namespaces[''];
        } else {
            return false;
        }
        // 先试试一步到位，用于符合PSR-0标准的库
        $fname = $path . DIRECTORY_SEPARATOR;
        $fname .= str_replace(array(
            '\\',
            '_'
        ), DIRECTORY_SEPARATOR, substr($class, $length));
        if (file_exists($fname . '.php')) {
            require_once $fname . '.php';
            if (self::exists($class, false)) {
                return true;
            }
        }
        // 尝试循序渐进地检查目标对应的路径
        while ($tok) {
            $path .= DIRECTORY_SEPARATOR . $tok;
            // 先检查文件，再检查目录，次序不可颠倒
            if (file_exists($path . '.php')) { // 找到文件了
                require_once $path . '.php';
                if (self::exists($class, false)) {
                    return true;
                }
            }
            if (! file_exists($path)) { // 目录不对，不要再找了
                return false;
            }
            $tok = strtok('\\_');
        }
    }
}
