<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Cache;


/**
 * 文本文件缓存
 */
class TextCache extends FileCache
{
    protected $ext = '.txt';
    
    public function encode($data)
    {
        return $data;
    }
    
    public function decode($data)
    {
        return $data;
    }
    
    public function readData()
    {
        $bytes = filesize($this->filename);
        if ($bytes > 0) {
            $content = file_get_contents($this->filename);
            $this->data = $this->decode($content);
        }
        return $this->data;
    }
    
    public function writeData($part = false)
    {
        $content = $this->encode($this->data);
        $bytes = file_put_contents($this->filename, $content, LOCK_EX);
        return $bytes && $bytes > 0;
    }
}
