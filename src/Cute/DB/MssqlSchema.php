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
class MssqlSchema
{
    protected $db = null;
    protected $dbname = '';
    protected $table = '';

    public function __construct(Database& $db, $table)
    {
        $this->db = $db;
        $this->dbname = $db->getDBName();
        $this->table = $db->getTableName($table, false);
    }
    
    public function getLimitType()
    {
        return DB::TYPE_TOP;
    }

    public function getPKey()
    {
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.%s WHERE TABLE_SCHEMA='dbo'"
            . " AND TABLE_CATALOG=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION";
        $params = array($this->dbname, $this->table);
        $pkey = $this->db->fetch(sprintf($sql, 'KEY_COLUMN_USAGE'), $params, 0);
        if (empty($pkey)) {
            $pkey = $this->db->fetch(sprintf($sql, 'COLUMNS'), $params, 0);
        }
        return $pkey;
    }

    public function getColumns()
    {
        $columns = array(
            'COLUMN_NAME', 'COLUMN_DEFAULT', 'COLUMN_KEY', 'IS_NULLABLE',
            'COLUMN_TYPE', 'DATA_TYPE', 'CHARACTER_MAXIMUM_LENGTH',
            'NUMERIC_PRECISION', 'NUMERIC_SCALE', 'DATETIME_PRECISION',
        );
        $sql = "SELECT %s FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo'"
            . " AND TABLE_CATALOG=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION";
        $sql = sprintf($sql, implode(', ', $columns));
        $params = array($this->dbname, $this->table);
        if ($stmt = $this->db->query($sql, $params)) {
            $style = PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE;
            $result = $stmt->fetchAll($style, '\\Cute\\ORM\\Column');
            $stmt->closeCursor();
            return $result;
        }
    }
    
    public function isExists()
    {
        $sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo'"
            . " AND TABLE_CATALOG=? AND TABLE_NAME=?";
        $params = array($this->dbname, $this->table);
        $table = $this->db->fetch($sql, $params, 0);
        return $this->db->getTableName($table, false) === $this->table;
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
        $sth = $this->db->query($sql);
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
