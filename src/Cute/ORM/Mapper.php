<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \PDO;
use \Cute\DB\Database;
use \Cute\DB\Query;


/**
 * 映射器
 */
class Mapper
{
    protected $db = null;
    protected $query = null;
    protected $model = '';
    protected $table = '';
    protected $fetch_style = 0;
    protected $relations = array();
    protected $nothing = false; //不查询，直接返回空

    public function __construct(Database& $db, $model = '', $table = '')
    {
        $this->db = $db;
        $this->table = $table;
        if ($model && is_object($model)) {
            $model = get_class($model);
        }
        $this->setModel($model);
    }
    
    public function setModel($model)
    {
        $this->fetch_style = PDO::FETCH_CLASS;
        if (empty($model)) {
            $this->model = '\\Cute\\ORM\\Model';
            return $this;
        }
        $this->model = $model;
        if (method_exists($this->model, '__set') ||
                method_exists($this->model, '__construct')) {
            $this->fetch_style = $this->fetch_style | PDO::FETCH_PROPS_LATE;
        }
        if (empty($this->table)) {
            $this->table = exec_method_array($this->model, 'getTable');
        }
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }
    
    public function getDB()
    {
        return $this->db;
    }

    public function getQuery()
    {
        if (! $this->query) {
            $this->query = new Query($this->getTable());
        }
        return $this->query;
    }
    
    public function getTable()
    {
        return $this->table;
    }
    
    public function getTableName($quote = false)
    {
        $db = $this->getDB();
        return $db->getTableName($this->getTable(), $quote);
    }
    
    public function getPKey()
    {
        $pkeys = exec_method_array($this->getModel(), 'getPKeys');
        return empty($pkeys) ? null : reset($pkeys);
    }
    
    /**
     * 分页，支持反向分页
     */
    public function setPage($page_size, $page_no = 1)
    {
        $page_no = intval($page_no); //0表示不分页，负数是反向页码
        if ($page_no < 0 && intval($page_size) > 0) {
            $total = intval($this->count());
        } else {
            $total = 0;
        }
        $this->getQuery()->setPage($page_size, $page_no, $total);
        return $this;
    }
    
    public function setNothing($nothing = true)
    {
        $this->nothing = $nothing;
        return $this;
    }

    public function join($name = '*')
    {
        $model = exec_construct_array($this->model);
        $relations = $model->getRelations();
        if ($name === '*') {
            $this->relations = & $relations;
        } else {
            $names = func_get_args();
            foreach ($names as $name) {
                if ($name && isset($relations[$name])) {
                    $this->relations[$name] = & $relations[$name];
                }
            }
        }
        return $this;
    }
    
    /**
     * 返回Model的数组
     */
    public function all($columns = '*', $fetch_style = null,
                        $or_cond = '', array& $args = array())
    {
        $db = $this->getDB();
        if ($this->nothing) {
            $result = array();
        } else {
            $query = $this->getQuery();
            $fetch_style = empty($fetch_style) ? $this->fetch_style : $fetch_style;
            $fetch_args = array($fetch_style, $this->getModel());
            $result = $query->select($db, $columns, 'fetchAll', $fetch_args, $or_cond, $args);
        }
        if (is_array($result)) {
            $table = $this->getTable();
            foreach ($this->relations as $name => &$relation) {
                $relation->bind($db, $table)->relative($name, $result);
            }
            return $result;
        }
    }

    /**
     * 获取单个Model对象或null
     */
    public function get($id, $key = false, $columns = '*')
    {
        if (empty($key)) {
            $key = $this->getPKey();
            if (empty($key)) {
                return;
            }
        }
        $this->getQuery()->filter($key, $id)->setPage(1, 1);
        $objs = $this->all($columns);
        if (count($objs) > 0) {
            return reset($objs);
        } else {
            return exec_construct_array($this->getModel());
        }
    }
    
    /**
     * 按fkey分组，用于外键查询
     */
    public function combine($fkey, array& $result, $unique = false, $columns = '*')
    {
        if (empty($fkey) || count($result) === 0) {
            return $result;
        }
        $db = $this->getDB();
        $query = $this->getQuery();
        $query->contain($fkey, array_keys($result));
        if ($columns === '*') {
            $table_name = $this->getTableName(true);
            $columns = sprintf('%s,%s.*', $fkey, $table_name);
        }
        $combine_style = $unique ? PDO::FETCH_UNIQUE : PDO::FETCH_GROUP;
        $fetch_style = $this->fetch_style | $combine_style;
        $fetch_args = array($fetch_style, $this->getModel());
        $result = $query->select($db, $columns, 'fetchAll', $fetch_args);
        return $result;
    }
    
    public function __call($name, $args)
    {
        $query = $this->getQuery();
        if (! method_exists($query, $name)) {
            array_unshift($args, $name);
            $name = 'apply';
        }
        if (in_array($name, Query::$db_actions)) {
            $db = $this->getDB();
            array_unshift($args, $db);
            return exec_method_array($query, $name, $args);
        } else {
            $result = exec_method_array($query, $name, $args);
            return ($result instanceof Query) ? $this : $result;
        }
    }
    
    public function add(& $object)
    {
        assert($object instanceof $this->model);
        $db = $this->getDB();
        $query = new Query($this->getTable());
        $data = $object->toArray();
        if ($object->isExists()) {
            $pkey = $this->getPKey();
            $query->filter($pkey, $object->getID());
            $query->update($db, $data);
        } else {
            $id = $query->insert($db, $data);
            $object->setID($id);
        }
        return $this;
    }
}
