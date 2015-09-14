<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \Cute\ORM\Relation;
use \Cute\DB\Database;
use \Cute\Utility\Inflect;


/**
 * 一对多关系
 */
class HasMany extends Relation
{
    protected $foreign_key = '';
    protected $is_unique = false;
    
    public function __construct($model = '', $foreign_key = '')
    {
        parent::__construct($model);
        $this->foreign_key = $foreign_key;
    }

    public function bind(Database& $db, $table = '')
    {
        if (empty($this->foreign_key)) {
            $this->foreign_key = Inflect::singularize($table) . '_id';
        }
        return parent::bind($db, $table);
    }
    
    public function getForeignKey()
    {
        return $this->foreign_key;
    }

    public function relative($name, array& $result)
    {
        if (empty($result)) {
            return array();
        }
        $fkey = $this->getForeignKey();
        $values = $this->getAttrs($result);
        $mapper = $this->getMapper();
        $mapper->combine($fkey, $values, $this->is_unique);
        $default = $this->is_unique ? null : array();
        $this->setAttrs($result, $values, $name, false, $default);
        return $values;
    }
}
