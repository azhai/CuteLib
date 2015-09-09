<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \Cute\ORM\Query;
use \Cute\ORM\HasMany;


/**
 * 多对多关系
 */
class ManyToMany extends HasMany
{
    protected $another_foreign_key = '';
    protected $middle_table = '';
    
    public function __construct($model = '\\Cute\\Model', $table = '',
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
        return $result;
    }
}
