<?php
/**
 * Project      CuteLib
 * Author       Ryan Liu <azhai@126.com>
 * Copyright (c) 2013 MIT License
 */

namespace Cute\Cache;


/**
 * JSON/BSON文件缓存
 */
class XSONCache extends TextCache
{
    protected $ext = '.json';

    public function encode($data)
    {
        return json_encode($data);
    }

    public function decode($data)
    {
        return json_decode($data);
    }
}
