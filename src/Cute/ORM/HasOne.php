<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \Cute\ORM\HasMany;


/**
 * 一对一关系，外键不在本表
 */
class HasOne extends HasMany
{
    protected $is_unique = true;
}
