<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Contrib\Signature;
use ISignature;


/**
 * MD5加密
 */
class MD5Sign implements ISignature
{
    protected $secrecy = ''; // 密钥

    public function __construct($secrecy)
    {
        $this->secrecy = $secrecy;
    }

    public function getName()
    {
        return 'MD5';
    }
    
    public function addFields(&$payment)
    {
    }

    public function sign($origin)
    {
        return md5($origin . $this->secrecy);
    }

    public function verify($origin, $crypto)
    {
        return $this->sign($origin) === $crypto;
    }
}
