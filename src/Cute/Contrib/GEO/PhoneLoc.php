<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Contrib\GEO;
use \Cute\Utility\Binary;


/**
 *
 * 号码归属地 phoneloc.dat
 * 下载
 *
 * +-----------------------+
 * +       文件头          +   //索引区第一条(4B) + 索引区最后一条(4B)
 * +-----------------------+
 * +       记录区          +   //(记录: 省 + \t + 市 + \t + 运营商 + \0) * m
 * +-----------------------+
 * +       索引区          +   //(索引: 电话头部(3B) * 2 + 记录偏移量(3B)) * n
 * +-----------------------+
 *
 */
class PhoneLoc extends Binary
{
    protected $term_size = 3;       //数据项定长，3字节
    protected $offset_size = 3;     //偏移量定长，3字节
    
    public function isStopNearStart()
    {
        return true;
    }
    
    /**
     * 读取第一个非\0字符
     */
    public function firstChar()
    {
        do {
            $char = $this->read();
            if ($char === false) { //文件结束EOF
                return '';
            }
        } while (ord($char) === 0);
        return $char;
    }
    
    /**
     * 查找目标所在行
     */
    public function lookFor($target)
    {
        if (! $this->fp) {
            $this->initiate('read');
            $this->readHeaders();
        }
        $this->seek($this->index_first);
        // 比较并决定方向
        $index_size = $this->getIndexSize();
        $sign = self::binSearch($this, 'compare', $target, $this->index_total, $index_size);
        // 进一步找出城市区号和名称，请在这以后关闭文件
        if ($sign < 0) {
            $this->seek(- $index_size, SEEK_CUR); //回退一条索引
        }
        $this->readHex($this->term_size); //开头
    }
    
    /**
     * 格式化电话号码，国际区号保留，中国大陆区号去除
     */
    public static function formatTel($number)
    {
        $number = str_replace('+', '00', trim($number));
        if (starts_with($number, '00')) { //去0后取前7位
            $number = '9' . str_pad($number, 6, '0');
        }
        $number = substr(ltrim($number, '0'), 0, 7);
        return intval($number);
    }
    
    public function writeTel($tel, $return = false)
    {
        $tel = self::formatTel($tel);
        return $this->writeNumber($tel, $this->term_size, $return);
    }
    
    public function readZone()
    {
        //可能有1字节\0分隔符
        return $this->readString($this->firstChar());
    }
    
    /**
     * 查找号码归属地
     */
    public function search($number)
    {
        //将要判断的号码转为3个字节HEX
        $number = self::formatTel($number);
        $number = self::padHex($number, $this->term_size);
        $this->lookFor($number);
        $curr_stop = $this->readHex($this->term_size); //结尾
        $this->seek($this->readNumber($this->offset_size)); //跳到记录区
        if (strcmp($number, $curr_stop) <= 0) {
            return $this->readZone();
        }
    }
}
