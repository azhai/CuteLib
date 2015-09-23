<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Utility;
use \DateTime;
use \DateTimeZone;


/**
 * 时间、历法
 */
class Calendar extends DateTime
{
    protected $timestamp = 0;

    /**
     * 构造函数
     */
    public function __construct($time = 'now', $timezone = null)
    {
        if (is_null($timezone) && $default = constant('DEFAULT_TIMEZONE')) {
            $timezone = new DateTimeZone($default);
        }
        if (is_numeric($time)) {
            parent::__construct('now', $timezone);
            $this->setTimestamp($time);
        } else {
            parent::__construct($time, $timezone);
        }
    }
    
    /*
     * 格式化为字符串，支持中文星期
     */
    public function format($format = 'Y-m-d H:i:s')
    {
        $result = parent::format($format);
        if (strpos($format, '星期w') !== false) {
            $weekdays = array('星期0'=>'星期日', '星期1'=>'星期一', '星期2'=>'星期二',
                '星期3'=>'星期三', '星期4'=>'星期四', '星期5'=>'星期五', '星期6'=>'星期六');
            $result = strtr($result, $weekdays);
        }
        return $result;
    }
    
    /**
     * 这个月第一天零点
     */
    public function thisMonthBegin()
    {
        return new self($this->format('Y-m-01'));
    }

    /**
     * 这个月最后一天零点
     */
    public function thisMonthEnd()
    {
        return new self($this->format('Y-m-01') . ' +1 month -1 day');
    }

    /**
     * 下个月第一天零点
     */
    public function nextMonthBegin()
    {
        return new self($this->format('Y-m-01') . ' +1 month');
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
     * 快速计算公式计算立春是2月几日（3/4/5/6），只符合最近三个世纪
     */
    public static function getSpringDay($year)
    {
        static $fixes = array('20' => 4.6295, '21' => 3.87, '22' => 4.15); #修正量
        $year = intval($year);
        $century = strval(ceil($year / 100));
        $figures = $year % 100;
        $fix = array_key_exists($century, $fixes) ? $fixes[$century] : 4;
        return floor($figures * 0.2422 + $fix) - floor(($figures - 1) / 4);
    }
    
    /**
     * 获得生肖index，以立春为分界线
     */
    public function getBirthAnimalIndex()
    {
        $year = intval($this->format('Y'));
        $month = intval($this->format('m'));
        $day = intval($this->format('d'));
        $month_day = $month * 100 + $day;
        if ($month_day < 200 + self::getSpringDay($year)) {
            $year -= 1;
        }
        $index = ($year - 1900) % 12; //1900年是鼠年
        return $index;
    }
    
    /**
     * 获得星座index
     */
    public function getHoroscopeIndex()
    {
        $horos = array( //星座分界线，公历
            120, 219, 321, 420, 521, 622, 723,
            823, 923, 1024, 1123, 1222,
        );
        $month = intval($this->format('m'));
        $day = intval($this->format('d'));
        $month_day = $month * 100 + $day;
        $index = $month - 1;
        if ($month_day < $horos[$index]) {
            $index = ($index == 0) ? 11 : $index - 1;
        }
        return $index;
    }
}
