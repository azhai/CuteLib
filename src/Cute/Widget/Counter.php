<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Widget;
use \Cute\Cache\Subject;


/**
 * 全局计数器
 */
class Counter extends Subject
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
     * 设置缓存，Redis优先
     */
    public function setCache($class = '\\Cute\\Cache\\RedisCache')
    {
        $cache = new $class($this->name);
        $cache->share($this->value, 'intval');
        $cache->initiate()->readData();
        $this->attach($cache);
        return $cache;
    }
    
    public function findCaches()
    {
        return $this->observers;
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
        $this->notify(); //写入缓存
        return $this->value;
    }
}
