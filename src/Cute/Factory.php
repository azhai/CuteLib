<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute;
use \Cute\Base\Storage;


/**
 * 对象工厂
 */
class Factory
{
    protected $storage = null;
    protected $objects = array(); // 对象池
    
    /**
     * 构造函数
     */
    public function __construct(Storage& $storage)
    {
        $this->storage = $storage;
    }
    
    /**
     * 正规化类名
     */
    public function normalize($class)
    {
        return rtrim($class, '\\');
    }

    /**
     * 生产对象
     */
    public function create($class, $name = 'default')
    {
        $class = $this->normalize($class);
        $section = $this->storage->getSectionOnce($class);
        $data = $section->getArray($name);
        if ($name !== 'default') {
            $data = array_merge($section->getArray('default'), $data);
        }
        if (class_exists($class)) {
            foreach ($data as $key => &$value) {
                if (starts_with($key, '\\')) {
                    $value = $this->load($key, $value);
                }
            }
            return exec_construct_array($class, array_values($data));
        }
    }

    /**
     * 重拾对象
     */
    public function load($class, $name = 'default')
    {
        $class = $this->normalize($class);
        if (! isset($this->objects[$class])) {
            $this->objects[$class] = array();
            if (! isset($this->objects[$class][$name])) {
                $this->objects[$class][$name] = $this->create($class, $name);
            }
        }
        return $this->objects[$class][$name];
    }
}
