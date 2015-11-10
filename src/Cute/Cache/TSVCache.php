<?php
/**
 * Project      CuteLib
 * Author       Ryan Liu <azhai@126.com>
 * Copyright (c) 2013 MIT License
 */

namespace Cute\Cache;


/**
 * CSV/TSV文件缓存
 */
class TSVCache extends FileCache
{
    protected $delimiter = "\t"; //列分隔符
    protected $ext = '.csv';

    public function __construct($name, $dir = false, $delimiter = '')
    {
        parent::__construct($name, $dir);
        if (!empty($delimiter)) {
            $this->delimiter = $delimiter;
        }
    }

    /**
     * @param int $at_least 最少列数
     * @return array 行列二维数组
     */
    public function readData($at_least = 0)
    {
        $this->data = [];
        $fh = fopen($this->filename, 'rb');
        if ($fh === false) {
            return $this->data;
        }
        do {
            $line = fgetcsv($fh, 0, $this->delimiter);
            if (is_null($line) || $line === false) {
                break; //无效的文件指针返回NULL，碰到文件结束时返回FALSE
            }
            if (is_null($line[0])) {
                $line = []; //空行将被返回为一个包含有单个 null 字段的数组
            }
            if ($at_least > 0 && count($line) < $at_least) {
                continue; //列数不足
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
