<?php

/**
 * Class HTML_QuickForm_advanced_settings
 */
class HTML_QuickForm_advanced_settings extends HTML_QuickForm_static
{
    /**
    * Class constructor
    *
    * @param string $text   raw HTML to add
    * @access public
    * @return void
    */
    public function __construct($name = '', $label = '')
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
    * @param HTML_QuickForm_Renderer    renderer object (only works with Default renderer!)
    * @access public
    * @return void
    */
    function accept(&$renderer, $required = false, $error = null)
    {
        $renderer->renderHtml($this);
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        $name = $this->getAttribute('name');
        $text = $this->getAttribute('label');

        return '<div class="form-group">
                    <label class="col-sm-2 control-label"></label>
                    <div class="col-sm-10">
                        <a id="'.$name.'" class="btn btn-default advanced_options" href="#">
                        <em class="fa fa-bars"></em>  '.$text.'
                        </a>
                    </div>
                 </div>';
    }
}
