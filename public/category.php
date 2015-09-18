<?php
require_once APP_ROOT . '/protected/handlers/BlogQuery.php';
use \Cute\Handler;


class CategoryHandler extends Handler
{
    use BlogQuery;
    
    public function get($name = false)
    {
        $query = $this->query('Category')->join();
        if ($name === false) {
            $query->setNothing();
        } else {
            $name = str_replace('-', ' ', strtoupper($name));
            $query->filter('name', $name);
        }
        $category = $query->setPage(1, 1)->all();
        //$this->logSQL();
        $sql_rows = $this->db->getPastSQL();
        foreach ($sql_rows as $row) {
            var_dump($row);
        }
        echo "\n<h3>分类树状图</h3>\n";
        echo "\n<pre>\n";
        $category->recur(function($obj) {
            $blanks = str_repeat(' ', $obj->depth * 3);
            printf("%s|-%s\n", $blanks, $obj->name);
        });
        echo "\n</pre>\n";
    }
}

app()->route('/', CategoryHandler);
app()->route('/<string>/', CategoryHandler);
