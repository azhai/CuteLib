<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Cache;
use \SplSubject;
use \SplObserver;


/**
 * 缓存对象
 */
class Subject implements SplSubject
{
    protected $observers = array();

    public function attach(SplObserver $observer)
    {
        $this->observers[] = $observer;
    }

    public function detach(SplObserver $observer)
    {
        $key = array_search($observer,$this->observers, true);
        if ($key) {
            unset($this->observers[$key]);
        }
    }

    public function notify()
    {
        foreach ($this->observers as &$observer) {
            $observer->update($this);
        }
    }
}
