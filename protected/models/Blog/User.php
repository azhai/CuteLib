<?php

namespace Blog;
use \Cute\ORM\Model;
use \Cute\ORM\Relation;


/**
 * User 模型
 */
class User extends Model
{
    protected $ID = NULL;
    public $user_login = '';
    public $user_pass = '';
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
            'metas' => new Relation(Relation::TYPE_BELONGS_TO, '\\Blog\\UserMeta'),
            'posts' => new Relation(Relation::TYPE_HAS_MANY, '\\Blog\\Post', '', 'post_author'),
        );
    }
}