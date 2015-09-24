<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Context;


/**
 * 输入参数过滤器
 * 注意$_SERVER中只有少量元素出现在INPUT_SERVER中
 */
class Input
{
    /* 可用INPUT_GET|INPUT_POST|INPUT_COOKIE|INPUT_SERVER|INPUT_ENV */
    protected static $instances = array();
    protected static $client_ip = '-';
    protected $input_type = null;   // INPUT类型
    protected $data = array();      // 过滤后数据
    protected $done = false;        // 是否读取过全部数据

    /**
     * 私有构造函数，传入外界输入常量名
     * @param string $input_name 常量名INPUT_*的后缀
     */
    protected function __construct($input_name = 'POST')
    {
        $this->input_type = constant('INPUT_' . $input_name);
        if ($input_name === 'REQUEST') {
            $this->raw_data = $_REQUEST;
        }
        assert(! is_null($this->input_type)); // 检查是否合法的输入类型
    }
    
    /**
     * get()的简写形式，将$type写在方法名末尾
     * 如：getInt($key, $default) 等价于 get($key, 'int', $default)
     */
    public function __call($name, $arguments)
    {
        if (starts_with($name, 'get') && count($arguments) > 0) {
            $type = substr($name, 3);
            @list($key, $default) = $arguments;
            return $this->get($key, $default, $type);
        }
    }

    /**
     * 获取每种INPUT的单例
     */
    public static function getInstance($input_name = 'POST')
    {
        $input_name = strtoupper($input_name);
        if (! isset(self::$instances[$input_name])) {
            $instance = new static($input_name);
            self::$instances[$input_name] = $instance;
        }
        return self::$instances[$input_name];
    }

    /**
     * 获取每种INPUT的单例
     */
    public static function request($key, $default = null, $type = false)
    {
        $post = self::getInstance('POST');
        $value = $post->get($key, null, $type);
        if (is_null($value)) {
            $get = self::getInstance('GET');
            $value = $get->get($key, $default, $type);
        }
        return $value;
    }
    
    /**
     * 获取真实HTTP客户端IP，按次序尝试
     *
     * @return string
     */
    public static function getClientIP()
    {
        if (strlen(self::$client_ip) >= 7) {
            return self::$client_ip;
        }
        $keys = array(
            'HTTP_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'
        );
        $server = self::getInstance('SERVER');
        foreach ($keys as $key) {
            $ipaddr = $server->get($key);
            if ($ipaddr && strlen($ipaddr) >= 7) {
                self::$client_ip = $ipaddr;
                break;
            }
        }
        return self::$client_ip;
    }
    
    /**
     * 判别类型的准确的常量表示
     * @param string $type 含糊的类型表述
     */
    public static function detectType($type)
    {
        $type = empty($type) ? 'string' : strtolower($type);
        foreach (filter_list() as $name) {
            if (ends_with($name, $type)) {
                return filter_id($name);
            }
        }
    }

    /**
     * 将值强制转换成对应类型
     * @param string/array $value 要转换的原始值/数组
     * @param string/array $type 转换类型/类型数组
     */
    public static function coerce($value, $type = 'raw')
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                if (is_array($type) && isset($type[$key])) {
                    $subtype = $type[$key];
                } else {
                    $subtype = $type;
                }
                $value[$key] = self::coerce($val, $subtype);
            }
        } else {
            $type = strtolower($type);
            if ($type === 'int' || $type === 'float') {
                settype($value, $type);
            } else if ($type === 'array') {
                $value = (array) $value;
            } else if ($type !== 'raw') {
                settype($value, 'string');
            }
        }
        return $value;
    }
    
    protected function filterData($key, $type)
    {
        $type = self::detectType($type);
        return filter_input($this->input_type, $key, $type);
    }
    
    protected function filterArrayData($types)
    {
        if (is_array($types)) {
            foreach ($types as $key => & $type) {
                if (is_array($type)) {
                    $type['filter'] = self::detectType($type['filter']);
                } else {
                    $type = self::detectType($type);
                }
            }
        } else {
            $types = self::detectType($types);
        }
        return filter_input_array($this->input_type, $types);
    }

    /**
     * 获取其中单个键的值，但是值可能为数组
     * @param string $key 对应的键名
     * @param string $type 过滤类型，默认false对应着'string'
     * @param mixed $default 默认值，除raw外，都会强制转换为$type类型，
     *          如果$default为数组，会将要获取的值当作数组处理
     * @return mixed 获取的值
     */
    public function get($key, $default = null, $type = false)
    {
        if (! array_key_exists($key, $this->data)) {
            if ($this->done === true) {
                if (! is_array($default)) {
                    $default = self::coerce($default, $type);
                }
                return $default;
            }
            if (is_array($default)) {
                $type = array($key => array(
                    'filter' => $type, 'flags' => FILTER_FORCE_ARRAY
                ));
                $data = $this->filterArrayData($type);
                $this->data = array_merge($this->data, $data);
            } else {
                $value = $this->filterData($key, $type);
                if (is_null($value) || $value === false) {
                    $value = self::coerce($default, $type);
                }
                $this->data[$key] = $value;
            }
        }
        return $this->data[$key];
    }
    
    /**
     * 获取其中单个键的值，并抛出它
     */
    public function pop($key, $default = null, $type = false)
    {
        $this->all();
        $value = $this->get($key, $default, $type);
        unset($this->data[$key]);
        return $value;
    }

    /**
     * 获取全部的值
     * @param string/array $types 过滤类型，默认false对应着'string'
     *              当$types是关联数组时，按键名对应类型过滤，否则，全部使用单一类型过滤
     * @return array 获取全部的值，关联数组
     */
    public function all($types = false)
    {
        if ($this->done === false) {
            $this->data = $this->filterArrayData($types);
            $this->done = true;
        }
        if (is_null($this->data)) {
            //当input数组为空时，filter_input_array()返回null
            $this->data = array();
        }
        return $this->data;
    }
}