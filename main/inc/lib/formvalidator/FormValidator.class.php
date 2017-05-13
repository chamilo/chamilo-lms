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
     * @param string $name Name of the form
     * @param string $method (optional) Method ('post' (default) or 'get')
     * @param string $action (optional) Action (default is $PHP_SELF)
     * @param string $target (optional) Form's target defaults to '_self'
     * @param mixed $attributes (optional) Extra attributes for <form> tag
     * @param string $layout
     * @param bool $trackSubmit (optional) Whether to track if the form was
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
        $this->setRequiredNote('<span class="form_required">*</span> <small>'.get_lang('ThisFieldIsRequired').'</small>');
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
     * @todo this function should be added in the element class
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
     * @param string $label The label for the form-element
     * @param string $name The element name
     * @param bool $required (optional)    Is the form-element required (default=true)
     * @param array $attributes (optional)    List of attributes for the form-element
     * @return HTML_QuickForm_text
     */
    public function addText($name, $label, $required = true, $attributes = array())
    {
        $element = $this->addElement('text', $name, $label, $attributes);
        $this->applyFilter($name, 'trim');
        if ($required) {
            $this->addRule($name, get_lang('ThisFieldIsRequired'), 'required');
        }

        return $element;
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
    public function addSelectLanguage($name, $label, $options = [], $attributes = [])
    {
        return $this->addElement('SelectLanguage', $name, $label, $options, $attributes);
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @param array $attributes
     * @throws
     */
    public function addSelectAjax($name, $label, $options = [], $attributes = [])
    {
        if (!isset($attributes['url'])) {
            throw new \Exception('select_ajax needs an URL');
        }
        $this->addElement(
            'select_ajax',
            $name,
            $label,
            $options,
            $attributes
        );
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
     * Returns a move style button
     * @param string $label Text appearing on the button
     * @param string $name Element name (for form treatment purposes)
     * @param bool $createElement Whether to use the create or add method
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
    public function addButtonSend($label, $name = 'submit', $createElement = false, $attributes = array())
    {
        return $this->addButton(
            $name,
            $label,
            'paper-plane',
            'primary',
            null,
            null,
            $attributes,
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
    public function addButtonNext($label, $name = 'submit', $attributes = array())
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
     * @param $name
     * @param $label
     * @param $collection
     * @param array $attributes
     * @param bool $addNoneOption
     * @param string $textCallable set a function getStringValue() by default __toString()
     *
     * @return HTML_QuickForm_element
     */
    public function addSelectFromCollection(
        $name,
        $label,
        $collection,
        $attributes = array(),
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
     * @throws Exception if the file doesn't have an id
     */
    public function addFile($name, $label, $attributes = array())
    {
        $element = $this->addElement('file', $name, $label, $attributes);
        if (isset($attributes['crop_image'])) {
            $id = $element->getAttribute('id');
            if (empty($id)) {
                throw new Exception('If you use the crop functionality the element must have an id');
            }
            $this->addHtml('
                <div class="form-group">
                <label for="cropImage" id="'.$id.'_label_crop_image" class="col-sm-2 control-label"></label>
                <div class="col-sm-8">
                <div id="'.$id.'_crop_image" class="cropCanvas">
                <img id="'.$id.'_preview_image">
                </div>
                <div>
                <button class="btn btn-primary hidden" name="cropButton" id="'.$id.'_crop_button" >
                    <em class="fa fa-crop"></em> '.
                    get_lang('CropYourPicture').'
                </button>
                </div>
                </div>
                </div>'
            );
            $this->addHidden($id.'_crop_result', '');
            $this->addHidden($id.'_crop_image_base_64', '');
        }
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
    public function addHtmlEditor(
        $name,
        $label,
        $required = true,
        $fullPage = false,
        $config = [],
        $style = false
    ) {
        $attributes = [];
        $attributes['rows'] = isset($config['rows']) ? $config['rows'] : 15;
        $attributes['cols'] = isset($config['cols']) ? $config['cols'] : 80;
        $attributes['cols-size'] = isset($config['cols-size']) ? $config['cols-size'] : [];
        $attributes['class'] = isset($config['class']) ? $config['class'] : [];

        $this->addElement('html_editor', $name, $label, $attributes, $config);
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
     * Adds a Google Maps Geolocalization field to the form
     *
     * @param $name
     * @param $label
     */
    public function addGeoLocationMapField($name, $label)
    {
        $gMapsPlugin = GoogleMapsPlugin::create();
        $geolocalization = $gMapsPlugin->get('enable_api') === 'true';

        if ($geolocalization) {
            $gmapsApiKey = $gMapsPlugin->get('api_key');
            $this->addHtml('<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key='.$gmapsApiKey.'" ></script>');
        }
        $this->addElement(
            'text',
            $name,
            $label,
            ['id' => $name]
        );
        $this->applyFilter($name, 'stripslashes');
        $this->applyFilter($name, 'trim');
        $this->addHtml('
                            <div class="form-group">
                                <label for="geolocalization_'.$name.'" class="col-sm-2 control-label"></label>
                                <div class="col-sm-8">
                                    <button class="null btn btn-default " id="geolocalization_'.$name.'" name="geolocalization_'.$name.'" type="submit"><em class="fa fa-map-marker"></em> '.get_lang('Geolocalization').'</button>
                                    <button class="null btn btn-default " id="myLocation_'.$name.'" name="myLocation_'.$name.'" type="submit"><em class="fa fa-crosshairs"></em> '.get_lang('MyLocation').'</button>
                                </div>
                            </div>
                        ');

        $this->addHtml('
                            <div class="form-group">
                                <label for="map_'.$name.'" class="col-sm-2 control-label">
                                    '.$label.' - '.get_lang('Map').'
                                </label>
                                <div class="col-sm-8">
                                    <div name="map_'.$name.'" id="map_'.$name.'" style="width:100%; height:300px;">
                                    </div>
                                </div>
                            </div>
                        ');

        $this->addHtml(
            '<script>
                $(document).ready(function() {

                    if (typeof google === "object") {

                        var address = $("#' . $name.'").val();
                        initializeGeo'.$name.'(address, false);

                        $("#geolocalization_'.$name.'").on("click", function() {
                            var address = $("#'.$name.'").val();
                            initializeGeo'.$name.'(address, false);
                            return false;
                        });

                        $("#myLocation_'.$name.'").on("click", function() {
                            myLocation'.$name.'();
                            return false;
                        });

                        $("#'.$name.'").keypress(function (event) {
                            if (event.which == 13) {
                                $("#geolocalization_'.$name.'").click();
                                return false;
                            }
                        });

                    } else {
                        $("#map_'.$name.'").html("<div class=\"alert alert-info\">'.get_lang('YouNeedToActivateTheGoogleMapsPluginInAdminPlatformToSeeTheMap').'</div>");
                    }

                });

                function myLocation'.$name.'() {
                    if (navigator.geolocation) {
                        var geoPosition = function(position) {
                            var lat = position.coords.latitude;
                            var lng = position.coords.longitude;
                            var latLng = new google.maps.LatLng(lat, lng);
                            initializeGeo'.$name.'(false, latLng)
                        };

                        var geoError = function(error) {
                            alert("Geocode ' . get_lang('Error').': " + error);
                        };

                        var geoOptions = {
                            enableHighAccuracy: true
                        };

                        navigator.geolocation.getCurrentPosition(geoPosition, geoError, geoOptions);
                    }
                }

                function initializeGeo'.$name.'(address, latLng) {
                    var geocoder = new google.maps.Geocoder();
                    var latlng = new google.maps.LatLng(-34.397, 150.644);
                    var myOptions = {
                        zoom: 15,
                        center: latlng,
                        mapTypeControl: true,
                        mapTypeControlOptions: {
                            style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
                        },
                        navigationControl: true,
                        mapTypeId: google.maps.MapTypeId.ROADMAP
                    };

                    map_'.$name.' = new google.maps.Map(document.getElementById("map_'.$name.'"), myOptions);

                    var parameter = address ? { "address": address } : latLng ? { "latLng": latLng } : false;

                    if (geocoder && parameter) {
                        geocoder.geocode(parameter, function(results, status) {
                            if (status == google.maps.GeocoderStatus.OK) {
                                if (status != google.maps.GeocoderStatus.ZERO_RESULTS) {
                                    map_'.$name.'.setCenter(results[0].geometry.location);
                                    if (!address) {
                                        $("#'.$name.'").val(results[0].formatted_address);
                                    }
                                    var infowindow = new google.maps.InfoWindow({
                                        content: "<b>" + $("#'.$name.'").val() + "</b>",
                                        size: new google.maps.Size(150, 50)
                                    });

                                    var marker = new google.maps.Marker({
                                        position: results[0].geometry.location,
                                        map: map_'.$name.',
                                        title: $("#'.$name.'").val()
                                    });
                                    google.maps.event.addListener(marker, "click", function() {
                                        infowindow.open(map_'.$name.', marker);
                                    });
                                } else {
                                    alert("' . get_lang("NotFound").'");
                                }

                            } else {
                                alert("Geocode ' . get_lang('Error').': '.get_lang("AddressField").' '.get_lang("NotFound").'");
                            }
                        });
                    }
                }
            </script>
        ');

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
     *
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
     * @param array $elements The array of elements
     * @param string $message The message displayed
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
        $returnValue = '';

        /** @var HTML_QuickForm_element $element */
        foreach ($this->_elements as $element) {
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
            $icon = Display::return_icon('progress_bar.gif');

            // @todo improve UI
            $returnValue .= '<br />

            <div id="loading_div_'.$id.'" class="loading_div" style="display:none;margin-left:40%; margin-top:10px; height:50px;">
                '.$icon.'
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
     * @param string $name The label for the form-element
     * @param string $label The element name
     * @param bool $required Optional. Is the form-element required (default=true)
     * @param array $attributes Optional. List of attributes for the form-element
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
     * @param bool $required Optional. Is the form-element required (default=true)
     * @param array $attributes Optional. List of attributes for the form-element
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
     * @param bool $required Optional. Is the form-element required (default=true)
     * @param array $attributes Optional. List of attributes for the form-element
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
     * @param string $name
     * @param $label
     * @param bool $required
     * @param array $attributes
     * @param bool $allowNegative
     * @param integer $minValue
     * @param null $maxValue
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
     * @param string $name The element name
     * @param string $label The label for the form-element
     * @param bool $required Optional. Is the form-element required (default=true)
     * @param array $attributes Optional. List of attributes for the form-element
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
     * @param bool $required Optional. Is the form-element required (default=true)
     * @param array $attributes Optional. List of attributes for the form-element
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

    /**
     * @param string $url
     */
    public function addMultipleUpload($url)
    {
        $inputName = 'input_file_upload';
        $this->addMultipleUploadJavascript($url, $inputName);

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
     *
     * @param string $url page that will handle the upload
     * @param string $inputName
     */
    private function addMultipleUploadJavascript($url, $inputName)
    {
        $icon = Display::return_icon('file_txt.gif');
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
                
            $('#".$inputName."').fileupload({
                url: url,
                dataType: 'json',
                // Enable image resizing, except for Android and Opera,
                // which actually support image resizing, but fail to
                // send Blob objects via XHR requests:
                disableImageResize: /Android(?!.*Chrome)|Opera/.test(window.navigator.userAgent),
                previewMaxWidth: 100,
                previewMaxHeight: 100,
                previewCrop: true,
                dropzone: $('#dropzone'),                                
            }).on('fileuploadadd', function (e, data) {
                data.context = $('<div class=\"row\" style=\"margin-bottom:35px\" />').appendTo('#files');
                $.each(data.files, function (index, file) {
                    var node = $('<div class=\"col-sm-5\">').text(file.name);                    
                    node.appendTo(data.context);
                });
            }).on('fileuploadprocessalways', function (e, data) {
                var index = data.index,
                    file = data.files[index],
                    node = $(data.context.children()[index]);
                if (file.preview) {
                    data.context
                        .prepend($('<div class=\"col-sm-2\">').html(file.preview))
                    ;
                } else {
                    data.context
                        .prepend($('<div class=\"col-sm-2\">').html('".$icon."'))
                    ;
                }
                if (index + 1 === data.files.length) {
                    data.context.find('button')
                        .text('Upload')
                        .prop('disabled', !!data.files.error);
                }
            }).on('fileuploadprogressall', function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                $('#progress .progress-bar').css(
                    'width',
                    progress + '%'
                );
            }).on('fileuploaddone', function (e, data) {
                $.each(data.result.files, function (index, file) {
                    if (file.url) {
                        var link = $('<a>')
                            .attr('target', '_blank')
                            .prop('href', file.url);
                        $(data.context.children()[index]).parent().wrap(link);                        
                        var successMessage = $('<div class=\"col-sm-3\">').html($('<span class=\"alert alert-success\"/>').text('" . addslashes(get_lang('UplUploadSucceeded'))."'));
                        $(data.context.children()[index]).parent().append(successMessage);
                    } else if (file.error) {
                        var error = $('<div class=\"col-sm-3\">').html($('<span class=\"alert alert-danger\"/>').text(file.error));
                        $(data.context.children()[index]).parent().append(error);
                    }
                });
                $('#dropzone').removeClass('hover');
            }).on('fileuploadfail', function (e, data) {
                $.each(data.files, function (index) {
                    var failedMessage = '" . addslashes(get_lang('UplUploadFailed'))."';
                    var error = $('<div class=\"col-sm-3\">').html($('<span class=\"alert alert-danger\"/>').text(failedMessage));
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

    /**
     * @param string $elementName
     * @param string $groupName if element is inside a group
     * @throws Exception
     */
    public function addPasswordRule($elementName, $groupName = '')
    {
        // Constant defined in old config/profile.conf.php
        if (CHECK_PASS_EASY_TO_FIND === true) {
            $message = get_lang('PassTooEasy').': '.api_generate_password();

            if (!empty($groupName)) {
                $groupObj = $this->getElement($groupName);

                if ($groupObj instanceof HTML_QuickForm_group) {
                    $elementName = $groupObj->getElementName($elementName);

                    if ($elementName === false) {
                        throw new Exception("The $groupName doesn't have the element $elementName");
                    }

                    $this->_rules[$elementName][] = array(
                        'type' => 'callback',
                        'format' => 'api_check_password',
                        'message' => $message,
                        'validation' => '',
                        'reset' => false,
                        'group' => $groupName
                    );
                }
            } else {
                $this->addRule(
                    $elementName,
                    $message,
                    'callback',
                    'api_check_password'
                );
            }
        }
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

    return ltrim($mobilePhoneNumber, '0');
}
