<?php
/**
 * Project      CuteLib
 * Author       Ryan Liu <azhai@126.com>
 * Copyright (c) 2013 MIT License
 */

namespace Cute\Cache;

use \SplSubject;
use \SplObserver;


/**
 * 缓存客户端
 */
abstract class BaseCache implements SplObserver
{
    protected $data = null;
    protected $ttl = 0; //失效时间
    protected $coerce = false;
    protected $errors = [];

    public function setExpire($ttl)
    {
        $this->ttl = intval($ttl);
        return $this;
    }

    public function share(&$data, $coerce = false, $ttl = 0)
    {
        $this->data = &$data;
        $this->coerce = $coerce;
        $this->ttl = intval($ttl);
        return $this;
    }

    public function update(SplSubject $subject = null)
    {
        if ($this->coerce) {
            $this->data = exec_function_array($this->coerce, [$this->data]);
        }
        $this->writeData();
    }
    
    public function isSuccessful()
    {
        return empty($this->errors);
    }

    abstract public function initiate();

    abstract public function writeData($part = false);

    abstract public function readData();

    abstract public function removeData();
}
