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
    protected $page = 1;
    protected $size = -1;
    protected $additions = array(
        'GROUP BY' => null, 'HAVING' => null,
        'ORDER BY' => null,
    );
    protected $relations = array();

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
    
    public function __call($name, $args)
    {
        $columns = empty($args) ? '' : implode(', ', $args);
        $name = str_replace('_', ' ', strtoupper($name));
        if ($name === 'GROUPBY' || $name === 'ORDERBY') {
            //将groupBy/orderBy改写为正确格式
            $name = substr($name, 0, -2) . ' BY'; 
        }
        if (array_key_exists($name, $this->additions)) {
            $this->additions[$name] = $columns;
            return $this;
        } else {
            $stmt = $this->select("$name($columns)");
            $data = $stmt->fetchColumn();
            $stmt->closeCursor();
            return $data;
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

    public function where($cond = '', array& $args = array())
    {
        $where = implode(' AND ', $this->constrains);
        $params = $this->parameters;
        if ($cond || count($args) > 0) {
            $where .= ($where ? ' AND ' : '') . $cond;
            $params = array_merge($params, $args);
        }
        $where = $where ? 'WHERE ' . $where : '';
        return array($where, $params);
    }
    
    /**
     * 分页
     */
    public function slice($size, $page = 1)
    {
        $this->size = intval($size); //负数表示不分页
        $this->page = intval($page); //0表示不分页，负数是反向页码
        return $this;
    }
    
    /**
     * 计算行数
     */
    public function count($columns = '*')
    {
        $stmt = $this->select("COUNT($columns)");
        $data = $stmt->fetchColumn();
        $stmt->closeCursor();
        return $data; //false是查询失败
    }
    
    public function getLimit($columns = '*')
    {
        $top = "";
        $limit = "";
        if (! starts_with($columns, 'COUNT') && $this->size > 0) { //限制行数
            $driver = $this->getDB()->getDriverName();
            if ($driver === 'sqlsrv') { //MS SQLServer的TOP格式
                $top = sprintf("TOP %d ", $this->size);
            } else if ($driver === 'mysql') { //MySQL的LIMIT格式
                $offset = "";
                if ($this->page > 0) {
                    $offset = (($this->page - 1) * $this->size) . ", ";
                } else if ($this->page < 0) { //反向页码，同时能检查页码是否越界
                    $last = ceil($this->count() / $this->size);
                    if ($last > 0 && ($page = $this->page + $last) > 0) {
                        $offset = ($page * $this->size) . ", ";
                    }
                }
                $limit = sprintf(" LIMIT %s%d", $offset, $this->size);
            }
        }
        return array($top, $limit);
    }
    
    /**
     * Select查询，返回stmt
     */
    public function select($columns = '*', $cond = '', array& $args = array())
    {
        $table_name = $this->getTable(true);
        list($where, $params) = $this->where($cond, $args);
        if (is_object($columns)) {
            $columns = get_object_vars($columns);
        }
        if (is_array($columns)) { //字段使用as别名
            array_walk($columns, create_function('&$v,$k', 
                    'if(!is_numeric($k))$v.=" as ".$k;'));
            $columns = implode(', ', $columns);
        }
        $columns = trim($columns);
        $additions = ""; //分组、排序
        foreach ($this->additions as $key => $value) {
            if (! is_null($value)) {
                $additions .= " $key $value";
            }
        }
        list($top, $limit) = $this->getLimit($columns);
        $sql = "SELECT $top$columns FROM $table_name";
        $sql .= ($where ? ' ' . $where : '') . $additions . $limit;
        $db = $this->getDB();
        return $db->query(rtrim($sql), $params);
    }
    
    /**
     * 返回关联数组的数组
     */
    public function rows($key = false, $columns = '*', $cond = '')
    {
        $args = array_slice(func_get_args(), 3);
        if ($stmt = $this->select($columns, $cond, $args)) {
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

    /**
     * 返回Model的数组
     */
    public function all($columns = '*', $cond = '')
    {
        $args = array_slice(func_get_args(), 3);
        if ($stmt = $this->select($columns, $cond, $args)) {
            $result = $stmt->fetchAll($this->fetch_style, $this->getModel());
            $stmt->closeCursor();
            foreach ($this->relations as $name => &$relation) {
                $relation->bind($this)->relative($name, $result);
            }
        }
        return $result;
    }
    
    /**
     * 按fkey分组，用于外键查询
     */
    public function combine($fkey, array& $result, $unique = false, $columns = '*')
    {
        if (empty($fkey) || count($result) === 0) {
            return $result;
        }
        $this->contain($fkey, array_keys($result));
        if ($columns === '*') {
            $columns = sprintf('%s,%s.*', $fkey, $this->getTable(false));
        }
        if ($stmt = $this->select($columns)) {
            $combine_style = $unique ? PDO::FETCH_UNIQUE : PDO::FETCH_GROUP;
            $fetch_style = $this->fetch_style | $combine_style;
            $result = $stmt->fetchAll($fetch_style, $this->getModel());
            $stmt->closeCursor();
        }
        return $result;
    }

    /**
     * 获取单个Model对象或null
     */
    public function get($id, $key = false, $columns = '*')
    {
        if (empty($key)) {
            $pkeys = exec_method_array($this->getModel(), 'getPKeys');
            if (empty($pkeys)) {
                return;
            }
            $key = reset($pkeys);
        }
        $objs = $this->slice(1)->all($columns, "$key = ?", $id);
        return count($objs) > 0 ? reset($objs) : null;
    }

    /**
     * 删除或清空
     */
    public function delete($cond = '')
    {
        $args = array_slice(func_get_args(), 1);
        $table_name = $this->getTable(true);
        list($where, $params) = $this->where($cond, $args);
        $sql = "DELETE FROM $table_name $where";
        $db = $this->getDB();
        if (empty($where) && $db->getDriverName() === 'mysql') {
            $sql = "TRUNCATE $table_name";
        }
        return $db->execute(rtrim($sql), $params);
    }
    
    public function getUpdateSet(array $changes)
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
    public function update(array $changes, $delay = false, $cond = '')
    {
        $args = array_slice(func_get_args(), 3);
        $table_name = $this->getTable(true);
        list($where, $params) = $this->where($cond, $args);
        list($setsql, $values) = $this->getUpdateSet($changes);
        $verb = $delay ? 'UPDATE DELAYED' : 'UPDATE';
        $sql = "$verb $table_name $setsql $where";
        $params = array_merge($values, $params);
        return $this->getDB()->execute(rtrim($sql), $params);
    }

    public function getInsertSQL(array $columns,
                                $delay = false, $replace = false)
    {
        $table_name = $this->getTable(true);
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
    public function insert(array $newbie, $delay = false, $replace = false)
    {
        assert($db = $this->getDB());
        list($sql, $marks) = $this->getInsertSQL(array_keys($newbie),
                                                $delay, $replace);
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
    public function insertBulk(array $rows, array $columns = null,
                                $delay = false, $replace = false)
    {
        assert(count($rows) > 0);
        $table_name = $this->getTable(true);
        if (empty($columns)) {
            $columns = array_keys(reset($rows));
        }
        list($sql, $marks) = $this->getInsertSQL($columns, $delay, $replace);
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
