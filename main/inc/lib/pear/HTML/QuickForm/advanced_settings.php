<?php
/**
 * HTML class for static data
 * @example  $form->addElement('advanced_settings', '<a href="#">advanced settings</a>');
 */

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
class HTML_QuickForm_advanced_settings extends HTML_QuickForm_static
{
    // {{{ constructor

   /**
    * Class constructor
    *
    * @param string $text   raw HTML to add
    * @access public
    * @return void
    */
    public function HTML_QuickForm_advanced_settings($text = null)
    {
        $this->HTML_QuickForm_static(null, null, $text);
        $this->_type = 'html';
    }

   /**
    * Accepts a renderer
    *
    * @param HTML_QuickForm_Renderer    renderer object (only works with Default renderer!)
    * @access public
    * @return void
    */
    function accept(&$renderer, $required=false, $error=null)
    {
        $renderer->renderHtml($this);
    }

    public function toHtml()
    {
         return '<div class="form-group ">
                    <label class="control-label col-sm-2"></label>
                    <div class="col-sm-10">
                    <div class="form-control-static">
                    '.HTML_QuickForm_static::toHtml().'
                    </div>
                    </div>
                 </div>

                ';
    }
}
