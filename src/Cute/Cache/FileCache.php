<?php
/**
 * Project      CuteLib
 * Author       Ryan Liu <azhai@126.com>
 * Copyright (c) 2013 MIT License
 */

namespace Cute\Cache;


/**
 * 文件缓存
 */
class FileCache extends BaseCache
{
    protected $filename = ''; //完整文件路径
    protected $ext = '.php';

    public function __construct($name, $dir = false)
    {
        if (empty($dir)) {
            $dir = sys_get_temp_dir();
        } else {
            $dir = rtrim($dir, DIRECTORY_SEPARATOR);
            @mkdir($dir, 0755, true);
        }
        $this->filename = $dir . DIRECTORY_SEPARATOR . $name . $this->ext;
        $this->initiate();
    }

    public function initiate()
    {
        if (!is_readable($this->filename)) {
            touch($this->filename);
        }
        return $this;
    }

    public function readData()
    {
        $bytes = filesize($this->filename);
        if ($bytes > 0) {
            $this->data = (include $this->filename);
        }
        return $this->data;
    }

    public function writeData($part = false)
    {
        $content = "<?php \nreturn " . var_export($this->data, true) . ";\n";
        try {
            $bytes = file_put_contents($this->filename, $content);
        } catch (\Exception $e) {
            $bytes = false;
            $this->errors[] = $e->getMessage();
        }
        return $bytes && $bytes > 0;
    }

    public function removeData()
    {
        if (file_exists($this->filename)) {
            return unlink($this->filename);
        }
    }
}
