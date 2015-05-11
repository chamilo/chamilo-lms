<?php
/**
 * Equation driver for Text_CAPTCHA.
 * Returns simple equations as string, e.g. "9 - 2"
 *
 * PHP version 5
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Christian Weiske <cweiske@php.net>
 * @author   Christian Wenz <wenz@php.net>
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */

/**
 * Equation driver for Text_CAPTCHA.
 * Returns simple equations as string, e.g. "9 - 2"
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Christian Weiske <cweiske@php.net>
 * @author   Christian Wenz <wenz@php.net>
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */
class Text_CAPTCHA_Driver_Equation extends Text_CAPTCHA_Driver_Base
{
    /**
     * Operators that may be used in the equation. Two numbers have to be filled in,
     * and %s is needed since number2text conversion may be applied and strings
     * filled in.
     *
     * @var array
     */
    private $_operators = array(
        '%s * %s',
        '%s + %s',
        '%s - %s',
        'min(%s, %s)',
        'max(%s, %s)'
    );

    /**
     * Minimal number to use in an equation.
     *
     * @var int
     */
    private $_min = 1;

    /**
     * Maximum number to use in an equation.
     *
     * @var int
     */
    private $_max = 10;

    /**
     * Whether numbers shall be converted to text.
     *
     * @var bool
     */
    private $_numbersToText = false;

    /**
     * This variable holds the locale for Numbers_Words.
     *
     * @var string
     */
    private $_locale = '';

    /**
     * Complexity of the generated equations.<br>
     * 1 - simple ones such as "1 + 10"<br>
     * 2 - harder ones such as "(3-2)*(min(5,6))"
     *
     * @var int
     */
    private $_severity = 1;

    /**
     * Initialize the driver.
     *
     * @param array $options CAPTCHA options with these keys:<br>
     *                       min           minimum numeric value
     *                       max           maximum numeric value
     *                       numbersToText boolean for number to text conversion
     *                       locale        locale for number to text conversion
     *                       severity      number for complexity
     *
     * @return void
     * @throws Text_CAPTCHA_Exception when numbersToText is true, but Number_Words
     *                                package is not available
     */
    public function initDriver($options = array())
    {
        if (isset($options['min'])) {
            $this->_min = (int)$options['min'];
        } else {
            $this->_min = 1;
        }
        if (isset($options['max'])) {
            $this->_max = (int)$options['max'];
        } else {
            $this->_max = 10;
        }
        if (isset($options['numbersToText'])) {
            $this->_numbersToText = (bool)$options['numbersToText'];
        } else {
            $this->_numbersToText = false;
        }
        if (isset($options['locale'])) {
            $this->_locale = (string)$options['locale'];
        } else {
            $this->_locale = '';
        }
        if (isset($options['severity'])) {
            $this->_severity = (int)$options['severity'];
        } else {
            $this->_severity = 1;
        }

        if ($this->_numbersToText) {
            include_once 'Numbers/Words.php';
            if (!class_exists('Numbers_Words')) {
                throw new Text_CAPTCHA_Exception('Number_Words package required');
            }
        }
    }

    /**
     * Create random CAPTCHA equation.
     * This method creates a random equation.
     *
     * @return void
     * @throws Text_CAPTCHA_Exception when invalid severity is specified
     */
    public function createCAPTCHA()
    {
        switch ($this->_severity) {
        case 1:
            list($equation, $phrase) = $this->_createSimpleEquation();
            break;
        case 2:
            list($eq1, $sol1) = $this->_createSimpleEquation();
            list($eq2, $sol2) = $this->_createSimpleEquation();
            $op3 = $this->_operators[mt_rand(0, count($this->_operators) - 1)];
            list(, $phrase) = $this->_solveSimpleEquation($sol1, $sol2, $op3);
            $equation = sprintf($op3, '(' . $eq1 . ')', '(' . $eq2 . ')');
            break;
        default:
            throw new Text_CAPTCHA_Exception(
                'Equation complexity of ' . $this->_severity . ' not supported'
            );
        }
        $this->setCaptcha($equation);
        $this->setPhrase($phrase);
    }

    /**
     * Creates a simple equation of type (number operator number).
     *
     * @return array Array with equation and solution
     */
    private function _createSimpleEquation()
    {
        $one = mt_rand($this->_min, $this->_max);
        $two = mt_rand($this->_min, $this->_max);
        $operator = $this->_operators[mt_rand(0, count($this->_operators) - 1)];

        return $this->_solveSimpleEquation($one, $two, $operator);
    }

    /**
     * Solves a simple equation with two given numbers and one operator as defined
     * in $this->_operators.
     * Also converts the numbers to words if required.
     *
     * @param int    $one      First number
     * @param int    $two      Second number
     * @param string $operator Operator used with those two numbers
     *
     * @return array    Array with equation and solution
     */
    private function _solveSimpleEquation($one, $two, $operator)
    {
        $equation = sprintf($operator, $one, $two);

        $function = create_function('', 'return ' . $equation . ';');

        if ($this->_numbersToText) {
            $numberWords = new Numbers_Words();
            $equation = sprintf(
                $operator,
                $numberWords->toWords($one, $this->_locale),
                $numberWords->toWords($two, $this->_locale)
            );
        }
        return array($equation, $function());
    }

    /**
     * Creates the captcha. This method is a placeholder, since the equation is
     * created in createCAPTCHA()
     *
     * @return void
     * @see createCAPTCHA()
     */
    public function createPhrase()
    {
        $this->setPhrase(null);
    }
}
