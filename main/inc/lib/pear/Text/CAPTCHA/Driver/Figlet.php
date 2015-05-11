<?php
/**
 * Require Figlet class for rendering the text.
 *
 * PHP version 5
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Aaron Wormus <wormus@php.net>
 * @author   Christian Wenz <wenz@php.net>
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */
/**
 * Text_CAPTCHA_Driver_Figlet - Text_CAPTCHA driver Figlet based CAPTCHAs
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Aaron Wormus <wormus@php.net>
 * @author   Christian Wenz <wenz@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 * @todo     define an obfuscation algorithm
 */
class Text_CAPTCHA_Driver_Figlet extends Text_CAPTCHA_Driver_Base
{
    /**
     * Text_Password options.
     *
     * @var array
     */
    private $_textPasswordOptions;

    /**
     * Width of CAPTCHA
     *
     * @var int
     */
    private $_width;

    /**
     * Length of CAPTCHA
     *
     * @var int
     */
    private $_length;

    /**
     * Figlet font
     *
     * @var string
     */
    private $_font;

    /**
     * Figlet font
     *
     * @var array
     */
    private $_style = array();

    /**
     * Output Format
     *
     * @var string
     */
    private $_output;

    /**
     * init function
     *
     * Initializes the new Text_CAPTCHA_Driver_Figlet object and creates a GD image
     *
     * @param array $options CAPTCHA options
     *
     * @return void
     * @throws Text_CAPTCHA_Exception when no options are given
     */
    public function initDriver($options = array())
    {
        if (!empty($options['output'])) {
            $this->_output = (string)$options['output'];
        } else {
            $this->_output = 'html';
        }

        if (isset($options['width']) && $options['width']) {
            $this->_width = (int)$options['width'];
        } else {
            $this->_width = 200;
        }

        if (!empty($options['length'])) {
            $this->_length = $options['length'];
        } else {
            $this->_length = 6;
        }

        if (!isset($options['phrase']) || empty($options['phrase'])) {
            $phraseOptions = (isset($options['phraseOptions'])
                && is_array($options['phraseOptions']))
                ? $options['phraseOptions'] : array();
            $this->_textPasswordOptions = $phraseOptions;
        } else {
            $this->setPhrase($options['phrase']);
        }

        if (!empty($options['style'])
            && is_array($options['style'])
        ) {
            $this->_style = $options['style'];
        }

        if (empty($this->_style['padding'])) {
            $this->_style['padding'] = '5px';
        }

        if (!empty($options['font_file'])) {
            if (is_array($options['font_file'])) {
                $arr = $options['font_file'];
                $this->_font = $arr[array_rand($arr)];
            } else {
                $this->_font = $options['font_file'];
            }
        }
    }

    /**
     * Create the passphrase.
     *
     * @return string
     */
    public function createPhrase()
    {
        $options = $this->_textPasswordOptions;
        $textPassword = new Text_Password();
        if (!is_array($options) || count($options) === 0) {
            $this->setPhrase($textPassword->create($this->_length));
        } else {
            if (count($options) === 1) {
                $this->setPhrase($textPassword->create($this->_length, $options[0]));
            } else {
                $this->setPhrase(
                    $textPassword->create($this->_length, $options[0], $options[1])
                );
            }
        }
    }

    /**
     * Create CAPTCHA image.
     *
     * This method creates a CAPTCHA image.
     *
     * @return void on error
     * @throws Text_CAPTCHA_Exception when loading font fails
     */
    public function createCAPTCHA()
    {
        $pear = new PEAR();
        $figlet = new Text_Figlet();
        if ($pear->isError($figlet->loadFont($this->_font))) {
            throw new Text_CAPTCHA_Exception('Error loading Text_Figlet font');
        }

        $outputString = $figlet->lineEcho($this->getPhrase());

        switch ($this->_output) {
        case 'text':
            $this->setCaptcha($outputString);
            break;
        case 'html':
            $this->setCaptcha($this->_getCAPTCHAAsHTML($outputString));
            break;
        case 'javascript':
            $this->setCaptcha($this->_getCAPTCHAAsJavascript($outputString));
            break;
        default:
            throw new Text_CAPTCHA_Exception('Invalid output option given');
        }
    }

    /**
     * Return CAPTCHA as HTML.
     *
     * This method returns the CAPTCHA as HTML.
     *
     * @param string $figletOutput output string from Figlet.
     *
     * @return string HTML Figlet image or PEAR error
     */
    private function _getCAPTCHAAsHTML($figletOutput)
    {
        $charWidth = strpos($figletOutput, "\n");
        $data = str_replace("\n", '<br />', $figletOutput);
        $textSize = ($this->_width / $charWidth) * 1.4;
        $cssOutput = "";
        foreach ($this->_style as $key => $value) {
            $cssOutput .= "$key: $value;";
        }

        $htmlOutput = '<div style="font-family: courier;
          font-size: ' . $textSize . 'px;
          width:' . $this->_width . 'px;
          text-align:center;">';
        $htmlOutput .= '<div style="' . $cssOutput . 'margin:0px;">
          <pre style="padding: 0px; margin: 0px;">' . $data . '</pre></div></div>';

        return $htmlOutput;
    }

    /**
     * Return CAPTCHA as Javascript version of HTML.
     *
     * This method returns the CAPTCHA as a Javascript string.
     * I'm not exactly sure what the point of doing this would be.
     *
     * @param string $figletOutput output string from Figlet.
     *
     * @return string javascript string or PEAR error
     */
    private function _getCAPTCHAAsJavascript($figletOutput)
    {
        $obfusData = rawurlencode($figletOutput);
        $javascript = "<script language=\"javascript\">
          document.write(unescape(\"$obfusData\" ) );
          </script>";
        return $javascript;
    }
}
