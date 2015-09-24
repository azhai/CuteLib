<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;


/**
 * 数据模型
 */
class Model
{
    protected static $_fields = array();

    public static function getTable()
    {
        return '';
    }

    public static function getPKeys()
    {
        return array();
    }

    public function getRelations()
    {
        return array();
    }

    public function getFields()
    {
        $table = $this->getTable();
        if (! isset(self::$_fields[$table])) {
            $fields = get_object_vars($this);
            foreach ($fields as $name => $default) {
                if (starts_with($name, '_')) {
                    unset($fields[$name]);
                }
            }
            self::$_fields[$table] = & $fields;
        }
        return self::$_fields[$table];
    }

    public function getID($i = 0)
    {
        if ($pkeys = $this->getPKeys()) {
            $pkey = $pkeys[$i];
            return $this->$pkey;
        }
    }

    public function setID($id)
    {
        if ($pkeys = $this->getPKeys()) {
            foreach ($pkeys as $i => $pkey) {
                $this->$pkey = func_get_arg($i);
            }
        }
        return $this;
    }

    public function isExists()
    {
        $id = $this->getID();
        return $id !== 0 && ! is_null($id);
    }

    public function toArray()
    {
        $data = get_object_vars($this);
        foreach ($data as $key => $value) {
            if (starts_with($key, '_')) {
                unset($data[$key]);
            }
        }
        return $data;
    }
}
