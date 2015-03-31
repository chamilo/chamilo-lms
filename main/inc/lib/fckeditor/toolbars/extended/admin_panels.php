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

// This is the visible toolbar set when the editor is maximized.
// If it has not been defined, then the toolbar set for the "normal" size is used.
$config['ToolbarSets']['Maximized'] = array(
    array('NewPage', '-', 'Preview', 'Print'),
    array('Cut', 'Copy', 'Paste', 'PasteText', 'PasteWord'),
    array('Undo', 'Redo', '-', 'SelectAll', 'Find', '-', 'RemoveFormat'),
    array('Link', 'Unlink', 'Anchor', 'Glossary'),
    array('Image', 'imgmapPopup', 'flvPlayer', 'EmbedMovies', 'YouTube', 'Flash', 'MP3', 'googlemaps', 'Smiley', 'SpecialChar', 'insertHtml', 'mimetex', 'asciimath', 'asciisvg', 'fckeditor_wiris_openFormulaEditor', 'fckeditor_wiris_openCAS'),
    '/',
    array('TableOC', 'Table', 'TableInsertRowAfter', 'TableDeleteRows', 'TableInsertColumnAfter', 'TableDeleteColumns', 'TableInsertCellAfter', 'TableDeleteCells', 'TableMergeCells', 'TableHorizontalSplitCell', 'TableVerticalSplitCell', 'TableCellProp', '-', 'CreateDiv'),
    array('UnorderedList', 'OrderedList', 'Rule', '-', 'Outdent', 'Indent', 'Blockquote'),
    array('JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyFull'),
    array('Bold', 'Italic', 'Underline', 'StrikeThrough', '-', 'Subscript', 'Superscript', '-', 'TextColor', 'BGColor'),
    array($VSpellCheck),
    array('Style', 'FontFormat', 'FontName', 'FontSize'),
    array('PageBreak', 'ShowBlocks', 'Source'),
    array('FitWindow')
);