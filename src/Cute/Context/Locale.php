<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Context;
use \Cute\Context\Input;


/**
 * 本地化
 */
class Locale
{
    protected $language = '';
    protected $locale_dir = '';
    protected $timezone = 'Asia/Shanghai';
    
    public function __construct($locale_dir = '', $timezone = '')
    {
        if (empty($locale_dir)) {
            $locale_dir = APP_ROOT . '/protected/locales';
        }
        $this->locale_dir = $locale_dir;
        $this->timezone = $timezone;
    }
    
    public function detectLanguage()
    {
        $input = Input::getInstance('SERVER');
        //strstr()需要PHP5.3.0以上版本支持第三个参数，如果找不到，返回false
        $language = strstr($input->get('LANG', ''), '.', true);
        if (empty($language)) {
            $accept = $input->get('HTTP_ACCEPT_LANGUAGE', '');
            preg_match_all('/([a-z]{2}\-?[A-Z]{0,2})/', $accept, $matches);
            foreach ($matches[0] as $language) {
                $language = str_replace('-', '_', $language);
                if (file_exists($this->locale_dir . '/' . $language)) {
                    break;
                }
            }
        }
        return $language;
    }

    /**
     * 设置页面语言，使用gettext
     * @param string $language 语言代码
     * @param string $domain 语言文件名
     * NOTICE: 修改翻译需要重启php-fpm才能生效
     */
    public function setLocale($language, $domain = 'messages')
    {
        if ($language) {
            $this->language = $language;
        } else {
            $this->language = $this->detectLanguage();
        }
        putenv('LANG=' . $this->language);
        setlocale(LC_ALL, $this->language);
        bindtextdomain($domain, $this->locale_dir);
        bind_textdomain_codeset($domain, 'UTF-8');
        textdomain($domain);
        return $this->language;
    }

    /**
     * 设置时区
     */
    public function setTimezone($timezone)
    {
        if ($timezone) {
            $this->timezone = $timezone;
        }
        @date_default_timezone_set($this->timezone);
        return $this->timezone;
    }
}
