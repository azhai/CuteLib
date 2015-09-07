<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Widget;
use \Cute\Widget\Counter;


/**
 * Redis计数器
 */
class RedisCounter extends Counter
{
    protected $redis = null;
    
    public function connect($host = '127.0.0.1', $port = 6379)
    {
        if (! extension_loaded('redis')) {
            return false;
        }
        $this->redis = new \Redis();
        try {
            $success = $this->redis->connect($host, $port);
        } catch (Exception $e) {
            $success = false;
        }
        if (! $success) {
            $this->redis = null;
        }
        return $success;
    }
    
    public function readValue()
    {
        $value = $this->redis->get($this->name);
        if ($value !== false && strlen(trim($value)) > 0) {
            $this->value = intval($value);
        } else {
            $this->writeValue();
        }
        return $this->value;
    }
    
    public function writeValue()
    {
        return $this->redis->set($this->name, $this->value);
    }
    
    public function remove()
    {
        if ($this->redis->exists($this->name)) {
            $result = $this->redis->del($this->name);
            $this->redis->close();
            return $result;
        }
    }
}
