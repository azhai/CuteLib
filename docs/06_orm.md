
## ORM  关系数据库

\Cute\DB\Database只能实现简单的查询，主要是

* execute() 增删改一类写查询Insert/Replace/Update/Delete/Truncate/Alter/Drop

* query() 只读查询Select/Show，返回PDOStatement对象

* fetch() 查询一行，返回一行或者一个数据

* transact() 数据库事务

\Cute\ORM\Query的generateModel()方法，能为每张表生成model文件，支持外键查询

```php
//以WordPress数据库为例，将每张表生成一个model文件，放在Blog包名下
$ns = 'Blog';
$db = $app->load('\\Cute\\DB\\Database', 'wordpress');
$tables = $db->listTables();
foreach ($tables as $table) {
    $model = '';
    if (ends_with($table, 'meta')) {
        $model = substr(ucfirst($table), 0, -4) . 'Meta';
    }
    Query::generateModel($db, $table, $model, $ns, true);
}
$app->import($ns, APP_ROOT . '/protected/models');

//接着在PostModel类中，添加与Comment的一对多关系
use \Cute\ORM\HasMany;
public function getRelations()
{
    return array(
        'comments' => new HasMany('\\Blog\\Comment', '', 'comment_post_ID'),
    );
}

//最后查询最近发表的5个Post，以及它们的Comment
$model = 'Post';
$query = new Query($db, sprintf('\\%s\\%s', $ns, $model));
$posts = $query->join('comments')->orderBy('post_date DESC')->setPage(5)->all();
```