<?php

/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;

/**
 * Class FormValidator
 * create/manipulate/validate user input.
 */
class FormValidator extends HTML_QuickForm
{
    public const LAYOUT_HORIZONTAL = 'horizontal';
    public const LAYOUT_INLINE = 'inline';
    public const LAYOUT_BOX = 'box';
    public const LAYOUT_BOX_NO_LABEL = 'box-no-label';
    public const LAYOUT_GRID = 'grid';

    public const TIMEPICKER_INCREMENT_DEFAULT = 15;

    public $with_progress_bar = false;
    private $layout;

    /**
     * Constructor.
     *
     * @param string $name        Name of the form
     * @param string $method      (optional) Method ('post' (default) or 'get')
     * @param string $action      (optional) Action (default is $PHP_SELF)
     * @param string $target      (optional) Form's target defaults to '_self'
     * @param mixed  $attributes  (optional) Extra attributes for <form> tag
     * @param string $layout
     * @param bool   $trackSubmit (optional) Whether to track if the form was
     *                            submitted by adding a special hidden field (default = true)
     */
    public function __construct(
        $name,
        $method = 'post',
        $action = '',
        $target = '',
        $attributes = [],
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

        // Form template
        $formTemplate = $this->getFormTemplate();

        switch ($layout) {
            case self::LAYOUT_HORIZONTAL:
                $attributes['class'] = 'form-horizontal';
                break;
            case self::LAYOUT_INLINE:
                $attributes['class'] = 'form-inline';
                break;
            case self::LAYOUT_BOX:
                $attributes['class'] = 'form-inline-box';
                break;
            case self::LAYOUT_GRID:
                $attributes['class'] = 'form-grid';
                $formTemplate = $this->getGridFormTemplate();
                break;
        }

        parent::__construct($name, $method, $action, $target, $attributes, $trackSubmit);

        // Modify the default templates
        $renderer = &$this->defaultRenderer();

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
            $renderer->setElementTemplate($templateSimple, 'buttons_in_action');

            $templateSimpleRight = '<div class="form-actions"> <div class="pull-right">{label} {element}</div></div>';
            $renderer->setElementTemplate($templateSimpleRight, 'buttons_in_action_right');
        }

        //Set Header template
        $renderer->setHeaderTemplate('<legend>{header}</legend>');

        //Set required field template
        $this->setRequiredNote(
            '<span class="form_required">*</span> <small>'.get_lang('ThisFieldIsRequired').'</small>'
        );

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
        </fieldset>
        {hidden}
        </form>';
    }

    /**
     * @return string
     */
    public function getGridFormTemplate()
    {
        return '
        <style>

        </style>
        <form{attributes}>
            <div class="form_list">
                {content}
            </div>
        {hidden}
        </form>';
    }

    /**
     * @todo this function should be added in the element class
     *
     * @return string
     */
    public function getDefaultElementTemplate()
    {
        return '
            <div class="form-group {error_class}">
                <label {label-for} class="col-sm-2 control-label {extra_label_class}" >
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
                        <span class="help-inline help-block">{error}</span>
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
     *
     * @param string|array $label      The label for the form-element
     * @param string       $name       The element name
     * @param bool         $required   (optional)    Is the form-element required (default=true)
     * @param array        $attributes (optional)    List of attributes for the form-element
     *
     * @return HTML_QuickForm_text
     */
    public function addText($name, $label, $required = true, $attributes = [], $createElement = false)
    {
        if ($createElement) {
            $element = $this->createElement('text', $name, $label, $attributes);
        } else {
            $element = $this->addElement('text', $name, $label, $attributes);
        }

        $this->applyFilter($name, 'trim');
        $this->applyFilter($name, 'html_filter');
        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        return $element;
    }

    /**
     * Adds a text field to the form to be used as internal url (URL without the domain part).
     * A trim-filter is attached to the field.
     *
     * @param string|array $label      The label for the form-element
     * @param string       $name       The element name
     * @param bool         $required   (optional)    Is the form-element required (default=true)
     * @param array        $attributes (optional)    List of attributes for the form-element
     *
     * @return HTML_QuickForm_text
     */
    public function addInternalUrl($name, $label, $required = true, $attributes = [], $createElement = false)
    {
        if ($createElement) {
            $element = $this->createElement('text', $name, $label, $attributes);
        } else {
            $element = $this->addElement('text', $name, $label, $attributes);
        }

        $this->applyFilter($name, 'trim');
        $this->applyFilter($name, 'plain_url_filter');
        $this->addRule($name, get_lang('InsertAValidUrl'), 'internal_url');

        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        return $element;
    }

    /**
     * Add hidden course params.
     */
    public function addCourseHiddenParams()
    {
        $this->addHidden('cidReq', api_get_course_id());
        $this->addHidden('id_session', api_get_session_id());
    }

    /**
     * The "date_range_picker" element creates 2 hidden fields
     * "elementName" + "_start"  and "elementName" + "_end"
     * For example if the name is "range", you will have 2 new fields
     * when executing $form->getSubmitValues()
     * "range_start" and "range_end".
     *
     * @param string $name
     * @param string $label
     * @param bool   $required
     * @param array  $attributes
     */
    public function addDateRangePicker($name, $label, $required = true, $attributes = [])
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
     * @param array  $attributes
     *
     * @return DatePicker
     */
    public function addDatePicker($name, $label, $attributes = [])
    {
        return $this->addElement('DatePicker', $name, $label, $attributes);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array  $attributes
     *
     * @return mixed
     */
    public function addSelectLanguage($name, $label, $options = [], $attributes = [])
    {
        return $this->addElement('SelectLanguage', $name, $label, $options, $attributes);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array  $options
     * @param array  $attributes
     *
     * @throws Exception
     *
     * @return HTML_QuickForm_element
     */
    public function addSelectAjax($name, $label, $options = [], $attributes = [])
    {
        if (!isset($attributes['url'])) {
            throw new \Exception('select_ajax needs an URL');
        }

        return $this->addElement(
            'select_ajax',
            $name,
            $label,
            $options,
            $attributes
        );
    }

    /**
     * @param string       $name
     * @param string|array $label
     * @param array        $attributes
     *
     * @return DateTimePicker
     */
    public function addDateTimePicker($name, $label, $attributes = [])
    {
        return $this->addElement('DateTimePicker', $name, $label, $attributes);
    }

    /**
     * @param string       $name
     * @param string|array $label
     * @param array        $attributes
     *
     * @return DateTimeRangePicker
     */
    public function addDateTimeRangePicker($name, $label, $attributes = [])
    {
        return $this->addElement('DateTimeRangePicker', $name, $label, $attributes);
    }

    /**
     * @param string $name
     * @param string $value
     * @param array  $attributes
     */
    public function addHidden($name, $value, $attributes = [])
    {
        $this->addElement('hidden', $name, $value, $attributes);
    }

    /**
     * @param string       $name
     * @param string|array $label
     * @param array        $attributes
     * @param bool         $required
     *
     * @return HTML_QuickForm_textarea
     */
    public function addTextarea($name, $label, $attributes = [], $required = false)
    {
        $element = $this->addElement('textarea', $name, $label, $attributes);

        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        return $element;
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $icon          font-awesome
     * @param string $style         default|primary|success|info|warning|danger|link
     * @param string $size          large|default|small|extra-small
     * @param string $class         Example plus is transformed to icon fa fa-plus
     * @param array  $attributes
     * @param bool   $createElement
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
        $attributes = [],
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
     * Returns a button with the primary color and a check mark.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
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
            [],
            $createElement
        );
    }

    /**
     * Returns a cancel button.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
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
            [],
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a "plus" icon.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
     * @param array  $attributes    Additional attributes
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonCreate($label, $name = 'submit', $createElement = false, $attributes = [])
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
     * Returns a button with the primary color and a pencil icon.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
     *
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
            [],
            $createElement
        );
    }

    /**
     * Returns a button with the danger color and a trash icon.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
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
            [],
            $createElement
        );
    }

    /**
     * Returns a move style button.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonMove($label, $name = 'submit', $createElement = false)
    {
        return $this->addButton(
            $name,
            $label,
            'arrow-circle-right',
            'primary',
            null,
            null,
            [],
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a paper-plane icon.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
     * @param array  $attributes
     * @param string $size
     * @param string $class
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonSend(
        $label,
        $name = 'submit',
        $createElement = false,
        $attributes = [],
        $size = 'default',
        $class = ''
    ) {
        return $this->addButton(
            $name,
            $label,
            'paper-plane',
            'primary',
            $size,
            $class,
            $attributes,
            $createElement
        );
    }

    /**
     * Returns a button with the default (grey?) color and a magnifier icon.
     *
     * @param string $label Text appearing on the button
     * @param string $name  Element name (for form treatment purposes)
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
     * Returns a button with the primary color and a right-pointing arrow icon.
     *
     * @param string $label      Text appearing on the button
     * @param string $name       Element name (for form treatment purposes)
     * @param array  $attributes Additional attributes
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonNext($label, $name = 'submit', $attributes = [])
    {
        return $this->addButton(
            $name,
            $label,
            'arrow-right',
            'primary',
            null,
            null,
            $attributes
        );
    }

    /**
     * Returns a button with the primary color and a check mark icon.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
     *
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
            [],
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a check-mark icon.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
     *
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
            [],
            $createElement
        );
    }

    /**
     * Shortcut to filter button.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
     *
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
            [],
            $createElement
        );
    }

    /**
     * Shortcut to reset button.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
     *
     * @return HTML_QuickForm_button
     */
    public function addButtonReset($label, $name = 'reset', $createElement = false)
    {
        $icon = 'eraser';
        $style = 'default';
        $size = 'default';
        $class = null;
        $attributes = [];

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
     * Returns a button with the primary color and an upload icon.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
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
            [],
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a download icon.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
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
            [],
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a magnifier icon.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
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
            [],
            $createElement
        );
    }

    /**
     * Returns a button with the primary color and a copy (double sheet) icon.
     *
     * @param string $label         Text appearing on the button
     * @param string $name          Element name (for form treatment purposes)
     * @param bool   $createElement Whether to use the create or add method
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
            [],
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
    public function addCheckBox($name, $label, $text = '', $attributes = [])
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
    public function addCheckBoxGroup($name, $label, $options = [], $attributes = [])
    {
        $group = [];
        foreach ($options as $value => $text) {
            $attributes['value'] = $value;
            $group[] = $this->createElement(
                'checkbox',
                $value,
                null,
                $text,
                $attributes
            );
        }

        return $this->addGroup($group, $name, $label);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array  $options
     * @param array  $attributes
     *
     * @return HTML_QuickForm_group
     */
    public function addRadio($name, $label, $options = [], $attributes = [])
    {
        $group = [];
        $counter = 1;
        foreach ($options as $key => $value) {
            $attributes['data-order'] = $counter;
            $group[] = $this->createElement('radio', null, null, $value, $key, $attributes);
            $counter++;
        }

        return $this->addGroup($group, $name, $label);
    }

    /**
     * @param string $name
     * @param mixed  $label      String, or array if form element with a comment
     * @param array  $options
     * @param array  $attributes
     *
     * @return HTML_QuickForm_select
     */
    public function addSelect($name, $label, $options = [], $attributes = [])
    {
        return $this->addElement('select', $name, $label, $options, $attributes);
    }

    /**
     * @param $name
     * @param $label
     * @param $collection
     * @param array  $attributes
     * @param bool   $addNoneOption
     * @param string $textCallable  set a function getStringValue() by default __toString()
     *
     * @return HTML_QuickForm_element
     */
    public function addSelectFromCollection(
        $name,
        $label,
        $collection,
        $attributes = [],
        $addNoneOption = false,
        $textCallable = ''
    ) {
        $options = [];

        if ($addNoneOption) {
            $options[0] = get_lang('None');
        }

        if (!empty($collection)) {
            foreach ($collection as $item) {
                $text = $item;
                if (!empty($textCallable)) {
                    $text = $item->$textCallable();
                }
                $options[$item->getId()] = $text;
            }
        }

        return $this->addElement('select', $name, $label, $options, $attributes);
    }

    /**
     * @param string $label
     * @param string $text
     * @param bool   $createElement
     *
     * @return HTML_QuickForm_Element
     */
    public function addLabel($label, $text, $createElement = false)
    {
        if ($createElement) {
            return $this->createElement(
                'label',
                $label,
                $text
            );
        }

        return $this->addElement('label', $label, $text);
    }

    /**
     * @param string $text
     */
    public function addHeader($text)
    {
        if (!empty($text)) {
            $this->addElement('header', $text);
        }
    }

    /**
     * @param string $name
     * @param string $label
     * @param array  $attributes
     *
     * @throws Exception if the file doesn't have an id
     *
     * @return HTML_QuickForm_file
     */
    public function addFile($name, $label, $attributes = [])
    {
        $element = $this->addElement('file', $name, $label, $attributes);
        if (isset($attributes['crop_image'])) {
            $id = $element->getAttribute('id');
            if (empty($id)) {
                throw new Exception('If you use the crop functionality the element must have an id');
            }
            $this->addHtml(
                '
                <div class="form-group" id="'.$id.'-form-group" style="display: none;">
                    <div class="col-sm-offset-2 col-sm-8">
                        <div id="'.$id.'_crop_image" class="cropCanvas thumbnail">
                            <img id="'.$id.'_preview_image">
                        </div>
                        <button class="btn btn-primary" type="button" name="cropButton" id="'.$id.'_crop_button">
                            <em class="fa fa-crop"></em> '.get_lang('CropYourPicture').'
                        </button>
                    </div>
                </div>'
            );
            $this->addHidden($id.'_crop_result', '');
            $this->addHidden($id.'_crop_image_base_64', '');
        }

        return $element;
    }

    /**
     * @param string $snippet
     */
    public function addHtml($snippet)
    {
        if (empty($snippet)) {
            return false;
        }
        $this->addElement('html', $snippet);

        return true;
    }

    /**
     * Draws a panel of options see the course_info/infocours.php page.
     *
     * @param string $name      internal name
     * @param string $title     visible title
     * @param array  $groupList list of group or elements
     */
    public function addPanelOption($name, $title, $groupList)
    {
        $this->addHtml('<div class="panel panel-default">');
        $this->addHtml(
            '
            <div class="panel-heading" role="tab" id="heading-'.$name.'-settings">
                <h4 class="panel-title">
                    <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                       href="#collapse-'.$name.'-settings" aria-expanded="false" aria-controls="collapse-'.$name.'-settings">
        '
        );
        $this->addHtml($title);
        $this->addHtml('</a></h4></div>');
        $this->addHtml('<div id="collapse-'.$name.'-settings" class="panel-collapse collapse" role="tabpanel"
             aria-labelledby="heading-'.$name.'-settings">
            <div class="panel-body">
        ');

        foreach ($groupList as $groupName => $group) {
            // Add group array
            if (!empty($groupName) && is_array($group)) {
                $this->addGroup($group, '', $groupName);
            }
            // Add element
            if ($group instanceof HTML_QuickForm_element) {
                $this->addElement($group);
            }
        }

        $this->addHtml('</div></div>');
        $this->addHtml('</div>');
    }

    /**
     * Adds a HTML-editor to the form.
     *
     * @param string       $name
     * @param string|array $label    The label for the form-element
     * @param bool         $required (optional) Is the form-element required (default=true)
     * @param bool         $fullPage (optional) When it is true, the editor loads completed html code for a full page
     * @param array        $config   (optional) Configuration settings for the online editor
     */
    public function addHtmlEditor(
        $name,
        $label,
        $required = true,
        $fullPage = false,
        $config = []
    ) {
        $attributes = [];
        $attributes['rows'] = isset($config['rows']) ? $config['rows'] : 15;
        $attributes['cols'] = isset($config['cols']) ? $config['cols'] : 80;
        $attributes['cols-size'] = isset($config['cols-size']) ? $config['cols-size'] : [];
        $attributes['class'] = isset($config['class']) ? $config['class'] : [];
        $attributes['id'] = isset($config['id']) ? $config['id'] : '';

        if (empty($attributes['id'])) {
            $attributes['id'] = $name;
        }

        $this->addElement('html_editor', $name, $label, $attributes, $config);
        $this->applyFilter($name, 'trim');
        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        /** @var HtmlEditor $element */
        $element = $this->getElement($name);
        $config['style'] = isset($config['style']) ? $config['style'] : false;
        if ($fullPage) {
            $config['fullPage'] = true;
            // Adds editor_content.css in ckEditor
            $config['style'] = true;
        }

        if ($element->editor) {
            $element->editor->processConfig($config);
        }
    }

    /**
     * Adds a Google Maps Geolocalization field to the form.
     *
     * @param      $name
     * @param      $label
     * @param bool $hideGeoLocalizationDetails
     */
    public function addGeoLocationMapField($name, $label, $dataValue, $hideGeoLocalizationDetails = false)
    {
        $gMapsPlugin = GoogleMapsPlugin::create();
        $geolocalization = $gMapsPlugin->get('enable_api') === 'true';

        if ($geolocalization && $gMapsPlugin->javascriptIncluded === false) {
            $gmapsApiKey = $gMapsPlugin->get('api_key');
            $url = '//maps.googleapis.com/maps/api/js?key='.$gmapsApiKey;
            $this->addHtml('<script type="text/javascript" src="'.$url.'" ></script>');
            $gMapsPlugin->javascriptIncluded = true;
        }

        $this->addElement(
            'text',
            $name,
            $label,
            ['id' => $name]
        );

        $this->addHidden(
            $name.'_coordinates',
            '',
            ['id' => $name.'_coordinates']
        );

        $this->applyFilter($name, 'stripslashes');
        $this->applyFilter($name, 'trim');

        $this->addHtml(Extrafield::getLocalizationJavascript($name, $dataValue));

        if ($hideGeoLocalizationDetails) {
            $this->addHtml('<div style="display:none">');
        }

        $this->addHtml(
            Extrafield::getLocalizationInput($name, $label)
        );

        if ($hideGeoLocalizationDetails) {
            $this->addHtml('</div>');
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
     * Adds a progress loading image to the form.
     */
    public function addProgress($delay = 2, $label = '')
    {
        if (empty($label)) {
            $label = get_lang('PleaseStandBy');
        }
        $this->with_progress_bar = true;
        $id = $this->getAttribute('id');

        $this->updateAttributes("onsubmit=\"javascript: addProgress('".$id."')\"");
        $this->addHtml('<script language="javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/upload.js" type="text/javascript"></script>');
    }

    /**
     * This function has been created for avoiding changes directly within QuickForm class.
     * When we use it, the element is threated as 'required' to be dealt during validation.
     *
     * @param array  $elements The array of elements
     * @param string $message  The message displayed
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
     *
     * @return string $return_value HTML code of the form
     */
    public function returnForm()
    {
        $returnValue = '';

        /** @var HTML_QuickForm_element $element */
        foreach ($this->_elements as &$element) {
            $element->setLayout($this->getLayout());
            $elementError = parent::getElementError($element->getName());
            if (!is_null($elementError)) {
                $returnValue .= Display::return_message($elementError, 'warning').'<br />';
                break;
            }
        }

        $returnValue .= parent::toHtml();
        // Add div-element which is to hold the progress bar
        $id = $this->getAttribute('id');
        if (isset($this->with_progress_bar) && $this->with_progress_bar) {
            // @todo improve UI
            $returnValue .= '<br />
            <div id="loading_div_'.$id.'" class="loading_div" style="display:none;margin-left:40%; margin-top:10px; height:50px;">
                <div class="wobblebar-loader"></div>
            </div>
            ';
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
     *
     * @deprecated use returnForm()
     */
    public function return_form()
    {
        return $this->returnForm();
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
     *
     * @param string $name       The label for the form-element
     * @param string $label      The element name
     * @param bool   $required   Optional. Is the form-element required (default=true)
     * @param array  $attributes Optional. List of attributes for the form-element
     */
    public function addUrl($name, $label, $required = true, $attributes = [])
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
     *
     * @param string $name       The element name
     * @param string $label      The label for the form-element
     * @param bool   $required   Optional. Is the form-element required (default=true)
     * @param array  $attributes Optional. List of attributes for the form-element
     */
    public function addTextLettersOnly(
        $name,
        $label,
        $required = false,
        $attributes = []
    ) {
        $attributes = array_merge(
            $attributes,
            [
                'pattern' => '[a-zA-ZñÑ]+',
                'title' => get_lang('OnlyLetters'),
            ]
        );

        $this->addElement(
            'text',
            $name,
            [
                $label,
                get_lang('OnlyLetters'),
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
     * @param string $name
     * @param string $label
     * @param array  $attributes
     * @param bool   $required
     *
     * @return HTML_QuickForm_element
     */
    public function addNumeric($name, $label, $attributes = [], $required = false)
    {
        $element = $this->addElement('Number', $name, $label, $attributes);

        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        return $element;
    }

    /**
     * Adds a text field for alphanumeric characters to the form.
     * A trim-filter is attached to the field.
     *
     * @param string $name       The element name
     * @param string $label      The label for the form-element
     * @param bool   $required   Optional. Is the form-element required (default=true)
     * @param array  $attributes Optional. List of attributes for the form-element
     */
    public function addTextAlphanumeric(
        $name,
        $label,
        $required = false,
        $attributes = []
    ) {
        $attributes = array_merge(
            $attributes,
            [
                'pattern' => '[a-zA-Z0-9ñÑ]+',
                'title' => get_lang('OnlyLettersAndNumbers'),
            ]
        );

        $this->addElement(
            'text',
            $name,
            [
                $label,
                get_lang('OnlyLettersAndNumbers'),
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
     * @param string $name
     * @param $label
     * @param bool  $required
     * @param array $attributes
     * @param bool  $allowNegative
     * @param int   $minValue
     * @param null  $maxValue
     */
    public function addFloat(
        $name,
        $label,
        $required = false,
        $attributes = [],
        $allowNegative = false,
        $minValue = null,
        $maxValue = null
    ) {
        $this->addElement(
            'FloatNumber',
            $name,
            $label,
            $attributes
        );

        $this->applyFilter($name, 'trim');

        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        // Rule allows "," and "."
        /*$this->addRule(
            $name,
            get_lang('OnlyNumbers'),
            'regex',
            '/(^-?\d\d*\.\d*$)|(^-?\d\d*$)|(^-?\.\d\d*$)|(^-?\d\d*\,\d*$)|(^-?\,\d\d*$)/'
        );*/

        if ($allowNegative == false) {
            $this->addRule(
                $name,
                get_lang('NegativeValue'),
                'compare',
                '>=',
                'server',
                false,
                false,
                0
            );
        }

        if (!is_null($minValue)) {
            $this->addRule(
                $name,
                get_lang('UnderMin'),
                'compare',
                '>=',
                'server',
                false,
                false,
                $minValue
            );
        }

        if (!is_null($maxValue)) {
            $this->addRule(
                $name,
                get_lang('OverMax'),
                'compare',
                '<=',
                'server',
                false,
                false,
                $maxValue
            );
        }
    }

    /**
     * Adds a text field for letters and spaces to the form.
     * A trim-filter is attached to the field.
     *
     * @param string $name       The element name
     * @param string $label      The label for the form-element
     * @param bool   $required   Optional. Is the form-element required (default=true)
     * @param array  $attributes Optional. List of attributes for the form-element
     */
    public function addTextLettersAndSpaces(
        $name,
        $label,
        $required = false,
        $attributes = []
    ) {
        $attributes = array_merge(
            $attributes,
            [
                'pattern' => '[a-zA-ZñÑ\s]+',
                'title' => get_lang('OnlyLettersAndSpaces'),
            ]
        );

        $this->addElement(
            'text',
            $name,
            [
                $label,
                get_lang('OnlyLettersAndSpaces'),
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
     *
     * @param string $name       The element name
     * @param string $label      The label for the form-element
     * @param bool   $required   Optional. Is the form-element required (default=true)
     * @param array  $attributes Optional. List of attributes for the form-element
     */
    public function addTextAlphanumericAndSpaces(
        $name,
        $label,
        $required = false,
        $attributes = []
    ) {
        $attributes = array_merge(
            $attributes,
            [
                'pattern' => '[a-zA-Z0-9ñÑ\s]+',
                'title' => get_lang('OnlyLettersAndNumbersAndSpaces'),
            ]
        );

        $this->addElement(
            'text',
            $name,
            [
                $label,
                get_lang('OnlyLettersAndNumbersAndSpaces'),
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

    /**
     * @param string $url
     * @param string $urlToRedirect after upload redirect to this page
     */
    public function addMultipleUpload($url, $urlToRedirect = '')
    {
        $inputName = 'input_file_upload';
        $this->addMultipleUploadJavascript($url, $inputName, $urlToRedirect);

        $this->addHtml('
            <div class="description-upload">
            '.get_lang('ClickToSelectOrDragAndDropMultipleFilesOnTheUploadField').'
            </div>
            <span class="btn btn-success fileinput-button">
                <i class="glyphicon glyphicon-plus"></i>
                <span>'.get_lang('AddFiles').'</span>
                <!-- The file input field used as target for the file upload widget -->
                <input id="'.$inputName.'" type="file" name="files[]" multiple>
            </span>
            <div id="dropzone">
                <div class="button-load">
                '.get_lang('UploadFiles').'
                </div>
            </div>
            <br />
            <!-- The global progress bar -->
            <div id="progress" class="progress">
                <div class="progress-bar progress-bar-success"></div>
            </div>
            <div id="files" class="files"></div>
        ');
    }

    /**
     * @throws Exception
     */
    public function addNoSamePasswordRule(string $elementName, User $user)
    {
        $passwordRequirements = api_get_configuration_value('password_requirements');

        if (!empty($passwordRequirements) && $passwordRequirements['force_different_password']) {
            $this->addRule(
                $elementName,
                get_lang('NewPasswordCannotBeSameAsCurrent'),
                'no_same_current_password',
                $user
            );
        }
    }

    /**
     * @param string $elementName
     * @param string $groupName   if element is inside a group
     *
     * @throws Exception
     */
    public function addPasswordRule($elementName, $groupName = '')
    {
        // Constant defined in old config/profile.conf.php
        if (CHECK_PASS_EASY_TO_FIND !== true) {
            return;
        }

        $message = get_lang('PassTooEasy').': '.api_generate_password();

        if (empty($groupName)) {
            $this->addRule(
                $elementName,
                $message,
                'callback',
                'api_check_password'
            );

            return;
        }

        $groupObj = $this->getElement($groupName);

        if ($groupObj instanceof HTML_QuickForm_group) {
            $elementName = $groupObj->getElementName($elementName);

            if ($elementName === false) {
                throw new Exception("The $groupName doesn't have the element $elementName");
            }

            $this->_rules[$elementName][] = [
                'type' => 'callback',
                'format' => 'api_check_password',
                'message' => $message,
                'validation' => '',
                'reset' => false,
                'group' => $groupName,
            ];
        }
    }

    /**
     * Add an element with user ID and avatar to the form.
     * It needs a Chamilo\UserBundle\Entity\User as value. The exported value is the Chamilo\UserBundle\Entity\User ID.
     *
     * @see \UserAvatar
     *
     * @param string $name
     * @param string $label
     * @param string $imageSize Optional. Small, medium or large image
     * @param string $subtitle  Optional. The subtitle for the field
     *
     * @return \UserAvatar
     */
    public function addUserAvatar($name, $label, $imageSize = 'small', $subtitle = '')
    {
        return $this->addElement('UserAvatar', $name, $label, ['image_size' => $imageSize, 'sub_title' => $subtitle]);
    }

    public function addCaptcha()
    {
        $ajax = api_get_path(WEB_AJAX_PATH).'form.ajax.php?a=get_captcha';
        $options = [
            'width' => 220,
            'height' => 90,
            'callback' => $ajax.'&var='.basename(__FILE__, '.php'),
            'sessionVar' => basename(__FILE__, '.php'),
            'imageOptions' => [
                'font_size' => 20,
                'font_path' => api_get_path(SYS_FONTS_PATH).'opensans/',
                'font_file' => 'OpenSans-Regular.ttf',
                //'output' => 'gif'
            ],
        ];

        $captcha_question = $this->addElement(
            'CAPTCHA_Image',
            'captcha_question',
            '',
            $options
        );
        $this->addElement('static', null, null, get_lang('ClickOnTheImageForANewOne'));

        $this->addElement(
            'text',
            'captcha',
            get_lang('EnterTheLettersYouSee'),
            ['size' => 40]
        );
        $this->addRule(
            'captcha',
            get_lang('EnterTheCharactersYouReadInTheImage'),
            'required',
            null,
            'client'
        );
        $this->addRule(
            'captcha',
            get_lang('TheTextYouEnteredDoesNotMatchThePicture'),
            'CAPTCHA',
            $captcha_question
        );
    }

    /**
     * @param array $typeList
     */
    public function addEmailTemplate($typeList)
    {
        $mailManager = new MailTemplateManager();
        foreach ($typeList as $type) {
            $list = $mailManager->get_all(
                ['where' => ['type = ? AND url_id = ?' => [$type, api_get_current_access_url_id()]]]
            );

            $options = [get_lang('Select')];
            $name = $type;
            $defaultId = '';
            foreach ($list as $item) {
                $options[$item['id']] = $item['name'];
                $name = $item['name'];
                if (empty($defaultId)) {
                    $defaultId = $item['default_template'] == 1 ? $item['id'] : '';
                }
            }

            $url = api_get_path(WEB_AJAX_PATH).'mail.ajax.php?a=select_option';
            $typeNoDots = 'email_template_option_'.str_replace('.tpl', '', $type);
            $this->addSelect(
                'email_template_option['.$type.']',
                $name,
                $options,
                ['id' => $typeNoDots]
            );

            $templateNoDots = 'email_template_'.str_replace('.tpl', '', $type);
            $templateNoDotsBlock = 'email_template_block_'.str_replace('.tpl', '', $type);
            $this->addHtml('<div id="'.$templateNoDotsBlock.'" style="display:none">');
            $this->addTextarea(
                $templateNoDots,
                get_lang('Preview'),
                ['disabled' => 'disabled ', 'id' => $templateNoDots, 'rows' => '5']
            );
            $this->addHtml('</div>');

            $this->addHtml("<script>
            $(function() {
                var defaultValue = '$defaultId';
                $('#$typeNoDots').val(defaultValue);
                $('#$typeNoDots').selectpicker('render');
                if (defaultValue != '') {
                    var selected = $('#$typeNoDots option:selected').val();
                    $.ajax({
                        url: '$url' + '&id=' + selected+ '&template_name=$type',
                        success: function (data) {
                            $('#$templateNoDots').html(data);
                            $('#$templateNoDotsBlock').show();
                            return;
                        },
                    });
                }

                $('#$typeNoDots').on('change', function(){
                    var selected = $('#$typeNoDots option:selected').val();
                    $.ajax({
                        url: '$url' + '&id=' + selected,
                        success: function (data) {
                            $('#$templateNoDots').html(data);
                            $('#$templateNoDotsBlock').show();
                            return;
                        },
                    });
                });
            });
            </script>");
        }
    }

    public static function getTimepickerIncrement(): int
    {
        $customIncrement = api_get_configuration_value('timepicker_increment');

        if (false !== $customIncrement) {
            return (int) $customIncrement;
        }

        return self::TIMEPICKER_INCREMENT_DEFAULT;
    }

    /**
     * @param string $url           page that will handle the upload
     * @param string $inputName
     * @param string $urlToRedirect
     */
    private function addMultipleUploadJavascript($url, $inputName, $urlToRedirect = '')
    {
        $target = '_blank';
        if (!empty($_SESSION['oLP']->lti_launch_id)) {
            $target = '_self';
        }
        $redirectCondition = '';
        if (!empty($urlToRedirect)) {
            $redirectCondition = "window.location.replace('$urlToRedirect'); ";
        }
        $maxFileSize = getIniMaxFileSizeInBytes();
        $icon = Display::return_icon('file_txt.gif');
        $errorUploadMessage = get_lang('FileSizeIsTooBig').' '.get_lang('MaxFileSize').' : '.getIniMaxFileSizeInBytes(true);
        $this->addHtml("
        <script>
        $(function () {
            'use strict';
            $('#".$this->getAttribute('id')."').submit(function() {
                return false;
            });

            $('#dropzone').on('click', function() {
                $('#".$inputName."').click();
            });

            var url = '".$url."';
            var uploadButton = $('<button/>')
                .addClass('btn btn-primary')
                .prop('disabled', true)
                .text('".addslashes(get_lang('Loading'))."')
                .on('click', function () {
                    var \$this = $(this),
                    data = \$this.data();
                    \$this
                        .off('click')
                        .text('".addslashes(get_lang('Cancel'))."')
                        .on('click', function () {
                            \$this.remove();
                            data.abort();
                        });
                    data.submit().always(function () {
                        \$this.remove();
                    });
                });

            var maxFileSize = parseInt('".$maxFileSize."');
            var counter = 0,
                total = 0;
            $('#".$inputName."').fileupload({
                url: url,
                dataType: 'json',
                // Enable image resizing, except for Android and Opera,
                // which actually support image resizing, but fail to
                // send Blob objects via XHR requests:
                disableImageResize: /Android(?!.*Chrome)|Opera/.test(window.navigator.userAgent),
                previewMaxWidth: 300,
                previewMaxHeight: 169,
                previewCrop: true,
                dropZone: $('#dropzone'),
                maxChunkSize: 10000000, // 10 MB
                sequentialUploads: true,
            }).on('fileuploadchunksend', function (e, data) {
                console.log('fileuploadchunkbeforesend');
                console.log(data);
                data.url = url + '&chunkAction=send';
            }).on('fileuploadchunkdone', function (e, data) {
                console.log('fileuploadchunkdone');
                console.log(data);
                if (data.uploadedBytes >= data.total) {
                    data.url = url + '&chunkAction=done';
                    data.submit();
                }
            }).on('fileuploadchunkfail', function (e, data) {
                console.log('fileuploadchunkfail');
                console.log(data);

            }).on('fileuploadadd', function (e, data) {
                data.context = $('<div class=\"row\" />').appendTo('#files');
                var errs = [];
                $.each(data.files, function (index, file) {
                    // check size
                    if (maxFileSize > 0 && data.files[index]['size'] > maxFileSize) {
                        errs.push(\"".$errorUploadMessage."\");
                    } else {
                        // array for all errors
                        var node = $('<div class=\"col-sm-5 file_name\">').text(file.name);
                        node.appendTo(data.context);
                        var iconLoading = $('<div class=\"col-sm-3\">').html(
                            $('<span id=\"image-loading'+index+'\"/>').html('".Display::return_icon('loading1.gif', get_lang('Uploading'), [], ICON_SIZE_MEDIUM)."')
                        );
                        $(data.context.children()[index]).parent().append(iconLoading);
                        total++;
                    }
                });

                // Output errors or submit data
                if (errs.length > 0) {
                    alert(\"".get_lang('AnErrorOccured')."\\n\" + errs.join(' '));
                    return false;
                } else {
                    data.submit();
                }

            }).on('fileuploadprocessalways', function (e, data) {
                var index = data.index,
                    file = data.files[index],
                    node = $(data.context.children()[index]);

                if (maxFileSize > 0 && data.files[index]['size'] > maxFileSize) {
                    return false;
                }
                if (file.preview) {
                    data.context.prepend($('<div class=\"col-sm-4\">').html(file.preview));
                } else {
                    data.context.prepend($('<div class=\"col-sm-4\">').html('".$icon."'));
                }
                if (index + 1 === data.files.length) {
                    data.context.find('button')
                        .text('Upload')
                        .prop('disabled', !!data.files.error);
                }
            }).on('fileuploadprogressall', function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10) - 2;
                $('#progress .progress-bar').css(
                    'width',
                    progress + '%'
                );
                $('#progress .progress-bar').text(progress + '%');
            }).on('fileuploaddone', function (e, data) {
                $.each(data.result.files, function (index, file) {
                    if (file.error) {
                        var link = $('<div>')
                            .attr({class : 'panel-image'})                            ;
                        $(data.context.children()[index]).parent().wrap(link);
                        // Update file name with new one from Chamilo
                        $(data.context.children()[index]).parent().find('.file_name').html(file.name);
                        var message = $('<div class=\"col-sm-3\">').html(
                            $('<span class=\"message-image-danger\"/>').text(file.error)
                        );
                        $(data.context.children()[index]).parent().append(message);

                        return;
                    }
                    if (file.url) {
                        var link = $('<a>')
                            .attr({target: '".$target."', class : 'panel-image'})
                            .prop('href', file.url);
                        $(data.context.children()[index]).parent().wrap(link);
                    }
                    // Update file name with new one from Chamilo
                    $(data.context.children()[index]).parent().find('.file_name').html(file.name);
                    $('#image-loading'+index).remove();
                    var message = $('<div class=\"col-sm-3\">').html(
                        $('<span class=\"message-image-success\"/>').text('".addslashes(get_lang('UplUploadSucceeded'))."')
                    );
                    $(data.context.children()[index]).parent().append(message);
                    counter++;
                });
                if (counter == total) {
                    $('#progress .progress-bar').css('width', '100%');
                    $('#progress .progress-bar').text('100%');
                }
                $('#dropzone').removeClass('hover');
                ".$redirectCondition."
            }).on('fileuploadfail', function (e, data) {
                $.each(data.files, function (index) {
                    var failedMessage = '".addslashes(get_lang('UplUploadFailed'))."';
                    var error = $('<div class=\"col-sm-3\">').html(
                        $('<span class=\"alert alert-danger\"/>').text(failedMessage)
                    );
                    $(data.context.children()[index]).parent().append(error);
                });
                $('#dropzone').removeClass('hover');
            }).prop('disabled', !$.support.fileInput).parent().addClass($.support.fileInput ? undefined : 'disabled');

            $('#dropzone').on('dragover', function (e) {
                // dragleave callback implementation
                $('#dropzone').addClass('hover');
            });

            $('#dropzone').on('dragleave', function (e) {
                $('#dropzone').removeClass('hover');
            });
            $('.fileinput-button').hide();
        });
        </script>");
    }
}

/**
 * Cleans HTML text filter.
 *
 * @param string $html HTML to clean
 * @param int    $mode (optional)
 *
 * @return string The cleaned HTML
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
 * Cleans mobile phone number text.
 *
 * @param string $mobilePhoneNumber Mobile phone number to clean
 *
 * @return string The cleaned mobile phone number
 */
function mobile_phone_number_filter($mobilePhoneNumber)
{
    $mobilePhoneNumber = str_replace(['+', '(', ')'], '', $mobilePhoneNumber);

    return ltrim($mobilePhoneNumber, '0');
}

/**
 * Cleans JS from a URL.
 *
 * @param string $html URL to clean
 * @param int    $mode (optional)
 *
 * @return string The cleaned URL
 */
function plain_url_filter($html, $mode = NO_HTML)
{
    $allowed_tags = HTML_QuickForm_Rule_HTML::get_allowed_tags($mode);
    $html = kses_no_null($html);
    $html = kses_js_entities($html);
    $allowed_html_fixed = kses_array_lc($allowed_tags);

    return kses_split($html, $allowed_html_fixed, ['http', 'https']);
}
