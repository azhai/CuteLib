<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Cache;


/**
 * Redis缓存
 */
class RedisCache extends BaseCache
{
    use \Cute\Base\Deferring;
    
    protected $name  = '';
    protected $redis = null;
    protected $host  = '';
    protected $port  = 0;
    protected $params = array(
        'persistent' => false,
        'socket' => null,
        'serializer' => null,
    );
    
    public function __construct($name, $host = '127.0.0.1', $port = 6379,
                                array $params = array())
    {
        $this->name = $name;
        $this->host = $host;
        $this->port = intval($port);
        if (isset($params['socket']) && is_string($params['socket'])) {
            if (starts_with($params['socket'], 'unix://')) {
                $params['socket'] = substr($params['socket'], 7);
            }
        }
        $this->params = array_merge($this->params, $params);
    }
    
    public function initiate()
    {
        if (extension_loaded('redis')) {
            $this->redis = new \Redis();
            try {
                $this->connect();
            } catch (\Exception $e) {
                $this->redis = null;
            }
        }
        return $this;
    }
    
    public function connect()
    {
        if (isset($this->params['socket'])) {
            $args = array($this->params['socket']);
        } else {
            $args = array($this->host, $this->port);
        }
        $connect = $this->params['persistent'] ? 'pconnect' : 'connect';
        exec_method_array($this->redis, $connect, $args);
        if ($serializer = $this->params['serializer']) {
            $serializer = constant('\\Redis::SERIALIZER_' . $serializer);
            $this->redis->setOption(\Redis::OPT_SERIALIZER, $serializer);
        }
    }
    
    public function close()
    {
        $this->redis->close();
    }
    
    public function readData()
    {
        $data = $this->redis->get($this->name);
        if ($data !== false) {
            $this->data = $data;
            return $this->data;
        }
    }
    
    public function writeData($part = false)
    {
        return $this->redis->set($this->name, $this->data, $this->ttl);
    }
    
    public function removeData()
    {
        if ($this->redis->exists($this->name)) {
            return $this->redis->del($this->name);
        }
    }
}
