<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \Cute\ORM\HasMany;
use \Cute\Utility\Inflect;


/**
 * 多对多关系
 */
class ManyToMany extends HasMany
{
    protected $another_foreign_key = '';
    protected $middle_table = '';
    
    public function __construct($model = '\\Cute\\ORM\\Model', $table = '',
            $foreign_key = '', $another_foreign_key = '', $middle_table = '')
    {
        parent::__construct($model, $table, $foreign_key);
        $this->another_foreign_key = $another_foreign_key;
        $this->middle_table = $middle_table;
    }
    
    public function getAnotherForeignKey($name = '')
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
        
        $fkey = $this->getForeignKey();
        $values = $this->getAttrs($result);
        $query = $this->newQuery('\\Cute\\ORM\\Model', $this->middle_table);
        $query->combine($fkey, $values, false);
        $an_fkey = $this->getAnotherForeignKey($name);
        $another_values = array();
        foreach ($values as $key => $value) {
            foreach ($value as $k => $val) {
                $index = $val->$an_fkey;
                $values[$key][$k] = $index;
                $another_values[$index] = null;
            }
        }
        $query = $this->newQuery();
        $query->combine(reset($pkeys), $another_values, true);
        foreach ($result as &$object) {
            $key = $object->getID();
            if (! isset($values[$key])) {
                continue;
            }
            $objs = array();
            foreach ($values[$key] as $index) {
                $objs[] = & $another_values[$index];
            }
            $object->$name = $objs;
        }
        return $another_values;
    }
}
