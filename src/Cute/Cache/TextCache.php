<?php
/**
 * Project      CuteLib
 * Author       Ryan Liu <azhai@126.com>
 * Copyright (c) 2013 MIT License
 */

namespace Cute\Cache;


/**
 * 文本文件缓存
 */
class TextCache extends FileCache
{
    protected $ext = '.txt';

    public function readData()
    {
        $bytes = filesize($this->filename);
        if ($bytes > 0) {
            try {
                $content = file_get_contents($this->filename);
                $this->data = $this->decode($content);
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
        return $this->data;
    }

    public function decode($data)
    {
        return $data;
    }

    public function writeData($part = false)
    {
        try {
            $content = $this->encode($this->data);
            $bytes = file_put_contents($this->filename, $content, LOCK_EX);
        } catch (\Exception $e) {
            $bytes = false;
            $this->errors[] = $e->getMessage();
        }
        return $bytes && $bytes > 0;
    }

    public function encode($data)
    {
        return $data;
    }
}
