<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * HTML class for a file upload field
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
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: file.php,v 1.25 2009/04/04 21:34:02 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * HTML class for a file upload field
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 3.2.11
 * @since       1.0
 */
class HTML_QuickForm_file extends HTML_QuickForm_input
{
    // {{{ properties

   /**
    * Uploaded file data, from $_FILES
    * @var array
    */
    var $_value = null;

    // }}}
    // {{{ constructor

    /**
     * Class constructor
     *
     * @param     string    Input field name attribute
     * @param     string    Input field label
     * @param     mixed     (optional)Either a typical HTML attribute string
     *                      or an associative array
     * @since     1.0
     * @access    public
     */
    public function __construct($elementName=null, $elementLabel=null, $attributes=null)
    {
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->setType('file');


    } //end constructor

    // }}}
    // {{{ setSize()

    /**
     * Sets size of file element
     *
     * @param     int    Size of file element
     * @since     1.0
     * @access    public
     */
    function setSize($size)
    {
        $this->updateAttributes(array('size' => $size));
    } //end func setSize

    // }}}
    // {{{ getSize()

    /**
     * Returns size of file element
     *
     * @since     1.0
     * @access    public
     * @return    int
     */
    function getSize()
    {
        return $this->getAttribute('size');
    } //end func getSize

    // }}}
    // {{{ freeze()

    /**
     * Freeze the element so that only its value is returned
     *
     * @access    public
     * @return    bool
     */
    function freeze()
    {
        return false;
    } //end func freeze

    // }}}
    // {{{ setValue()

    /**
     * Sets value for file element.
     *
     * Actually this does nothing. The function is defined here to override
     * HTML_Quickform_input's behaviour of setting the 'value' attribute. As
     * no sane user-agent uses <input type="file">'s value for anything
     * (because of security implications) we implement file's value as a
     * read-only property with a special meaning.
     *
     * @param     mixed    Value for file element
     * @since     3.0
     * @access    public
     */
    function setValue($value)
    {
        return null;
    } //end func setValue

    // }}}
    // {{{ getValue()

    /**
     * Returns information about the uploaded file
     *
     * @since     3.0
     * @access    public
     * @return    array
     */
    public function getValue()
    {
        return $this->_value;
    } // end func getValue

    // }}}
    // {{{ onQuickFormEvent()

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param     string    Name of event
     * @param     mixed     event arguments
     * @param     object    calling object
     * @since     1.0
     * @access    public
     * @return    bool
     */
    public function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event) {
            case 'updateValue':
                if ($caller->getAttribute('method') == 'get') {
                    throw new \Exception('Cannot add a file upload field to a GET method form');
                }
                $this->_value = $this->_findValue();
                $caller->updateAttributes(array('enctype' => 'multipart/form-data'));
                $caller->setMaxFileSize();
                break;
            case 'addElement':
                $this->onQuickFormEvent('createElement', $arg, $caller);

                return $this->onQuickFormEvent('updateValue', null, $caller);
                break;
            case 'createElement':
                //$className = get_class($this);
                //$this &= new $className($arg[0], $arg[1], $arg[2]);

                break;
        }
        return true;
    }

    /**
     * Moves an uploaded file into the destination
     *
     * @param    string  Destination directory path
     * @param    string  New file name
     * @access   public
     * @return   bool    Whether the file was moved successfully
     */
    public function moveUploadedFile($dest, $fileName = '')
    {
        if ($dest != ''  && substr($dest, -1) != '/') {
            $dest .= '/';
        }
        $fileName = ($fileName != '') ? $fileName : basename($this->_value['name']);
        return move_uploaded_file($this->_value['tmp_name'], $dest . $fileName);
    }

    /**
     * Checks if the element contains an uploaded file
     *
     * @access    public
     * @return    bool      true if file has been uploaded, false otherwise
     */
    public function isUploadedFile()
    {
        return self::_ruleIsUploadedFile($this->_value);
    }

    /**
     * Checks if the given element contains an uploaded file
     *
     * @param     array     Uploaded file info (from $_FILES)
     * @access    private
     * @return    bool      true if file has been uploaded, false otherwise
     */
    public static function _ruleIsUploadedFile($elementValue)
    {
        if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
            (!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none')) {
            return is_uploaded_file($elementValue['tmp_name']);
        } else {
            return false;
        }
    }


   /**
    * Tries to find the element value from the values array
    *
    * Needs to be redefined here as $_FILES is populated differently from
    * other arrays when element name is of the form foo[bar]
    *
    * @access    public
    * @return    mixed
    */
    public function _findValue(&$values = null)
    {
        if (empty($_FILES)) {
            return null;
        }
        $elementName = $this->getName();
        if (isset($_FILES[$elementName])) {
            return $_FILES[$elementName];
        } elseif (false !== ($pos = strpos($elementName, '['))) {
            $base  = str_replace(
                        array('\\', '\''), array('\\\\', '\\\''),
                        substr($elementName, 0, $pos)
                    );
            $idx   = "['" . str_replace(
                        array('\\', '\'', ']', '['), array('\\\\', '\\\'', '', "']['"),
                        substr($elementName, $pos + 1, -1)
                     ) . "']";
            $props = array('name', 'type', 'size', 'tmp_name', 'error');
            $code  = "if (!isset(\$_FILES['{$base}']['name']{$idx})) {\n" .
                     "    return null;\n" .
                     "} else {\n" .
                     "    \$value = array();\n";
            foreach ($props as $prop) {
                $code .= "    \$value['{$prop}'] = \$_FILES['{$base}']['{$prop}']{$idx};\n";
            }
            return eval($code . "    return \$value;\n}\n");
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getElementJS($param)
    {
        $id = $this->getAttribute('id');
        $ratio = '16 / 9';
        if (!empty($param['ratio'])) {
            $ratio = $param['ratio'];
        }
        return '<script>
        $(document).ready(function() {
            var $image = $("#'.$id.'_preview_image");
            var $input = $("[name=\''.$id.'_crop_result\']");
            var $cropButton = $("#'.$id.'_crop_button");
            var canvas = "";
            var imageWidth = "";
            var imageHeight = "";
            
            $("#'.$id.'").change(function() {
                var oFReader = new FileReader();
                oFReader.readAsDataURL(document.getElementById("'.$id.'").files[0]);
        
                oFReader.onload = function (oFREvent) {
                    $image.attr("src", this.result);
                    $("#'.$id.'_label_crop_image").html("'.get_lang('Preview').'");
                    $("#'.$id.'_crop_image").addClass("thumbnail");
                    $cropButton.removeClass("hidden");
                    // Destroy cropper
                    $image.cropper("destroy");
        
                    $image.cropper({
                        aspectRatio: ' . $ratio . ',
                        responsive : true,
                        center : false,
                        guides : false,
                        movable: false,
                        zoomable: false,
                        rotatable: false,
                        scalable: false,
                        crop: function(e) {
                            // Output the result data for cropping image.
                            $input.val(e.x+","+e.y+","+e.width+","+e.height);
                        }
                    });
                };
            });
            
            $("#'.$id.'_crop_button").on("click", function() {
                var canvas = $image.cropper("getCroppedCanvas");
                var dataUrl = canvas.toDataURL();
                $image.attr("src", dataUrl);
                $image.cropper("destroy");
                $cropButton.addClass("hidden");
                return false;
            });
        });
        </script>';
    }

    public function toHtml()
    {
        $js = '';
        if (isset($this->_attributes['crop_image'])) {
            $ratio = '16 / 9';
            if (!empty($this->_attributes['crop_ratio'])) {
                $ratio = $this->_attributes['crop_ratio'];
            }
            $js = $this->getElementJS(array('ratio' => $ratio));
        }

        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
            return $js.$this->_getTabs() . '<input' . $this->_getAttrString($this->_attributes) . ' />';
        }
    } //end func toHtml

}
