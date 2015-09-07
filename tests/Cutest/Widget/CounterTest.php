<?php
namespace Cutest\Widget;
use \PHPUnit_Framework_TestCase as TestCase;
use \Cute\Widget\FileCounter;
use \Cute\Widget\RedisCounter;


class CounterTest extends TestCase
{
    protected static $file_counter = null;
    protected static $redis_counter = null;
    
    public static function setUpBeforeClass()
    {
        self::$file_counter = new FileCounter('test_val', -1);
        self::$file_counter->connect();
        self::$file_counter->readValue();
        self::$redis_counter = new RedisCounter('test_val', 0);
        self::$redis_counter->connect();
        self::$redis_counter->readValue();
    }
    
    public static function tearDownAfterClass()
    {
        self::$file_counter->remove();
        self::$redis_counter->remove();
    }

    public function test01TextIncrease()
    {
        $val = self::$file_counter->increase();
        $this->assertEquals(0, $val);
        $val = self::$file_counter->increase();
        $this->assertEquals(1, $val);
    }

    public function test02RedisIncrease()
    {
        $val = self::$redis_counter->increase();
        $this->assertEquals(1, $val);
        $val = self::$redis_counter->increase(2);
        $this->assertEquals(3, $val);
    }
}

