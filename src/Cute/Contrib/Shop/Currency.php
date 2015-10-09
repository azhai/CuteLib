<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 *
 * 汇率牌价  http://stock.finance.sina.com.cn/forex/api/openapi.php/ForexService.getAllBankForex
 */

namespace Cute\Contrib\Shop;
use \Cute\Cache\TSVCache;



/**
 * 货币
 */
class Currency
{
    protected static $instances = array();
    protected $code;
    protected $rate = 0.0;
    protected $numeric = '';
    protected $decimals = 2;

    /**
     * Create a new Currency object
     */
    protected function __construct($code, $rate = 100.0,
                            $numeric = '', $decimals = 2)
    {
        $this->code = $code;
        $this->rate = $rate;
        $this->numeric = $numeric;
        $this->decimals = $decimals;
    }

    /**
     * Find a specific currency
     *
     * @param  string $code The three letter currency code
     * @return mixed  A Currency object, or null if no currency was found
     */
    public static function getInstance($code = 'CNY')
    {
        $code = strtoupper($code);
        if (! isset(self::$instances[$code])) {
            if ($code === 'CNY') {
                self::$instances['CNY'] = new self('CNY', '156', 2);
            } else {
                self::initAllCurrencies();
            }
        }
        return self::$instances[$code];
    }

    /**
     * 获取兑换另一种货币的汇率
     * @param object $to_curr 另一种货币
     * @return float/false 汇率
     */
    public function toRate($to_curr)
    {
        if ($to_curr && $this->rate > 0 && $to_curr->rate > 0) {
            return $this->rate / $to_curr->rate;
        }
        return false;
    }

    /**
     * Get the three letter code for the currency
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get the numeric code for this currency
     *
     * @return string
     */
    public function getNumeric()
    {
        return $this->numeric;
    }

    /**
     * Get the number of decimal places for this currency
     *
     * @return int
     */
    public function getDecimals()
    {
        return $this->decimals;
    }
    
    /**
     * 从文件中加载数据
     */
    public static function initAllCurrencies()
    {
        $data = array();
        $cache = new TSVCache('currencies', APP_ROOT . '/misc');
        $cache->share($data);
        $cache->initiate()->readData();
        foreach ($data as $row) {
            @list($code, $title, $num, $dec, $rate) = $row;
            self::$instances[$code] = new self($code, $rate, $num, $dec);
        }
    }
}
