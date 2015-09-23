<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Commun;
use \Cute\Base\Mocking;

defined('GEARMAN_SUCCESS') or define('GEARMAN_SUCCESS', 0);



class Job
{
    protected $json = '';
    
    public function __construct($json)
    {
        $this->json = $json;
    }
    
    public function workload()
    {
        return $this->json;
    }
    
    public function worknorm()
    {
        $json = $this->workload();
        return json_decode($json, true);
    }
}


/**
 * Gearman任务分发
 * 传递复杂参数或多个参数时，需要用json_encode/json_decode
 */
class JobServer
{
    use \Cute\Base\Deferring;
    
    protected static $instance = null;
    protected $worker_file = ''; // 备用worker文件
    protected $callbacks = array();
    protected $host = '';
    protected $port = 4730;
    protected $timeout = 0;  //单位：milliseconds(1/1000 second)
    protected $client = null;
    protected $worker = null;

    /**
     * 构造函数，给出ServerIP，默认端口4730
     */
    protected function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = intval($port);
    }
    
    public static function getInstance($host = '127.0.0.1',
                                $port = 4730, $timeout = 0)
    {
        if (! self::$instance) {
            self::$instance = new self($host, $port);
            self::$instance->setTimeout($timeout);
        }
        return self::$instance;
    }

    public function close()
    {
        unset($this->client);
        unset($this->worker);
        unset($this->callbacks);
    }

    public function setTimeout($timeout = 0)
    {
        $this->timeout = intval($timeout);
        return $this;
    }

    public function setWorkerFile($worker_file)
    {
        $this->worker_file = $worker_file;
        return $this;
    }
    
    public function standIn($name, $json)
    {
        if ($this->worker_file) {
            defined('WORKER_RUNNING') or define('WORKER_RUNNING', true); //防止worker运行
            require $this->worker_file; //不能使用require_once
        }
        if ($callback = self::$instance->$name) {
            $job = new Job($json);
            return $callback($job);
        }
    }
    
    public function __call($name, $args)
    {
        $json = json_encode($args);
        $client = $this->getClient();
        if ($client instanceof Mocking) {
            return $this->standIn($name, $json);
        }
        try {
            $result = $client->doNormal($name, $json);
            assert($client->returnCode() === GEARMAN_SUCCESS);
        } catch (\Exception $e) { //调用备用函数
            $result = $this->standIn($name, $json);
        }
        return $result;
    }
    
    public function __set($name, $func)
    {
        $this->callbacks[$name] = $func;
        $worker = $this->getWorker();
        return $worker->addFunction($name, $func);
    }
    
    public function __get($name)
    {
        if (isset($this->callbacks[$name])) {
            return $this->callbacks[$name];
        }
    }

    public function getClient()
    {
        if (! $this->client) {
            if (class_exists('\\GearmanClient')) {
                $this->client = new \GearmanClient();
                $this->client->addServer($this->host, $this->port);
            } else {
                $this->client = Mocking::mock();
            }
            if ($this->timeout >= 0) {
                $this->client->setTimeout($this->timeout);
            }
        }
        return $this->client;
    }

    public function getWorker()
    {
        if (! $this->worker) {
            if (class_exists('\\GearmanWorker')) {
                $this->worker = new \GearmanWorker();
                $this->worker->addServer($this->host, $this->port);
            } else {
                $this->worker = Mocking::mock();
            }
        }
        return $this->worker;
    }

    public function run()
    {
        if (! defined('WORKER_RUNNING')) {
            define('WORKER_RUNNING', true); //防止worker重复运行
            while ($this->getWorker()->work());
        }
    }
}
