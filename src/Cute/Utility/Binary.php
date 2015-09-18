<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Utility;



/**
 * 二进制文件
 *
 * 例如：纯真IP数据库QQWry.dat
 * +-----------------------+
 * +       文件头          +   //索引区第一条(4B) + 索引区最后一条(4B)
 * +-----------------------+
 * +       记录区          +   //记录 * m
 * +-----------------------+
 * +       索引区          +   //(索引: IP地址(4B) + 记录偏移量(3B)) * n
 * +-----------------------+
 */
class Binary
{
    protected $is_bom =  false;     //文件编码是否高位在前
    protected $filename = '';       //数据文件名
    protected $fp = null;           //文件字节流
    protected $term_size = 0;       //数据项定长，纯真IP库是4字节
    protected $offset_size = 0;     //偏移量定长，纯真IP库是3字节
    protected $index_first = 0;     //第一条索引位置
    protected $index_last = 0;      //最后一条索引位置
    protected $index_total = 0;     //索引数量
    
    public function __construct($filename, $term_size = 4,
                            $offset_size = 3, $is_bom = false)
    {
        $this->filename = $filename;
        $this->term_size = $term_size;
        $this->offset_size = $offset_size;
        $this->is_bom = $is_bom;
    }
    
    public function initiate($action = 'read')
    {
        if (! file_exists($this->filename)) {
            @mkdir(dirname($this->filename), 0755, true);
            touch($this->filename, 0666);
        }
        $mode = ($action === 'read') ? 'rb' : 'wb';
        $this->fp = fopen($this->filename, $mode);
        return $this;
    }

    public function isBOM()
    {
        return $this->is_bom;
    }
    
    public function close()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
    }
    
    /**
     * 报告指针位置（绝对地址）
     */
    public function tell()
    {
        return ftell($this->fp);
    }
    
    /**
     * 指针跳到某位置
     * $whence: SEEK_SET=绝对 / SEEK_CUR=相对 / SEEK_END=倒数
     */
    public function seek($position, $whence = SEEK_SET)
    {
        if ($whence === SEEK_SET) {
            $position = abs($position);
        } else if ($whence === SEEK_END) {
            $position = - abs($position);
        }
        $result = fseek($this->fp, $position, $whence);
        return $result === 0; //fseek成功时返回0，失败时返回-1
    }
    
    /**
     * 删减文件内容
     */
    public function truncate($remain_size = 0)
    {
        return ftruncate($this->fp, $remain_size);
    }
    
    /**
     * 读取文件
     */
    public function read($bytes = 1)
    {
        if ($bytes === 1) {
            return fgetc($this->fp);
        } else if ($bytes > 1) {
            return fread($this->fp, $bytes);
        }
    }
    
    /**
     * 读取字符串，直到\0或EOF
     */
    public function readString($string = '')
    {
        while (1) {
            $char = $this->read();
            //读到文件结尾EOF或字符串结尾\0
            if ($char === false || ord($char) === 0) {
                break;
            }
            $string .= $char;
        }
        return $string;
    }
    
    /**
     * 读取若干字节的HEX，一个字节是两个Hex
     */
    public function readHex($bytes = 1)
    {
        $hex = bin2hex($this->read($bytes));
        if (! $this->isBOM() && $bytes > 1) {
            $hex = implode('', array_reverse(str_split($hex, 2)));
        }
        return $hex;
    }
    
    /**
     * 读取一个整数，1、2、4、8字节
     */
    public function readInt($bytes = 4)
    {
        $type = 'C';
        if ($bytes === 2) {
            $type = $this->isBOM() ? 'n' : 'v';
        } else if ($bytes === 4) {
            $type = $this->isBOM() ? 'N' : 'V';
        } else if ($bytes === 8) {
            $type = $this->isBOM() ? 'J' : 'P';
        }
        $assoc = unpack($type . 'int', $this->read($bytes));
        return $assoc['int'];
    }
    
    /**
     * 读取一个整数，若干字节
     */
    public function readNumber($bytes = 3)
    {
        $hexes = $this->readHex($bytes);
        return intval($hexes, 16);
    }
    
    /**
     * 读取头部信息，前8个字节
     */
    public function readHeaders()
    {
        if ($this->index_first <= 0) {
            $this->index_first = $this->readInt();   #第一条索引区位置，4字节
            $this->index_last = $this->readInt();          #最后一条索引区位置，4字节
            $bytes = $this->index_last - $this->index_first;
            $index_size = $this->term_size + $this->offset_size;
            if ($index_size > 0) {
                $this->index_total = floor($bytes / $index_size) + 1;
            }
        }
        return $this->index_first;
    }
    
    /**
     * 写入文件
     */
    public function write($data)
    {
        return fwrite($this->fp, $data);
    }
    
    /**
     * 写入字符串，默认添加\0结尾
     */
    public function writeString($string, $end_char = null, $return = false)
    {
        $string .= (is_string($end_char) ? $end_char : chr(0));
        return $return ? $string : $this->write($string);
    }
    
    /**
     * 写入若干字节的HEX，一个字节是两个Hex
     */
    public function writeHex($hex, $bytes = false, $return = false)
    {
        if (empty($bytes)) {
            $bytes = ceil(strlen($hex) / 2);
        }
        $hex = str_pad($hex, $bytes * 2, '0', STR_PAD_LEFT);
        if (! $this->isBOM() && $bytes > 1) {
            $hex = implode('', array_reverse(str_split($hex, 2)));
        }
        $bin = @hex2bin($hex);
        return $return ? $bin : $this->write($bin);
    }
    
    /**
     * 写入一个整数，1、2、4、8字节
     */
    public function writeInt($data, $bytes = 4, $return = false)
    {
        $type = 'C';
        if ($bytes === 2) {
            $type = $this->isBOM() ? 'n' : 'v';
        } else if ($bytes === 4) {
            $type = $this->isBOM() ? 'N' : 'V';
        } else if ($bytes === 8) {
            $type = $this->isBOM() ? 'J' : 'P';
        }
        $bin = pack($type, $data);
        return $return ? $bin : $this->write($bin);
    }
    
    /**
     * 写入一个整数，若干字节
     */
    public function writeNumber($data, $bytes = 3, $return = false)
    {
        $hexes = dechex(intval($data));
        return $this->writeHex($hexes, $bytes, $return);
    }
    
    /**
     * 写入头部信息，可选的版本号
     */
    public function writeHeaders($version = '')
    {
        $this->seek(0);
        $this->writeInt($this->index_first);   #第一条索引区位置，4字节
        $this->writeInt($this->index_last);    #最后一条索引区位置，4字节
        if ($version) {
            $this->writeString($version);
        }
    }

    /**
     * 读取和比对数据项
     */
    public function compare($offset, $target, $index_size)
    {
        $this->seek($this->index_first + $offset * $index_size);
        $current = $this->readHex($this->term_size); //开头
        $this->seek(- $this->term_size, SEEK_CUR); //回退当前索引开头
        return strcmp($target, $current); // 指出下次偏移的方向
    }

    /**
     * 二分（折半）查找算法
     */
    public static function binSearch(& $object, $method, $target,
                                            $total, $index_size)
    {
        $left = 0;
        $right = $total;
        do {
            $middle = $left + floor(($right - $left) / 2);
            $sign = $object->$method($middle, $target, $index_size);
            if ($sign > 0) { //目标在右侧
                $left = $middle;
            } else if ($sign < 0) { //目标在左侧
                $right = $middle;
            } else {
                break;
            }
        } while ($right - $left > 1);
        return $sign;
    }
}
