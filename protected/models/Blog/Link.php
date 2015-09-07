<?php

namespace Blog;
use \Cute\ORM\Model;
use \Cute\ORM\Relation;


/**
 * Link 模型
 */
class Link extends Model
{
    protected $link_id = NULL;
    public $link_url = '';
    public $link_name = '';
    public $link_image = '';
    public $link_target = '';
    public $link_description = '';
    public $link_visible = 'Y';
    public $link_owner = 1;
    public $link_rating = 0;
    public $link_updated = '0000-00-00 00:00:00';
    public $link_rel = '';
    public $link_notes = NULL;
    public $link_rss = '';

    public static function getTable()
    {
        return 'links';
    }

    public static function getPKeys()
    {
        return array('link_id');
    }

    public function getRelations()
    {
        return array();
    }
}