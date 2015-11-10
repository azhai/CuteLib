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
class RedisDictCache extends RedisCache
{
    public function readData()
    {
        $data = $this->redis->hGetAll($this->name);
        if ($data !== false) {
            $this->data = $data;
            return $this->data;
        }
    }

    public function writeData($part = false)
    {
        $count = 0;
        foreach ($this->data as $key => $value) {
            $this->redis->hSet($this->name, $key, $value);
            $count ++;
        }
        $this->redis->expire($this->name, $this->ttl);
        return $count;
    }
}
