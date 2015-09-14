<?php
use \Cute\Handler;
use \Cute\ORM\Mapper;


trait BlogQuery
{
    protected $db = null;
    protected $logger = null;
    protected $ns = 'Blog';
    
    public function init($method)
    {
        $this->db = $this->app->load('\\Cute\\DB\\MySQL', 'wordpress');
        $dir = APP_ROOT . '/protected/models';
        $this->app->import($this->ns, $dir);
        $tables = $this->db->listTables();
        foreach ($tables as $table) {
            $model = '';
            if (ends_with($table, 'meta')) {
                $model = substr(ucfirst($table), 0, -4) . 'Meta';
            }
            $this->db->generateModel($dir, $table, $model, $this->ns, true);
        }
        return parent::init($method);
    }
    
    public function query($model)
    {
        return new Mapper($this->db, sprintf('\\%s\\%s', $this->ns, $model));
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
