<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Base;
use \ArrayObject;


/**
 * 存储容器
 */
class Storage extends ArrayObject
{
    const CONFIG_SECTION_NAME = 'configs';
    
    /**
     * 构造函数，flags默认为ARRAY_AS_PROPS
     */
    public function __construct($input = array(), $flags = ArrayObject::ARRAY_AS_PROPS)
    {
        parent::__construct($input, $flags);
    }

    /**
     * 读取配置文件
     */
    public static function newInstance($filename, $ext = false)
    {
        $filename .= empty($ext) ? '' : $ext;
        assert(is_readable($filename));
        return new self(include $filename);
    }

    /**
     * 读取配置项
     */
    public function getItem($name, $default = null)
    {
        $item = $this->offsetGet($name);
        return is_null($item) ? $default : $item;
    }

    /**
     * 读取数组类配置项
     */
    public function getArray($name, array $default = array())
    {
        $data = $this->getItem($name);
        return is_array($data) ? $data : $default;
    }

    /**
     * 读取配置区
     */
    public function getSection($name)
    {
        $data = $this->getArray($name, array());
        return new self($data);
    }

    /**
     * 读取配置区，并缓存起来
     */
    public function getSectionOnce($name)
    {
        $data = $this->getItem($name);
        if (! ($data instanceof self)) {
            $data = new self($data);
            $this->offsetSet($name, $data);
        }
        return $data;
    }
    
    /**
     * 获取公开配置信息
     */
    public function getConfig($name, $default = null)
    {
        $section = $this->getSectionOnce(self::CONFIG_SECTION_NAME);
        return $section->getItem($name, $default);
    }
}
