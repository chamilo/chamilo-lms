<?php
/**
 * Base class file for all Text_CAPTCHA drivers.
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
 * Base class file for all Text_CAPTCHA drivers.
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */
abstract class Text_CAPTCHA_Driver_Base implements Text_CAPTCHA_Driver
{
    /**
     * Captcha
     *
     * @var object|string
     */
    private $_captcha;

    /**
     * Phrase
     *
     * @var string
     */
    private $_phrase;

    /**
     * Sets secret CAPTCHA phrase.
     * This method sets the CAPTCHA phrase (use null for a random phrase)
     *
     * @param string $phrase The (new) phrase
     *
     * @return void
     */
    public final function setPhrase($phrase)
    {
        $this->_phrase = $phrase;
    }

    /**
     * Return secret CAPTCHA phrase
     * This method returns the CAPTCHA phrase
     *
     * @return  string   secret phrase
     */
    public final function getPhrase()
    {
        return $this->_phrase;
    }

    /**
     * Sets the generated captcha.
     *
     * @param object|string $captcha the generated captcha
     *
     * @return void
     */
    protected final function setCaptcha($captcha)
    {
        $this->_captcha = $captcha;
    }

    /**
     * Place holder for the real getCAPTCHA() method
     * used by extended classes to return the generated CAPTCHA
     * (as an image resource, as an ASCII text, ...)
     *
     * @return string|object
     */
    public final function getCAPTCHA()
    {
        return $this->_captcha;
    }

    /**
     * Reset the phrase and the CAPTCHA.
     *
     * @return void
     */
    public function resetDriver()
    {
        $this->setPhrase(null);
        $this->setCaptcha(null);
    }
}
