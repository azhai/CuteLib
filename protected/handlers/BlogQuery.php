<?php
use \Cute\Handler;
use \Cute\ORM\Query;


trait BlogQuery
{
    protected $db = null;
    protected $logger = null;
    protected $ns = 'Blog';
    
    public function loadModels($ns, $dir)
    {
        if (file_exists($dir . '/' . $ns)) {
            return $this->app->import($ns, $dir);
        }
        $tables = $this->db->listTables();
        foreach ($tables as $table) {
            $model = '';
            if (ends_with($table, 'meta')) {
                $model = substr(ucfirst($table), 0, -4) . 'Meta';
            }
            Query::generateModel($this->db, $table, $model, $ns, true);
        }
        return $this->app->import($ns, $dir);
    }
    
    public function init($method)
    {
        $this->db = $this->app->load('\\Cute\\DB\\Database', 'wordpress');
        $this->loadModels($this->ns, APP_ROOT . '/protected/models');
        return parent::init($method);
    }
    
    public function query($model)
    {
        return new Query($this->db, sprintf('\\%s\\%s', $this->ns, $model));
    }
    
    public function logSQL()
    {
        if (! $this->logger) {
            $this->logger = $this->app->load('\\Cute\\Logging\\FileLogger', 'sql');
        }
        $sql_rows = $this->db->getPastSQL();
        foreach ($sql_rows as $row) {
            $this->logger->info('{act} "{sql}";', $row);
        }
    }
}
