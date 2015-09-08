<?php
/* For licensing terms, see /license.txt */

/**
 * Class FormValidator
 * create/manipulate/validate user input.
 */
class FormValidator extends HTML_QuickForm
{
    const LAYOUT_HORIZONTAL = 'horizontal';
    const LAYOUT_INLINE = 'inline';
    const LAYOUT_BOX = 'box';
    const LAYOUT_BOX_NO_LABEL = 'box-no-label';

    public $with_progress_bar = false;
    private $layout;

    /**
     * Constructor
     * @param string $name					Name of the form
     * @param string $method (optional			Method ('post' (default) or 'get')
     * @param string $action (optional			Action (default is $PHP_SELF)
     * @param string $target (optional			Form's target defaults to '_self'
     * @param mixed $attributes (optional)		Extra attributes for <form> tag
     * @param string $layout
     * @param bool $trackSubmit (optional)		Whether to track if the form was
     * submitted by adding a special hidden field (default = true)
     */
    public function __construct(
        $name,
        $method = 'post',
        $action = '',
        $target = '',
        $attributes = array(),
        $layout = self::LAYOUT_HORIZONTAL,
        $trackSubmit = true
    ) {
        // Default form class.
        if (is_array($attributes) && !isset($attributes['class']) || empty($attributes)) {
            $attributes['class'] = 'form-horizontal';
        }

        if (isset($attributes['class']) && strpos($attributes['class'], 'form-search') !== false) {
            $layout = 'inline';
        }

        $this->setLayout($layout);

        switch ($layout) {
            case self::LAYOUT_HORIZONTAL:
                $attributes['class'] = 'form-horizontal';
                break;
            case self::LAYOUT_INLINE:
            case self::LAYOUT_BOX:
                $attributes['class'] = 'form-inline';
                break;
        }

        parent::__construct($name, $method, $action, $target, $attributes, $trackSubmit);

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
            $renderer->setElementTemplate($this->getDefaultElementTemplate());

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
        $this->setRequiredNote('<span class="form_required">*</span> <small>' . get_lang('ThisFieldIsRequired') . '</small>');
        $noteTemplate = <<<EOT
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">{requiredNote}</div>
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
    public function getDefaultElementTemplate()
    {
        return '
            <div class="form-group {error_class}">
                <label {label-for} class="col-sm-2 control-label" >
                    <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                    {label}
                </label>
                <div class="col-sm-8">
                    {icon}
                    {element}

                    <!-- BEGIN label_2 -->
                        <p class="help-block">{label_2}</p>
                    <!-- END label_2 -->

                    <!-- BEGIN error -->
                        <span class="help-inline">{error}</span>
                    <!-- END error -->
                </div>
                <div class="col-sm-2">
                    <!-- BEGIN label_3 -->
                        {label_3}
                    <!-- END label_3 -->
                </div>
            </div>';
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
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
     * @param string $label
     * @param array $attributes
     *
     * @return mixed
     */
    public function addDatePicker($name, $label, $attributes = [])
    {
        return $this->addElement('DatePicker', $name, $label, $attributes);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $attributes
     *
     * @return mixed
     */
    public function addDateTimePicker($name, $label, $attributes = [])
    {
        return $this->addElement('DateTimePicker', $name, $label, $attributes);
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
    public function addButton(
        $name,
        $label,
        $icon = 'check',
        $style = 'default',
        $size = 'default',
        $class = null,
        $attributes = array(),
        $createElement = false
    ) {
        if ($createElement) {
            return $this->createElement(
                'button',
                $name,
                $label,
                $icon,
                $style,
                $size,
                $class,
                $attributes
            );
        }

        return $this->addElement(
            'button',
            $name,
            $label,
            $icon,
            $style,
            $size,
            $class,
            $attributes
        );
    }

    /**
     * Returns a button with the primary color and a check mark
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonSave($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'check',
            'primary',
            null,
            null,
            array(),
            $createElement
        );
    }

    /**
     * Returns a cancel button
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonCancel($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'times',
            'danger',
            null,
            null,
            array(),
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a "plus" icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     * @param array $attributes Additional attributes
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonCreate($label, $name = 'submit', $createElement = false, $attributes = array())
    {
        return $this->addButton(
            $name,
            $label,
            'plus',
            'primary',
            null,
            null,
            $attributes,
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a pencil icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     * @return HTML_QuickForm_button
     */
    public function addButtonUpdate($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'pencil',
            'primary',
            null,
            null,
            array(),
            $createElement
        );
    }

    /**
     * Returns a button with the danger color and a trash icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonDelete($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'trash',
            'danger',
            null,
            null,
            array(),
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a paper-plane icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonSend($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'paper-plane',
            'primary',
            null,
            null,
            array(),
            $createElement
        );
    }

    /**
     * Returns a button with the default (grey?) color and a magnifier icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonSearch($label = null, $name = 'submit')
    {
        if (empty($label)) {
            $label = get_lang('Search');
        }

        return $this->addButton($name, $label, 'search', 'default');
    }

    /**
     * Returns a button with the primary color and a right-pointing arrow icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param array $attributes Additional attributes
     * @return HTML_QuickForm_button
     */
    public function addButtonNext($label, $name = 'submit',$attributes = array())
    {
        return $this->addButton($name, $label, 'arrow-right', 'primary', null, null, $attributes);
    }

    /**
     * Returns a button with the primary color and a check mark icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     * @return HTML_QuickForm_button
     */
    public function addButtonImport($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'check',
            'primary',
            null,
            null,
            array(),
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a check-mark icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     * @return HTML_QuickForm_button
     */
    public function addButtonExport($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'check',
            'primary',
            null,
            null,
            array(),
            $createElement
        );
    }

    /**
     * Shortcut to filter button
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     * @return HTML_QuickForm_button
     */
    public function addButtonFilter($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'filter',
            'primary',
            null,
            null,
            array(),
            $createElement
        );
    }

    /**
     * Shortcut to reset button
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     * @return HTML_QuickForm_button
     */
    public function addButtonReset($label, $name = 'reset', $createElement = false)
    {
        $icon = 'eraser';
        $style = 'default';
        $size = 'default';
        $class = null;
        $attributes = array();

        if ($createElement) {
            return $this->createElement(
                'reset',
                $name,
                $label,
                $icon,
                $style,
                $size,
                $class,
                $attributes
            );
        }

        return $this->addElement(
            'reset',
            $name,
            $label,
            $icon,
            $style,
            $size,
            $class,
            $attributes
        );
    }

    /**
     * Returns a button with the primary color and an upload icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonUpload($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'upload',
            'primary',
            null,
            null,
            array(),
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a download icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonDownload($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'download',
            'primary',
            null,
            null,
            array(),
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a magnifier icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonPreview($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'search',
            'primary',
            null,
            null,
            array(),
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a copy (double sheet) icon
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonCopy($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'copy',
            'primary',
            null,
            null,
            array(),
            $createElement
        );
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
            $group[] = $this->createElement('checkbox', $value, null, $text, $attributes);
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
     * @param array  $options
     * @param array  $attributes
     *
     * @return HTML_QuickForm_select
     */
    public function addSelect($name, $label, $options = array(), $attributes = array())
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
    public function addFile($name, $label, $attributes = array())
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
     * @param string $label The label for the form-element
     * @param bool   $required (optional) Is the form-element required (default=true)
     * @param bool   $fullPage (optional) When it is true, the editor loads completed html code for a full page.
     * @param array  $config (optional) Configuration settings for the online editor.
     * @param bool   $style
     */
    public function addHtmlEditor($name, $label, $required = true, $fullPage = false, $config = array(), $style = false)
    {
        $config['rows'] = isset($config['rows']) ? $config['rows'] : 15;
        $config['cols'] = isset($config['cols']) ? $config['cols'] : 80;
        $this->addElement('html_editor', $name, $label, $config, $style);
        $this->applyFilter($name, 'trim');
        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        /** @var HtmlEditor $element */
        $element = $this->getElement($name);
        if ($style) {
            $config['style'] = true;
        }
        if ($fullPage) {
            $config['fullPage'] = true;
        }

        if ($element->editor) {
            $element->editor->processConfig($config);
        }
    }

    /**
     * @param string $name
     * @param string $label
     *
     * @return mixed
     */
    public function addButtonAdvancedSettings($name, $label = '')
    {
        $label = !empty($label) ? $label : get_lang('AdvancedParameters');

        return $this->addElement('advanced_settings', $name, $label);
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
     *
     * submits the form and the start of the progress bar.
     * @deprecated ?
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
        echo $this->returnForm();
    }

    /**
     * Returns the HTML code of the form.
     * @return string $return_value HTML code of the form
     */
    public function returnForm()
    {
        $error = false;
        /** @var HTML_QuickForm_element $element */
        foreach ($this->_elements as $element) {
            if (!is_null(parent::getElementError($element->getName()))) {
                $error = true;
                break;
            }
        }

        $returnValue = '';
        $js = null;

        if ($error) {
            $returnValue = Display::return_message(
                get_lang('FormHasErrorsPleaseComplete'),
                'warning'
            );
        }

        $returnValue .= $js;
        $returnValue .= parent::toHtml();
        // Add div-element which is to hold the progress bar
        if (isset($this->with_progress_bar) && $this->with_progress_bar) {
            $returnValue .= '<div id="dynamic_div" style="display:block; margin-left:40%; margin-top:10px; height:50px;"></div>';
        }

        return $returnValue;
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
     * @deprecated use returnForm()
     */
    public function return_form()
    {
        return $this->returnForm();
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

    /**
     * @return HTML_QuickForm_Renderer_Default
     */
    public static function getDefaultRenderer()
    {
        return
            isset($GLOBALS['_HTML_QuickForm_default_renderer']) ?
                $GLOBALS['_HTML_QuickForm_default_renderer'] : null;
    }

    /**
     * Adds a input of type url to the form.
     * @param type $name The label for the form-element
     * @param type $label The element name
     * @param type $required Optional. Is the form-element required (default=true)
     * @param type $attributes Optional. List of attributes for the form-element
     */
    public function addUrl($name, $label, $required = true, $attributes = array())
    {
        $this->addElement('url', $name, $label, $attributes);
        $this->applyFilter($name, 'trim');
        $this->addRule($name, get_lang('InsertAValidUrl'), 'url');

        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    /**
     * Adds a text field for letters to the form.
     * A trim-filter is attached to the field.
     * @param string $name The element name
     * @param string $label The label for the form-element
     * @param bool $required	Optional. Is the form-element required (default=true)
     * @param array $attributes Optional. List of attributes for the form-element
     */
    public function addTextLettersOnly(
        $name,
        $label,
        $required = false,
        $attributes = []
    )
    {
        $attributes = array_merge(
            $attributes,
            [
                'pattern' => '[a-zA-ZñÑ]+',
                'title' => get_lang('OnlyLetters')
            ]
        );

        $this->addElement(
            'text',
            $name,
            [
                $label,
                get_lang('OnlyLetters')
            ],
            $attributes
        );

        $this->applyFilter($name, 'trim');

        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        $this->addRule(
            $name,
            get_lang('OnlyLetters'),
            'regex',
            '/^[a-zA-ZñÑ]+$/'
        );
    }

    /**
     * Adds a text field for alphanumeric characters to the form.
     * A trim-filter is attached to the field.
     * @param string $name The element name
     * @param string $label The label for the form-element
     * @param bool $required	Optional. Is the form-element required (default=true)
     * @param array $attributes Optional. List of attributes for the form-element
     */
    public function addTextAlphanumeric(
        $name,
        $label,
        $required = false,
        $attributes = []
    )
    {
        $attributes = array_merge(
            $attributes,
            [
                'pattern' => '[a-zA-Z0-9ñÑ]+',
                'title' => get_lang('OnlyLettersAndNumbers')
            ]
        );

        $this->addElement(
            'text',
            $name,
            [
                $label,
                get_lang('OnlyLettersAndNumbers')
            ],
            $attributes
        );

        $this->applyFilter($name, 'trim');

        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        $this->addRule(
            $name,
            get_lang('OnlyLettersAndNumbers'),
            'regex',
            '/^[a-zA-Z0-9ÑÑ]+$/'
        );
    }

    /**
     * Adds a text field for letters and spaces to the form.
     * A trim-filter is attached to the field.
     * @param string $name The element name
     * @param string $label The label for the form-element
     * @param bool $required	Optional. Is the form-element required (default=true)
     * @param array $attributes Optional. List of attributes for the form-element
     */
    public function addTextLettersAndSpaces(
        $name,
        $label,
        $required = false,
        $attributes = []
    )
    {
        $attributes = array_merge(
            $attributes,
            [
                'pattern' => '[a-zA-ZñÑ\s]+',
                'title' => get_lang('OnlyLettersAndSpaces')
            ]
        );

        $this->addElement(
            'text',
            $name,
            [
                $label,
                get_lang('OnlyLettersAndSpaces')
            ],
            $attributes
        );

        $this->applyFilter($name, 'trim');

        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        $this->addRule(
            $name,
            get_lang('OnlyLettersAndSpaces'),
            'regex',
            '/^[a-zA-ZñÑ\s]+$/'
        );
    }

    /**
     * Adds a text field for alphanumeric and spaces characters to the form.
     * A trim-filter is attached to the field.
     * @param string $name The element name
     * @param string $label The label for the form-element
     * @param bool $required	Optional. Is the form-element required (default=true)
     * @param array $attributes Optional. List of attributes for the form-element
     */
    public function addTextAlphanumericAndSpaces(
        $name,
        $label,
        $required = false,
        $attributes = []
    )
    {
        $attributes = array_merge(
            $attributes,
            [
                'pattern' => '[a-zA-Z0-9ñÑ\s]+',
                'title' => get_lang('OnlyLettersAndNumbersAndSpaces')
            ]
        );

        $this->addElement(
            'text',
            $name,
            [
                $label,
                get_lang('OnlyLettersAndNumbersAndSpaces')
            ],
            $attributes
        );

        $this->applyFilter($name, 'trim');

        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        $this->addRule(
            $name,
            get_lang('OnlyLettersAndNumbersAndSpaces'),
            'regex',
            '/^[a-zA-Z0-9ñÑ\s]+$/'
        );
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
