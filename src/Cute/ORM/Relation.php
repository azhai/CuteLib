<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \Cute\DB\Database;
use \Cute\ORM\Mapper;


/**
 * 关系
 */
abstract class Relation
{
    protected $mapper = null;
    protected $model = '';
    
    public function __construct($model = '')
    {
        $this->model = empty($model) ? '\\Cute\\ORM\\Model' : $model;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function bind(Database& $db, $table = '')
    {
        $this->mapper = new Mapper($db, $this->model);
        return $this;
    }

    public function getMapper($model = '', $table = '')
    {
        if (empty($model) && empty($table)) {
            return $this->mapper;
        } else {
            $db = $this->mapper->getDB();
            return new Mapper($db, $model, $table);
        }
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
