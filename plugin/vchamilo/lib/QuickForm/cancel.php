<?php
require_once('HTML/QuickForm/submit.php');

/**
 * HTML class for a submit type element
 *
 * @author       Valery Fremaux
 * @access       public
 */
class HTML_QuickForm_cancel extends HTML_QuickForm_button
{
    // {{{ constructor

    /**
     * Class constructor
     *
     * @since     1.0
     * @access    public
     * @return    void
     */
    function HTML_QuickForm_cancel($elementName=null, $value=null, $escapeurl=null, $attributes = null)
    {
        if ($elementName==null){
            $elementName = 'cancel';
        }
        if ($value == null){
            $value = get_lang('cancel');
        }

        parent::HTML_QuickForm_button($elementName, $value, $attributes);

        if ($escapeurl!=null){
            $this->updateAttributes(array('onclick'=>'window.location.href = "'.$escapeurl.'"; return true;'));
        }
    } //end constructor
    
    function onQuickFormEvent($event, $arg, &$caller){
        
        $value = $arg[0];
        $escapeurl = $arg[1];

        if ($value != null){
            $this->updateAttributes(array('value'=> $value));
        }

        if ($escapeurl != null){
            $this->updateAttributes(array('onclick'=>'window.location.href = "'.$escapeurl.'"; return true;'));
        }  
        return true;      
    }

    function getFrozenHtml(){
        return HTML_QuickForm_submit::getFrozenHtml();
    }
    
    function freeze(){
        return HTML_QuickForm_submit::freeze();
    }
    // }}}
} //end class MoodleQuickForm_cancel
