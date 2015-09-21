<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Contrib\GEO;
use \Cute\Base\IP;
use \Cute\Utility\Binary;


/**
 *
 * 国家IP数据库
 * 下载 https://db-ip.com/db/download/country
 *
 * +-----------------------+
 * +       文件头          +   //索引区第一条(4B) + 索引区最后一条(4B)
 * +-----------------------+
 * +       记录区          +   //(记录: 国家缩写(2B) + \0) * n
 * +-----------------------+
 * +       索引区          +   //(索引: IP地址(4B) * 2 + 记录偏移量(3B)) * n
 * +-----------------------+
 *
 */
class IPCountry extends Binary
{
    protected $term_size = 4;       //数据项定长，4字节
    protected $offset_size = 2;     //偏移量定长，2字节
    
    public function isStopNearStart()
    {
        return true;
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
     * 将IP对象或字符串ip转为HEX格式
     */
    public static function formatIP($ipaddr)
    {
        return IP::toHex($ipaddr);
    }
    
    public function writeIP($ipaddr)
    {
        $hex = self::formatIP($ipaddr);
        return $this->writeHex($hex);
    }
    
    public function readZone()
    {
        $code = $this->readString();
        return trim($code);
    }
    
    /**
     * 查找IP详细位置
     */
    public function search($ipaddr)
    {
        //将要判断的IP转为4个字节HEX
        $ipaddr = self::formatIP($ipaddr);
        $this->lookFor($ipaddr);
        if ($this->isStopNearStart()) {
            $curr_stop = $this->readHex($this->term_size); //结尾
            $this->seek($this->readNumber($this->offset_size)); //跳到记录区
        } else {
            $this->seek($this->readNumber($this->offset_size)); //跳到记录区
            $curr_stop = $this->readHex($this->term_size); //结尾
        }
        if (strcmp($ipaddr, $curr_stop) <= 0) {
            return $this->readZone();
        }
    }
}
