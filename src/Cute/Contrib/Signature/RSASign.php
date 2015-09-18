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
 * RSA加密
 */
class RSASign implements ISignature
{
    protected $its_pubkey_path = ''; // 对方公钥
    protected $my_prikey_path = '';  // 己方私钥
    public $digest = null; // 摘要算法函数

    public function __construct($my_prikey_path, $its_pubkey_path = false)
    {
        $this->my_prikey_path = $my_prikey_path;
        if ($its_pubkey_path === false) {
            $this->its_pubkey_path = str_replace('private', 'public', $my_prikey_path);
        } else {
            $this->its_pubkey_path = $its_pubkey_path;
        }
    }

    public function getName()
    {
        return 'RSA';
    }
    
    public function addFields(&$payment)
    {
    }
    
    public function getPrivateKey()
    {
        $private_key = file_get_contents($this->my_prikey_path);
        return openssl_get_privatekey($private_key);
    }
    
    public function getPublicKey()
    {
        $public_key = file_get_contents($this->its_pubkey_path);
        return openssl_get_publickey($public_key);
    }

    public function sign($origin)
    {
        $resource = $this->getPrivateKey();
        if ($this->digest) {
            $digest = $this->digest;
            $origin = $digest($origin);
        }
        openssl_sign($origin, $crypto, $resource, OPENSSL_ALGO_SHA1);
        if (is_resource($resource)) {
            openssl_free_key($resource);
        }
        $crypto_b64 = base64_encode($crypto); 
        return $crypto_b64;
    }

    public function verify($origin, $crypto_b64)
    {
        $resource = $this->getPublicKey();
        if (! $resource) {
            return false;
        }
        //parse_str()会将base64中的+号转为空格，这里再将其还原
        $crypto_b64 = str_replace(' ', '+', $crypto_b64);
        $crypto = base64_decode($crypto_b64);
        if ($this->digest) {
            $digest = $this->digest;
            $origin = $digest($origin);
        }
        $result = openssl_verify($origin, $crypto, $resource, OPENSSL_ALGO_SHA1);
        if (is_resource($resource)) {
            openssl_free_key($resource);
        }
        return $result;
    }
}
