<?php
/**
 * Require Image_Text class for generating the text.
 *
 * PHP version 5
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Christian Wenz <wenz@php.net>
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 */

/**
 * Text_CAPTCHA_Driver_Image - Text_CAPTCHA driver graphical CAPTCHAs
 *
 * Class to create a graphical Turing test
 *
 * @category Text
 * @package  Text_CAPTCHA
 * @author   Christian Wenz <wenz@php.net>
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     http://pear.php.net/package/Text_CAPTCHA
 * @todo     refine the obfuscation algorithm :-)
 * @todo     consider removing Image_Text dependency
 */
class Text_CAPTCHA_Driver_Image extends Text_CAPTCHA_Driver_Base
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
     * Height of CAPTCHA
     *
     * @var int
     */
    private $_height;

    /**
     * CAPTCHA output format
     *
     * @var string
     */
    private $_output;

    /**
     * Further options (here: for Image_Text)
     *
     * @var array
     */
    private $_imageOptions = array(
        'font_size' => 24,
        'font_path' => './',
        'font_file' => 'COUR.TTF',
        'text_color' => '#000000',
        'lines_color' => '#CACACA',
        'background_color' => '#555555',
        'antialias' => false
    );

    /**
     * Init function
     *
     * Initializes the new Text_CAPTCHA_Driver_Image object and creates a GD image
     *
     * @param array $options CAPTCHA options
     *
     * @return void
     */
    public function initDriver($options = array())
    {
        if (is_array($options)) {
            if (isset($options['width']) && is_int($options['width'])) {
                $this->_width = $options['width'];
            } else {
                $this->_width = 200;
            }
            if (isset($options['height']) && is_int($options['height'])) {
                $this->_height = $options['height'];
            } else {
                $this->_height = 80;
            }
            if (!isset($options['phrase']) || empty($options['phrase'])) {
                $phraseOptions = (isset($options['phraseOptions'])
                    && is_array($options['phraseOptions']))
                    ? $options['phraseOptions'] : array();
                $this->_textPasswordOptions = $phraseOptions;
            } else {
                $this->setPhrase($options['phrase']);
            }
            if (!isset($options['output']) || empty($options['output'])) {
                $this->_output = 'jpeg';
            } else {
                $this->_output = $options['output'];
            }
            if (isset($options['imageOptions'])
                && is_array($options['imageOptions'])
                && count($options['imageOptions']) > 0
            ) {
                $this->_imageOptions = array_merge(
                    $this->_imageOptions, $options['imageOptions']
                );
            }
        }
    }

    /**
     * Create CAPTCHA image.
     *
     * This method creates a CAPTCHA image.
     *
     * @return void
     * @throws Text_CAPTCHA_Exception when image generation with Image_Text produces
     *               an error
     */
    public function createCAPTCHA()
    {
        $options['canvas'] = array(
            'width' => $this->_width,
            'height' => $this->_height
        );
        $options['width'] = $this->_width - 20;
        $options['height'] = $this->_height - 20;
        $options['cx'] = ceil(($this->_width) / 2 + 10);
        $options['cy'] = ceil(($this->_height) / 2 + 10);
        $options['angle'] = rand(0, 30) - 15;
        $options['font_size'] = $this->_imageOptions['font_size'];
        $options['font_path'] = $this->_imageOptions['font_path'];
        $options['font_file'] = $this->_imageOptions['font_file'];
        $options['color'] = array($this->_imageOptions['text_color']);
        $options['background_color'] = $this->_imageOptions['background_color'];
        $options['max_lines'] = 1;
        $options['mode'] = 'auto';

        do {
            $imageText = new Image_Text($this->getPhrase(), $options);
            $imageText->init();
            $result = $imageText->measurize();
        } while ($result === false && --$options['font_size'] > 0);
        if ($result === false) {
            throw new Text_CAPTCHA_Exception(
                'The text provided does not fit in the image dimensions'
            );
        }
        $imageText->render();
        $image = $imageText->getImg();

        if ($this->_imageOptions['antialias'] && function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $colors = Image_Text::convertString2RGB(
            $this->_imageOptions['lines_color']
        );
        $linesColor = imagecolorallocate(
            $image, $colors['r'], $colors['g'], $colors['b']
        );
        //some obfuscation
        for ($i = 0; $i < 3; $i++) {
            $x1 = rand(0, $this->_width - 1);
            $y1 = rand(0, round($this->_height / 10, 0));
            $x2 = rand(0, round($this->_width / 10, 0));
            $y2 = rand(0, $this->_height - 1);
            imageline($image, $x1, $y1, $x2, $y2, $linesColor);
            $x1 = rand(0, $this->_width - 1);
            $y1 = $this->_height - rand(1, round($this->_height / 10, 0));
            $x2 = $this->_width - rand(1, round($this->_width / 10, 0));
            $y2 = rand(0, $this->_height - 1);
            imageline($image, $x1, $y1, $x2, $y2, $linesColor);
            $cx = rand(0, $this->_width - 50) + 25;
            $cy = rand(0, $this->_height - 50) + 25;
            $w = rand(1, 24);
            imagearc($image, $cx, $cy, $w, $w, 0, 360, $linesColor);
        }

        // @todo remove hardcoded value
        $this->_output = 'jpg';

        if ($this->_output == 'gif' && imagetypes() & IMG_GIF) {
            $this->setCaptcha($this->_getCAPTCHAAsGIF($image));
        } else if (($this->_output == 'jpg' && imagetypes() & IMG_JPG)
            || ($this->_output == 'jpeg' && imagetypes() & IMG_JPEG)
        ) {
            $this->setCaptcha($this->_getCAPTCHAAsJPEG($image));
        } else if ($this->_output == 'png' && imagetypes() & IMG_PNG) {
            $this->setCaptcha($this->_getCAPTCHAAsPNG($image));
        } else if ($this->_output == 'resource') {
            $this->setCaptcha($image);
        } else {
            throw new Text_CAPTCHA_Exception(
                "Unknown or unsupported output type specified"
            );
        }

        imagedestroy($image);
    }

    /**
     * Return CAPTCHA as PNG.
     *
     * This method returns the CAPTCHA as PNG.
     *
     * @param resource $image generated image
     *
     * @return string image contents
     */
    private function _getCAPTCHAAsPNG($image)
    {
        ob_start();
        imagepng($image);
        $data = ob_get_contents();
        ob_end_clean();
        return $data;
    }

    /**
     * Return CAPTCHA as JPEG.
     *
     * This method returns the CAPTCHA as JPEG.
     *
     * @param resource $image generated image
     *
     * @return string image contents
     */
    public function _getCAPTCHAAsJPEG($image)
    {
        ob_start();
        imagejpeg($image);
        $data = ob_get_contents();
        ob_end_clean();
        return $data;
    }

    /**
     * Return CAPTCHA as GIF.
     *
     * This method returns the CAPTCHA as GIF.
     *
     * @param resource $image generated image
     *
     * @return string image contents
     */
    private function _getCAPTCHAAsGIF($image)
    {
        ob_start();
        imagegif($image);
        $data = ob_get_contents();
        ob_end_clean();
        return $data;
    }

    /**
     * Create random CAPTCHA phrase, Image edition (with size check).
     *
     * This method creates a random phrase, maximum 8 characters or width / 25,
     * whatever is smaller.
     *
     * @return void
     */
    public function createPhrase()
    {
        $len = intval(min(8, $this->_width / 25));
        $options = $this->_textPasswordOptions;
        $textPassword = new Text_Password();
        if (!is_array($options) || count($options) === 0) {
            $this->setPhrase($textPassword->create($len));
        } else {
            if (count($options) === 1) {
                $this->setPhrase($textPassword->create($len, $options[0]));
            } else {
                $this->setPhrase(
                    $textPassword->create($len, $options[0], $options[1])
                );
            }
        }
    }
}
