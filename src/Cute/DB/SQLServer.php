<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\DB;
use \PDO;
use \Cute\DB\Database;


/**
 * MS SQLServer数据库
 */
class SQLServer extends Database
{
    protected $port = 1433;
    protected $charset = 'utf8';
    
    public function connect($dbname, $tblpre = '', $create = false)
    {
        $dsn = sprintf('dblib:host=%s;port=%d;charset=%s', $this->host, $this->port, $this->charset);
        $this->pdo = new PDO($dsn, $this->user, $this->password);
        $this->pdo->exec(sprintf("USE `%s`", $this->dbname));
        $this->tblpre = $tblpre;
        if (! array_key_exists($this->dbname, $this->past_sqls)) {
            $this->past_sqls[$this->dbname] = array();
        }
        return $this;
    }

    public function getTableName($table, $quote = false)
    {
        $table_name = $this->tblpre . $table;
        return $quote ? '[' . $table_name . ']' : $table_name;
    }
    
    public function getLimit($length, $offset = 0)
    {
        $top = sprintf("TOP %d ", $length);
        return array($top, "");
    }

    public function getPKey($table)
    {
        $table_name = $this->getTableName($table);
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.%s WHERE TABLE_SCHEMA='dbo'"
            . " AND TABLE_CATALOG=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION";
        $params = array($this->dbname, $table_name);
        $pkey = $this->fetch(sprintf($sql, 'KEY_COLUMN_USAGE'), $params, 0);
        if (empty($pkey)) {
            $pkey = $this->fetch(sprintf($sql, 'COLUMNS'), $params, 0);
        }
        return $pkey;
    }

    public function getColumns($table)
    {
        $table_name = $this->getTableName($table);
        $columns = array(
            'COLUMN_NAME', 'COLUMN_DEFAULT', 'COLUMN_KEY', 'IS_NULLABLE',
            'COLUMN_TYPE', 'DATA_TYPE', 'CHARACTER_MAXIMUM_LENGTH',
            'NUMERIC_PRECISION', 'NUMERIC_SCALE', 'DATETIME_PRECISION',
        );
        $sql = "SELECT %s FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo'"
            . " AND TABLE_CATALOG=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION";
        $sql = sprintf($sql, implode(', ', $columns));
        $params = array($this->dbname, $table_name);
        if ($stmt = $this->query($sql, $params)) {
            $style = PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE;
            $result = $stmt->fetchAll($style, '\\Cute\\ORM\\Column');
            $stmt->closeCursor();
            return $result;
        }
    }
    
    public function isExists($table)
    {
        $table_name = $this->getTableName($table);
        $sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo'"
            . " AND TABLE_CATALOG=? AND TABLE_NAME=?";
        $params = array($this->dbname, $this->table);
        $table = $this->fetch($sql, $params, 0);
        return $table_name === $table;
    }
    
    public function getCreateSQL($new_table, $columns = array(), $same_db = false, $same_type = false)
    {
        $pk_field = "";
        $pk_state = "";
        $other_fields = "";
        $columns = $this->getColumns();
        if ($same_db || $same_type) {
            foreach ($columns as $column) {
                if ($column->getCategory() === 'char') {
                    $type = "[" . $column->type . "](" . intval($column->length) . ")";
                } else if ($column->type === 'numeric') {
                    $type = "[numeric](" . $column->precision . ", " . $column->scale . ")";
                } else {
                    $type = "[" . $column->type . "]";
                }
                if ($column->isPrimaryKey()) {//主键
                    $pk_field = "    [$name] $type IDENTITY(1,1) NOT FOR REPLICATION NOT NULL,";
                    $pk_state = "PRIMARY KEY NONCLUSTERED ( [$name] ASC )";
                } else {
                    $null = $column->isNullable() ? "NULL" : "NOT NULL DEFAULT " . $column->default;
                    $other_fields .= "    [$name] $type $null,\n";
                }
            }
        } else {
            foreach ($columns as $column) {
                $default = trim($column->default, "()");
                if ($column->getCategory() === 'char') {
                    $length = intval($column->length);
                    $type = ($length > 255 || $length < 0) ? "text" : "varchar($length)";
                } else if ($column->getCategory() === 'int') {
                    $precision = intval($column->precision);
                    if ($default === '') {
                        $default = "0";
                    }
                    $type = $column->type . "($precision)";
                } else if ($column->getCategory() === 'float') {
                    $precision = intval($column->precision);
                    $scale = intval($column->scale);
                    if ($default === '') {
                        $default = "0.0";
                    }
                    $type = $column->type;
                    if ($column->type === 'real') {
                        $type = 'float';
                    } else if ($column->type === 'money') {
                        $type = 'numeric';
                    }
                    $type .= "($precision,$scale)";
                } else if ($column->getCategory() === 'datetime') {
                    $type = 'datetime';
                } else {
                    $type = $column->type;
                }
                if ($column->isPrimaryKey()) {//主键
                    $pk_field = "    [$name] $type IDENTITY(1,1) NOT FOR REPLICATION NOT NULL,";
                    $pk_state = "PRIMARY KEY NONCLUSTERED ( [$name] ASC )";
                } else if (starts_with($type, 'date') || ends_with($type, 'text')) {
                    $other_fields .= "    [$name] $type NULL,\n";
                } else if ($column->isNullable()) {
                    $other_fields .= "    [$name] $type NULL,\n";
                } else {
                    $other_fields .= "    [$name] $type NOT NULL DEFAULT '$default',\n";
                }
            }
        }
        $tpl = <<<EOD
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='%s')
BEGIN
CREATE TABLE [dbo].[%s] (
%s
%s
    CONSTRAINT [PK_%s]
    %s
    WITH ( PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF,
        ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON )
    ON [PRIMARY]
) ON [PRIMARY]
END
EOD;
        if (empty($pk_field)) {
            $other_fields = rtrim($other_fields, ",\n");
        }
        $sql = sprintf($tpl, $new_table, $new_table, $pk_field, $other_fields, $new_table, $pk_state);
        return $sql;
    }

    /**
     * 将当前范围的数据输出到文件，默认格式为TSV文本
     * @param string $fname 输出文件路径
     * @param string $ftb 字段值分隔符
     * @param string $ltb 行数据分隔符
     * @param string $oeb 字段值定界符
     * @param string $nrb NULL的替代符号
     * @return int 输出的数据行数
     */
    public function sqlToFile($sql, $fname, $ftb = "\t", $ltb = PHP_EOL, $oeb = '"', $nrb = null)
    {
        @mkdir(dirname($fname), 0664, true);
        $fh = fopen($fname, 'wb');
        $lines = 0;
        $sth = $this->query($sql);
        while ($row = $sth->fetch()) {
            if (is_null($nrb)) {
                fputcsv($fh, $row, $ftb, $oeb);
            } else { // 使用$nrb表示NULL
                fwrite($fh, self::csvline($row, $ftb, $ltb, $oeb, $nrb));
            }
            $lines ++;
        }
        $sth->closeCursor();
        fclose($fh);
        return $lines;
    }
}
