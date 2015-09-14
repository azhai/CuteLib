<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\DB;
use \Cute\DB\Database;


/**
 * 查询
 */
class Query
{
    const COUNT_INSERT_BULK_MAX = 500; //批量插入一次最多条数
    public static $db_actions = array( //需要Database对象作为第一个参数的方法
        'select', 'update', 'delete', 'insert', 'insertBulk', 'apply',
    );
    protected $table = '';
    protected $constrains = array();
    protected $parameters = array();
    protected $offset = 0;
    protected $length = -1;
    protected $additions = array(
        'GROUP BY' => null, 'HAVING' => null,
        'ORDER BY' => null,
    );

    public function __construct($table)
    {
        $this->table = $table;
    }
    
    public function getTable()
    {
        return $this->table;
    }

    public function deduceWhere($or_cond = '', array& $args = array())
    {
        $where = implode(' AND ', $this->constrains);
        $params = $this->parameters;
        if (! empty($or_cond)) {
            if ($where) {
                $where = '(' . $where . ') OR (' . $or_cond . ')';
            } else {
                $where = $or_cond;
            }
            if (count($args) > 0) {
                $params = array_merge($params, $args);
            }
        }
        $where = $where ? 'WHERE ' . $where : '';
        return array($where, $params);
    }
    
    protected function deduceTail($exclude = '')
    {
        $excludes = func_get_args();
        $additions = ""; //分组、排序
        foreach ($this->additions as $key => $value) {
            if (! is_null($value) && ! in_array($key, $excludes)) {
                $additions .= " $key $value";
            }
        }
        return $additions;
    }
    
    public function groupBy($group, $having = null)
    {
        $this->additions['GROUP BY'] = $group;
        $this->additions['HAVING'] = $having;
        return $this;
    }
    
    public function orderBy($order, $orient = '')
    {
        if ($orient) {
            $order .= ' ' . strtoupper($orient);
        }
        $this->additions['ORDER BY'] = $order;
        return $this;
    }
    
    /**
     * 分页
     */
    public function setPage($page_size, $page_no = 1, $total = 0)
    {
        $this->length = intval($page_size) < 0 ? -1 : intval($page_size);
        if ($this->length > 0) {
            $page_no = intval($page_no); //0表示不分页，负数是反向页码
            if ($page_no < 0 && $total > 0) {
                $last_page = ceil($total / $this->length);
                $page_no += $last_page + 1; //反向页码，同时能检查页码是否越界
            }
            if ($page_no > 0) {
                $this->offset = ($page_no - 1) * $this->length;
            }
        }
        return $this;
    }

    public function contain($field, array $values)
    {
        $count = count($values);
        if ($count === 0) {
            $op = 'IS NULL';
        } else if ($count === 1) {
            $op = '= ?';
        } else {
            $marks = implode(', ', array_fill(0, count($values), '?'));
            $op = 'IN (' . $marks . ')';
        }
        $values = array_values($values);
        $this->constrains[] = "$field $op";
        $this->parameters = array_merge($this->parameters, $values);
        return $this;
    }

    public function filter($cond, $value, $op = '= ?')
    {
        $op = empty($op) ? false : strtoupper($op);
        if (is_array($value)) {
            if (substr_count($cond, '?') === count($value)) {
                $this->constrains[] = "$field $op";
                $this->parameters = array_merge($this->parameters, $value);
            } else {
                $this->contain($cond, $value);
            }
        } else if (is_null($value)) {
            if (trim($op) === '<>') {
                $this->constrains[] = "$cond IS NOT NULL";
            } else {
                $this->constrains[] = "$cond IS NULL";
            }
        } else {
            $this->constrains[] = ($op === false) ? $cond : "$cond $op";
            $this->parameters[] = $value;
        }
        return $this;
    }
    
    /**
     * 获取要查询的字段形式
     */
    public static function getSelectSQL($table_name, $columns = '*', $prefix = "")
    {
        if (is_object($columns)) {
            $columns = get_object_vars($columns);
        }
        if (is_array($columns)) { //字段使用as别名
            array_walk($columns, create_function('&$v,$k', 
                    'if(!is_numeric($k))$v.=" as ".$k;'));
            $columns = implode(', ', $columns);
        }
        $columns = trim($columns);
        $sql = "SELECT $prefix$columns FROM $table_name";
        return $sql;
    }
    
    /**
     * Select查询
     */
    public function select(Database& $db, $columns = '*', $fetch = 'fetchAll',
            array $fetch_args = array(), $or_cond = '', array& $args = array())
    {
        $top = "";
        $limit = "";
        if (starts_with($columns, 'COUNT')) {
            $additions = $this->deduceTail('ORDER BY');
        } else {
            $additions = $this->deduceTail();
            if ($this->length > 0) {
                list($top, $limit) = $db->getLimit($this->length, $this->offset);
            }
        }
        $table_name = $db->getTableName($this->getTable(), true);
        list($where, $params) = $this->deduceWhere($or_cond = '', $args);
        $sql = self::getSelectSQL($table_name, $columns, $top);
        $sql .= ($where ? ' ' . $where : '') . $additions . $limit;
        $stmt = $db->query(rtrim($sql), $params);
        if ($stmt && $fetch) {
            $result = exec_method_array($stmt, $fetch, $fetch_args);
            $stmt->closeCursor();
            return $result;
        }
    }
    
    public function apply(Database& $db, $func)
    {
        $func = strtoupper($func);
        $args = array_slice(func_get_args(), 2);
        if (empty($args)) {
            $columns = $func === 'COUNT' ? '*' : '';
        } else {
            $columns = implode(', ', $args);
        }
        $columns = $func . '(' . $columns . ')';
        $result = $this->select($db, $columns, 'fetchColumn');
        return $result; //false是查询失败
    }

    /**
     * 删除或清空
     */
    public function delete(Database& $db)
    {
        $table_name = $db->getTableName($this->getTable(), true);
        list($where, $params) = $this->deduceWhere();
        $sql = "DELETE FROM $table_name $where";
        if (empty($where) && $db->getDriverName() === 'mysql') {
            $sql = "TRUNCATE $table_name";
        }
        return $db->execute(rtrim($sql), $params);
    }
    
    public static function getUpdateSet(array $changes)
    {
        $sets = array();
        $values = array();
        foreach ($changes as $key => $val) {
            $sets[] = $key . '=?';
            $values[] = $val;
        }
        $setsql = "SET " . implode(', ', $sets);
        return array($setsql, $values);
    }

    /**
     * 更新表中的一些字段
     * @param array $changes 更新的字段和值，关联数组
     * @param boolean $delay 延迟写入
     * @param string $cond 条件
     * @param array $args 条件中替代值
     * @return 影响的行数
     */
    public function update(Database& $db, array $changes, $delay = false)
    {
        list($where, $params) = $this->deduceWhere();
        list($setsql, $values) = self::getUpdateSet($changes);
        $verb = $delay ? 'UPDATE DELAYED' : 'UPDATE';
        $table_name = $db->getTableName($this->getTable(), true);
        $sql = "$verb $table_name $setsql $where";
        $params = array_merge($values, $params);
        return $db->execute(rtrim($sql), $params);
    }

    public static function getInsertSQL($table_name, array $columns,
                                $delay = false, $replace = false)
    {
        if ($replace === true) {
            $verb = $delay ? 'REPLACE DELAYED' : 'REPLACE INTO';
        } else {
            $verb = $delay ? 'INSERT DELAYED' : 'INSERT INTO';
        }
        if ($count = count($columns)) {
            $columns = implode(',', $columns);
            $marks = implode(', ', array_fill(0, $count, '?'));
            return array("$verb $table_name ($columns)", $marks);
        } else {
            return array("$verb $table_name", '');
        }
    }

    /**
     * 往表中插入一行
     * @param array $newbie 插入的字段和值，关联数组
     * @param boolean $delay 延迟写入
     * @return 自增ID
     */
    public function insert(Database& $db, array $newbie,
                            $delay = false, $replace = false)
    {
        $table_name = $db->getTableName($this->getTable(), true);
        list($sql, $marks) = self::getInsertSQL($table_name,
                            array_keys($newbie), $delay, $replace);
        if (! empty($marks)) {
            $sql .= " VALUES ($marks)";
            $params = array_values($newbie);
            if ($db->execute($sql, $params)) {
                return $db->getPDO()->lastInsertId();
            }
        }
    }

    /**
     * 插于多行
     */
    public function insertBulk(Database& $db, array $rows,
                    array $columns = null, $delay = false, $replace = false)
    {
        assert(count($rows) > 0);
        if (empty($columns)) {
            $columns = array_keys(reset($rows));
        }
        $table_name = $db->getTableName($this->getTable(), true);
        list($sql, $marks) = self::getInsertSQL($table_name, $columns, $delay, $replace);
        $chunks = array_chunk($rows, self::COUNT_INSERT_BULK_MAX);
        foreach ($chunks as $chunk) {
            $more_marks = array_fill(0, count($chunk), "($marks)");
            $sql .= " VALUES " . implode(', ', $more_marks);
            $more_values = array_map('array_values', $chunk);
            $params = exec_funcution_array('array_merge', $more_values);
            $db->execute($sql, $params);
        }
    }
}
