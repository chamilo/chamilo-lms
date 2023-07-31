<?php

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
   /**
    * Uploaded file data, from $_FILES
    * @var array
    */
    var $_value = null;

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
    }

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
    }

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
    }

    /**
     * Freeze the element so that only its value is returned
     *
     * @access    public
     * @return    bool
     */
    function freeze()
    {
        return false;
    }

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
    }

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
        $ratio = 'aspectRatio: 16 / 9';
        $cropMove = '';

        if (!empty($param['ratio'])) {
            $ratio = 'aspectRatio: '.$param['ratio'].',';
        }
        $scalable = 'false';
        if (!empty($param['scalable']) && $param['scalable'] != 'false') {
            $ratio = '';
            $scalable = $param['scalable'];
        }

        if (!empty($param['maxRatio']) && !empty($param['minRatio'])) {
            $ratio = 'autoCropArea: 1, ';
            $scalable = 'true';
            $cropMove = ',cropmove: function (e) {
                var cropBoxData = $image.cropper(\'getCropBoxData\');
                var minAspectRatio = ' . $param['minRatio'] . ';
                var maxAspectRatio = ' . $param['maxRatio'] . ';
                var cropBoxWidth = cropBoxData.width;
                var aspectRatio = cropBoxWidth / cropBoxData.height;

                if (aspectRatio < minAspectRatio) {
                    $image.cropper(\'setCropBoxData\', {
                        height: cropBoxWidth / minAspectRatio
                    });
                } else if (aspectRatio > maxAspectRatio) {
                    $image.cropper(\'setCropBoxData\', {
                        height: cropBoxWidth / maxAspectRatio
                    });
                }
            }';
        }

        return '<script>
        $(function() {
            var $inputFile = $(\'#'.$id.'\'),
                $image = $(\'#'.$id.'_preview_image\'),
                $input = $(\'[name="'.$id.'_crop_result"]\'),
                $cropButton = $(\'#'.$id.'_crop_button\'),
                $formGroup = $(\'#'.$id.'-form-group\');

            function isValidType(file) {
                var fileTypes = [\'image/jpg\', \'image/jpeg\', \'image/gif\', \'image/png\'];

                for(var i = 0; i < fileTypes.length; i++) {
                    if(file.type === fileTypes[i]) {
                        return true;
                    }
                }

                return false;
            }

            function imageCropper() {
                $formGroup.show();
                $cropButton.show();
                $image
                    .cropper(\'destroy\')
                    .cropper({
                        '.$ratio.'
                        responsive : true,
                        center : false,
                        guides : false,
                        movable: false,
                        zoomable: false,
                        rotatable: false,
                        scalable: '.$scalable.',
                        crop: function(e) {
                            // Output the result data for cropping image.
                            $input.val(e.x + \',\' + e.y + \',\' + e.width + \',\' + e.height);
                        }
                        ' . $cropMove . '
                    });
            }

            $inputFile.on(\'change\', function () {
                var inputFile = this,
                    file = inputFile.files[0],
                    fileReader = new FileReader();

                if (!isValidType(file)) {
                    $formGroup.hide();
                    $cropButton.hide();

                    if (inputFile.setCustomValidity) {
                        inputFile.setCustomValidity(
                            inputFile.title ? inputFile.title : \''.get_lang('OnlyImagesAllowed').'\'
                        );
                    }

                    return;
                }

                if (inputFile.setCustomValidity) {
                    inputFile.setCustomValidity(\'\');
                }

                fileReader.readAsDataURL(file);
                fileReader.onload = function () {
                    $image
                        .attr(\'src\', this.result)
                        .on(\'load\', imageCropper);
                };
            });

            $cropButton.on(\'click\', function () {
                var canvas = $image.cropper(\'getCroppedCanvas\'),
                    dataUrl = canvas.toDataURL();

                $image.attr(\'src\', dataUrl).cropper(\'destroy\').off(\'load\', imageCropper);
                $(\'[name="'.$id.'_crop_image_base_64"]\').val(dataUrl);
                $cropButton.hide();
            });
        });
        </script>';
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        $js = '';
        if (isset($this->_attributes['crop_image'])) {
            $ratio = '16 / 9';
            if (!empty($this->_attributes['crop_ratio'])) {
                $ratio = $this->_attributes['crop_ratio'];
            }
            $scalable = 'false';
            if (!empty($this->_attributes['crop_scalable'])) {
                $scalable = $this->_attributes['crop_scalable'];
            }

            $maxRatio = $minRatio = null;
            if (!empty($this->_attributes['crop_min_ratio']) && !empty($this->_attributes['crop_max_ratio'])) {
                $minRatio = $this->_attributes['crop_min_ratio'];
                $maxRatio = $this->_attributes['crop_max_ratio'];
            }

            $js = $this->getElementJS(array('ratio' => $ratio, 'scalable' => $scalable, 'minRatio' => $minRatio, 'maxRatio' => $maxRatio));
        }

        if ($this->isFrozen()) {
            return $this->getFrozenHtml();
        } else {
            $class = '';
            if (isset($this->_attributes['custom']) && $this->_attributes['custom']) {
                $class = 'input-file';
        }

        return $js.$this->_getTabs().
                '<input class="'.$class.'" '.$this->_getAttrString($this->_attributes).' />';
        }
    }

    /**
     * @param string $layout
     *
     * @return string
     */
    public function getTemplate($layout)
    {
        $name = $this->getName();
        $attributes = $this->getAttributes();
        $size = $this->calculateSize();

        switch ($layout) {
            case FormValidator::LAYOUT_INLINE:
                return '
                <div class="form-group {error_class}">
                    <label {label-for} >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        {label}
                    </label>
                    {element}
                </div>';
                break;
            case FormValidator::LAYOUT_HORIZONTAL:
                if (isset($attributes['custom']) && $attributes['custom']) {
                    $template = '
                        <div class="input-file-container">
                            {element}
                            <label tabindex="0" {label-for} class="input-file-trigger">
                                <i class="fa fa-picture-o fa-lg" aria-hidden="true"></i> {label}
                            </label>
                        </div>
                        <p class="file-return"></p>
                        <script>
                            document.querySelector("html").classList.add(\'js\');
                            var fileInput  = document.querySelector( ".input-file" ),
                                button     = document.querySelector( ".input-file-trigger" ),
                                the_return = document.querySelector(".file-return");

                            button.addEventListener("keydown", function(event) {
                                if ( event.keyCode == 13 || event.keyCode == 32 ) {
                                    fileInput.focus();
                                }
                            });
                            button.addEventListener("click", function(event) {
                               fileInput.focus();
                               return false;
                            });
                            fileInput.addEventListener("change", function(event) {
                                fileName = this.value;
                                if (this.files[0]) {
                                    fileName = this.files[0].name;
                                }
                                the_return.innerHTML = fileName;
                            });
                        </script>
                    ';
                } else {
                    $template = '
                    <div id="file_'.$name.'" class="form-group {error_class}">

                        <label {label-for} class="col-sm-'.$size[0].' control-label" >
                            <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                            {label}
                        </label>
                         <div class="col-sm-'.$size[1].'">
                            {icon}
                            {element}
                            <!-- BEGIN label_2 -->
                                <p class="help-block">{label_2}</p>
                            <!-- END label_2 -->
                            <!-- BEGIN error -->
                                <span class="help-inline help-block">{error}</span>
                            <!-- END error -->
                        </div>
                        <div class="col-sm-'.$size[2].'">
                            <!-- BEGIN label_3 -->
                                {label_3}
                            <!-- END label_3 -->
                        </div>
                    </div>';
                }
                return $template;
                break;
            case FormValidator::LAYOUT_BOX_NO_LABEL:
                return '
                        <label {label-for}>{label}</label>
                        <div class="input-group">

                            {icon}
                            {element}
                        </div>';
                break;
        }
    }

}
