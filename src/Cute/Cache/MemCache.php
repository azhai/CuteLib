<?php
/**
 * Project      CuteLib
 * Author       Ryan Liu <azhai@126.com>
 * Copyright (c) 2013 MIT License
 */

namespace Cute\Cache;


/**
 * Memcache缓存
 */
class MemCache extends BaseCache
{
    use \Cute\Base\Deferring;

    const MEMCACHE_WEIGHT_UNIT = 12;
    protected $name = '';
    protected $memcache = null;
    protected $host = '';
    protected $port = 0;
    protected $persistent = false;
    protected $weight = 1;

    public function __construct($name, $host = '127.0.0.1', $port = 11211,
                                $persistent = false, $weight = 1)
    {
        $this->name = $name;
        $this->host = $host;
        $this->port = intval($port);
        $this->persistent = $persistent;
        $this->weight = intval($weight);
        $this->initiate();
    }

    public function initiate()
    {
        if (!extension_loaded('memcache')) {
            $this->errors[] = 'Extension memcache is not found !';
        } else {
            $this->memcache = new \Memcache();
            try {
                $this->connect();
            } catch (\Exception $e) {
                $this->memcache = null;
                $this->errors[] = $e->getMessage();
            }
        }
        return $this;
    }

    public function connect()
    {
        $weight = intval($this->weight) * self::MEMCACHE_WEIGHT_UNIT;
        $this->memcache->addServer($this->host, $this->port,
            $this->persistent, $weight);
    }

    public function close()
    {
        if ($this->memcache) {
            $this->memcache->close();
        }
    }

    public function readData()
    {
        $data = $this->memcache->get($this->name);
        if ($data !== false) {
            return $data;
        }
    }

    public function writeData($part = false)
    {
        return $this->memcache->set($this->name, $this->data,
            MEMCACHE_COMPRESSED, $this->ttl);
    }

    public function removeData()
    {
        if ($this->memcache->exists($this->name)) {
            return $this->memcache->delete($this->name);
        }
    }
}
