<?php
// Chamilo LMS
// See license terms in chamilo/documentation/license.txt

// Training tools
// Documents

// For more information: http://docs.fckeditor.net/FCKeditor_2.x/Developers_Guide/Configuration/Configuration_Options

//NOTE: Does not include Replace because it is redundant, being in the same tab to Find.
//TODO: DocProps  don't run ok here.

// Hide/show SpellCheck buttom
if ((api_get_setting('allow_spellcheck') == 'true')) {
    $VSpellCheck='SpellCheck';
}
else{
    $VSpellCheck='';
}

// This is the visible toolbar set when the editor has "normal" size.
$config['ToolbarSets']['Normal'] = array(
    array('Save','NewPage','Templates','-','PasteWord'),
    array('Undo','Redo'),
    array('Link','Image','flvPlayer','Flash','YouTube','MP3','TableOC','mimetex','asciimath','asciisvg'),
    array('UnorderedList','OrderedList','Rule'),
    array('JustifyLeft','JustifyCenter','JustifyFull'),
	array('Abbr'),
    array('FontFormat','FontName','FontSize','Bold','Italic','Underline','TextColor','BGColor','Source'),
    array('FitWindow')
);

// This is the visible toolbar set when the editor is maximized.
// If it has not been defined, then the toolbar set for the "normal" size is used.
$config['ToolbarSets']['Maximized'] = array(
    array('Save','NewPage','Templates','-','Preview','Print'),
    array('Cut','Copy','Paste','PasteText','PasteWord'),
    array('Undo','Redo','-','SelectAll','Find','-','RemoveFormat'),
    array('Link','Unlink','Anchor','Glossary'),
    array('Image','imgmapPopup','flvPlayer','Flash','YouTube','EmbedMovies','MP3','googlemaps','Smiley','SpecialChar','insertHtml','mimetex','asciimath','asciisvg','fckeditor_wiris_openFormulaEditor','fckeditor_wiris_openCAS'),
'/',
    array('TableOC','Table','TableInsertRowAfter','TableDeleteRows','TableInsertColumnAfter','TableDeleteColumns','TableInsertCellAfter','TableDeleteCells','TableMergeCells','TableHorizontalSplitCell','TableVerticalSplitCell','TableCellProp','-','CreateDiv'),
    array('UnorderedList','OrderedList','Rule','-','Outdent','Indent','Blockquote'),
    array('JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'),
    array('Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript','-','TextColor','BGColor'),
	array('Abbr'),
    array($VSpellCheck),
    array('Style','FontFormat','FontName','FontSize'),
    array('PageBreak','ShowBlocks','Source'),
    array('FitWindow')

);

// Sets whether the toolbar can be collapsed/expanded or not.
// Possible values: true , false
//$config['ToolbarCanCollapse'] = true;

// Sets how the editor's toolbar should start - expanded or collapsed.
// Possible values: true , false
//$config['ToolbarStartExpanded'] = true;

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
//$config['Height'] = '600';
