<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

require_once ('HTML/QuickForm.php');
require_once ('HTML/QuickForm/advmultiselect.php');


/**
 * Filter
 */
define('NO_HTML', 1);
define('STUDENT_HTML', 2);
define('TEACHER_HTML', 3);
define('STUDENT_HTML_FULLPAGE',4);
define('TEACHER_HTML_FULLPAGE',5);
/**
 * Objects of this class can be used to create/manipulate/validate user input.
 */
class FormValidator extends HTML_QuickForm
{
	var $with_progress_bar=false;
	/**
	 * Constructor
	 * @param string $form_name Name of the form
	 * @param string $method Method ('post' (default) or 'get')
	 * @param string $action Action (default is $PHP_SELF)
	 * @param string $target Form's target defaults to '_self'
	 * @param mixed $attributes (optional)Extra attributes for <form> tag
	 * @param bool $trackSubmit (optional)Whether to track if the form was
	 * submitted by adding a special hidden field (default = true)
	 */
	function FormValidator($form_name, $method = 'post', $action = '', $target = '', $attributes = null, $trackSubmit = true)
	{
		$this->HTML_QuickForm($form_name, $method,$action, $target, $attributes, $trackSubmit);
		// Load some custom elements and rules
		$dir = dirname(__FILE__).'/';	
		$this->registerElementType('html_editor', $dir.'Element/html_editor.php', 'HTML_QuickForm_html_editor');
		$this->registerElementType('datepicker', $dir.'Element/datepicker.php', 'HTML_QuickForm_datepicker');
		$this->registerElementType('datepickerdate', $dir.'Element/datepickerdate.php', 'HTML_QuickForm_datepickerdate');
		$this->registerElementType('receivers', $dir.'Element/receivers.php', 'HTML_QuickForm_receivers');
		$this->registerElementType('select_language', $dir.'Element/select_language.php', 'HTML_QuickForm_Select_Language');
		$this->registerElementType('select_theme', $dir.'Element/select_theme.php', 'HTML_QuickForm_Select_Theme');
		$this->registerElementType('style_button', $dir.'Element/style_button.php', 'HTML_QuickForm_stylebutton');
		$this->registerElementType('style_submit_button', $dir.'Element/style_submit_button.php', 'HTML_QuickForm_stylesubmitbutton');
		$this->registerElementType('style_reset_button', $dir.'Element/style_reset_button.php', 'HTML_QuickForm_styleresetbutton');
		$this->registerRule('date', null, 'HTML_QuickForm_Rule_Date', $dir.'Rule/Date.php');
		$this->registerRule('date_compare', null, 'HTML_QuickForm_Rule_DateCompare', $dir.'Rule/DateCompare.php');
		$this->registerRule('html',null,'HTML_QuickForm_Rule_HTML',$dir.'Rule/HTML.php');
		$this->registerRule('username_available',null,'HTML_QuickForm_Rule_UsernameAvailable',$dir.'Rule/UsernameAvailable.php');
		$this->registerRule('username',null,'HTML_QuickForm_Rule_Username',$dir.'Rule/Username.php');
		$this->registerRule('filetype',null,'HTML_QuickForm_Rule_Filetype',$dir.'Rule/Filetype.php');
		$this->registerRule('multiple_required','required','HTML_QuickForm_Rule_MultipleRequired',$dir.'Rule/MultipleRequired.php');

		// Modify the default templates
		$renderer = & $this->defaultRenderer();
		$form_template = <<<EOT

<form {attributes}>
{content}
	<div class="clear">
		&nbsp;
	</div>
</form>

EOT;
		$renderer->setFormTemplate($form_template);
		$element_template = <<<EOT
	<div class="row">
		<div class="label">
			<!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}
		</div>
		<div class="formw">
			<!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->	{element}
		</div>
	</div>

EOT;
		$renderer->setElementTemplate($element_template);
		$header_template = <<<EOT
	<div class="row">
		<div class="form_header">{header}</div>
	</div>

EOT;
		$renderer->setHeaderTemplate($header_template);
		HTML_QuickForm :: setRequiredNote('<span class="form_required">*</span> <small>'.get_lang('ThisFieldIsRequired').'</small>');
		$required_note_template = <<<EOT
	<div class="row">
		<div class="label"></div>
		<div class="formw">{requiredNote}</div>
	</div>
EOT;
		$renderer->setRequiredNoteTemplate($required_note_template);
	}

	/**
	 * Add a textfield to the form.
	 * A trim-filter is attached to the field.
	 * @param string $label The label for the form-element
	 * @param string $name The element name
	 * @param boolean $required Is the form-element required (default=true)
	 * @param array $attributes Optional list of attributes for the form-element
	 */
	function add_textfield( $name, $label,$required = true, $attributes = array())
	{
		$this->addElement('text',$name,$label,$attributes);
		$this->applyFilter($name,'trim');
		if($required)
		{
			$this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
		}
	}

	/**
	 * Add a HTML-editor to the form to fill in a title.
	 * A trim-filter is attached to the field.
	 * A HTML-filter is attached to the field (cleans HTML)
	 * A rule is attached to check for unwanted HTML
	 * @param string $label The label for the form-element
	 * @param string $name The element name
	 * @param boolean $required Is the form-element required (default=true)
	 * @param boolean $full_page When it is true, the editor loads completed html code for a full page.
	 * @param array $editor_config	Optional configuration settings for the online editor.
	 */
	function add_html_editor($name, $label, $required = true, $full_page = false, $config = null)
	{
		$this->addElement('html_editor', $name, $label, 'rows="15" cols="80"', $config);
		$this->applyFilter($name,'trim');
		$html_type = STUDENT_HTML;
		if(!empty($_SESSION['status']))
		{
			$html_type = $_SESSION['status'] == COURSEMANAGER ? TEACHER_HTML : STUDENT_HTML;
		}
		if($full_page)
		{
			$html_type = $_SESSION['status'] == COURSEMANAGER ? TEACHER_HTML_FULLPAGE : STUDENT_HTML_FULLPAGE;
			//First *filter* the HTML (markup, indenting, ...)
			//$this->applyFilter($name,'html_filter_teacher_fullpage');
		}
		else
		{
			//First *filter* the HTML (markup, indenting, ...)
			//$this->applyFilter($name,'html_filter_teacher');
		}
		if($required)
		{
			$this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
		}
		if($full_page)
		{
			$el = $this->getElement($name);
			$el->fullPage = true;
		}
		//Add rule to check not-allowed HTML
		//$this->addRule($name,get_lang('SomeHTMLNotAllowed'),'html',$html_type);
	}

	/**
	 * Add a datepicker element to the form
	 * A rule is added to check if the date is a valid one
	 * @param string $label The label for the form-element
	 * @param string $name The element name
	 */
	function add_datepicker($name,$label)
	{
		$this->addElement('datepicker', $name, $label, array ('form_name' => $this->getAttribute('name')));
		$this->addRule($name, get_lang('InvalidDate'), 'date');
	}

	/**
	 * Add a datepickerdate element to the form
	 * A rule is added to check if the date is a valid one
	 * @param string $label The label for the form-element
	 * @param string $name The element name
	 */
	function add_datepickerdate($name,$label)
	{
		$this->addElement('datepickerdate', $name, $label, array ('form_name' => $this->getAttribute('name')));
		$this->addRule($name, get_lang('InvalidDate'), 'date');
	}

	/**
	 * Add a timewindow element to the form.
	 * 2 datepicker elements are added and a rule to check if the first date is
	 * before the second one.
	 * @param string $label The label for the form-element
	 * @param string $name The element name
	 */
	function add_timewindow($name_1, $name_2,  $label_1,$label_2)
	{
		$this->add_datepicker($name_1, $label_1);
		$this->add_datepicker( $name_2, $label_2);
		$this->addRule(array ($name_1, $name_2), get_lang('StartDateShouldBeBeforeEndDate'), 'date_compare', 'lte');
	}
	/**
	 * Add a button to the form to add resources.
	 */
	function add_resource_button()
	{
		$group[] = $this->createElement('static','add_resource_img',null,'<img src="'.api_get_path(WEB_CODE_PATH).'img/attachment.gif" alt="'.get_lang('Attachment').'"/>');
		$group[] = $this->createElement('submit','add_resource',get_lang('Attachment'),'class="link_alike"');
		$this->addGroup($group);
	}
	/**
	 * Adds a progress bar to the form.
	 *
	 * Once the user submits the form, a progress bar (animated gif) is
	 * displayed. The progress bar will disappear once the page has been
	 * reloaded.
	 *
	 * @param int $delay The number of seconds between the moment the user
	 * submits the form and the start of the progress bar.
	 */
	function add_progress_bar($delay = 2, $label='')
	{
		if(empty($label))
		{
			$label = get_lang('PleaseStandBy');
		}
		$this->with_progress_bar = true;
		$this->updateAttributes("onsubmit=\"myUpload.start('dynamic_div','".api_get_path(WEB_CODE_PATH)."img/progress_bar.gif','".$label."','".$this->getAttribute('id')."')\"");
		$this->addElement('html','<script language="javascript" src="'.api_get_path(WEB_CODE_PATH).'inc/lib/javascript/upload.js" type="text/javascript"></script>');
		$this->addElement('html','<script type="text/javascript">var myUpload = new upload('.(abs(intval($delay))*1000).');</script>');
	}
	
	
	/**
	 * Use the new functions (php 5.2) allowing to display a real upload progress.
	 * @param upload_id the value of the field UPLOAD_IDENTIFIER
	 * @param elementAfter the first element of the form (to place at first UPLOAD_IDENTIFIER
	 * @param delay the frequency of the xajax call
	 * @param waitAfterUpload
	 */
	function add_real_progress_bar($upload_id, $elementAfter, $delay=2,$waitAfterUpload=false)
	{
		if(!function_exists('uploadprogress_get_info'))
		{
			$this -> add_progress_bar($delay);
			return;
		}
		
		if(!class_exists('xajax')) {
			require_once api_get_path(LIBRARY_PATH).'xajax/xajax.inc.php';		
		}
		
		$xajax_upload = new xajax(api_get_path(WEB_CODE_PATH).'inc/lib/upload.xajax.php');
		
		$xajax_upload -> registerFunction ('updateProgress');
		
	
		// IMPORTANT : must be the first element of the form
		$el = $this->insertElementBefore(FormValidator::createElement('html','<input type="hidden" name="UPLOAD_IDENTIFIER" value="'.$upload_id.'" />'), $elementAfter);
		
		$this->addElement('html','<br />');
		
		// add the div where the progress bar will be displayed
		$this->addElement('html','
		<div id="dynamic_div_container" style="display:none">
			<div id="dynamic_div_label">'.get_lang('UploadFile').'</div>
			<div id="dynamic_div_frame" style="width:214px; height:12px; border:1px solid grey; background-image:url('.api_get_path(REL_PATH).'main/img/real_upload_frame.gif);">
				<div id="dynamic_div_filled" style="width:0%;height:100%;background-image:url('.api_get_path(REL_PATH).'main/img/real_upload_step.gif);background-repeat:repeat-x;background-position:center;"></div>
			</div>
		</div>');
		
		if($waitAfterUpload){
			$this->addElement('html','
			<div id="dynamic_div_waiter_container" style="display:none">
				<div id="dynamic_div_waiter_label">
					'.get_lang('SlideshowConversion').'
				</div>			
				<div id="dynamic_div_waiter_frame">
					<img src="'.api_get_path(WEB_CODE_PATH).'img/real_upload_frame.gif" />
				</div>		
			</div>

		');
		}
		
		// get the xajax code
		$this->addElement('html',$xajax_upload -> getJavascript(api_get_path(WEB_CODE_PATH).'inc/lib/xajax'));
		
		// get the upload code
		$this->addElement('html','<script language="javascript" src="'.api_get_path(WEB_CODE_PATH).'inc/lib/javascript/upload.js" type="text/javascript"></script>');
		$this->addElement('html','<script type="text/javascript">var myUpload = new upload('.(abs(intval($delay))*1000).');</script>');
		
		if(!$waitAfterUpload)
		{
			$waitAfterUpload = 0;
		}
		// add the upload event
		$this->updateAttributes("onsubmit=\"myUpload.startRealUpload('dynamic_div','".$upload_id."','".$this->getAttribute('id')."',".$waitAfterUpload.")\"");
		
		
	}
	
	/**
	 * This function avoid to change directly QuickForm class.
	 * When we use it, the element is threated as 'required' to be dealt during validation
	 * @param array $element the array of elements
	 * @param string $message the message displayed
	 */
	function add_multiple_required_rule($elements, $message)
	{
		$this->_required[] = $elements[0];
		$this -> addRule ($elements , $message , 'multiple_required');		
	}
	
	/**
	 * Display the form.
	 * If an element in the form didn't validate, an error message is showed
	 * asking the user to complete the form.
	 */
	function display()
	{
		echo $this->return_form();
	}
	/**
	 * Returns the HTML code of the form.
	 * If an element in the form didn't validate, an error message is showed
	 * asking the user to complete the form.
	 *
	 * @return string $return_value HTML code of the form
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @version Dokeos 1.8, august 2006
	 */
	function return_form()
	{
		$error = false;
		foreach($this->_elements as $index => $element)
		{
			if( !is_null(parent::getElementError($element->getName())) )
			{
				$error = true;
				break;
			}
		}
		if($error)
		{
			Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'));
		}
		$return_value = parent::toHtml();
		// Add the div which will hold the progress bar
		if(isset($this->with_progress_bar) && $this->with_progress_bar)
		{
			$return_value .= '<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;"></div>';
		}
		return $return_value;
	}
}


/**
 * Clean HTML
 * @param string HTML to clean
 * @param int $mode
 * @return string The cleaned HTML
 */
function html_filter($html, $mode = NO_HTML)
{
	require_once(dirname(__FILE__).'/Rule/HTML.php');
	$allowed_tags = HTML_QuickForm_Rule_HTML::get_allowed_tags($mode);
	$cleaned_html = kses($html,$allowed_tags);
	return $cleaned_html;
}
function html_filter_teacher($html)
{
	return html_filter($html,TEACHER_HTML);
}
function html_filter_student($html)
{
	return html_filter($html,STUDENT_HTML);
}
function html_filter_teacher_fullpage($html)
{
	return html_filter($html,TEACHER_HTML_FULLPAGE);
}
function html_filter_student_fullpage($html)
{
	return html_filter($html,STUDENT_HTML_FULLPAGE);
}
?>
