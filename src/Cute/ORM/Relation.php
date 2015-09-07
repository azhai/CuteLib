<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \Cute\ORM\Query;
use \Cute\Utility\Inflect;


/**
 * 关系
 */
class Relation
{
    const TYPE_ONE_TO_ONE = 1; //一对一
    const TYPE_BELONGS_TO = 2; //多对一
    const TYPE_HAS_MANY = 3; //一对多
    const TYPE_MANY_TO_MANY = 4; //多对多
    
    protected $query = null;
    protected $type = 1;
    protected $model = '';
    protected $table = '';
    protected $foreign_key = '';
    protected $another_foreign_key = '';
    protected $middle_table = '';
    
    public function __construct($type, $model = '\\Cute\\Model', $table = '',
            $foreign_key = '', $another_foreign_key = '', $middle_table = '')
    {
        $this->type = $type;
        $this->model = $model;
        $this->table = $table;
        $this->foreign_key = $foreign_key;
        $this->another_foreign_key = $another_foreign_key;
        $this->middle_table = $middle_table;
    }

    public function bind(Query& $query)
    {
        $this->query = $query;
        return $this;
    }

    public function relative($name, array& $result)
    {
        if (empty($result)) {
            return array();
        }
        switch ($this->type) {
            case self::TYPE_ONE_TO_ONE:
            case self::TYPE_BELONGS_TO:
                $data = $this->belongsTo($name, $result);
                break;
            case self::TYPE_HAS_MANY:
                $data = $this->hasMany($name, $result);
                break;
            case self::TYPE_MANY_TO_MANY:
                $data = $this->manyToMany($name, $result);
                break;
            default:
                $data = array();
                break;
        }
        return $data;
    }

    public function belongsTo($name, array& $result)
    {
        assert($db = $this->query->getDB());
        if (empty($this->foreign_key)) {
            $this->foreign_key = $name . '_id';
        }
        $fkey = $this->foreign_key;
        $query = new Query($db, $this->model, $this->table);
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

    public function hasMany($name, array& $result)
    {
        assert($db = $this->query->getDB());
        if (empty($this->foreign_key)) {
            $table_name = $this->query->getTable();
            $this->foreign_key = Inflect::singularize($table_name) . '_id';
        }
        $fkey = $this->foreign_key;
        $query = new Query($db, $this->model, $this->table);
        $values = array();
        foreach ($result as &$object) {
            $values[$object->getID()] = null;
        }
        $query->combine($fkey, $values, false);
        foreach ($result as &$object) {
            $key = $object->getID();
            if (isset($values[$key])) {
                $object->$name = & $values[$key];
            } else {
                $object->$name = array();
            }
        }
        return $values;
    }

    public function manyToMany($name, array& $result)
    {
        throw new \BadMethodCallException();
    }
}
