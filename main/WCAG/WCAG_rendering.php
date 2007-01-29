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
	
	
/**
* Converter Plaintext to (x)HTML
*/
function text2HTML ($Text)
{
		$t = $Text;
		$t = stripslashes($t);
		$t = htmlentities($t);

		$t = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />\n", $t);
		$t = str_replace("  ", " &nbsp;", $t);
        return $t;
}

/**
*	add a form for set WCAG content (replace FCK)
*	@version 1.1
*/
function &prepare_admin_form( $xhtml )
{
	$startP = stripos ($xhtml, "<p>");
	$endP =  stripos ($xhtml, "</p>");	
	$text = substr ($xhtml, $startP+3, $endP-$startP-3 );
	// convert HTML to text.
	$text = str_replace("<br />", "", $text);
	$text = str_replace("&nbsp;", " ", $text);
	
	$startImgURL = stripos ($xhtml, "src=\"");
	$endImgURL = stripos ($xhtml, "\" ");
	$url = substr ($xhtml, $startImgURL+5, $endImgURL-$startImgURL-5 );
	
	$subxhtml = substr ($xhtml, $endImgURL+2, $startP);
	$startImgLabel = stripos ($subxhtml, "alt=\"");
	$endImgLabel = stripos ($subxhtml, "\" ");
	$label = substr ($subxhtml, $startImgLabel+5, $endImgLabel-$startImgLabel-5 );
	
	$subxhtml = substr ($xhtml, $endP+2, 9999999999);
	$startLinkURL = stripos ($subxhtml, "ref=\"");
	$endLinkURL = stripos ($subxhtml, "\">");
	$link = substr ($subxhtml, $startLinkURL+5, $endLinkURL-$startLinkURL-5 );
	
	$endLinkLabel = stripos ($subxhtml, "</a>");
	$linkLabel = substr ( $subxhtml, $endLinkURL+2, $endLinkLabel-$endLinkURL-2 );
	
	$values = array("text"=>$text,
                    "imagefile"=>$url,
                    "imageLabel"=>$label,
                    "link"=>$link,
					"linkLabel"=>$linkLabel);

	$form = new FormValidator('waiForm');
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

} // end class WAI_Renderin
?>