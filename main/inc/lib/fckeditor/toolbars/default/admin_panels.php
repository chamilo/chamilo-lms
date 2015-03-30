<?php
/* For licensing terms, see /license.txt */
/**
 * AdminPanels FCKEditor's toolbar
 * For more information: http://docs.fckeditor.net/FCKeditor_2.x/Developers_Guide/Configuration/Configuration_Options
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo
 */

// Hide/show SpellCheck buttom
if ((api_get_setting('allow_spellcheck') == 'true')) {
    $VSpellCheck = 'SpellCheck';
} else {
    $VSpellCheck = '';
}

// This is the visible toolbar set when the editor has "normal" size.
$config['ToolbarSets']['Normal'] = array(
    array('NewPage', '-', 'PasteWord'),
    array('Undo', 'Redo'),
    array('Link', 'Image', 'flvPlayer', 'Flash', 'MP3', 'mimetex'),
    '/',
    array('Bold', 'Italic', 'Underline', 'TextColor', 'BGColor'),
    array('UnorderedList', 'OrderedList', 'Rule'),
    array('JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyFull'),
    array('FontFormat', 'FontName', 'FontSize'),
    array('FitWindow')
);

// Sets whether the toolbar can be collapsed/expanded or not.
// Possible values: true , false
$config['ToolbarCanCollapse'] = true;

// Sets how the editor's toolbar should start - expanded or collapsed.
// Possible values: true , false
$config['ToolbarStartExpanded'] = false;

//This option sets the location of the toolbar.
// Possible values: 'In' , 'None' , 'Out:[TargetId]' , 'Out:[TargetWindow]([TargetId])'
//$config['ToolbarLocation'] = 'In';

// A setting for blocking copy/paste functions of the editor.
// This setting activates on leaners only. For users with other statuses there is no blocking copy/paste.
// Possible values: true , false
//$config['BlockCopyPaste'] = false;

// Here new width and height of the editor may be set.
// Possible values, examples: 300 , '250' , '100%' , ...
//$config['Width'] = '100%';
//$config['Height'] = '400';
