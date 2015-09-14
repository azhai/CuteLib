<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\DB;
use \DateTime;


/**
 * 字面量，不被转义
 */
class Literal
{
    protected static $format = "'%s'";
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return strval($this->value);
    }
    
    public static function quoteNull()
    {
        return 'NULL';
    }
     
    public static function quote($value)
    {
        if (is_null($value)) {
            return self::quoteNull();
        } else if ($value instanceof self) {
            return strval($value);
        }
        if ($value instanceof DateTime) {
            $value = $value->format('Y-m-d H:i:s');
        } else if (is_string($value)) {
            $value = convert($value, 'UTF-8');
        } else {
            $value = strval($value);
        }
        return sprintf(self::$format, $value);
    }
}
