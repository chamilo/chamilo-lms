<?php

/**
 * Text_CAPTCHA - creates a CAPTCHA for Turing tests.
 * Base class file for using Text_CAPTCHA.
 *
 * PHP version 5
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Christian Wenz <wenz@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */

/**
 * Text_CAPTCHA - creates a CAPTCHA for Turing tests.
 * Class to create a Turing test for websites by creating an image, ASCII art or
 * something else with some (obfuscated) characters.
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Christian Wenz <wenz@php.net>
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */
class Text_CAPTCHA
{
    /**
     * driver for Text_CAPTCHA
     *
     * @var Text_CAPTCHA_Driver_Base
     */
    private $_driver;

    /**
     * check if an initial driver init was done.
     *
     * @var bool
     */
    private $_driverInitDone = false;

    /**
     * Constructor for the TEXT_CAPTCHA object with the given driver.
     *
     * @param Text_CAPTCHA_Driver $driver driver
     *
     * @throws Text_CAPTCHA_Exception no driver given
     */
    function __construct($driver)
    {
        if (is_null($driver)) {
            throw new Text_CAPTCHA_Exception("No driver given");
        }
        $this->_driver = $driver;
        $this->_driverInitDone = false;
    }

    /**
     * Create a new Text_CAPTCHA object.
     *
     * @param string $driver name of driver class to initialize
     *
     * @return Text_CAPTCHA a newly created Text_CAPTCHA object
     * @throws Text_CAPTCHA_Exception when driver could not be loaded
     *
     */
    public static function factory($driver)
    {
        $driver = basename($driver);
        $class = 'Text_CAPTCHA_Driver_' . $driver;
        /*$file = str_replace('_', '/', $class) . '.php';
        //check if it exists and can be loaded
        if (!@fclose(@fopen($file, 'r', true))) {
            throw new Text_CAPTCHA_Exception(
                'Driver ' . $driver . ' cannot be loaded.'
            );
        }
        //continue with including the driver
        include_once $file;*/

        $driver = new $class;

        return new Text_CAPTCHA($driver);
    }

    /**
     * Create random CAPTCHA phrase
     *
     * @param boolean|string $newPhrase new Phrase to use or true to generate a new
     *                                  one
     *
     * @return void
     * @throws Text_CAPTCHA_Exception when driver is not initialized
     */
    public final function generate($newPhrase = false)
    {
        if (!$this->_driverInitDone) {
            throw new Text_CAPTCHA_Exception("Driver not initialized");
        }
        if ($newPhrase === true || is_null($this->_driver->getPhrase())) {
            $this->_driver->createPhrase();
        } else if (strlen($newPhrase) > 0) {
            $this->_driver->setPhrase($newPhrase);
        }
        $this->_driver->createCAPTCHA();
    }

    /**
     * Reinitialize the entire Text_CAPTCHA object.
     *
     * @param array $options Options to pass in.
     *
     * @return void
     */
    public final function init($options = array())
    {
        $this->_driver->resetDriver();
        $this->_driver->initDriver($options);
        $this->_driverInitDone = true;
        $this->generate();
    }

    /**
     * Place holder for the real getCAPTCHA() method used by extended classes to
     * return the generated CAPTCHA (as an image resource, as an ASCII text, ...).
     *
     * @return string|object
     */
    public final function getCAPTCHA()
    {
        return $this->_driver->getCAPTCHA();
    }

    /**
     * Return secret CAPTCHA phrase.
     *
     * @return string secret phrase
     */
    public final function getPhrase()
    {
        return $this->_driver->getPhrase();
    }

    /**
     * Place holder for the real getCAPTCHA() method used by extended classes to
     * return the generated CAPTCHA (as an image resource, as an ASCII text, ...).
     *
     * @return string|object
     */
    public function getCAPTCHAAsJPEG()
    {
        return $this->_driver->_getCAPTCHAAsJPEG();
    }
}
