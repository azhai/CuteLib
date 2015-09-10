<?php

namespace Blog;
use \Cute\ORM\Model;


/**
 * Option 模型
 */
class Option extends Model
{
    protected $option_id = NULL;
    public $option_name = '';
    public $option_value = NULL;
    public $autoload = 'yes';

    public static function getTable()
    {
        return 'options';
    }

    public static function getPKeys()
    {
        return array('option_id');
    }

    public function getRelations()
    {
        return array();
    }
}