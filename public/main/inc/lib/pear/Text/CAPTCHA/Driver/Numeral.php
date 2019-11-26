<?php
/**
 * Class used for numeral captchas
 *
 * PHP version 5
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   David Coallier <davidc@agoraproduction.com>
 * @author   Christian Wenz <wenz@php.net>
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */

/**
 * Class used for numeral captchas
 *
 * This class is intended to be used to generate numeral captchas as such as:
 * Example:
 *  Give me the answer to "54 + 2" to prove that you are human.
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   David Coallier <davidc@agoraproduction.com>
 * @author   Christian Wenz <wenz@php.net>
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */
class Text_CAPTCHA_Driver_Numeral extends Text_CAPTCHA_Driver_Base
{
    /**
     * This variable holds the minimum range value default set to "1".
     *
     * @var integer $_minValue The minimum value of the number range.
     */
    private $_minValue = 1;

    /**
     * This variable holds the maximum range value default set to "50".
     *
     * @var integer $_maxValue The maximum value of the number range.
     */
    private $_maxValue = 50;

    /**
     * The valid operators to use in the numeral captcha. We could use / and * but
     * not yet.
     *
     * @var array $_operators The operations for the captcha.
     */
    private $_operators = array('-', '+');

    /**
     * This variable is basically the operation that we're going to be using in the
     * numeral captcha we are about to generate.
     *
     * @var string $_operator The operation's operator to use.
     */
    private $_operator = '';

    /**
     * This variable holds the first number of the numeral operation we are about to
     * generate.
     *
     * @var integer $_firstNumber The first number of the operation.
     */
    private $_firstNumber = 0;

    /**
     * This variable holds the value of the second variable of the operation we are
     * about to generate for the captcha.
     *
     * @var integer $_secondNumber The second number of the operation.
     */
    private $_secondNumber = 0;

    /**
     * Initialize numeric CAPTCHA.
     *
     * @param array $options CAPTCHA options with these keys:<br>
     *                       minValue minimum value<br>
     *                       maxValue maximum value
     *
     * @return void
     */
    public function initDriver($options = array())
    {
        if (isset($options['minValue'])) {
            $this->_minValue = (int)$options['minValue'];
        } else {
            $this->_minValue = 1;
        }
        if (isset($options['maxValue'])) {
            $this->_maxValue = (int)$options['maxValue'];
        } else {
            $this->_maxValue = 50;
        }
        if (isset($options['operator'])) {
            $this->_operator = $options['operator'];
        } else {
            $this->_operator = '';
        }
        if (isset($options['firstValue'])) {
            $this->_firstNumber = (int)$options['firstValue'];
        } else {
            $this->_firstNumber = 0;
        }
        if (isset($options['secondValue'])) {
            $this->_secondNumber = (int)$options['secondValue'];
        } else {
            $this->_secondNumber = 0;
        }
    }

    /**
     * Create the CAPTCHA (the numeral expression).
     *
     * This function determines a random numeral expression and set the associated
     * class properties.
     *
     * @return void
     * @see _generateFirstNumber()
     * @see _generateSecondNumber()
     * @see _generateOperator()
     * @see _generateOperation()
     */
    public function createCAPTCHA()
    {
        if ($this->_firstNumber == 0) {
            $this->_firstNumber = $this->_generateNumber();
        }
        if ($this->_secondNumber == 0) {
            $this->_secondNumber = $this->_generateNumber();
        }
        if (empty($this->_operator)) {
            $this->_operator = $this->_operators[array_rand($this->_operators)];
        }
        $this->_generateOperation();
    }

    /**
     * Set operation.
     *
     * This variable sets the operation variable by taking the firstNumber,
     * secondNumber and operator.
     *
     * @return void
     * @see _operation
     * @see _firstNumber
     * @see _operator
     * @see _secondNumber
     */
    private function _setOperation()
    {
        $this->setCaptcha(
            $this->_firstNumber . ' ' . $this->_operator . ' ' . $this->_secondNumber
        );
    }

    /**
     * Generate a number.
     *
     * This function takes the parameters that are in the $this->_maxValue and
     * $this->_minValue and get the random number from them using mt_rand().
     *
     * @return integer Random value between _minValue and _maxValue
     * @see _minValue
     * @see _maxValue
     */
    private function _generateNumber()
    {
        return mt_rand($this->_minValue, $this->_maxValue);
    }

    /**
     * Adds values.
     *
     * This function will add the firstNumber and the secondNumber value and then
     * call setAnswer to set the answer value.
     *
     * @return void
     * @see _firstNumber
     * @see _secondNumber
     * @see _setAnswer()
     */
    private function _doAdd()
    {
        $phrase = $this->_firstNumber + $this->_secondNumber;
        $this->setPhrase($phrase);
    }

    /**
     * Does a subtract on the values.
     *
     * This function executes a subtraction on the firstNumber and the secondNumber
     * to then call $this->setAnswer to set the answer value.
     *
     * If the _firstNumber value is smaller than the _secondNumber value then we
     * regenerate the first number and regenerate the operation.
     *
     * @return void
     * @see _firstNumber
     * @see _secondNumber
     * @see _setOperation()
     * @see Text_CAPTCHA::setPhrase()
     */
    private function _doSubtract()
    {
        $first = $this->_firstNumber;
        $second = $this->_secondNumber;

        /**
         * Check if firstNumber is smaller than secondNumber
         */
        if ($first < $second) {
            $this->_firstNumber = $second;
            $this->_secondNumber = $first;
            $this->_setOperation();
        }

        $phrase = $this->_firstNumber - $this->_secondNumber;
        $this->setPhrase($phrase);
    }

    /**
     * Generate the operation
     *
     * This function will call the _setOperation() function to set the operation
     * string that will be called to display the operation, and call the function
     * necessary depending on which operation is set by this->operator.
     *
     * @return void
     * @see _setOperation()
     * @see _operator
     * @see _doAdd()
     * @see _doSubtract()
     */
    private function _generateOperation()
    {
        $this->_setOperation();
        switch ($this->_operator) {
        case '+':
            $this->_doAdd();
            break;
        case '-':
            $this->_doSubtract();
            break;
        default:
            $this->_operator = "+";
            $this->_setOperation();
            $this->_doAdd();
            break;
        }
    }

    /**
     * Create random CAPTCHA phrase. This method is a placeholder, since the equation
     * is created in createCAPTCHA()
     *
     * @return string
     */
    public function createPhrase()
    {
        $this->setCaptcha(null);
    }
}
