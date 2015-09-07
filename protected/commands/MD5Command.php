<?php
use \Cute\Command;


class MD5Command extends Command
{
    public function execute()
    {
        $x = strval(reset($this->args));
        $this->app->writeln('md5("%s") : "%s"', $x, md5($x));
    }
}


