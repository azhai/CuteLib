<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Widget;
use \Cute\Widget\RedisCounter;
use \Cute\Widget\FileCounter;


/**
 * 全局计数器
 */
abstract class Counter
{
    protected $name = '';
    protected $value = 0;
    protected $maxium = 0;

    /**
     * 构造函数
     */
    public function __construct($name, $value = 0, $maxium = 0)
    {
        $this->name = $name;
        $this->value = intval($value);
        $this->maxium = intval($maxium);
    }

    /**
     * 创建实例，Redis优先
     */
    public static function newInstance($name, $value = 0, $maxium = 0)
    {
        $obj = new RedisCounter($name, $value, $maxium);
        if (! $obj->connect()) {
            $obj = new FileCounter($name, $value, $maxium);
            $obj->connect();
        }
        $obj->readValue();
        return $obj;
    }
    
    /**
     * 自增
     */
    public function increase($step = 1)
    {
        $this->value += $step;
        if ($this->maxium > 0) {
            $this->value = $this->value % $this->maxium;
        }
        $this->writeValue();
        return $this->value;
    }
    
    abstract public function connect();
    
    abstract public function readValue();
    
    abstract public function writeValue();
    
    abstract public function remove();
}
