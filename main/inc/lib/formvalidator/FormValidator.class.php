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
     * @param string $form_name					Name of the form
     * @param string $method (optional			Method ('post' (default) or 'get')
     * @param string $action (optional			Action (default is $PHP_SELF)
     * @param string $target (optional			Form's target defaults to '_self'
     * @param mixed $attributes (optional)		Extra attributes for <form> tag
     * @param bool $track_submit (optional)		Whether to track if the form was
     * submitted by adding a special hidden field (default = true)
     */
    public function __construct(
        $form_name = null,
        $method = 'post',
        $action = '',
        $target = '',
        $attributes = null,
        $track_submit = true
    ) {
        // Default form class
        if (is_array($attributes) && !isset($attributes['class']) || empty($attributes)) {
            $attributes['class'] = 'form-horizontal';
        }

        // Fixing form search
        if (is_array($attributes) && isset($attributes['class'])) {
            //if (strpos($attributes['class'], 'form-search')) {
            if ($attributes['class'] == 'form-search') {
            //    $attributes['class'] = str_replace('form-search', 'form-inline', $attributes['class']);
                $attributes['class']  = 'form-inline';
            }
        }

        // Allow form with no names
        if (empty($form_name)) {
            $form_name = uniqid();
        }

        parent::__construct($form_name, $method, $action, $target, $attributes, $track_submit);

        // Load some custom elements and rules
        $dir = api_get_path(LIBRARY_PATH).'formvalidator/';
        $this->registerElementType('html_editor', $dir . 'Element/html_editor.php', 'HTML_QuickForm_html_editor');
        $this->registerElementType('date_range_picker', $dir . 'Element/DateRangePicker.php', 'DateRangePicker');
        $this->registerElementType('date_time_picker', $dir . 'Element/DateTimePicker.php', 'DateTimePicker');
        $this->registerElementType('date_picker', $dir . 'Element/DatePicker.php', 'DatePicker');

        $this->registerElementType('datepicker', $dir . 'Element/datepicker.php', 'HTML_QuickForm_datepicker');
        $this->registerElementType('datepickerdate', $dir . 'Element/datepickerdate.php', 'HTML_QuickForm_datepickerdate');
        $this->registerElementType('receivers', $dir . 'Element/receivers.php', 'HTML_QuickForm_receivers');
        $this->registerElementType('select_language', $dir . 'Element/select_language.php', 'HTML_QuickForm_Select_Language');
        $this->registerElementType('select_ajax', $dir . 'Element/select_ajax.php', 'HTML_QuickForm_Select_Ajax');
        $this->registerElementType('select_theme', $dir . 'Element/select_theme.php', 'HTML_QuickForm_Select_Theme');
        $this->registerElementType('style_submit_button', $dir . 'Element/style_submit_button.php', 'HTML_QuickForm_stylesubmitbutton');
        $this->registerElementType('style_reset_button', $dir . 'Element/style_reset_button.php', 'HTML_QuickForm_styleresetbutton');
        $this->registerElementType('button', $dir . 'Element/style_submit_button.php', 'HTML_QuickForm_stylesubmitbutton');
        $this->registerElementType('captcha', 'HTML/QuickForm/CAPTCHA.php', 'HTML_QuickForm_CAPTCHA');
        $this->registerElementType('CAPTCHA_Image', 'HTML/QuickForm/CAPTCHA/Image.php', 'HTML_QuickForm_CAPTCHA_Image');

        $this->registerRule('date', null, 'HTML_QuickForm_Rule_Date', $dir . 'Rule/Date.php');
        $this->registerRule('datetime', null, 'DateTimeRule', $dir . 'Rule/DateTimeRule.php');
        $this->registerRule('date_compare', null, 'HTML_QuickForm_Rule_DateCompare', $dir . 'Rule/DateCompare.php');
        $this->registerRule('html', null, 'HTML_QuickForm_Rule_HTML', $dir . 'Rule/HTML.php');
        $this->registerRule('username_available', null, 'HTML_QuickForm_Rule_UsernameAvailable', $dir . 'Rule/UsernameAvailable.php');
        $this->registerRule('username', null, 'HTML_QuickForm_Rule_Username', $dir . 'Rule/Username.php');
        $this->registerRule('filetype', null, 'HTML_QuickForm_Rule_Filetype', $dir . 'Rule/Filetype.php');
        $this->registerRule('multiple_required', 'required', 'HTML_QuickForm_Rule_MultipleRequired', $dir . 'Rule/MultipleRequired.php');
        $this->registerRule('url', null, 'HTML_QuickForm_Rule_Url', $dir . 'Rule/Url.php');
        $this->registerRule('compare_fields', null, 'HTML_QuickForm_Compare_Fields', $dir . 'Rule/CompareFields.php');
        $this->registerRule('compare_datetime_text', null, 'HTML_QuickForm_Rule_CompareDateTimeText', $dir . 'Rule/CompareDateTimeText.php');
        $this->registerRule('CAPTCHA', 'rule', 'HTML_QuickForm_Rule_CAPTCHA', 'HTML/QuickForm/Rule/CAPTCHA.php');

        // Modify the default templates
        /** @var  HTML_QuickForm_Renderer_Default $renderer */
        $renderer = & $this->defaultRenderer();

        // Form template
        $renderer->setFormTemplate($this->getFormTemplate());

        // Element template
        if (isset($attributes['class']) && $attributes['class'] == 'well form-inline') {
            $element_template = ' {label}  {element} ';
            $renderer->setElementTemplate($element_template);
        } elseif (isset($attributes['class']) && $attributes['class'] == 'form-search') {
            $element_template = ' {label}  {element} ';
            $renderer->setElementTemplate($element_template);
        } else {

            if (is_array($attributes) && isset($attributes['class']) && $attributes['class'] == 'form-inline') {
                $element_template = $this->getDefaultInlineElementTemplate();
            } else {
                $element_template = $this->getDefaultElementTemplate();
            }

            $renderer->setElementTemplate($element_template);

            // Display a gray div in the buttons
            $button_element_template_simple = '<div class="form-actions">{label} {element}</div>';
            $renderer->setElementTemplate($button_element_template_simple, 'submit_in_actions');

            //Display a gray div in the buttons + makes the button available when scrolling
            $button_element_template_in_bottom = '<div class="form-actions bottom_actions">{label} {element}</div>';
            $renderer->setElementTemplate($button_element_template_in_bottom, 'submit_fixed_in_bottom');
            $renderer->setElementTemplate($button_element_template_simple, 'buttons_in_action');

            $button_element_template_simple_right = '<div class="form-actions"> <div class="pull-right">{label} {element}</div></div>';
            $renderer->setElementTemplate($button_element_template_simple_right, 'buttons_in_action_right');
        }

        // Set Header template
        $renderer->setHeaderTemplate('<h2>{header}</h2>');

        //Set required field template
        HTML_QuickForm::setRequiredNote('<span class="form_required">*</span> <small>' . get_lang('ThisFieldIsRequired') . '</small>');
        $required_note_template = <<<EOT
	<div class="form-group">
		<div class="col-sm-2">{requiredNote}</div>
	</div>
EOT;
        $renderer->setRequiredNoteTemplate($required_note_template);
    }

    /**
     * @return string
     */
    private function getFormTemplate()
    {
        return '<form{attributes}>
                <fieldset>{content}</fieldset>
                {hidden}
                </form>';
    }

    /**
     * @return string
     */
    private function getDefaultElementTemplate()
    {
        return '
            <div class="form-group {error_class}">
                <label class="col-sm-2 control-label">
                    <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                    {label}
                </label>

                <div class="col-sm-10">
                    {element}

                    <!-- BEGIN label_3 -->
                        {label_3}
                    <!-- END label_3 -->

                    <!-- BEGIN label_2 -->
                        <span class="help-block">
                            {label_2}
                        </span>
                    <!-- END label_2 -->

                    <!-- BEGIN error -->
                        <span class=" col-sm-2 help-block">{error}</span>
                    <!-- END error -->
                </div>
            </div>';
    }

    /**
     * @return string
     */
    private function getDefaultInlineElementTemplate()
    {
        return '
            <div class="form-group {error_class}">
                <label class="sr-only">
                    <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                    {label}
                </label>
                {element}

                <!-- BEGIN label_3 -->
                    {label_3}
                <!-- END label_3 -->

                <!-- BEGIN label_2 -->
                    <span class="help-block">
                        {label_2}
                    </span>
                <!-- END label_2 -->

                <!-- BEGIN error -->
                    <span class=" col-sm-2 help-block">{error}</span>
                <!-- END error -->

            </div>';
    }

    /**
     * Adds a text field to the form.
     * A trim-filter is attached to the field.
     * @param string $label						The label for the form-element
     * @param string $name						The element name
     * @param boolean $required	(optional)		Is the form-element required (default=true)
     * @param array $attributes (optional)		List of attributes for the form-element
     */
    public function add_textfield($name, $label, $required = true, $attributes = array())
    {
        $this->addElement('text', $name, $label, $attributes);
        $this->applyFilter($name, 'trim');
        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    /**
     * date_range_picker element creates 2 hidden fields
     * elementName + "_start" elementName "_end"
     * @param string $name
     * @param string $label
     * @param bool $required
     * @param array $attributes
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
    function add_hidden($name, $value)
    {
        $this->addElement('hidden', $name, $value);
    }

    public function add_textarea($name, $label, $attributes = array())
    {
        $this->addElement('textarea', $name, $label, $attributes);
    }

    public function add_button($name, $label, $attributes = array())
    {
        $this->addElement('button', $name, $label, $attributes);
    }

    public function add_checkbox($name, $label, $trailer = '', $attributes = array())
    {
        $this->addElement('checkbox', $name, $label, $trailer, $attributes);
    }

    public function add_radio($name, $label, $options = '')
    {
        $group = array();
        foreach ($options as $key => $value) {
            $group[] = $this->createElement('radio', null, null, $value, $key);
        }
        $this->addGroup($group, $name, $label);
    }

    public function add_select($name, $label, $options = '', $attributes = array())
    {
        $this->addElement('select', $name, $label, $options, $attributes);
    }

    public function add_label($label, $text)
    {
        $this->addElement('label', $label, $text);
    }

    public function add_header($text)
    {
        $this->addElement('header', $text);
    }

    public function add_file($name, $label, $attributes = array())
    {
        $this->addElement('file', $name, $label, $attributes);
    }

    public function add_html($snippet)
    {
        $this->addElement('html', $snippet);
    }

    /**
     * Adds a HTML-editor to the form to fill in a title.
     * A trim-filter is attached to the field.
     * A HTML-filter is attached to the field (cleans HTML)
     * A rule is attached to check for unwanted HTML
     * @param string $name
     * @param string $label						The label for the form-element
     * @param boolean $required	(optional)		Is the form-element required (default=true)
     * @param boolean $full_page (optional)		When it is true, the editor loads completed html code for a full page.
     * @param array $config (optional)	Configuration settings for the online editor.
     *
     */
    public function add_html_editor($name, $label, $required = true, $full_page = false, $config = null)
    {
        $this->addElement('html_editor', $name, $label, 'rows="15" cols="80"', $config);
        $this->applyFilter($name, 'trim');
        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        /** @var HTML_QuickForm_html_editor $element */
        $element = $this->getElement($name);
        $element->editor->processConfig($config);
    }

    /**
     * Adds a date picker element to the form
     * A rule is added to check if the date is a valid one
     * @param string $label						The label for the form-element
     * @param string $name						The element name
     */
    public function add_datepicker($name, $label)
    {
        $this->addElement('datepicker', $name, $label, array('form_name' => $this->getAttribute('name')));
        $this->_elements[$this->_elementIndex[$name]]->setLocalOption('minYear', 1900); // TODO: Now - 9 years
        $this->addRule($name, get_lang('InvalidDate'), 'date');
    }

    /**
     * Adds a date picker date element to the form
     * A rule is added to check if the date is a valid one
     * @param string $label						The label for the form-element
     * @param string $name						The element name
     * @deprecated
     */
    public function add_datepickerdate($name, $label)
    {
        $this->addElement('datepickerdate', $name, $label, array('form_name' => $this->getAttribute('name')));
        $this->_elements[$this->_elementIndex[$name]]->setLocalOption('minYear', 1900); // TODO: Now - 9 years
        $this->addRule($name, get_lang('InvalidDate'), 'date');
    }

    /**
     * Adds a timewindow element to the form.
     * 2 datepicker elements are added and a rule to check if the first date is
     * before the second one.
     * @param string $label						The label for the form-element
     * @param string $name						The element name
     * @deprecated
     */
    public function add_timewindow($name_1, $name_2, $label_1, $label_2)
    {
        $this->add_datepicker($name_1, $label_1);
        $this->add_datepicker($name_2, $label_2);
        $this->addRule(array($name_1, $name_2), get_lang('StartDateShouldBeBeforeEndDate'), 'date_compare', 'lte');
    }

    /**
     * Adds a progress bar to the form.
     *
     * Once the user submits the form, a progress bar (animated gif) is
     * displayed. The progress bar will disappear once the page has been
     * reloaded.
     *
     * @param int $delay (optional)				The number of seconds between the moment the user
     * @param string $label (optional)			Custom label to be shown
     * submits the form and the start of the progress bar.
     */
    public function add_progress_bar($delay = 2, $label = '')
    {
        if (empty($label)) {
            $label = get_lang('PleaseStandBy');
        }
        $this->with_progress_bar = true;
        $this->updateAttributes("onsubmit=\"javascript: myUpload.start('dynamic_div','" . api_get_path(WEB_IMG_PATH) . "progress_bar.gif','" . $label . "','" . $this->getAttribute('id') . "')\"");
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

        if (!class_exists('xajax')) {
            require_once api_get_path(LIBRARY_PATH) . 'xajax/xajax.inc.php';
        }

        $xajax_upload = new xajax(api_get_path(WEB_LIBRARY_PATH).'upload.xajax.php');
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
        $this->addElement('html', $xajax_upload->getJavascript(api_get_path(WEB_LIBRARY_PATH).'xajax'));

        // Get the upload code
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
     * @param array $elements					The array of elements
     * @param string $message					The message displayed
     */
    public function add_multiple_required_rule($elements, $message)
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
     */
    public function return_form()
    {
        $error = false;
        $addDateLibraries = false;
        $dateElementTypes = array('date_range_picker', 'date_time_picker', 'date_picker', 'datepicker', 'datetimepicker');

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
            $js = api_get_js('jquery-ui/jquery-ui-i18n.min.js');
            $js .= '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'datetimepicker/jquery-ui-timepicker-addon.js" type="text/javascript"></script>';
            $js .= '<link href="'.api_get_path(WEB_LIBRARY_JS_PATH).'datetimepicker/jquery-ui-timepicker-addon.css" rel="stylesheet" type="text/css" />';
            $js .= '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'daterange/moment.min.js" type="text/javascript"></script>';
            $js .= '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'daterange/daterangepicker.js" type="text/javascript"></script>';
            $js .= '<link href="'.api_get_path(WEB_LIBRARY_JS_PATH).'daterange/daterangepicker-bs2.css" rel="stylesheet" type="text/css" />';

            $isocode = api_get_language_isocode();
            if ($isocode != 'en') {
                $js .= '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/datetimepicker/i18n/jquery-ui-timepicker-'.$isocode.'.js" type="text/javascript"></script>';
                $js .= '<script>
                $(function(){
                    $.datepicker.setDefaults($.datepicker.regional["'.$isocode.'"]);
                     moment.lang("'.$isocode.'");
                });
                </script>';
            }
        }

        if ($error) {
            $return_value = Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'warning');
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
     * @param string $name
     * @param string $label
     * @param array $values
     * @param array $attributes
     */
    public function addDoubleMultipleSelect($name, $label, $values, $attributes = array())
    {
        $group = $this->addElement('advmultiselect', $name, $label, $values, $attributes);
        $group->setElementTemplate('
        {javascript}
        <table{class}>
        <!-- BEGIN label_2 --><tr><th>{label_2}</th><!-- END label_2 -->
        <!-- BEGIN label_3 --><th>&nbsp;</th><th>{label_3}</th></tr><!-- END label_3 -->
        <tr>
          <td valign="top">{unselected}</td>
          <td align="center">{add}<br /><br />{remove}</td>
          <td valign="top">{selected}</td>
        </tr>
        </table>
        ');
        $group->setButtonAttributes('add', array('class' => 'btn arrowr'));
        $group->setButtonAttributes('remove', array('class' => 'btn arrowl'));
    }

    /**
     * @return string
     */
    public function getDoubleMultipleSelectTemplate()
    {
        return '
            <div class="form-group">
                <label class="col-sm-2 control-label">
                    <!-- BEGIN required -->
                    <span class="form_required">*</span> <!-- END required -->
                    {label}
                </label>
                <div class="col-sm-10">
                    <table cellpadding="0" cellspacing="0">
                        <tr>
                            <!-- BEGIN error -->
                            <span class="form_error">{error}</span>
                            <br /><!-- END error -->
                            <td>{element}</td>
                        </tr>
                    </table>
                </div>
            </div>';
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
     * @param array form_data
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

        $result = new FormValidator($form_name, $form_method, $form_action, $form_target, $form_attributes, $form_track_submit);

        $defaults = array();
        foreach ($form_data['items'] as $item) {
            $name = $item['name'];
            $type = isset($item['type']) ? $item['type'] : 'text';
            $label = isset($item['label']) ? $item['label'] : '';
            if ($type == 'wysiwyg') {
                $element = $result->add_html_editor($name, $label);
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
// Used when applying filters

/**
 * Cleans HTML text
 * @param string $html                        HTML to clean
 * @param int $mode (optional)
 * @return string                                The cleaned HTML
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
