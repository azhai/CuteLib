<?php

namespace Blog;
use \Cute\ORM\Model;
use \Cute\ORM\BelongsTo;


/**
 * CommentMeta 模型
 */
class CommentMeta extends Model
{
    protected $meta_id = NULL;
    public $comment_id = 0;
    public $meta_key = NULL;
    public $meta_value = NULL;

    public static function getTable()
    {
        return 'commentmeta';
    }

    public static function getPKeys()
    {
        return array('meta_id');
    }

    public function getRelations()
    {
        return array(
            'comment' => new BelongsTo('\\Blog\\Comment'),
        );
    }
}