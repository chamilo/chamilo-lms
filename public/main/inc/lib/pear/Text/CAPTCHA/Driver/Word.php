<?php
/**
 * Text_CAPTCHA_Driver_Word - Text_CAPTCHA driver word CAPTCHAs
 * Class to create a textual Turing test
 *
 * PHP version 5
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Tobias Schlitt <schlitt@php.net>
 * @author   Christian Wenz <wenz@php.net>
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */

/**
 * Require Numbers_Words class for generating the text.
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Tobias Schlitt <schlitt@php.net>
 * @author   Christian Wenz <wenz@php.net>
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */
class Text_CAPTCHA_Driver_Word extends Text_CAPTCHA_Driver_Base
{
    /**
     * Phrase length.
     * This variable holds the length of the Word.
     *
     * @var integer
     */
    private $_length;

    /**
     * Numbers_Words mode.
     * This variable holds the mode for Numbers_Words.
     *
     * @var String
     */
    private $_mode;

    /**
     * Locale
     * This variable holds the locale for Numbers_Words
     *
     * @var string
     */
    private $_locale;

    /**
     * Initializes the new Text_CAPTCHA_Driver_Word object.
     *
     * @param array $options CAPTCHA options with these keys:<br>
     *                       phrase  The "secret word" of the CAPTCHA<br>
     *                       length  The number of characters in the phrase<br>
     *                       locale  The locale for Numbers_Words<br>
     *                       mode    The mode for Numbers_Words
     *
     * @return void
     */
    public function initDriver($options = array())
    {
        if (isset($options['length']) && is_int($options['length'])) {
            $this->_length = $options['length'];
        } else {
            $this->_length = 4;
        }
        if (isset($options['phrase']) && !empty($options['phrase'])) {
            $this->setPhrase((string)$options['phrase']);
        } else {
            $this->createPhrase();
        }
        if (isset($options['mode']) && !empty($options['mode'])) {
            $this->_mode = $options['mode'];
        } else {
            $this->_mode = 'single';
        }
        if (isset($options['locale']) && !empty($options['locale'])) {
            $this->_locale = $options['locale'];
        } else {
            $this->_locale = 'en_US';
        }
    }

    /**
     * Create random CAPTCHA phrase, "Word edition" (numbers only).
     * This method creates a random phrase
     *
     * @return void
     */
    public function createPhrase()
    {
        $phrase = new Text_Password();
        $this->setPhrase(
            $phrase->create(
                $this->_length, 'unpronounceable', 'numeric'
            )
        );
    }

    /**
     * Place holder for the real _createCAPTCHA() method
     * used by extended classes to generate CAPTCHA from phrase
     *
     * @return void
     */
    public function createCAPTCHA()
    {
        $res = '';
        $numberWords = new Numbers_Words();
        $phrase = $this->getPhrase();
        if ($this->_mode == 'single') {
            $phraseArr = str_split($phrase);
            for ($i = 0; $i < strlen($phrase); $i++) {
                $res .= ' ' . $numberWords->toWords($phraseArr[$i], $this->_locale);
            }
        } else {
            $res = $numberWords->toWords($phrase, $this->_locale);
        }
        $this->setCaptcha($res);
    }
}
