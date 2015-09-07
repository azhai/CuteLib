<?php

namespace Blog;
use \Cute\ORM\Model;
use \Cute\ORM\Relation;


/**
 * Term 模型
 */
class Term extends Model
{
    protected $term_id = NULL;
    public $name = '';
    public $slug = '';
    public $term_group = 0;

    public static function getTable()
    {
        return 'terms';
    }

    public static function getPKeys()
    {
        return array('term_id');
    }

    public function getRelations()
    {
        return array();
    }
}