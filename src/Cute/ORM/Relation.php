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
    
    public function __construct($model = '\\Cute\\Model', $table = '')
    {
        $this->model = $model;
        $this->table = $table;
    }

    public function bind(Query& $query)
    {
        $this->query = $query;
        return $this;
    }

    public function newQuery()
    {
        assert($db = $this->query->getDB());
        return new Query($db, $this->model, $this->table);
    }

    abstract public function relative($name, array& $result);
}
