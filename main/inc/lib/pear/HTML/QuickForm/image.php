<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * HTML class for an <input type="image" /> element
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: image.php,v 1.6 2009/04/04 21:34:03 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * HTML class for an <input type="image" /> element
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.11
 * @since       1.0
 */
class HTML_QuickForm_image extends HTML_QuickForm_input
{
    // {{{ constructor

    /**
     * Class constructor
     *
     * @param     string    $elementName    (optional)Element name attribute
     * @param     string    $src            (optional)Image source
     * @param     mixed     $attributes     (optional)Either a typical HTML attribute string
     *                                      or an associative array
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function __construct($elementName=null, $src='', $attributes=null)
    {
        parent::__construct($elementName, null, $attributes);
        $this->setType('image');
        $this->setSource($src);
    } // end class constructor

    // }}}
    // {{{ setSource()

    /**
     * Sets source for image element
     *
     * @param     string    $src  source for image element
     * @since     1.0
     * @access    public
     * @return    void
     */
    function setSource($src)
    {
        $this->updateAttributes(array('src' => $src));
    } // end func setSource

    // }}}
    // {{{ setBorder()

    /**
     * Sets border size for image element
     *
     * @param     string    $border  border for image element
     * @since     1.0
     * @access    public
     * @return    void
     */
    function setBorder($border)
    {
        $this->updateAttributes(array('border' => $border));
    }

    /**
     * Sets alignment for image element
     *
     * @param     string    $align  alignment for image element
     * @since     1.0
     * @access    public
     * @return    void
     */
    function setAlign($align)
    {
        $this->updateAttributes(array('align' => $align));
    }

    /**
     * Freeze the element so that only its value is returned
     *
     * @access    public
     * @return    void
     */
    function freeze()
    {
        return false;
    }
}
