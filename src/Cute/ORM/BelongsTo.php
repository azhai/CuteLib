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
    protected $foreign_key = '';
    
    public function __construct($model = '\\Cute\\Model', $table = '',
                                $foreign_key = '')
    {
        parent::__construct($model, $table);
        $this->foreign_key = $foreign_key;
    }
    
    public function getForeignKey($name = '')
    {
        if (empty($this->foreign_key)) {
            $this->foreign_key = $name . '_id';
        }
        return $this->foreign_key;
    }

    public function relative($name, array& $result)
    {
        if (empty($result)) {
            return array();
        }
        $fkey = $this->getForeignKey($name);
        $query = $this->newQuery();
        $pkeys = exec_method_array($this->model, 'getPKeys');
        if (empty($pkeys)) {
            return array();
        }
        $values = array();
        foreach ($result as &$object) {
            $values[$object->$fkey] = null;
        }
        $query->combine(reset($pkeys), $values, true);
        foreach ($result as &$object) {
            $key = $object->$fkey;
            if (isset($values[$key])) {
                $object->$name = & $values[$key];
            } else {
                $object->$name = null;
            }
        }
        return $values;
    }
}
