<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \Cute\ORM\Relation;
use \Cute\DB\Database;
use \PDO;


/**
 * 嵌套集合/树状结构/无限分类
 */
class NestedSet extends Relation
{
    public function relative($name, array& $result)
    {
        $query = $this->getMapper()->orderBy('low_value');
        if (! empty($result)) {
            $root = array_pop($result);
            $args = array($root->getLow(), $root->getHigh());
            $query->filter('low_value', $args, 'BETWEEN ? AND ?');
        }
        $table_name = $query->getTableName(true);
        $columns = sprintf('low_value,%s.*', $table_name);
        $fetch_style = PDO::FETCH_CLASS | PDO::FETCH_UNIQUE;
        $objects = $query->all($columns, $fetch_style);
        if (count($objects) === 0) {
            return;
        }
        $i = 0;
        $parents = array();
        $result = reset($objects);
        foreach ($objects as $low => &$object) {
            $object->$name = array();
            if (! $object->isLeaf()) {
                $parents[$low + 1] = $low; //首个子节点
            }
            if (isset($parents[$low])) {
                $object->parent = & $objects[$parents[$low]];
                $object->depth = $object->parent->depth +1;
                array_push($object->parent->$name, $object);
                $high = $object->getHigh();
                if ($high < $object->parent->getHigh() - 1) {
                    $parents[$high + 1] = $parents[$low]; //后续兄弟节点
                }
            }
        }
        return $result;
    }
}
