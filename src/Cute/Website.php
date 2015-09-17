<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute;
use \Cute\Application;
use \Cute\Context\Router;


/**
 * 网站
 */
class Website extends Application
{
    protected $method = '';
    public $url = '';
    public $rule = '';

    /**
     * 初始化环境
     */
    public function initiate()
    {
        $router = Router::getCurrent();
        $this->install($router, array(
            'dispatch', 'abort', 'redirect'
        ));
        $this->install('\\Cute\\Context\\Input', array(
            'getClientIP', 'input' => 'getInstance',
        ));
        return $this;
    }
    
    public function route($path, $handler)
    {
        $router = Router::getCurrent();
        return $router->route($path, $handler);
    }
    
    public function mount($directory, $wildcard = '*.php')
    {
        $router = Router::getCurrent();
        $router->mount($directory, $wildcard);
        return $this;
    }
    
    /**
     * 获取当前method
     */
    public function getMethod()
    {
        if (empty($this->method)) {
            $input = $this->input('SERVER');
            $method = $input->request('_method', '');
            if (empty($method)) {
                $method = $input->get('REQUEST_METHOD', 'GET');
            }
            $this->method = strtolower($method);
        }
        return $this->method;
    }
    
    /**
     * 获取当前网址对应handlers，并从后向前运行第一个拦截成功的
     */
    public function run()
    {
        $route_key = $this->getConfig('route_key', 'r');
        $path = $this->input('GET')->get($route_key, '/');
        try {
            $route = $this->dispatch($path);
            $succor = null;
            foreach ($route['handlers'] as $handler) {
                if (empty($handler)) {
                    continue;
                }
                if (is_string($handler) && class_exists($handler, true)) {
                    $handler = new $handler($succor);
                }
                if ($handler && is_callable($handler)) {
                    $succor = & $handler;
                }
            }
            if ($succor) {
                $this->url = $route['url'];
                $this->rule = $route['rule'];
                $output = exec_function_array($succor, $route['args']);
            }
        } catch (\Exception $e) {
            $output = strval($e);
        }
        return die($output);
    }
}
