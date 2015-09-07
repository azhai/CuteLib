<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\DB;
use \DateTime;
use \PDO;
use \PDOException;
use \Cute\DB\Literal;

/**
 * 数据库
 */
class Database
{
    const TYPE_UNSUPPORT = 0;
    const TYPE_TOP = 1;
    const TYPE_LIMIT = 2;
    
    protected $pdo = null;
    protected $dbname = '';
    protected $prefix = '';

    public function __construct(PDO& $pdo, $dbname = '', $prefix = '')
    {
        $this->pdo = $pdo;
        if ($dbname) {
            $this->useDB($dbname, $prefix);
        }
    }

    public function useDB($dbname, $prefix = '', $create = false)
    {
        assert(! is_null($this->pdo));
        if ($create && $this->getDriverName() === 'mysql') {
            $this->pdo->exec("CREATE DATABASE IF NOT EXIST `$dbname`");
        }
        $this->pdo->exec("USE `$dbname`");
        $this->dbname = $dbname;
        $this->prefix = empty($prefix) ? '' : $prefix;
        return $this;
    }

    public function getPDO()
    {
        return $this->pdo;
    }

    public function getDriverName()
    {
        assert(! is_null($this->pdo));
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $driver = strtolower($driver);
        return $driver === 'dblib' ? 'sqlsrv' : $driver;
    }

    public function getDBName()
    {
        return $this->dbname;
    }

    public function getTableName($table, $quote = false)
    {
        $table_name = $this->prefix . $table;
        $driver_name = $this->getDriverName();
        if ($driver_name === 'mysql') {
            $sql = "SHOW Variables LIKE 'lower_case_table_names'";
            $name_case = $this->fetch($sql, array(), 'Value');
            if (intval($name_case) === 1) { //不区分表名大小写
                $table_name = strtolower($table_name);
            }
        }
        if ($quote === false) {
            return $table_name;
        }
        switch ($driver_name) {
            case 'mysql':
                $table_name = "`$table_name`";
                break;
            case 'sqlite':
            case 'sqlsrv':
                $table_name = "[$table_name]";
                break;
        }
        return $table_name;
    }
    
    public function inline($param)
    {
        return new Literal($param);
    }
    
    public function quote($param)
    {
        if (is_null($this->pdo) || is_null($param) ||
                $param instanceof Literal || $param instanceof DateTime) {
            return Literal::quote($param);
        } else {
            $type = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
            return $this->pdo->quote($param, $type);
        }
    }

    public function embed($sql, array $params = array())
    {
        foreach ($params as & $param) {
            $param = $this->quote($param);
        }
        $sql = str_replace('?', '%s', $sql);
        return vsprintf($sql, $params);
    }

    public function execute($sql, array $params = array())
    {
        assert(! is_null($this->pdo));
        if (! empty($params)) {
            $sql = $this->embed($sql, $params);
        }
        try {
            $result = $this->pdo->exec($sql);
        } catch (PDOException $e) {
            $message = "SQL: $sql\n" . $e->getMessage();
            throw new PDOException($message);
        }
        return $result;
    }

    public function query($sql, array $params = array())
    {
        assert(! is_null($this->pdo));
        try {
            $stmt = $this->pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $sql = $this->embed($sql, $params);
            $message = "SQL: $sql\n" . $e->getMessage();
            throw new PDOException($message);
        }
        return $stmt;
    }

    public function fetch($sql, array $params = array(), $col = false)
    {
        if ($stmt = $this->query($sql, $params)) {
            if (is_numeric($col)) {
                $result = $stmt->fetchColumn(intval($col));
            } else {
                $result = $stmt->fetch();
                if ($result && $col) {
                    $result = isset($result[$col]) ? $result[$col] : null;
                }
            }
            $stmt->closeCursor();
            return $result;
        }
    }

    public function listTables()
    {
        if (empty($this->prefix)) {
            $sql = sprintf("SHOW TABLES FROM %s", $this->getDBName());
        } else {
            $pattern = $this->quote(str_replace('_', '\_', $this->prefix) . '%');
            $sql = sprintf("SHOW TABLES LIKE %s", $pattern);
        }
        $result = array();
        if ($stmt = $this->query($sql)) {
            $prelen = strlen($this->prefix);
            while ($table = $stmt->fetchColumn(0)) {
                $result[] = substr($table, $prelen);
            }
            $stmt->closeCursor();
        }
        return $result;
    }

    public function transact(callable $transaction)
    {
        assert(! is_null($this->pdo));
        if ($this->pdo->beginTransaction()) {
            $args = func_get_args();
            array_unshift($args, $this);
            try {
                $transaction($args);
                $this->pdo->commit();
            } catch (PDOException $e) {
                $this->pdo->rollBack();
            }
        }
    }

    public function readFields($table)
    {
        $driver = $this->getDriverName();
        $class = '\\Cute\\DB\\' . ucfirst($driver) . 'Schema';
        $schema = new $class($this, $table);
        $columns = $schema->getColumns();
        $pkeys = array();
        $fields = array();
        foreach ($columns as $column) {
            if ($column->isPrimaryKey()) {
                $pkeys[] = $column->name;
            }
            $default = $column->default;
            $cate = $column->getCategory();
            if ($cate === 'int') {
                $default = intval($default);
            } else if ($cate === 'float') {
                $default = floatval($default);
            }
            $fields[$column->name] = $default;
        }
        return compact('table', 'pkeys', 'fields');
    }
}
