<?php

namespace Blog;
use \Cute\ORM\Model;
use \Cute\ORM\BelongsTo;


/**
 * UserMeta 模型
 */
class UserMeta extends Model
{
    protected $umeta_id = NULL;
    public $user_id = 0;
    public $meta_key = NULL;
    public $meta_value = NULL;

    public static function getTable()
    {
        return 'usermeta';
    }

    public static function getPKeys()
    {
        return array('umeta_id');
    }

    public function getRelations()
    {
        return array(
            'user' => new BelongsTo('\\Blog\\User'),
        );
    }
}