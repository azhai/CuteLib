<?php
require_once APP_ROOT . '/protected/handlers/BlogQuery.php';
use \Cute\Handler;


class BlogHandler extends Handler
{
    use BlogQuery;
    
    public function get($title = false)
    {
        $query = $this->query('Post')->join('comments');
        if ($title === false) {
            $post = $query->all(10);
        } else {
            $post = $query->get($title, 'post_name');
        }
        var_dump($post);
    }
}


class BlogUserHandler extends Handler
{
    use BlogQuery;
    
    public function get($username)
    {
        $query = $this->query('User')->join('user_group');
        $user = $query->get($username, 'user_login');
        var_dump($user);
    }
}

app()->route('/', BlogHandler);
app()->route('/<string>/', BlogHandler);
app()->route('/user/<string>/', BlogUserHandler);
