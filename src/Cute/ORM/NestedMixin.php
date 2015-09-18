<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\ORM;
use \Cute\ORM\NestedSet;


/**
 * Nested节点
 */
trait NestedMixin
{
    protected $low_value = 0;
    protected $high_value = 0;
    public $depth = 0;
    public $parent = null;

    public function getRelations()
    {
        return array(
            'children' => new NestedSet(__CLASS__),
        );
    }
    
    public function getLow()
    {
        return $this->low_value;
    }
    
    public function getHigh()
    {
        return $this->high_value;
    }
    
    public function isLeaf()
    {
        return $this->getHigh() - $this->getLow() === 1;
    }
    
    public function recur($func)
    {
        $func($this);
        foreach ($this->children as $child) {
            $child->recur($func);
        }
    }
}
