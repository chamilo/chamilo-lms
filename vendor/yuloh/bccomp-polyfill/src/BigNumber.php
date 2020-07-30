<?php

namespace Yuloh\BcCompPolyfill;

class BigNumber
{
    /**
     * @var string
     */
    private $characteristic = '0';

    /**
     * @var int
     */
    private $characteristicLength;

    /**
     * @var string
     */
    private $mantissa = '';

    /**
     * @var boolean
     */
    private $isNegative = false;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        if (static::isNumeric($value)) {
            $this->setValues($value);
        }
    }

    /**
     * @return boolean
     */
    public function isNegative()
    {
        return $this->isNegative;
    }

    /**
     * @return boolean
     */
    public function isPositive()
    {
        return !$this->isNegative();
    }

    /**
     * @return boolean
     */
    public function isZero()
    {
        return $this->getCharacteristic() === '0' && $this->getMantissa() === '';
    }

    /**
     * @return string
     */
    public function getCharacteristic()
    {
        return $this->characteristic;
    }

    /**
     * @return int
     */
    public function getCharacteristicLength()
    {
        if (is_null($this->characteristicLength)) {
            $this->characteristicLength = strlen($this->getCharacteristic());
        }

        return $this->characteristicLength;
    }

    /**
     * @return string
     */
    public function getMantissa()
    {
        return $this->mantissa;
    }

    /**
     * @param string $value
     */
    private function setValues($value)
    {
        if ($value[0] === '-') {
            $this->isNegative = true;
            $value            = substr($value, 1);
        }

        $this->characteristic = static::parseCharacteristic($value);
        $this->mantissa       = static::parseMantissa($value);
    }

    /**
     * @param  string $value
     * @return string
     */
    private static function parseCharacteristic($value)
    {
        if (strpos($value, '.') !== false) {
            $value = substr($value, 0, strpos($value, '.'));
        }

        return strlen($value) === 1 ? $value : ltrim($value, '0');
    }

    /**
     * @param  string $value
     * @return string
     */
    private static function parseMantissa($value)
    {
        if (($separatorPos = strrpos($value, '.')) === false) {
            return '';
        }

        $value = substr($value, strrpos($value, '.') + 1);

        return rtrim($value, '0');
    }

    /**
     * @param  string $value
     * @return boolean
     */
    private static function isNumeric($value)
    {
        // remove the last decimal separator only.
        // If it has more decimal separators it's invalid.
        $separatorPos = strrpos($value, '.');
        if ($separatorPos !== false) {
            $value = substr_replace($value, '', $separatorPos, 1);
        }

        return ctype_digit($value) || ($value[0] === '-' && ctype_digit(substr($value, 1)));
    }
}
