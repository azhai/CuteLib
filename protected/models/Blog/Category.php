<?php

namespace Blog;
use \Cute\ORM\Model;


/**
 * Category 模型
 */
class Category extends Model
{
    use \Blog\CategoryMixin;
    protected $id = NULL;
    public $name = NULL;

    public static function getTable()
    {
        return 'category';
    }

    public static function getPKeys()
    {
        return array('id');
    }
    
}