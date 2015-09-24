<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Logging;
use \Cute\Context\Input;
use \Cute\Utility\Word;


/**
 * 日志
 */
abstract class Logger
{
    const EMERGENCY = 'EMERGENCY';
    const ALERT = 'ALERT';
    const CRITICAL = 'CRITICAL';
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const NOTICE = 'NOTICE';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    
    protected $threshold = false;
    protected $threshold_value = null;
    
    /**
     * 构造函数，设置过滤级别
     * @param string $threshold 过滤级别（低于本级别的不记录）
     */
    public function __construct($threshold = false)
    {
        if ($threshold === false) {
            $this->threshold = self::DEBUG;
        } else {
            $this->threshold = strtoupper($threshold);
        }
    }
    
    public static function format($message, array $context = array())
    {
        $content = is_null($message) ? '' : (string) $message;
        return Word::replaceWith($content, $context, '{', '}');
    }
    
    public static function getClientIP()
    {
        return Input::getClientIP();
    }
    
    /**
     * 比较两个过滤级别的重要程度
     * @param string $level  消息级别
     * @return bool 消息级别持平或更重要
     */
    public function compareLevel($level)
    {
        static $level_orders = array(
            self::EMERGENCY => 0,
            self::ALERT     => 1,
            self::CRITICAL  => 2,
            self::ERROR     => 3,
            self::WARNING   => 4,
            self::NOTICE    => 5,
            self::INFO      => 6,
            self::DEBUG     => 7,
        );
        if (is_null($this->threshold_value) && isset($level_orders[$this->threshold])) {
            $this->threshold_value = $level_orders[$this->threshold];
        }
        if (isset($level_orders[$level])) {
            return $level_orders[$level] <= $this->threshold_value;
        }
    }
    
    /**
     * System is unusable.
     */
    public function emergency($message, array $context = array())
    {
        $this->rawlog(self::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     */
    public function alert($message, array $context = array())
    {
        $this->rawlog(self::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     */
    public function critical($message, array $context = array())
    {
        $this->rawlog(self::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public function error($message, array $context = array())
    {
        $this->rawlog(self::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     */
    public function warning($message, array $context = array())
    {
        $this->rawlog(self::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice($message, array $context = array())
    {
        $this->rawlog(self::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     */
    public function info($message, array $context = array())
    {
        $this->rawlog(self::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     */
    public function debug($message, array $context = array())
    {
        $this->rawlog(self::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    abstract public function rawlog($level, $message, array $context = array());
    
    abstract public function close();
}
