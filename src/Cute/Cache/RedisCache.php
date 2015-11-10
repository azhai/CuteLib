<?php
/**
 * Project      CuteLib
 * Author       Ryan Liu <azhai@126.com>
 * Copyright (c) 2013 MIT License
 */

namespace Cute\Cache;


/**
 * Redis缓存
 */
class RedisCache extends BaseCache
{
    use \Cute\Base\Deferring;

    protected $redis = null;
    protected $name = '';
    protected $host = '';
    protected $port = 0;
    protected $params = [
        'persistent' => false,
        'socket' => null,
        'serializer' => null,
    ];

    public function __construct($name, $host = '127.0.0.1', $port = 6379,
                                array $params = [])
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
        $this->initiate();
    }

    public function initiate()
    {
        if (!extension_loaded('redis')) {
            $this->errors[] = 'Extension redis is not found !';
        } else {
            $this->redis = new \Redis();
            try {
                $this->connect();
            } catch (\Exception $e) {
                $this->redis = null;
                $this->errors[] = $e->getMessage();
            }
        }
        return $this;
    }

    public function connect()
    {
        if (isset($this->params['socket'])) {
            $args = [$this->params['socket']];
        } else {
            $args = [$this->host, $this->port];
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
        if ($this->redis) {
            $this->redis->close();
        }
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
