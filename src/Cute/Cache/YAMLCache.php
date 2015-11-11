<?php

/**
 * Project      CuteLib
 * Author       Ryan Liu <azhai@126.com>
 * Copyright (c) 2013 MIT License
 */

namespace Cute\Cache;

/**
 * YAML文件缓存
 */
class YAMLCache extends FileCache
{

    protected $ext = '.yml';

    protected function readFile()
    {
        $data = file_get_contents($this->filename);
        if (extension_loaded('yaml')) {
            return yaml_parse($data);
        } else if (class_exists('sfYamlParser', true)) {
            $parser = new \sfYamlParser();
            return $parser->parse($data);
        }
    }

    protected function writeFile($data, $timeout = 0)
    {
        if (extension_loaded('yaml')) {
            $data = yaml_emit($data, YAML_UTF8_ENCODING, YAML_LN_BREAK);
        } else if (class_exists('sfYamlDumper', true)) {
            $dumper = new \sfYamlDumper();
            $data = $dumper->dump($data);
        }
        $bytes = file_put_contents($this->filename, $data, LOCK_EX);
        return $bytes && $bytes > 0;
    }

}
