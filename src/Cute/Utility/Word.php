<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Utility;


/**
 * 单词转换
 */
class Word
{
    protected $content = '';

    /**
     * 构造函数
     */
    public function __construct($content = '')
    {
        $this->content = $content;
    }
    
    /**
     * 产生16进制随机字符串
     */
    public static function randHash($length = 6)
    {
        $length = $length > 32 ? 32 : $length;
        $hash = md5(mt_rand() . time());
        $buffers = substr($hash, 0, $length);
        return $buffers;
    }

    /**
     * 产生可识别的随机字符串
     */
    public static function randString($length = 6, $shuffles = 2, $good_letters = '')
    {
        if (empty($good_letters)) {
            // 字符池，去掉了难以分辨的0,1,o,O,l,I
            $good_letters = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        }
        srand((float)microtime() * 1000000);
        $buffer = '';
        // 每次可以产生的字符串最大长度
        $gen_length = ceil($length / $shuffles);
        while ($length > 0) {
            $good_letters = str_shuffle($good_letters);
            $buffer .= substr($good_letters, 0, $gen_length);
            $length -= $gen_length;
            $gen_length = min($length, $gen_length);
        }
        return $buffer;
    }

    /**
     * 含有非ASCII字符
     */
    public function hasNonASCII()
    {
        return preg_match('/[^\x20-\x7f]/', $this->content);
    }

    /**
     * 提取第一条网址
     */
    public function fetchFirstURL()
    {
        if (preg_match('/^http[^\x23-\x76]/i', $this->content, $matches)) {
            return $matches[1]; //已经排除空格\x20
        }
    }

    /**
     * 保留字符串中的数字和小数点
     */
    public function getNumbers($to_int = true)
    {
        $times = preg_match_all('/[\d.]+/', $this->content, $matches);
        if ($times === 0 || $times === false) {
            return false;
        }
        $number = implode(current($matches));
        return $to_int ? intval($number) : $number;
    }

    /**
     * 将版本号转为整数，版本号分为三段
     */
    public function ver2int()
    {
        $version = $this->getNumbers(false);
        $vernums = array_map('intval', explode('.', $version)); //将点号分隔的版本号转为整数
        $vernums = array_pad($vernums, 3, 0);
        return intval(vsprintf('%d%02d%02d', $vernums));
    }
}
