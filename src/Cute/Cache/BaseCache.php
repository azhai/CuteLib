<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
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
    
    public function share(&$data, $coerce = false, $ttl = 0)
    {
        $this->data = & $data;
        $this->coerce = $coerce;
        $this->ttl = intval($ttl);
        return $this;
    }
    
    public function update(SplSubject $subject)
    {
        if ($coerce = $this->coerce) {
            $this->data = $coerce($this->data);
        }
        $this->writeData();
    }
    
    abstract public function initiate();
    
    abstract public function readData();
    
    abstract public function removeData();
    
    abstract public function writeData($part = false);
}
