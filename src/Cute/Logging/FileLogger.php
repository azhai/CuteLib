<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Logging;
use \Cute\Logging\Logger;

defined('LOG_WRITE_FILE_FREQ') or define('LOG_WRITE_FILE_FREQ', 0.2); //写文件的概率


/**
 * 文件日志
 */
class FileLogger extends Logger
{
    use \Cute\Base\Destructible;
    
    protected $filename = '';
    protected $records = array();
    
    /**
     * 构造函数，设置文件位置和过滤级别
     * @param string $name 日志名称
     * @param string $directory 日志目录
     * @param string $threshold 过滤级别（低于本级别的不记录）
     */
    public function __construct($name = '', $directory = false, $threshold = false)
    {
        $this->filename = ($name ? $name . '_' : '') . '%s.log';
        if ($directory === false) {
            $directory = realpath('./logs');
        }
        if (is_dir($directory) || mkdir($directory, 0777, true)) {
            $this->filename = $directory . DIRECTORY_SEPARATOR . $this->filename;
        }
        parent::__construct($threshold);
        $this->defer();
    }
    
    public function close()
    {
        $this->writeFiles();
        unset($this->records);
    }
    
    public function writeFiles()
    {
        foreach ($this->records as $date => & $records) {
            $file = sprintf($this->filename, $date);
            $appends = implode('', $records);
            $bytes = file_put_contents($file, $appends, FILE_APPEND | LOCK_EX);
            if ($bytes !== false) { //写入成功，清除已写记录
                $records = array();
            }
        }
    }
    
    public function append()
    {
        $record = implode(' ', func_get_args());
        $today = date('Ymd');
        if (! isset($this->records[$today])) {
            $this->records[$today] = array();
        }
        array_push($this->records[$today], $record . PHP_EOL);
    }
    
    public function rawlog($level, $message, array $context = array())
    {
        $level = strtoupper($level);
        if ($this->compareLevel($level)) {
            $content = self::format($message, $context);
            $ipaddr = self::getClientIP();
            $datetime = date('Y-m-d H:i:s');
            $this->append($datetime, $ipaddr, $level, $content);
        }
        if (LOG_WRITE_FILE_FREQ >= 1 || LOG_WRITE_FILE_FREQ >= mt_rand(1, 10000) / 10000) {
            $this->writeFiles();
        }
    }
}