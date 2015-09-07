<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute;


/**
 * WEB控制器
 */
class Handler
{
    protected $app = null;
    protected $succor = null;
    
    public function __construct($succor = null)
    {
        $this->app = app();
        $this->succor = $succor;
    }
    
    public function init($method)
    {
        return method_exists($this, $method) ? $method : '';
    }
    
    public function __invoke()
    {
        $method = $this->app->getMethod();
        $args = func_get_args();
        if ($method = $this->init($method)) {
            return exec_method_array($this, $method, $args);
        } else if ($this->succor) {
            return exec_function_array($this->succor, $args);
        } else {
            return $this->app->abort(403);
        }
    }
}
