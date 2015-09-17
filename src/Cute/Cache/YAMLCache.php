<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Cache;


/**
 * YAML文件缓存
 */
class YAMLCache extends TextCache
{
    protected $ext = '.yml';
    
    public function encode($data)
    {
        if (extension_loaded('yaml')) {
            return yaml_emit($data, YAML_UTF8_ENCODING, YAML_LN_BREAK);
        } else if (class_exists('sfYamlDumper', true)) {
            $dumper = new \sfYamlDumper();
            return $dumper->dump($data);
        }
    }
    
    public function decode($data)
    {
        if (extension_loaded('yaml')) {
            return yaml_parse($data);
        } else if (class_exists('sfYamlParser', true)) {
            $parser = new \sfYamlParser();
            return $parser->parse($data);
        }
    }
}
