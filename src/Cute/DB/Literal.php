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
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return strval($this->value);
    }
     
    public static function quote($value)
    {
        $format = "'%s'";
        if (is_null($value)) {
            $value = 'NULL';
        } else if ($value instanceof self) {
            $value = strval($value);
        } else if ($value instanceof DateTime) {
            $value = sprintf($format, $value->format('Y-m-d H:i:s'));
        } else if (is_string($value)) {
            $value = sprintf($format, convert($value, 'UTF-8'));
        } else {
            $value = sprintf($format, strval($value));
        }
        return $value;
    }
}
