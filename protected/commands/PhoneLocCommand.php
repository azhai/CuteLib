<?php
use \Cute\Command;
use \Cute\Cache\TSVCache;
use \Cute\Contrib\GEO\PhoneLoc;


class PhoneLocCommand extends Command
{
    protected $dat = null;
    protected $idat = null;
    
    protected function writeData(& $data)
    {
        $last_pos = 0;
        $start_item = '';
        $stop_item = '';
        $records = array();
        foreach ($data as $i => $row) {
            $words = implode(' ', array_slice($row, 1));
            if (! isset($records[$words])) {
                $position = $this->dat->tell();
                $this->dat->writeString($words);
                $records[$words] = $position;
            } else {
                $position = $records[$words];
            }
            if ($last_pos !== $position) {
                if ($start_item) {
                    $this->idat->writeTel($start_item);
                    $this->idat->writeTel($stop_item);
                    $this->idat->writeOffset($last_pos);
                }
                $last_pos = $position;
                $start_item = $row[0];
            }
            $stop_item = $row[0];
        }
        $this->idat->writeTel($start_item);
        $this->idat->writeTel($stop_item);
        $this->idat->writeOffset($last_pos);
        $this->dat->appendIndexes($this->idat);
    }
    
    public function execute()
    {
        $this->dat = new PhoneLoc(APP_ROOT . '/misc/phoneloc.dat');
        $version = date('Y.m');
        $this->dat->initiate('write');
        $this->dat->writeHeaders($version);
        $this->idat = new PhoneLoc(); //临时文件用于存放索引数据
        $this->idat->initiate('write');
        $cache = new TSVCache('mobile', APP_ROOT . '/misc');
        $data = $cache->initiate()->readData(2);
        $this->writeData($data);
        $this->dat->writeHeaders();
        $this->idat->close();
        $this->dat->close();
        $total = $this->dat->initiate('read')->readHeaders();
        $this->app->writeln('Write %d indexes in binary file.', $total);
        $this->app->writeln('DONE');
    }
}


