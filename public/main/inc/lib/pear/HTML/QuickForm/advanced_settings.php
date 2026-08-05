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
                'name' => $name,
            )
        );
        $this->_type = 'html';
    }

    /**
     * Accepts a renderer.
     *
     * @param HTML_QuickForm_Renderer $renderer Renderer object.
     * @param bool                    $required Whether the element is required.
     * @param string|null             $error    Validation error.
     *
     * @return void
     */
    public function accept(&$renderer, $required = false, $error = null)
    {
        $renderer->renderHtml($this);
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        $name = (string) $this->getAttribute('name');
        $text = $this->getAttribute('label');
        $label = is_array($text) ? $text[0] : $text;

        $showLabel = (string) $label;
        $advancedLabel = get_lang('Advanced settings');
        $hideLabel = $showLabel === $advancedLabel ? get_lang('Hide advanced settings') : $showLabel;

        $nameAttribute = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $targetAttribute = htmlspecialchars($name.'_options', ENT_QUOTES, 'UTF-8');
        $showLabelAttribute = htmlspecialchars($showLabel, ENT_QUOTES, 'UTF-8');
        $hideLabelAttribute = htmlspecialchars($hideLabel, ENT_QUOTES, 'UTF-8');
        $labelHtml = htmlspecialchars($showLabel, ENT_QUOTES, 'UTF-8');

        $toggleScript = "return (function(button){var target=document.getElementById(button.id+'_options');if(!target){return false;}var isExpanded=window.getComputedStyle(target).display!=='none';target.style.display=isExpanded?'none':'block';button.setAttribute('aria-expanded',isExpanded?'false':'true');var icon=button.querySelector('[data-advanced-settings-icon]');if(icon){icon.className=isExpanded?'mdi mdi-chevron-right':'mdi mdi-chevron-down';}var label=button.querySelector('[data-advanced-settings-label]');if(label){label.textContent=isExpanded?button.getAttribute('data-show-label'):button.getAttribute('data-hide-label');}return false;})(this);";
        $toggleScriptAttribute = htmlspecialchars($toggleScript, ENT_QUOTES, 'UTF-8');

        $html = '<div class="legacy-advanced-settings" style="margin: 1rem 0;">';

        if (is_array($text) && isset($text[1])) {
            $html .= '<span class="clearfix">'.$text[1].'</span>';
        }

        $html .= '
            <button
                id="'.$nameAttribute.'"
                type="button"
                aria-controls="'.$targetAttribute.'"
                aria-expanded="false"
                autocomplete="off"
                data-show-label="'.$showLabelAttribute.'"
                data-hide-label="'.$hideLabelAttribute.'"
                onclick="'.$toggleScriptAttribute.'"
                style="display:inline-flex;align-items:center;gap:.35rem;border:0;background:transparent;padding:0;color:#246fa8;font-weight:600;line-height:1.5;cursor:pointer;"
            >
                <em data-advanced-settings-icon class="mdi mdi-chevron-right" style="font-size:1rem;line-height:1;"></em>
                <span data-advanced-settings-label>'.$labelHtml.'</span>
            </button>
        ';

        if (is_array($text) && isset($text[2])) {
            $html .= '<div class="help-block">'.$text[2].'</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
