<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \Cute\ORM\Relation;


/**
 * 一对一或多对一关系
 */
class BelongsTo extends Relation
{
    protected $another_foreign_key = '';
    
    public function __construct($model = '', $another_foreign_key = '')
    {
        parent::__construct($model);
        $this->another_foreign_key = $another_foreign_key;
    }
    
    public function getAnotherForeignKey($name)
    {
        if (empty($this->another_foreign_key)) {
            $this->another_foreign_key = $name . '_id';
        }
        return $this->another_foreign_key;
    }

    public function relative($name, array& $result)
    {
        if (empty($result)) {
            return array();
        }
        $pkeys = exec_method_array($this->model, 'getPKeys');
        if (empty($pkeys)) {
            return array();
        }
        $fkey = $this->getAnotherForeignKey($name);
        $values = $this->getAttrs($result, $fkey);
        $mapper = $this->getMapper();
        $mapper->combine(reset($pkeys), $values, true);
        $this->setAttrs($result, $values, $name, $fkey);
        return $values;
    }
}
