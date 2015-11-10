<?php
/**
 * Project      CuteLib
 * Author       Ryan Liu <azhai@126.com>
 * Copyright (c) 2013 MIT License
 * Code From:
 *      http://www.sitepoint.com/saving-php-sessions-in-redis/
 */

namespace Cute\Web;

use \Cute\Cache\RedisCache;
use \SessionHandlerInterface;


/**
 * Redis会话保存管理器
 * Notice:
 *  传统的文件会话保存管理器，在会话开始的时候会给会话数据文件加锁。
 */
class SessionHandler extends RedisCache
    implements SessionHandlerInterface
{
    const PREFIX = 'PHPSESSID:';
    protected $ttl = 1800; //失效时间
    protected $params = [
        'persistent' => true,
        'socket' => null,
        'serializer' => null,
    ];

    public function __construct($host = '127.0.0.1', $port = 6379)
    {
        parent::__construct('', $host, $port);
        $this->initiate();
        if ($this->isSuccessful()) {
            session_set_save_handler($this);
        }
        @session_start();
    }

    public function open($save_path, $session_name)
    {
        // No action necessary because connection is injected
        // in constructor and arguments are not applicable.
    }

    public function read($sid)
    {
        return $this->setName($sid)->readData();
    }

    public function setName($sid)
    {
        $this->name = self::PREFIX . $sid;
        return $this;
    }

    public function write($sid, $data)
    {
        $this->data = $data;
        $this->setName($sid)->writeData();
    }

    public function destroy($sid)
    {
        return $this->setName($sid)->removeData();
    }

    public function gc($max_lifetime)
    {
        // no action necessary because using EXPIRE
    }
}