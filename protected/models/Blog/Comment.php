<?php

namespace Blog;
use \Cute\ORM\Model;
use \Cute\ORM\BelongsTo;
use \Cute\ORM\HasMany;
use \Cute\ORM\ManyToMany;


/**
 * Comment 模型
 */
class Comment extends Model
{
    protected $comment_ID = NULL;
    public $comment_post_ID = 0;
    public $comment_author = NULL;
    public $comment_author_email = '';
    public $comment_author_url = '';
    public $comment_author_IP = '';
    public $comment_date = '0000-00-00 00:00:00';
    public $comment_date_gmt = '0000-00-00 00:00:00';
    public $comment_content = NULL;
    public $comment_karma = 0;
    public $comment_approved = '1';
    public $comment_agent = '';
    public $comment_type = '';
    public $comment_parent = 0;
    public $user_id = 0;

    public static function getTable()
    {
        return 'comments';
    }

    public static function getPKeys()
    {
        return array('comment_ID');
    }

    public function getRelations()
    {
        return array(
            'metas' => new HasMany('\\Blog\\CommentMeta'),
            'post' => new BelongsTo('\\Blog\\Post', '', 'comment_post_ID'),
            'user' => new BelongsTo('\\Blog\\User'),
            'taxonomies' => new ManyToMany('\\Blog\\TermTaxonomy', '',
                            'object_id', 'term_taxonomy_id', 'term_relationships'),
        );
    }
}