<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Widget;
use \Cute\Widget\Counter;


/**
 * 文本计数器
 */
class FileCounter extends Counter
{
    protected $filename = '';
    
    public function connect($dir = false)
    {
        if (empty($dir) && $dir !== '') {
            $dir = sys_get_temp_dir();
        } else {
            $dir = rtrim(str_replace('\\', '/', $dir), '/');
        }
        $this->filename = $dir . '/' . $this->name . '.txt';
        if (! is_readable($this->filename)) {
            $success = touch($this->filename);
        } else {
            $success = true;
        }
        if (! $success) {
            $this->filename = '';
        }
        return $success;
    }
    
    public function readValue()
    {
        $value = false;
        $bytes = filesize($this->filename);
        if ($bytes > 0) {
            $value = file_get_contents($this->filename);
        }
        if ($value !== false && strlen(trim($value)) > 0) {
            $this->value = intval($value);
        } else {
            $this->writeValue();
        }
        return $this->value;
    }
    
    public function writeValue()
    {
        $bytes = file_put_contents($this->filename, $this->value);
        return $bytes && $bytes > 0;
    }
    
    public function remove()
    {
        if (file_exists($this->filename)) {
            return unlink($this->filename);
        }
    }
}
