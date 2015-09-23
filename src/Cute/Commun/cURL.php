<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Commun;
use \Cute\Base\Mocking;
use \Cute\Logging\Logger;


/**
 * cURL的HTTP客户端
 * NOTICE:
 *   PHP的cURL无法看到本地的/etc/hosts文件，而Bash的curl可以
 */
class cURL
{
    use \Cute\Base\Deferring;
    
    const CONN_FAIL_ERRNO = 7;
    const DNS_FAIL_ERRNO = 28;
    
    protected $base_url = '';
    protected $global_opts = array(); //备份全局options
    protected $logger = null;

    public function __construct($base_url = '', Logger& $logger = null)
    {
        \app()->import('Unirest', VENDOR_ROOT . '/unirest/src');
        $this->setBaseURL($base_url);
        $this->logger = Mocking::mock($logger);
    }

    public function close()
    {
        unset($this->logger);
    }
    
    public static function getRequestMethod($method = 'GET')
    {
        return @constant('\Unirest\Method::' . strtoupper($method));
    }

    public function setBaseURL($base_url)
    {
        $this->base_url = rtrim($base_url, '/');
        return $this;
    }

    /**
     * 加入options
     */
    public function prepare(array $options = array())
    {
        if (! array_key_exists('timeout', $options)
                    && ! array_key_exists('Timeout', $options)) {
            $options['Timeout'] = intval(ini_get('default_socket_timeout'));
        }
        if (! array_key_exists('useragent', $options)
                    && ! array_key_exists('UserAgent', $options)) {
            $options['UserAgent'] = 'Mozilla/4.0';
        }
        if (empty($this->global_opts)) { //未保存过
            $this->global_opts = \Unirest\Request::curlOpts(array());
        }
        if (! empty($options)) {
            \Unirest\Request::curlOpts($this->global_opts);
        }
        return $this;
    }
    
    /**
     * 还原options和记录日志
     */
    public function finish(& $response, $method = 'GET', $reqbody = '-', $phrase = '')
    {
        \Unirest\Request::clearCurlOpts();
        \Unirest\Request::curlOpts($this->global_opts);
        if ($this->logger instanceof Mocking) {
            return;
        }
        $url = \Unirest\Request::getInfo(CURLINFO_EFFECTIVE_URL);
        $connect_time = \Unirest\Request::getInfo(CURLINFO_CONNECT_TIME);
        $total_time = \Unirest\Request::getInfo(CURLINFO_TOTAL_TIME);
        $code = $response->code;
        $resbody = $response->body ?: '-';
        $phrase .= $phrase ? "\n" : "";
        $this->logger->info("{$method} \"{$url}\" {$connect_time} {$total_time} {$code}"
                . "\n{$phrase}>>>>>>>>\n{$reqbody}\n<<<<<<<<\n{$resbody}\n");
    }
    
    public function getURLString($url)
    {
        $url = $this->base_url . '/' . ltrim($url, '/');
        return $url;
    }
    
    public function getBodyString($body)
    {
        if (empty($body)) {
            $body = '-';
        } else if (is_array($body) || $body instanceof \Traversable) {
            $body = \Unirest\Request::buildHTTPCurlQuery($body);
            $body = http_build_query($body);
        }
        return $body;
    }
    
    public function __call($name, $args)
    {
        $method = self::getRequestMethod($name);
        if ($method) {
            $this->prepare();
            if (! empty($this->base_url)) {
                $args[0] = $this->getURLString($args[0]);
            }
            $phrase = '';
            try {
                $result = exec_method_array('\\Unirest\\Request', $name, $args);
            } catch (\Exception $e) {
                $phrase = $e->getMessage();
            }
            $body = $this->getBodyString($args[2]);
            $this->finish($result, $method, $body, $phrase);
        } else {
            $result = exec_method_array(\Unirest\Request, $name, $args);
        }
        return $result;
    }
}
