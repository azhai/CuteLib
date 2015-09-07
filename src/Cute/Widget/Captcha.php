<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Widget;
use \Cute\Utility\Word;


/**
 * 验证码
 */
class Captcha
{
    const ENCRYPT_SALT = 'captcha_code_salt';
    const FILENAME_PREFIX = 'cc_';
    public $phrase_size = 6;
    public $width = 80;
    public $height = 30;
    public $font = null;
    public $finger_print = null;
    protected $builder = null;
    protected $savedir = '';
    protected $saveurl = '';

    /**
     * 构造函数
     */
    public function __construct($phrase = '')
    {
        \app()->import('Gregwar', VENDOR_ROOT);
        $this->builder = new \Gregwar\Captcha\CaptchaBuilder();
        if (! empty($phrase)) {
            $this->phrase_size = strlen($phrase);
            $this->builder->setPhrase($phrase);
        } else {
            $this->refresh();
        }
        $this->savedir = APP_ROOT . '/public/assets/captcha';
        @mkdir($this->savedir, 0755, true);
        $this->saveurl = app()->getAssetURL() . '/captcha';
    }
    
    public static function newInstance($phrase_size = 6, $width = 80,
            $height = 30, $font = null, $finger_print = null)
    {
        $instance = new self();
        $instance->phrase_size = $phrase_size;
        $instance->width = $width;
        $instance->height = $height;
        $instance->font = $font;
        $instance->finger_print = $finger_print;
        return $instance;
    }

    /**
     * 加密验证码文本
     * @param string $phrase 验证码文本
     * @return string 哈希后的验证码
     */
    protected static function encrypt($phrase)
    {
        return md5(strtolower($phrase) . self::ENCRYPT_SALT);
    }

    /**
     * 清理旧的验证码图片文件
     * @param string $dir 验证码目录
     * @param float $freq 频率，大于等于1时每次都删除
     * @param int $limit 最近一段时间的文件不要清理，单位：秒
     */
    public static function clean($dir, $freq = 0.3, $limit = 60)
    {
        $rand = mt_rand(1, 10000) / 10000;
        if ($freq <= 0 || $freq >= 1 || $rand <= $freq) { // 命中概率
            $limit_time = time() - $limit;
            $files = glob($dir . 'cc_*.jpg');
            foreach ($files as $file) { //清理旧的图片文件
                if (fileatime($file) < $limit_time) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * 更换phrase
     */
    public function refresh()
    {
        $phrase = Word::randString($this->phrase_size);
        $this->builder->setPhrase($phrase);
        return $this;
    }
    
    /**
     * 设置图片格式
     */
    public function build(array $args = array())
    {
        $origin = array(
            $this->width, $this->height,
            $this->font, $this->finger_print,
        );
        //$args后面缺少的元素使用$origin的元素补齐
        exec_method_array($this->builder, 'build', $args + $origin);
        return $this->builder;
    }
    
    /**
     * 展示验证码用于同源
     */
    public function show()
    {
        @session_start();
        $phrase = $this->builder->getPhrase();
        $_SESSION['phrase'] = self::encrypt($phrase);
        @header('Content-Type: image/jpeg');
        $this->build(func_get_args());
        return $this->builder->output();
    }
    
    /**
     * 保存验证码用于跨域，提供跳转网址
     */
    public function save()
    {
        self::clean($this->savedir, 0.3, 60);
        $filename = uniqid(self::FILENAME_PREFIX) . '.jpg';
        $this->build(func_get_args());
        $this->builder->save($this->savedir . '/' . $filename);
        $phrase = $this->builder->getPhrase();
        $url = $this->saveurl . '/' . $filename . '#' . self::encrypt($phrase);
        die($url);
    }
}
