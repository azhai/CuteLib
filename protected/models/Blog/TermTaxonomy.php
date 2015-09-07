<?php

namespace Blog;
use \Cute\ORM\Model;
use \Cute\ORM\Relation;


/**
 * TermTaxonomy 模型
 */
class TermTaxonomy extends Model
{
    protected $term_taxonomy_id = NULL;
    public $term_id = 0;
    public $taxonomy = '';
    public $description = NULL;
    public $parent = 0;
    public $count = 0;

    public static function getTable()
    {
        return 'term_taxonomy';
    }

    public static function getPKeys()
    {
        return array('term_taxonomy_id');
    }

    public function getRelations()
    {
        return array();
    }
}