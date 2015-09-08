<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Base;


/**
 * 可替代类
 */
trait Mocking
{
    protected $inner = null;
    
    /**
     * 构造函数
     */
    private function __construct(& $inner = null)
    {
        $this->inner = $inner;
    }
    
    /**
     * 推出准备好的正品，否则推出替代品
     */
    public static function mock(& $inner = null)
    {
        $obj = new self($inner);
        return $obj->isReady() ? $obj->inner : $obj;
    }
    
    /**
     * 调用方法
     */
    public function __call($name, $arguments)
    {
        return false;
    }
    
    /**
     * 设置属性
     */
    public function __set($name, $value)
    {
        return false;
    }
    
    /**
     * 获取属性
     */
    public function __get($name)
    {
        return;
    }
    
    /**
     * 检查临界状态
     */
    public function isReady()
    {
        return ! is_null($this->inner);
    }
}
