<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \Cute\ORM\Query;


/**
 * 关系
 */
abstract class Relation
{
    protected $query = null;
    protected $model = '';
    protected $table = '';
    
    public function __construct($model = '\\Cute\\ORM\\Model', $table = '')
    {
        $this->model = $model;
        $this->table = $table;
    }

    public function bind(Query& $query)
    {
        $this->query = $query;
        return $this;
    }

    public function newQuery($model = '', $table = '')
    {
        assert($db = $this->query->getDB());
        if (empty($model)) {
            $model = & $this->model;
        }
        if (empty($table)) {
            $table = $this->table;
        }
        return new Query($db, $model, $table);
    }
    
    protected function getAttrs(array& $result, $attr = false)
    {
        $attrs = array();
        foreach ($result as &$object) {
            $key = $attr ? $object->$attr : $object->getID();
            $attrs[$key] = null;
        }
        return $attrs;
    }
    
    protected function setAttrs(array& $result, array& $values, $name,
                                $attr = false, $default = null)
    {
        foreach ($result as &$object) {
            $key = $attr ? $object->$attr : $object->getID();
            if (isset($values[$key])) {
                $object->$name = & $values[$key];
            } else {
                $object->$name = $default;
            }
        }
    }

    abstract public function relative($name, array& $result);
}
