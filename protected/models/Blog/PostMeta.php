<?php

namespace Blog;
use \Cute\ORM\Model;
use \Cute\ORM\BelongsTo;


/**
 * PostMeta 模型
 */
class PostMeta extends Model
{
    protected $meta_id = NULL;
    public $post_id = 0;
    public $meta_key = NULL;
    public $meta_value = NULL;

    public static function getTable()
    {
        return 'postmeta';
    }

    public static function getPKeys()
    {
        return array('meta_id');
    }

    public function getRelations()
    {
        return array(
            'post' => new BelongsTo('\\Blog\\Post'),
        );
    }
}