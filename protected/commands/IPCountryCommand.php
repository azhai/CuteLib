<?php
use \Cute\Command;
use \Cute\Cache\TSVCache;
use \Cute\Contrib\GEO\IPCountry;


class IPCountryCommand extends Command
{
    protected $dat = null;
    protected $idat = null;
    
    protected function writeData(& $data)
    {
        $records = array();
        foreach ($data as $i => $row) {
            if (strpos($row[0], ':') !== false) { //IPv6
                break;
            }
            $words = $row[2];
            if (! isset($records[$words])) {
                $position = $this->dat->tell();
                $this->dat->writeString($words);
                $records[$words] = $position;
            } else {
                $position = $records[$words];
            }
            $this->idat->writeIP($row[0]);
            $this->idat->writeIP($row[1]);
            $this->idat->writeOffset($position);
        }
        $this->dat->appendIndexes($this->idat);
    }
    
    public function execute()
    {
        $this->dat = new IPCountry(APP_ROOT . '/misc/ipcountry.dat');
        $version = date('Y.m');
        $this->dat->initiate('write');
        $this->dat->writeHeaders($version);
        $this->idat = new IPCountry(); //临时文件用于存放索引数据
        $this->idat->initiate('write');
        $cache = new TSVCache('dbip-country', APP_ROOT . '/misc', ',');
        $data = $cache->initiate()->readData(3);
        $this->writeData($data);
        $this->dat->writeHeaders();
        $this->idat->close();
        $this->dat->close();
        $total = $this->dat->initiate('read')->readHeaders();
        $this->app->writeln('Write %d indexes in binary file.', $total);
        $this->app->writeln('DONE');
    }
}


