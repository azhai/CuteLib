<?php
namespace Cutest\Widget;

use \PHPUnit_Framework_TestCase as TestCase;
use \Cute\Web\SessionHandler;
use \Cute\Cache\RedisCache;
use \Cute\Form\Captcha;


class SessionTest extends TestCase
{
    protected static $cache = null;

    public static function setUpBeforeClass()
    {
        $name = SessionHandler::PREFIX . session_id();
        self::$cache = new RedisCache($name);
    }

    public function notest01Set()
    {
        $ymd = date('Ymd');
        $_SESSION['ymd'] = $ymd;
        $data = 'ymd|s:8:"' . $ymd . '";';
        $sess = self::$cache->readData();
        $this->assertTrue(strpos($sess, $data) !== false);
    }

    public function notest02Captcha()
    {
        $captcha = new Captcha('', 6);
        $phrase = $captcha->build()->getPhrase();
        $this->assertEquals($_SESSION['phrase'], Captcha::encrypt($phrase));
    }
}

