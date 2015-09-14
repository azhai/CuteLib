<?php

namespace Blog;
use \Cute\ORM\Model;
use \Cute\ORM\BelongsTo;
use \Cute\ORM\HasMany;
use \Cute\ORM\ManyToMany;


/**
 * Post 模型
 */
class Post extends Model
{
    protected $ID = NULL;
    public $post_author = 0;
    public $post_date = '0000-00-00 00:00:00';
    public $post_date_gmt = '0000-00-00 00:00:00';
    public $post_content = NULL;
    public $post_title = NULL;
    public $post_excerpt = NULL;
    public $post_status = 'publish';
    public $comment_status = 'open';
    public $ping_status = 'open';
    public $post_password = '';
    public $post_name = '';
    public $to_ping = NULL;
    public $pinged = NULL;
    public $post_modified = '0000-00-00 00:00:00';
    public $post_modified_gmt = '0000-00-00 00:00:00';
    public $post_content_filtered = NULL;
    public $post_parent = 0;
    public $guid = '';
    public $menu_order = 0;
    public $post_type = 'post';
    public $post_mime_type = '';
    public $comment_count = 0;

    public static function getTable()
    {
        return 'posts';
    }

    public static function getPKeys()
    {
        return array('ID');
    }

    public function getRelations()
    {
        return array(
            'metas' => new HasMany('\\Blog\\PostMeta'),
            'comments' => new HasMany('\\Blog\\Comment', 'comment_post_ID'),
            'author' => new BelongsTo('\\Blog\\User', 'post_author'),
            'taxonomies' => new ManyToMany('\\Blog\\TermTaxonomy', 'object_id',
                                    'term_taxonomy_id', 'term_relationships'),
        );
    }
}