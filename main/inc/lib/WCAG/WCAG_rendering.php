<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+
//
// $Id: wai_rendering.php,v 1.0 2006/10/07 20:12:17 avb Exp $

include_once ('../inc/global.inc.php');
include_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

/**
* helper for WCAG rendering.
*
* @author      Patrick Vandermaesen <pvandermaesen@noctis.be>
* @version     1.0
*/
class WCAG_Rendering {
	
	function editor_header() {
		return '<div id="WCAG-editor"><div class="title">'.get_lang('WCAGEditor').'</div><div class="body">';
	}
	
	function editor_footer() {
		return '</div></div>';
	}
	
	function prepareXHTML() {
		$text = $_POST['text'];
		$text = WCAG_Rendering::text_to_HTML ( $text );
		$imageFile = $_POST['imagefile'];				
		$imageLabel = $_POST['imageLabel'];
		$link = $_POST['link'];				
		$linkLabel = $_POST['linkLabel'];
		if (strlen($linkLabel) == 0) {
			$linkLabel = $link;
		}
		$home_top='<div id="WCAG-home"><img src="'.$imageFile.'" alt="'.$imageLabel.'" />'.'<p>'.$text.'</p>';
		if (strlen($link) > 0) {
			$home_top = $home_top.'<a href="'.$link.'">'.$linkLabel.'</a>';
		}
		$home_top=$home_top."<div style=\"clear:both;\"><span></span></div></div>";
		return $home_top;
	}
	
	/**
	* this method validate the content of current request (from WCAG editor).
	* this function return the error msg.
	*/
	function request_validation() {
		$imageFile = $_POST['imagefile'];				
		$imageLabel = $_POST['imageLabel'];
		if ((strlen($imageFile) > 0) and (strlen($imageLabel) == 0)) {
			return get_lang('errorNoLabel');
		}
		return '';
	}
	
/**
* Converter Plaintext to (x)HTML
*/
function text_to_HTML ($Text)
{
		$t = $Text;
		$t = stripslashes($t);
		$t = str_replace(">", "&gt;", $t);
		$t = str_replace("<", "&lt;", $t);

		$t = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />\n", $t);
		$t = str_replace("  ", " &nbsp;", $t);
        return $t;
}

function HTML_to_text ($xhtml) {
	// convert HTML to text.
	$text = str_replace("<br />", "", $xhtml);
	$text = str_replace("<br/>", "", $text);
	$text = str_replace("&nbsp;", " ", $text);
	return $text;
}

function extract_data ($xhtml) {
	$text = $xhtml;
	if (stripos($xhtml, '<p>')) {
		$startP = stripos ($xhtml, "<p>");
		$endP =  stripos ($xhtml, "</p>");
		$text = substr ($xhtml, $startP+3, $endP-$startP-3 );
	}	
	
	// convert HTML to text.
	$text = WCAG_Rendering::HTML_to_text($text);

	$url='';
	if (stripos($xhtml, '<img')) {	
		$startImgURL = stripos ($xhtml, "src=\"");
		$endImgURL = stripos ($xhtml, "\" ");
		$url = substr ($xhtml, $startImgURL+5, $endImgURL-$startImgURL-5 );
		$subxhtml = substr ($xhtml, $endImgURL+2, $startP);
		$startImgLabel = stripos ($subxhtml, "alt=\"");
		$endImgLabel = stripos ($subxhtml, "\" ");
		$label = substr ($subxhtml, $startImgLabel+5, $endImgLabel-$startImgLabel-5 );
	}
	
	$subxhtml = substr ($xhtml, $endImgURL+2, $startP);
	$startImgLabel = stripos ($subxhtml, "alt=\"");
	$endImgLabel = stripos ($subxhtml, "\" ");
	$label = substr ($subxhtml, $startImgLabel+5, $endImgLabel-$startImgLabel-5 );
	
	$subxhtml = substr ($xhtml, $endP+2, 9999999999);
	$link="";
	$linkLabel="";
	if (stripos($subxhtml, '<a href')) {
		$startLinkURL = stripos ($subxhtml, "ref=\"");
		$endLinkURL = stripos ($subxhtml, "\">");
		$link = substr ($subxhtml, $startLinkURL+5, $endLinkURL-$startLinkURL-5 );
		
		$endLinkLabel = stripos ($subxhtml, "</a>");
		$linkLabel = substr ( $subxhtml, $endLinkURL+2, $endLinkLabel-$endLinkURL-2 );
	}
	
	$values = array("text"=>$text,
                    "imagefile"=>$url,
                    "imageLabel"=>$label,
                    "link"=>$link,
					"linkLabel"=>$linkLabel);
	return $values;
}

/**
*	add a form for set WCAG content (replace FCK)
*	@version 1.1
*/
function &prepare_admin_form( $xhtml, &$form )
{
	$values = WCAG_Rendering::extract_data($xhtml);

	if ($form == null) {
		$form = new FormValidator('waiForm');
	}
	$form->addElement('textarea','text',get_lang('WCAGContent'));
	$file =& $form->addElement('text','imagefile',get_lang('WCAGImage'));
	$form->addElement('text','imageLabel',get_lang('WCAGLabel'));
	$form->addElement('text','link',get_lang('WCAGLink'));
	$form->addElement('text','linkLabel',get_lang('WCAGLinkLabel'));
	
	$form->setDefaults($values);
	
	$renderer =& $form->defaultRenderer();
	$element_template = '<!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}<br />
			<!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->	{element}<br />';
	$renderer->setElementTemplate($element_template);
	
	return $form;
}

function &create_xhtml($xhtml) {
	$values = WCAG_Rendering::extract_data($xhtml);
	$xhtml = WCAG_Rendering::editor_header();
	$xhtml .= get_lang('WCAGContent').'<br />';
	$xhtml .= '<textarea name="text">'.$values['text'].'</textarea>';
	$xhtml .= get_lang('WCAGImage').'<br />';
	$xhtml .= '<input type="text" name="imagefile" value="'.$values['imagefile'].'"/>';
	$xhtml .= '<br />';
	$xhtml .= '<a href="#" onclick="OpenFileBrowser (\''.api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/'.'editor/plugins/ImageManagerStandalone/genericManager.php?uploadPath=/\');">'.get_lang('SelectPicture').'</a>';
	$xhtml .= '<br />';
	$xhtml .= get_lang('WCAGLabel').'<br />';
	$xhtml .= '<input type="text" name="imageLabel" value="'.$values['imageLabel'].'"/>';
	$xhtml .= get_lang('WCAGLink').'<br />';
	$xhtml .= '<input type="text" name="link" value="'.$values['link'].'"/>';
	$xhtml .= get_lang('WCAGLinkLabel').'<br />';
	$xhtml .= '<input type="text" name="linkLabel" value="'.$values['linkLabel'].'"/>';

	$xhtml .= WCAG_Rendering::editor_footer();;
	return $xhtml;
}

} // end class WAI_Rendering
?>