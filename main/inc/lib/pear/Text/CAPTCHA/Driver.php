<?php
/**
 * Interface for Drivers
 *
 * PHP version 5
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */
/**
 * Interface for Text_CAPTCHA drivers
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */
interface Text_CAPTCHA_Driver
{
    /**
     * Clear the internal state of the driver.
     *
     * @return void
     */
    function resetDriver();

    /**
     * Initialize the driver with the given options.
     *
     * @param array $options options for the driver as array
     *
     * @return void
     * @throws Text_CAPTCHA_Exception something went wrong during init
     */
    function initDriver($options);

    /**
     * Generate the CAPTCHA.
     *
     * @return void
     * @throws Text_CAPTCHA_Exception something went wrong during creation of CAPTCHA
     */
    function createCAPTCHA();

    /**
     * Generate the phrase for the CAPTCHA.
     *
     * @return void
     * @throws Text_CAPTCHA_Exception something went wrong during creation of phrase
     */
    function createPhrase();
}