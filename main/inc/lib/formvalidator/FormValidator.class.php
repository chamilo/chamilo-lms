<?php
/* For licensing terms, see /license.txt */

/**
 * Objects of this class can be used to create/manipulate/validate user input.
 */
class FormValidator extends HTML_QuickForm
{
    public $with_progress_bar = false;

    /**
     * Constructor
     * @param string $name					Name of the form
     * @param string $method (optional			Method ('post' (default) or 'get')
     * @param string $action (optional			Action (default is $PHP_SELF)
     * @param string $target (optional			Form's target defaults to '_self'
     * @param mixed $attributes (optional)		Extra attributes for <form> tag
     * @param bool $trackSubmit (optional)		Whether to track if the form was
     * submitted by adding a special hidden field (default = true)
     */
    public function __construct(
        $name,
        $method = 'post',
        $action = '',
        $target = '',
        $attributes = null,
        $trackSubmit = true
    ) {
        // Default form class.
        if (is_array($attributes) && !isset($attributes['class']) || empty($attributes)) {
            $attributes['class'] = 'form-horizontal';
        }

        parent::__construct($name, $method, $action, $target, $attributes, $trackSubmit);

        // Load some custom elements and rules
        $dir = api_get_path(LIBRARY_PATH) . 'formvalidator/';

        $this->registerRule('date', null, 'HTML_QuickForm_Rule_Date', $dir . 'Rule/Date.php');
        $this->registerRule('datetime', null, 'DateTimeRule', $dir . 'Rule/DateTimeRule.php');
        $this->registerRule('date_compare', null, 'HTML_QuickForm_Rule_DateCompare', $dir . 'Rule/DateCompare.php');
        $this->registerRule('html', null, 'HTML_QuickForm_Rule_HTML', $dir . 'Rule/HTML.php');
        $this->registerRule('username_available', null, 'HTML_QuickForm_Rule_UsernameAvailable', $dir . 'Rule/UsernameAvailable.php');
        $this->registerRule('username', null, 'HTML_QuickForm_Rule_Username', $dir . 'Rule/Username.php');
        $this->registerRule('filetype', null, 'HTML_QuickForm_Rule_Filetype', $dir . 'Rule/Filetype.php');
        $this->registerRule('multiple_required', 'required', 'HTML_QuickForm_Rule_MultipleRequired', $dir . 'Rule/MultipleRequired.php');
        $this->registerRule('url', null, 'HTML_QuickForm_Rule_Url', $dir . 'Rule/Url.php');
        $this->registerRule('mobile_phone_number', null, 'HTML_QuickForm_Rule_Mobile_Phone_Number', $dir . 'Rule/MobilePhoneNumber.php');
        $this->registerRule('compare_fields', null, 'HTML_QuickForm_Compare_Fields', $dir . 'Rule/CompareFields.php');
        $this->registerRule('CAPTCHA', 'rule', 'HTML_QuickForm_Rule_CAPTCHA', 'HTML/QuickForm/Rule/CAPTCHA.php');

        // Modify the default templates
        $renderer = & $this->defaultRenderer();

        // Form template
        $formTemplate = $this->getFormTemplate();
        $renderer->setFormTemplate($formTemplate);

        // Element template
        if (isset($attributes['class']) && $attributes['class'] == 'form-inline') {
            $elementTemplate = ' {label}  {element} ';
            $renderer->setElementTemplate($elementTemplate);
        } elseif (isset($attributes['class']) && $attributes['class'] == 'form-search') {
            $elementTemplate = ' {label}  {element} ';
            $renderer->setElementTemplate($elementTemplate);
        } else {
            $renderer->setElementTemplate($this->getElementTemplate());

            // Display a gray div in the buttons
            $templateSimple = '<div class="form-actions">{label} {element}</div>';
            $renderer->setElementTemplate($templateSimple, 'submit_in_actions');

            //Display a gray div in the buttons + makes the button available when scrolling
            $templateBottom = '<div class="form-actions bottom_actions bg-form">{label} {element}</div>';
            $renderer->setElementTemplate($templateBottom, 'submit_fixed_in_bottom');

            //When you want to group buttons use something like this
            /* $group = array();
              $group[] = $form->createElement('button', 'mark_all', get_lang('MarkAll'));
              $group[] = $form->createElement('button', 'unmark_all', get_lang('UnmarkAll'));
              $form->addGroup($group, 'buttons_in_action');
             */
            $renderer->setElementTemplate($templateSimple, 'buttons_in_action');

            $templateSimpleRight = '<div class="form-actions"> <div class="pull-right">{label} {element}</div></div>';
            $renderer->setElementTemplate($templateSimpleRight, 'buttons_in_action_right');
        }

        //Set Header template
        $renderer->setHeaderTemplate('<legend>{header}</legend>');

        //Set required field template
        HTML_QuickForm::setRequiredNote('<span class="form_required">*</span> <small>' . get_lang('ThisFieldIsRequired') . '</small>');
        $noteTemplate = <<<EOT
	<div class="control-group">
		<div class="controls">{requiredNote}</div>
	</div>
EOT;
        $renderer->setRequiredNoteTemplate($noteTemplate);
    }

    /**
     * @return string
     */
    public function getFormTemplate()
    {
        return '<form{attributes}>
        <fieldset>
            {content}
            <div class="clear"></div>
        </fieldset>
        {hidden}
        </form>';
    }

    /**
     * @return string
     */
    public function getElementTemplate()
    {
        return '
            <div class="form-group {error_class}">
                <label {label-for} class="col-sm-2 control-label" >
                    <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                    {label}
                </label>
                <div class="col-sm-10">
                    {element}

                    <!-- BEGIN label_3 -->
                        {label_3}
                    <!-- END label_3 -->

                    <!-- BEGIN label_2 -->
                        <p class="help-block">{label_2}</p>
                    <!-- END label_2 -->

                    <!-- BEGIN error -->
                        <span class="help-inline">{error}</span>
                    <!-- END error -->
                </div>
            </div>';
    }

    /**
     * Adds a text field to the form.
     * A trim-filter is attached to the field.
     * @param string $label					The label for the form-element
     * @param string $name					The element name
     * @param bool   $required	(optional)	Is the form-element required (default=true)
     * @param array  $attributes (optional)	List of attributes for the form-element
     */
    public function addText($name, $label, $required = true, $attributes = array())
    {
        $this->addElement('text', $name, $label, $attributes);
        $this->applyFilter($name, 'trim');
        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    /**
     * The "date_range_picker" element creates 2 hidden fields
     * "elementName" + "_start"  and "elementName" + "_end"
     * For example if the name is "range", you will have 2 new fields
     * when executing $form->getSubmitValues()
     * "range_start" and "range_end"
     *
     * @param string $name
     * @param string $label
     * @param bool   $required
     * @param array  $attributes
     */
    public function addDateRangePicker($name, $label, $required = true, $attributes = array())
    {
        $this->addElement('date_range_picker', $name, $label, $attributes);
        $this->addElement('hidden', $name.'_start');
        $this->addElement('hidden', $name.'_end');

        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addHidden($name, $value)
    {
        $this->addElement('hidden', $name, $value);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array  $attributes
     *
     * @return HTML_QuickForm_textarea
     */
    public function addTextarea($name, $label, $attributes = array())
    {
        return $this->addElement('textarea', $name, $label, $attributes);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $icon font-awesome
     * @param string $style default|primary|success|info|warning|danger|link
     * @param string $size large|default|small|extra-small
     * @param string $class Example plus is transformed to icon fa fa-plus
     * @param array  $attributes
     *
     * @return HTML_QuickForm_button
     */
    public function addButton($name, $label, $icon = 'check', $style = 'default', $size = 'default', $class = 'btn', $attributes = array())
    {
        return $this->addElement('button', $name, $label, $icon, $style, $size, $class, $attributes);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $text
     * @param array  $attributes
     *
     * @return HTML_QuickForm_checkbox
     */
    public function addCheckBox($name, $label, $text = '', $attributes = array())
    {
        return $this->addElement('checkbox', $name, $label, $text, $attributes);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array  $options
     * @param array  $attributes
     *
     * @return HTML_QuickForm_group
     */
    public function addCheckBoxGroup($name, $label, $options = array(), $attributes = array())
    {
        $group = array();
        foreach ($options as $value => $text) {
            $attributes['value'] = $value;
            $group[] = $this->createElement('checkbox', null, null, $text, $attributes);
        }

        return $this->addGroup($group, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array  $options
     * @param array  $attributes
     *
     * @return HTML_QuickForm_radio
     */
    public function addRadio($name, $label, $options = array(), $attributes = array())
    {
        $group = array();
        foreach ($options as $key => $value) {
            $group[] = $this->createElement('radio', null, null, $value, $key, $attributes);
        }

        return $this->addGroup($group, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $options
     * @param array  $attributes
     *
     * @return HTML_QuickForm_select
     */
    public function addSelect($name, $label, $options = '', $attributes = array())
    {
        return $this->addElement('select', $name, $label, $options, $attributes);
    }

    /**
     * @param string $label
     * @param string $text
     *
     * @return HTML_QuickForm_label
     */
    public function addLabel($label, $text)
    {
        return $this->addElement('label', $label, $text);
    }

    /**
     * @param string $text
     */
    public function addHeader($text)
    {
        $this->addElement('header', $text);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array  $attributes
     */
    public function add_file($name, $label, $attributes = array())
    {
        $this->addElement('file', $name, $label, $attributes);
    }

    /**
     * @param string $snippet
     */
    public function addHtml($snippet)
    {
        $this->addElement('html', $snippet);
    }

    /**
     * Adds a HTML-editor to the form
     * @param string $name
     * @param string $label				 The label for the form-element
     * @param bool   $required	(optional) Is the form-element required (default=true)
     * @param bool   $fullPage (optional) When it is true, the editor loads completed html code for a full page.
     * @param array  $config (optional)	Configuration settings for the online editor.
     */
    public function addHtmlEditor($name, $label, $required = true, $fullPage = false, $config = array())
    {
        $this->addElement('html_editor', $name, $label, 'rows="15" cols="80"', $config);
        $this->applyFilter($name, 'trim');
        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        /** @var HtmlEditor $element */
        $element = $this->getElement($name);

        if ($fullPage) {
            $config['FullPage'] = true;
        }

        if ($element->editor) {
            $element->editor->processConfig($config);
        }
    }

    /**
     * Adds a progress bar to the form.
     *
     * Once the user submits the form, a progress bar (animated gif) is
     * displayed. The progress bar will disappear once the page has been
     * reloaded.
     *
     * @param int $delay (optional)	 The number of seconds between the moment the user
     * @param string $label (optional)	Custom label to be shown
     * submits the form and the start of the progress bar.
     */
    public function add_progress_bar($delay = 2, $label = '')
    {
        if (empty($label)) {
            $label = get_lang('PleaseStandBy');
        }
        $this->with_progress_bar = true;
        $this->updateAttributes("onsubmit=\"javascript: myUpload.start('dynamic_div','" . api_get_path(WEB_IMG_PATH) . "progress_bar.gif','" . $label . "','" . $this->getAttribute('id') . "')\"");
        $this->addElement('html', '<script language="javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/upload.js" type="text/javascript"></script>');
        $this->addElement('html', '<script type="text/javascript">var myUpload = new upload(' . (abs(intval($delay)) * 1000) . ');</script>');
    }

    /**
     * Uses new functions (php 5.2) for displaying real upload progress.
     * @param string $upload_id							The value of the field UPLOAD_IDENTIFIER, the second parameter (XXX) of the $form->addElement('file', XXX) sentence
     * @param string $element_after						The first element of the form (to place at first UPLOAD_IDENTIFIER)
     * @param int $delay (optional)						The frequency of the xajax call
     * @param bool $wait_after_upload (optional)
     */
    public function add_real_progress_bar($upload_id, $element_after, $delay = 2, $wait_after_upload = false)
    {
        if (!function_exists('uploadprogress_get_info')) {
            $this->add_progress_bar($delay);
            return;
        }

        $xajax_upload = new xajax(api_get_path(WEB_LIBRARY_PATH) . 'upload.xajax.php');

        $xajax_upload->registerFunction('updateProgress');


        // IMPORTANT : must be the first element of the form
        $el = $this->insertElementBefore(FormValidator::createElement('html', '<input type="hidden" name="UPLOAD_IDENTIFIER" value="' . $upload_id . '" />'), $element_after);

        $this->addElement('html', '<br />');

        // Add div-element where the progress bar is to be displayed
        $this->addElement('html', '
                		<div id="dynamic_div_container" style="display:none">
                			<div id="dynamic_div_label">' . get_lang('UploadFile') . '</div>
                			<div id="dynamic_div_frame" style="width:214px; height:12px; border:1px solid grey; background-image:url(' . api_get_path(WEB_IMG_PATH) . 'real_upload_frame.gif);">
                				<div id="dynamic_div_filled" style="width:0%;height:100%;background-image:url(' . api_get_path(WEB_IMG_PATH) . 'real_upload_step.gif);background-repeat:repeat-x;background-position:center;"></div>
                			</div>
                		</div>');

        if ($wait_after_upload) {
            $this->addElement('html', '
			<div id="dynamic_div_waiter_container" style="display:none">
				<div id="dynamic_div_waiter_label">
					' . get_lang('SlideshowConversion') . '
				</div>
				<div id="dynamic_div_waiter_frame">
					<img src="' . api_get_path(WEB_IMG_PATH) . 'real_upload_frame.gif" />
				</div>
			</div>
		');
        }

        // Get the xajax code
        $this->addElement('html', $xajax_upload->getJavascript(api_get_path(WEB_LIBRARY_PATH) . 'xajax'));

        // Get the upload code
        $this->addElement('html', '<script language="javascript" src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/upload.js" type="text/javascript"></script>');
        $this->addElement('html', '<script type="text/javascript">var myUpload = new upload(' . (abs(intval($delay)) * 1000) . ');</script>');

        if (!$wait_after_upload) {
            $wait_after_upload = 0;
        }

        // Add the upload event
        $this->updateAttributes("onsubmit=\"javascript: myUpload.startRealUpload('dynamic_div','" . $upload_id . "','" . $this->getAttribute('id') . "'," . $wait_after_upload . ")\"");
    }

    /**
     * This function has been created for avoiding changes directly within QuickForm class.
     * When we use it, the element is threated as 'required' to be dealt during validation.
     * @param array $element					The array of elements
     * @param string $message					The message displayed
     */
    function add_multiple_required_rule($elements, $message)
    {
        $this->_required[] = $elements[0];
        $this->addRule($elements, $message, 'multiple_required');
    }

    /**
     * Displays the form.
     * If an element in the form didn't validate, an error message is showed
     * asking the user to complete the form.
     */
    public function display()
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
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, august 2006
     * @author Julio Montoya
     */
    public function return_form()
    {
        $error = false;
        $addDateLibraries = false;
        $dateElementTypes = array(
            'date_range_picker',
            'date_time_picker',
            'date_picker',
            'datepicker',
            'datetimepicker'
        );
        /** @var HTML_QuickForm_element $element */
        foreach ($this->_elements as $element) {
            if (in_array($element->getType(), $dateElementTypes)) {
                $addDateLibraries = true;
            }
            if (!is_null(parent::getElementError($element->getName()))) {
                $error = true;
                break;
            }
        }
        $return_value = '';
        $js = null;
        if ($addDateLibraries) {

            $js .= '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/daterange/moment.min.js" type="text/javascript"></script>';
            $js .= '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/datetimepicker/jquery-ui-timepicker-addon.js" type="text/javascript"></script>';
            $js .= '<link href="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/datetimepicker/jquery-ui-timepicker-addon.css" rel="stylesheet" type="text/css" />';
            $js .= '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/daterange/daterangepicker.js" type="text/javascript"></script>';
            $js .= '<link href="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/daterange/daterangepicker-bs2.css" rel="stylesheet" type="text/css" />';

            $isoCode = api_get_language_isocode();

            if ($isoCode != 'en') {
                $js .= api_get_js('jquery-ui/jquery-ui-i18n.min.js');
                $js .= '<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/datetimepicker/i18n/jquery-ui-timepicker-' . $isoCode . '.js" type="text/javascript"></script>';
                $js .= '<script>
                $(function(){
                    moment.lang("' . $isoCode . '");
                    $.datepicker.setDefaults($.datepicker.regional["' . $isoCode . '"]);
                });
                </script>';
            }
        }

        if ($error) {
            $return_value = Display::return_message(get_lang('FormHasErrorsPleaseComplete'),
                'warning');
        }

        $return_value .= $js;
        $return_value .= parent::toHtml();
        // Add div-element which is to hold the progress bar
        if (isset($this->with_progress_bar) && $this->with_progress_bar) {
            $return_value .= '<div id="dynamic_div" style="display:block; margin-left:40%; margin-top:10px; height:50px;"></div>';
        }

        return $return_value;
    }

    /**
     * Create a form validator based on an array of form data:
     *
     *         array(
     *             'name' => 'zombie_report_parameters',    //optional
     *             'method' => 'GET',                       //optional
     *             'items' => array(
     *                 array(
     *                     'name' => 'ceiling',
     *                     'label' => 'Ceiling',            //optional
     *                     'type' => 'date',
     *                     'default' => date()              //optional
     *                 ),
     *                 array(
     *                     'name' => 'active_only',
     *                     'label' => 'ActiveOnly',
     *                     'type' => 'checkbox',
     *                     'default' => true
     *                 ),
     *                 array(
     *                     'name' => 'submit_button',
     *                     'type' => 'style_submit_button',
     *                     'value' => get_lang('Search'),
     *                     'attributes' => array('class' => 'search')
     *                 )
     *             )
     *         );
     *
     * @param array $form_data
     * @deprecated use normal FormValidator construct
     *
     * @return FormValidator
     */
    public static function create($form_data)
    {
        if (empty($form_data)) {
            return null;
        }
        $form_name = isset($form_data['name']) ? $form_data['name'] : 'form';
        $form_method = isset($form_data['method']) ? $form_data['method'] : 'POST';
        $form_action = isset($form_data['action']) ? $form_data['action'] : '';
        $form_target = isset($form_data['target']) ? $form_data['target'] : '';
        $form_attributes = isset($form_data['attributes']) ? $form_data['attributes'] : null;
        $form_track_submit = isset($form_data['track_submit']) ? $form_data['track_submit'] : true;
        $reset = null;
        $result = new FormValidator($form_name, $form_method, $form_action, $form_target, $form_attributes, $form_track_submit);

        $defaults = array();
        foreach ($form_data['items'] as $item) {
            $name = $item['name'];
            $type = isset($item['type']) ? $item['type'] : 'text';
            $label = isset($item['label']) ? $item['label'] : '';
            if ($type == 'wysiwyg') {
                $element = $result->addHtmlEditor($name, $label);
            } else {
                $element = $result->addElement($type, $name, $label);
            }
            if (isset($item['attributes'])) {
                $attributes = $item['attributes'];
                $element->setAttributes($attributes);
            }
            if (isset($item['value'])) {
                $value = $item['value'];
                $element->setValue($value);
            }
            if (isset($item['default'])) {
                $defaults[$name] = $item['default'];
            }
            if (isset($item['rules'])) {
                $rules = $item['rules'];
                foreach ($rules as $rule) {
                    $message = $rule['message'];
                    $type = $rule['type'];
                    $format = isset($rule['format']) ? $rule['format'] : null;
                    $validation = isset($rule['validation']) ? $rule['validation'] : 'server';
                    $force = isset($rule['force']) ? $rule['force'] : false;
                    $result->addRule($name, $message, $type, $format, $validation, $reset, $force);
                }
            }
        }
        $result->setDefaults($defaults);

        return $result;
    }
}

/**
 * Cleans HTML text filter
 * @param string $html			HTML to clean
 * @param int $mode (optional)
 * @return string				The cleaned HTML
 */
function html_filter($html, $mode = NO_HTML)
{
    $allowed_tags = HTML_QuickForm_Rule_HTML::get_allowed_tags($mode);
    $cleaned_html = kses($html, $allowed_tags);
    return $cleaned_html;
}

function html_filter_teacher($html)
{
    return html_filter($html, TEACHER_HTML);
}

function html_filter_student($html)
{
    return html_filter($html, STUDENT_HTML);
}

function html_filter_teacher_fullpage($html)
{
    return html_filter($html, TEACHER_HTML_FULLPAGE);
}

function html_filter_student_fullpage($html)
{
    return html_filter($html, STUDENT_HTML_FULLPAGE);
}

/**
 * Cleans mobile phone number text
 * @param string $mobilePhoneNumber     Mobile phone number to clean
 * @return string                       The cleaned mobile phone number
 */
function mobile_phone_number_filter($mobilePhoneNumber)
{
    $mobilePhoneNumber = str_replace(array('+', '(', ')'), '', $mobilePhoneNumber);
    return ltrim($mobilePhoneNumber,'0');
}
