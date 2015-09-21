<?php
/**
 * @name    Project CuteLib
 * @url     https://github.com/azhai/CuteLib
 * @author  Ryan Liu <azhai@126.com>
 * @copyright 2013-2015 MIT License.
 */

namespace Cute\Contrib\Shop;
use \Cute\Contrib\Shop\Currency;


/**
 * 金额（人民币）
 */
class Amount
{
    protected $currency = null;
    protected $integral = 0;
    protected $millesimal = 0;

    public function __construct($value, Currency $currency = null)
    {
        $this->setValue($value);
        if (is_null($currency)) {
            $currency = Currency::getInstance();
        }
        $this->currency = $currency;
    }

    public function setValue($value)
    {
        $this->integral = intval($value);
        $this->millesimal = round(floatval($value) * 1000 % 1000);
        if ($this->millesimal === 1000) { //可能四舍五入后刚好进位
            $this->integral += 1;
            $this->millesimal = 0;
        }
    }
    
    public function getValue()
    {
        return $this->integral + $this->millesimal / 1000;
    }

    public function format($pattern = null)
    {
        $value = $this->getValue();
        $decimals = $this->getCurrencyDec();
        if (strtoupper($pattern) === '%L') {
            return intval($value * pow(10, $decimals));
        } else {
            if (is_null($pattern)) {
                $pattern = '%.' . $decimals . 'f';
            }
            return sprintf($pattern, $value);
        }
    }
    
    /**
     * 货币转换
     */
    public function toCurrency($code = 'CNY')
    {
        if ($this->currency->getCode() === $code) {
            return $this;
        }
        $currency = Currency::getInstance($code);
        $rate = $this->currency->toRate($currency);
        $value = $this->getValue() * $rate;
        return new self($value, $currency);
    }

    public function getCurrencyCode()
    {
        return $this->currency->getCode();
    }

    public function getCurrencyNum()
    {
        return $this->currency->getNumeric();
    }

    public function getCurrencyDec()
    {
        return $this->currency->getDecimals();
    }
}
