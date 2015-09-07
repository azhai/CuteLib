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
use \Cute\View\Templater;
use \Cute\Utility\Inflect;



/**
 * 查询
 */
class Query
{
    const COUNT_INSERT_BULK_MAX = 500; //批量插入一次最多条数
    protected $db = null;
    protected $table = '';
    protected $model = '';
    protected $fetch_style = 0;
    protected $constrains = array();
    protected $parameters = array();
    public $additions = array();
    public $relations = array();

    public function __construct(Database& $db, $model = '\\Cute\\ORM\\Model', $table = '')
    {
        $this->db = $db;
        if (is_object($model)) {
            $this->model = get_class($model);
        } else {
            $this->model = $model;
        }
        $this->fetch_style = PDO::FETCH_CLASS;
        if (method_exists($this->model, '__set') ||
                method_exists($this->model, '__construct')) {
            $this->fetch_style = $this->fetch_style | PDO::FETCH_PROPS_LATE;
        }
        if (empty($table) || $this->model !== '\\Cute\\ORM\\Model') {
            $this->table = exec_method_array($this->model, 'getTable');
        } else {
            $this->table = $table;
        }
    }

    public static function generateModel(Database& $db, $table, $name = '',
            $ns = '', $singular = false)
    {
        $data = $db->readFields($table);
        if (empty($name)) {
            $name = $singular ? Inflect::singularize($table) : $table;
            $name = Inflect::camelize($name);
        }
        $dir = APP_ROOT . '/models';
        if ($ns) {
            $dir .= '/' . str_replace('\\', '/', trim($ns));
        }
        if (! file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename = "$dir/$name.php";
        $data['name'] = $name;
        $data['ns'] = $ns;
        $data['protecteds'] = array('password', 'salt');
        $tpl = new Templater(SRC_ROOT);
        ob_start();
        $tpl->render('model_tpl.php', $data);
        $content = "<?php\n\n" . trim(ob_get_clean());
        file_put_contents($filename, $content);
        return $filename;
    }

    public function getDB()
    {
        return $this->db;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getTable($quote = null)
    {
        if (is_null($quote)) {
            return $this->table;
        } else {
            assert($db = $this->getDB());
            return $db->getTableName($this->table, $quote);
        }
    }

    public function join($name)
    {
        $names = func_get_args();
        $model = exec_construct_array($this->model);
        $relations = $model->getRelations();
        foreach ($names as $name) {
            if (isset($relations[$name])) {
                $this->relations[$name] = & $relations[$name];
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

    public function where(array& $args = array())
    {
        $where = implode(' AND ', $this->constrains);
        $params = $this->parameters;
        if (count($args) > 0) {
            $where .= ($where ? ' AND ' : '') . array_shift($args);
            $params = array_merge($params, $args);
        }
        $where = $where ? 'WHERE ' . $where : '';
        return array($where, $params);
    }
    
    public function select($columns = '*', array $args = array())
    {
        $table_name = $this->getTable(true);
        list($where, $params) = $this->where($args);
        if (is_object($columns)) {
            $columns = get_object_vars($columns);
        }
        if (is_array($columns)) {
            array_walk($columns, create_function('&$v,$k', 
                    'if(!is_numeric($k))$v.=" as ".$k;'));
            $columns = implode(', ', $columns);
        }
        $columns = trim($columns);
        $sql = "SELECT $columns FROM $table_name $where";
        foreach ($this->additions as $key => $value) {
            $sql .= " $key $value";
        }
        return $this->getDB()->query(rtrim($sql), $params);
    }

    public function rows($key = false, $columns = '*')
    {
        $args = array_slice(func_get_args(), 2);
        if ($stmt = $this->select($columns, $args)) {
            $result = array();
            if ($key === false || is_null($key)) {
                $result = $stmt->fetchAll();
            } else {
                while ($row = $stmt->fetch()) {
                    $result[$row[$key]] = $row;
                }
            }
            $stmt->closeCursor();
            return $result;
        }
    }

    public function all($limit = -1, $columns = '*')
    {
        if ($limit >= 0) {
            $this->additions['LIMIT'] = intval($limit);
        }
        $args = array_slice(func_get_args(), 2);
        if ($stmt = $this->select($columns, $args)) {
            $result = $stmt->fetchAll($this->fetch_style, $this->getModel());
            $stmt->closeCursor();
            foreach ($this->relations as $name => &$relation) {
                $relation->bind($this)->relative($name, $result);
            }
        }
        return $result;
    }

    public function combine($field, array& $result, $unique = false, $columns = '*')
    {
        if (empty($field) || count($result) === 0) {
            return $result;
        }
        $this->contain($field, array_keys($result));
        if ($columns === '*') {
            $columns = sprintf('%s,%s.*', $field, $this->getTable(false));
        }
        if ($stmt = $this->select($columns)) {
            $combine_style = $unique ? PDO::FETCH_UNIQUE : PDO::FETCH_GROUP;
            $fetch_style = $this->fetch_style | $combine_style;
            $result = $stmt->fetchAll($fetch_style, $this->getModel());
            $stmt->closeCursor();
        }
        return $result;
    }

    public function get($id, $key = false, $columns = '*')
    {
        if (empty($key)) {
            $pkeys = exec_method_array($this->getModel(), 'getPKeys');
            if (empty($pkeys)) {
                return;
            }
            $key = reset($pkeys);
        }
        $objs = $this->all(1, $columns, "$key = ?", $id);
        return count($objs) > 0 ? reset($objs) : null;
    }

    public function delete()
    {
        $args = func_get_args();
        $table_name = $this->getTable(true);
        list($where, $params) = $this->where($args);
        $sql = "DELETE FROM $table_name $where";
        $db = $this->getDB();
        if (empty($where) && $db->getDriverName() === 'mysql') {
            $sql = "TRUNCATE $table_name";
        }
        return $db->execute(rtrim($sql), $params);
    }

    /**
     * 更新表中的一些字段
     * @param string $table 数据表名
     * @param array $changes 更新的字段和值，关联数组
     * @param boolean $delay 延迟写入
     * @param string $where 条件
     * @param mixed ... where条件中的替代值
     *              ... 其他替代值
     * @return 影响的行数
     */
    public function update(array $changes, $delay = false)
    {
        $args = array_slice(func_get_args(), 2);
        $table_name = $this->getTable(true);
        list($where, $params) = $this->where($args);
        $sets = array();
        $values = array();
        foreach ($changes as $key => $val) {
            $sets[] = $key . '=?';
            $values[] = $val;
        }
        $verb = $delay ? 'UPDATE DELAYED' : 'UPDATE';
        $sql = "$verb $table_name SET " . implode(', ', $sets) . " $where";
        $params = array_merge($values, $params);
        return $this->getDB()->execute(rtrim($sql), $params);
    }

    public function getInsertSQL(array $columns, $delay = false)
    {
        $table_name = $this->getTable(true);
        $verb = $delay ? 'INSERT DELAYED' : 'INSERT INTO';
        if ($count = count($columns)) {
            $columns = implode(',', $columns);
            $marks = implode(', ', array_fill(0, $count, '?'));
            return array("$verb $table_name ($columns)", $marks);
        } else {
            return array("$verb $table_name", '');
        }
    }

    public function insert(array $newbie, $delay = false)
    {
        assert($db = $this->getDB());
        list($sql, $marks) = $this->getInsertSQL(array_keys($newbie), $delay);
        if (! empty($marks)) {
            $sql .= " VALUES ($marks)";
            $params = array_values($newbie);
            if ($db->execute($sql, $params)) {
                return $db->getPDO()->lastInsertId();
            }
        }
    }

    public function insertBulk(array $rows, array $columns = null, $delay = false)
    {
        assert(count($rows) > 0);
        $table_name = $this->getTable(true);
        if (empty($columns)) {
            $columns = array_keys(reset($rows));
        }
        list($sql, $marks) = $this->getInsertSQL($columns, $delay);
        $chunks = array_chunk($rows, self::COUNT_INSERT_BULK_MAX);
        foreach ($chunks as $chunk) {
            $more_marks = array_fill(0, count($chunk), "($marks)");
            $sql .= " VALUES " . implode(', ', $more_marks);
            $more_values = array_map('array_values', $chunk);
            $params = exec_funcution_array('array_merge', $more_values);
            $this->getDB()->execute($sql, $params);
        }
    }
}
