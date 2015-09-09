<?php
if ($ns):
    echo 'namespace ' . trim($ns, '\\') . ";\n";
endif;
?>
use \Cute\ORM\Model;


/**
 * <?=$name?> 模型
 */
class <?=$name?> extends Model
{
<?php
foreach ($fields as $field => $default):
    if (in_array($field, $pkeys)):
?>
    protected $<?=$field?> = NULL;
<?php elseif (in_array($field, $protecteds)): ?>
    protected $<?=$field?> = <?=var_export($default, true)?>;
<?php else: ?>
    public $<?=$field?> = <?=var_export($default, true)?>;
<?php
    endif;
endforeach;
?>

    public static function getTable()
    {
        return '<?=$table?>';
    }

    public static function getPKeys()
    {
        return array('<?=implode("', '", $pkeys)?>');
    }

    public function getRelations()
    {
        return array();
    }
}
