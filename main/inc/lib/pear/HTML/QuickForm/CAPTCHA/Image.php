<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Element for HTML_QuickForm to display a CAPTCHA image
 *
 * The HTML_QuickForm_CAPTCHA package adds an element to the
 * HTML_QuickForm package to display a CAPTCHA image.
 *
 * This package requires the use of a PHP session.
 *
 * PHP versions 4 and 5
 *
 * @category  HTML
 * @package   HTML_QuickForm_CAPTCHA
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright 2006-2008 by Philippe Jausions / 11abacus
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD
 * @version   CVS: $Id: Image.php,v 1.1 2008/04/26 23:27:30 jausions Exp $
 * @link      http://pear.php.net/package/HTML_QuickForm_CAPTCHA
 */

/**
 * Required packages
 */
require_once 'HTML/QuickForm/CAPTCHA.php';
require_once 'Text/CAPTCHA/Driver/Image.php';

/**
 * Element for HTML_QuickForm to display a CAPTCHA image
 *
 * The HTML_QuickForm_CAPTCHA package adds an element to the
 * HTML_QuickForm package to display a CAPTCHA image.
 *
 * Options for the element
 * <ul>
 *  <li>'width'        (integer) width of the image,</li>
 *  <li>'height'       (integer) height of the image,</li>
 *  <li>'imageOptions' (array)   options passed to the Image_Text
 *                               constructor,</li>
 *  <li>'callback'     (string)  URL of callback script that will generate
 *                               and output the image itself,</li>
 *  <li>'alt'          (string)  the alt text for the image,</li>
 *  <li>'sessionVar'   (string)  name of session variable containing
 *                               the Text_CAPTCHA instance (defaults to
 *                               _HTML_QuickForm_CAPTCHA.)</li>
 * </ul>
 *
 * This package requires the use of a PHP session.
 *
 * @category  HTML
 * @package   HTML_QuickForm_CAPTCHA
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright 2006-2008 by Philippe Jausions / 11abacus
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD
 * @version   Release: 0.3.0
 * @link      http://pear.php.net/package/HTML_QuickForm_CAPTCHA
 * @see       Text_CAPTCHA_Driver_Image
 */
class HTML_QuickForm_CAPTCHA_Image extends HTML_QuickForm_CAPTCHA
{

    function HTML_QuickForm_CAPTCHA_Image($elementName = null, $elementLabel = null, $options = null, $attributes = null) {
        return parent::HTML_QuickForm_CAPTCHA($elementName, $elementLabel, $options, $attributes);
    }

    /**
     * Default options
     *
     * @var    array
     * @access protected
     */
    var $_options = array(
            'sessionVar'   => '_HTML_QuickForm_CAPTCHA',
            'width'        => '200',
            'height'       => '80',
            'alt'          => 'Click to view another image',
            'callback'     => '',
            'imageOptions' => null,
            'phrase'       => null,
    );

    /**
     * CAPTCHA driver
     *
     * @var    string
     * @access protected
     */
    var $_CAPTCHA_driver = 'Image';

    /**
     * Returns the HTML for the CAPTCHA image
     *
     * @return string
     * @access public
     */
    function toHtml()
    {
        if ($this->_flagFrozen) {
            return '';
        }

        $result = parent::_initCAPTCHA();

        if (PEAR::isError($result)) {
            return $result;
        }

        $html      = '';
        $tabs      = $this->_getTabs();
        $inputName = $this->getName();
        $imgName   = 'QF_CAPTCHA_'.$inputName;

        if ($this->getComment() != '') {
            $html .= $tabs.'<!-- '.$this->getComment().' // -->';
        }

        $attr = $this->_attributes;
        unset($attr['type']);
        unset($attr['value']);
        unset($attr['name']);

        $html = $tabs.'<a href="'.$this->_options['callback']
               .'" target="_blank" '
               .$this->_getAttrString($attr)
               .' onclick="var cancelClick = false; '
               .$this->getOnclickJs($imgName)
               .' return !cancelClick;"><img src="'
               .$this->_options['callback'].'" name="'.$imgName
               .'" id="'.$imgName.'" width="'.$this->_options['width']
               .'" height="'.$this->_options['height'].'" title="'
               .htmlspecialchars($this->_options['alt']).'" /></a>';

        return $html;
    }

    /**
     * Creates the javascript for the onclick event which will
     * reload a new CAPTCHA image
     *
     * @param string $imageName The image name/id
     *
     * @return string
     * @access public
     */
    function getOnclickJs($imageName)
    {
        $onclickJs = ''
            .'if (document.images) {'
            .'  var img = new Image();'
            .'  var d = new Date();'
            .'  img.src = this.href + ((this.href.indexOf(\'?\') == -1) '
                                     .'? \'?\' : \'&\') + d.getTime();'
            .'  document.images[\''.addslashes($imageName).'\'].src = img.src;'
            .'  cancelClick = true;'
            .'}';
        return $onclickJs;
    }
}
