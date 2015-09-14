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
use \Cute\Utility\Inflect;
use \Cute\View\Templater;


/**
 * 数据库
 */
abstract class Database
{
    const DB_ACTION_READ = 'R';
    const DB_ACTION_WRITE = 'W';
    
    protected $pdo = null;
    protected $user = '';
    protected $password = '';
    protected $dbname = '';
    protected $tblpre = '';
    protected $host = '';
    protected $port = 0;
    protected $charset = '';
    protected $past_sqls = array();

    public function __construct($user, $password, $dbname, $tblpre = '',
                        $host = '127.0.0.1', $port = 0, $charset = '')
    {
        $this->user = $user;
        $this->password = $password;
        $this->dbname = $dbname;
        $this->tblpre = $tblpre;
        $this->host = $host;
        if (intval($port) > 0) {
            $this->port = intval($port);
        }
        if (! empty($charset)) {
            $this->charset = $charset;
        }
    }

    public function getPDO()
    {
        if (! $this->pdo) {
            $this->connect($this->dbname, $this->tblpre);
        }
        return $this->pdo;
    }

    public function getPastSQL($dbname = false, $offset = 0)
    {
        if ($dbname === true || $dbname === '*') {
            return $this->past_sqls;
        }
        $dbname = empty($dbname) ? $this->dbname : $dbname;
        $sqls = $this->past_sqls[$dbname];
        $offset = empty($offset) ? 0 : - abs($offset);
        return $offset ? array_slice($sqls, $offset, null, true) : $sqls;
    }

    public function getDriverName()
    {
        $driver = $this->getPDO()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $driver = strtolower($driver);
        return $driver === 'dblib' ? 'sqlsrv' : $driver;
    }
    
    public function inline($param)
    {
        return new Literal($param);
    }
    
    public function quote($param)
    {
        if (is_null($param)) {
            return Literal::quoteNull();
        } else if ($param instanceof Literal || $param instanceof DateTime) {
            return Literal::quote($param);
        } else {
            $type = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
            return $this->getPDO()->quote($param, $type);
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
        if (! empty($params)) {
            $sql = $this->embed($sql, $params);
        }
        try {
            $result = $this->getPDO()->exec($sql);
        } catch (PDOException $e) {
            $message = "SQL: $sql\n" . $e->getMessage();
            throw new PDOException($message);
        }
        if (SQL_VERBOSE) {
            $this->past_sqls[$this->dbname][] = array(
                'act' => self::DB_ACTION_WRITE, 'sql' => $sql,
            );
        }
        return $result;
    }

    public function query($sql, array $params = array())
    {
        try {
            $stmt = $this->getPDO()->prepare($sql);
            if ($stmt->execute($params)) {
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $sql = $this->embed($sql, $params);
            $message = "SQL: $sql\n" . $e->getMessage();
            throw new PDOException($message);
        }
        if (SQL_VERBOSE) {
            $sql = $this->embed($sql, $params);
            $this->past_sqls[$this->dbname][] = array(
                'act' => self::DB_ACTION_READ, 'sql' => $sql,
            );
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

    public function transact(callable $transaction)
    {
        $pdo = $this->getPDO();
        if ($pdo->beginTransaction()) {
            $args = func_get_args();
            array_unshift($args, $this);
            try {
                $transaction($args);
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
            }
        }
    }

    public function listTables()
    {
        if (empty($this->tblpre)) {
            $sql = "SHOW TABLES FROM ?";
            $param = $this->getDBName();
        } else {
            $sql = "SHOW TABLES LIKE ?";
            $param = str_replace('_', '\_', $this->tblpre) . '%';
        }
        $result = array();
        if ($stmt = $this->query($sql, array($param))) {
            $prelen = strlen($this->tblpre);
            while ($table = $stmt->fetchColumn(0)) {
                $result[] = substr($table, $prelen);
            }
            $stmt->closeCursor();
        }
        return $result;
    }

    public function readFields($table)
    {
        $columns = $this->getColumns($table);
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

    public function generateModel($dir, $table, $model = '',
                                $ns = '', $singular = false)
    {
        if (empty($model)) {
            $model = $singular ? Inflect::singularize($table) : $table;
            $model = Inflect::camelize($model);
        }
        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($ns) {
            $dir .= str_replace('\\', DIRECTORY_SEPARATOR, trim($ns));
            if (! file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
        }
        $filename = $dir . DIRECTORY_SEPARATOR . $model . '.php';
        if (class_exists($ns . '\\' . $model)) {
            return $filename;
        }
        $data = $this->readFields($table);
        $data['name'] = $model;
        $data['ns'] = $ns;
        $data['mixin'] = null;
        $data['relations_in_mixin'] = false;
        $mixin = $ns . '\\' . $model . 'Mixin';
        if (trait_exists($mixin, true)) {
            foreach ($data['fields'] as $field => $default) {
                if (property_exists($mixin, $field)) {
                    unset($data['fields'][$field]);
                }
            }
            $data['mixin'] = $mixin;
            $data['relations_in_mixin'] = method_exists($mixin, 'getRelations');
        }
        $tpl = new Templater(SRC_ROOT);
        ob_start();
        $tpl->render('model_tpl.php', $data);
        $content = "<?php\n\n" . trim(ob_get_clean());
        file_put_contents($filename, $content);
        return $filename;
    }

    public static function csvline($row, $ftb = "\t", $ltb = PHP_EOL,
                                            $oeb = '"', $nrb = null)
    {
        foreach ($row as & $item) {
            if (is_null($item)) {
                $item = $nrb;
            } else if (is_string($item)) {
                if (strpbrk($item, " $ftb") !== false) {
                    $item = "$oeb$item$oeb";
                }
            }
        }
        return implode($ftb, $row) . $ltb;
    }
    
    abstract public function connect($dbname, $tblpre = '', $create = false);
    
    abstract public function getLimit($length, $offset = 0);
    
    abstract public function getColumns($table);
}
