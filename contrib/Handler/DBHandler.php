<?php
/**
 * Project      CuteLib
 * Author       Ryan Liu <azhai@126.com>
 * Copyright (c) 2013 MIT License
 */

namespace Cute\Contrib\Handler;


use Cute\Cache\RedisDictCache;


trait DBHandler
{
    protected $logger = null;
    protected $dbman = null;
    protected $dbtype = 'mysql';

    public function setup()
    {
        $model_dir = APP_ROOT . '/models';
        if (!empty($this->modns)) {
            $this->app->importStrip($this->modns, $model_dir);
        }
        $this->dbman = $this->app->load($this->dbtype, $this->dbkey)->getManager();
        $cache_key = sprintf('tables:%s.%s', $this->dbtype, $this->dbkey);
        $cache = new RedisDictCache($cache_key);
        $tables = $this->dbman->setCache($cache, 3600);
        if (empty($tables)) { //找不到表映射数据，从数据库中读取
            $this->dbman->setSingular(true);
            $this->dbman->genAllModels($model_dir, $this->modns);
        }
    }

    public function logSQL()
    {
        if (!$this->logger) {
            $this->logger = $this->app->load('logger', 'sql');
        }
        $sql_rows = $this->dbman->getDB()->getPastSQL();
        foreach ($sql_rows as $row) {
            $this->logger->info('{act} "{sql};"', $row);
        }
    }

    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            $this->$name = $this->dbman->loadModel($name);
        }
        return $this->$name;
    }
}
