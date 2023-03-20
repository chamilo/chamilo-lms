<?php

/**
 * Class HTML_QuickForm_advanced_settings
 */
class HTML_QuickForm_advanced_settings extends HTML_QuickForm_static
{
    public function __construct($name = '', $label = '')
    {
        if (empty($label)) {
            $label = get_lang('Advanced settings');
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
        $label = is_array($text) ? $text[0] : $text;

        $html = '<div class="mb-6">';
        if (is_array($text) && isset($text[1])) {
            $html .= '<span class="clearfix">'.$text[1].'</span>';
        }

        $html .= '
            <div class="">
                <button id="'.$name.'" type="button" class="btn btn--secondary advanced_options"
                        data-toggle="button" aria-pressed="false" autocomplete="off">
                    <em class="mr-1 mdi mdi-cog"></em> '.$label.'
                </button>
            </div>
        ';

        if (is_array($text) && isset($text[2])) {
            $html .= '<div class="help-block">'.$text[2].'</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
