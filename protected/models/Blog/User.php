<?php

namespace Blog;
use \Cute\ORM\Model;
use \Cute\ORM\HasMany;


/**
 * User 模型
 */
class User extends Model
{
    use \Blog\UserMixin;
    protected $ID = NULL;
    public $user_nicename = '';
    public $user_email = '';
    public $user_url = '';
    public $user_registered = '0000-00-00 00:00:00';
    public $user_activation_key = '';
    public $user_status = 0;
    public $display_name = '';

    public static function getTable()
    {
        return 'users';
    }

    public static function getPKeys()
    {
        return array('ID');
    }

    public function getRelations()
    {
        return array(
            'metas' => new HasMany('\\Blog\\UserMeta'),
            'posts' => new HasMany('\\Blog\\Post', 'post_author'),
            'comments' => new HasMany('\\Blog\\Comment'),
            'links' => new HasMany('\\Blog\\Link', 'link_owner'),
        );
    }
}