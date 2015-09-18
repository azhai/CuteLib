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
 * HMAC加密
 */
class HMACSign implements ISignature
{
    protected $secrecy = ''; // 密钥

    public function __construct($secrecy)
    {
        $this->secrecy = $secrecy;
    }

    public function getName()
    {
        return 'HMAC';
    }
    
    public function addFields(&$payment)
    {
    }

    public function sign($origin)
    {
        return hash_hmac('md5', convert($origin, 'UTF-8'), $this->secrecy);
    }

    public function verify($origin, $crypto)
    {
        return $this->sign($origin) === $crypto;
    }
}
