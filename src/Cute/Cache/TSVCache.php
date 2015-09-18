<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Cache;


/**
 * CSV/TSV文件缓存
 */
class TSVCache extends FileCache
{
    protected $delimiter = ''; //列分隔符
    protected $ext = '.csv';
    
    public function __construct($name, $dir = false, $delimiter = "\t")
    {
        parent::__construct($name, $dir);
        $this->delimiter = $delimiter;
    }
    
    public function readData()
    {
        $this->data = array();
        $fh = fopen($this->filename, 'rb');
        if ($fh === false) {
            return $this->data;
        }
        do {
            $line = fgetcsv($fh, 0, $this->delimiter);
            if ($line === false) {
                break;
            }
            $this->data[] = $line;
        } while (1);
        fclose($fh);
        return $this->data;
    }
    
    public function writeData($part = false)
    {
        $size = 0;
        $fh = fopen($this->filename, 'wb');
        if ($fh === false) {
            return $size;
        }
        foreach ($this->data as $row) {
            $size += fputcsv($fh, $row, $this->delimiter);
        }
        fclose($fh);
        return $size;
    }
}
