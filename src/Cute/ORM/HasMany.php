<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \Cute\ORM\Relation;
use \Cute\Utility\Inflect;


/**
 * 一对多关系
 */
class HasMany extends Relation
{
    protected $foreign_key = '';
    protected $is_unique = false;
    
    public function __construct($model = '\\Cute\\ORM\\Model', $table = '',
                                $foreign_key = '')
    {
        parent::__construct($model, $table);
        $this->foreign_key = $foreign_key;
    }
    
    public function getForeignKey($name = '')
    {
        if (empty($this->foreign_key)) {
            $table_name = $this->query->getTable();
            $this->foreign_key = Inflect::singularize($table_name) . '_id';
        }
        return $this->foreign_key;
    }

    public function relative($name, array& $result)
    {
        if (empty($result)) {
            return array();
        }
        $fkey = $this->getForeignKey();
        $values = $this->getAttrs($result);
        $query = $this->newQuery();
        $query->combine($fkey, $values, $this->is_unique);
        $default = $this->is_unique ? null : array();
        $this->setAttrs($result, $values, $name, false, $default);
        return $values;
    }
}
