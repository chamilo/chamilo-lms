<?php
/**
 * HTML class for static data
 * @example  $form->addElement('advanced_settings', '<a href="#">advanced settings</a>');
 */
//require_once 'HTML/QuickForm/static.php';

/**
 * A pseudo-element used for adding raw HTML to form
 *
 * Intended for use with the default renderer only, template-based
 * ones may (and probably will) completely ignore this
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 3.2.11
 * @since       3.0
 * @deprecated  Please use the templates rather than add raw HTML via this element
 */
class HTML_QuickForm_advanced_settings extends HTML_QuickForm_element
{
    /**
     * @param string $name
     * @param string $label
     * @param array $attributes
     */
    function HTML_QuickForm_advanced_settings($name = null, $label = null)
    {
        if (empty($label)) {
            $label = get_lang('AdvancedParameters');
        }
        $this->updateAttributes(
            array(
                'label' => $label,
                'name' => $name
            )
        );
        $this->_type = 'html';
    }

    /**
     * Accepts a renderer
     *
     * @param HTML_QuickForm_Renderer renderer object (only works with Default renderer!)
     * @access public
     * @return void
     */
    function accept(&$renderer, $required = false, $error = null)
    {
        $renderer->renderHtml($this);
    }

    function toHtml()
    {
        $name = $this->getAttribute('name');
        $text = $this->getAttribute('label');

        return '<div class="form-group">
                    <label class="col-sm-2 control-label"></label>
                    <div class="col-sm-10">
                        <a id="'.$name.'" class="btn btn-default advanced_options" href="#">
                        <i class="fa fa-bars"></i>  '.$text.'
                        </a>
                    </div>
                 </div>';
    }
}
