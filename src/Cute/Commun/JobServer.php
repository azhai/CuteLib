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


class WorkerException extends \Exception {}


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
}


/**
 * Gearman任务分发
 * 传递复杂参数或多个参数时，需要用json_encode/json_decode
 */
class JobServer
{
    use \Cute\Base\Deferring;
    
    public $worker_file = ''; // 备用worker文件
    public $callbacks = array();
    protected $host = '';
    protected $timeout = -1;  //单位：milliseconds(1/1000 second)
    protected $client = null;
    protected $worker = null;

    /**
     * 构造函数，给出ServerIP，默认端口4730
     */
    public function __construct($host = '127.0.0.1', $timeout = -1, $worker_file = false)
    {
        $this->host = $host;
        $this->timeout = $timeout;
        if (! empty($worker_file)) {
            $this->worker_file = $worker_file;
        }
    }

    public function close()
    {
        unset($this->client);
        unset($this->worker);
        unset($this->callbacks);
    }
    
    public function call($name, $json)
    {
        $client = $this->getClient();
        $result = @exec_method_array($client, 'doNormal', array($name, $json));
        if ($client->returnCode() !== GEARMAN_SUCCESS) {
            throw new WorkerException($client->error());
        }
        return $result;
    }
    
    public function __call($name, $args)
    {
        $json = json_encode($args);
        try {
            $result = $this->call($name, $json);
        } catch (WorkerException $e) { //调用备用函数
            if ($this->worker_file) {
                defined('WORKER_RUNNING') or define('WORKER_RUNNING', true); //防止worker运行
                require_once $this->worker_file;
            }
            if ($this && $callback = $this->$name) {
                $job = new Job($json);
                $result = $callback($job);
            }
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
                $this->client->addServer($this->host);
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
                $this->worker->addServer($this->host);
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
