<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Utility;


/**
 * 日期
 */
class Date extends \DateTime
{
    protected $timestamp = 0;

    /**
     * 构造函数
     */
    public function __construct($time = 'now', $timezone = null)
    {
        if (is_null($timezone) && $default = constant('DEFAULT_TIMEZONE')) {
            $timezone = new \DateTimeZone($default);
        }
        if (is_numeric($time)) {
            parent::__construct('now', $timezone);
            $this->setTimestamp($time);
        } else {
            parent::__construct($time, $timezone);
        }
    }
    
    /**
     * 将表示时长（最大单位为周）的字符串转为秒数
     */
    public static function parseDurtion($durtion)
    {
        if (empty($durtion)) {
            return 0;
        }
        if (is_int($durtion) || is_float($durtion)) {
            return $durtion;
        }
        if (is_string($durtion)) {
            $unit = strtolower(substr($durtion, -1));
            if (is_numeric($unit)) { //无单位
                return floatval($durtion);
            }
            $durtion = trim(substr($durtion, 0, -1));
            $times = 1;
            switch ($unit) {
                case 'w':
                    $times *= 7;
                case 'd':
                    $times *= 24;
                case 'h':
                    $times *= 60;
                case 'm':
                    $times *= 60;
            }
            return floatval($durtion) * $times;
        }
    }
    
    /**
     * 这个月第一天零点
     */
    public function thisMonthBegin() {
        return new self($this->format('Y-m-01'));
    }

    /**
     * 这个月最后一天零点
     */
    public function thisMonthEnd() {
        return new self($this->format('Y-m-01') . ' +1 month -1 day');
    }

    /**
     * 下个月第一天零点
     */
    public function nextMonthBegin() {
        return new self($this->format('Y-m-01') . ' +1 month');
    }
}
