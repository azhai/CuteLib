<?php
namespace Cutest\Widget;

use \PHPUnit_Framework_TestCase as TestCase;
use \Cute\Contrib\Widget\Counter;


class CounterTest extends TestCase
{
    protected static $counter = null;

    public static function setUpBeforeClass()
    {
        self::$counter = new Counter('test_val', -1);
    }

    public static function tearDownAfterClass()
    {
        $caches = self::$counter->findCaches();
        foreach ($caches as & $cache) {
            $cache->removeData();
        }
    }

    public function test01RedisIncrease()
    {
        $cache = self::$counter->setCache('\\Cute\\Cache\\RedisCache');
        $val = self::$counter->increase();
        $this->assertEquals(0, $val);
        $this->assertEquals(0, $cache->readData());
    }

    public function test02TextIncrease()
    {
        $cache = self::$counter->setCache('\\Cute\\Cache\\TextCache');
        $val = self::$counter->increase();
        $this->assertEquals(1, $val);
        $this->assertEquals(1, $cache->readData());
    }
}

