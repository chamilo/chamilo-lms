<?php

abstract class ChamiloForm
{
    protected $_form;
    protected $_mode;
    protected $_cancelurl;
    protected $_customdata;

    public function __construct($mode, $returnurl, $cancelurl, $customdata)
    {
        $this->_mode = $mode;
        $this->_cancelurl = $cancelurl;
        $this->_customdata = $customdata;

        $attributes = array('style' => 'width: 60%; float: '.($text_dir == 'rtl' ? 'right;' : 'left;'));
        // $this->_form = new FormValidator($mode.'_instance', 'post', $returnurl, '', $attributes, true);
        $this->_form = new FormValidator($mode.'_instance', 'post', $returnurl, '', $attributes);
    }

    abstract function definition();
    abstract function validation($data, $files = null);

    function validate() {
        return $this->_form->validate();
    }

    function display() {
        return $this->_form->display();
    }

    function definition_after_data(){
    }

    function return_form(){
        return $this->_form->toHtml();
    }

    function is_in_add_mode(){
        return $this->_mode == 'add';
    }

    /**
     * Use this method to a cancel and submit button to the end of your form. Pass a param of false
     * if you don't want a cancel button in your form. If you have a cancel button make sure you
     * check for it being pressed using is_cancelled() and redirecting if it is true before trying to
     * get data with get_data().
     *
     * @param boolean $cancel whether to show cancel button, default true
     * @param string $submitlabel label for submit button, defaults to get_string('savechanges')
     */
    public function add_action_buttons($cancel = true, $submitlabel = null, $cancellabel = null)
    {
        // TODO : refine lang fetch to effective global strings.
        if (is_null($submitlabel)){
            $submitlabel = get_lang('save');
        }

        if (is_null($cancellabel)){
            $submitlabel = get_lang('cancel');
        }

        $cform =& $this->_form;
        if ($cancel){
            //when two elements we need a group
            $buttonarray = array();
            $buttonarray[] = &$cform->createElement('submit', 'submitbutton', $submitlabel);
            //$buttonarray[] = &$cform->createElement('cancel', $cancellabel, $this->_cancelurl);
            $cform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        } else {
            //no group needed
            $cform->addElement('submit', 'submitbutton', $submitlabel);
        }
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @param bool $slashed true means return data with addslashes applied
     * @return object submitted data; NULL if not valid or not submitted
     */
    public function get_data($slashed=true)
    {
        $cform =& $this->_form;

        if ($this->is_submitted() and $this->is_validated()) {
            $data = $cform->exportValues(null, $slashed);
            unset($data['sesskey']); // we do not need to return sesskey
            unset($data['_qf__'.$this->_formname]);   // we do not need the submission marker too
            if (empty($data)) {
                return NULL;
            } else {
                return (object)$data;
            }
        } else {
            return NULL;
        }
    }

    /**
     * Return submitted data without validation or NULL if there is no submitted data.
     *
     * @param bool $slashed true means return data with addslashes applied
     * @return object submitted data; NULL if not submitted
     */
    public function get_submitted_data($slashed=true)
    {
        $cform =& $this->_form;

        if ($this->is_submitted()) {
            $data = $cform->exportValues(null, $slashed);
            unset($data['sesskey']); // we do not need to return sesskey
            unset($data['_qf__'.$this->_formname]);   // we do not need the submission marker too
            if (empty($data)) {
                return NULL;
            } else {
                return (object)$data;
            }
        } else {
            return NULL;
        }
    }

    /**
     * Check that form was submitted. Does not check validity of submitted data.
     *
     * @return bool true if form properly submitted
     */
    public function is_submitted()
    {
        return $this->_form->isSubmitted();
    }

    /**
     * Return true if a cancel button has been pressed resulting in the form being submitted.
     *
     * @return bool true if a cancel button has been pressed
     */
    public function is_cancelled()
    {
        $cform =& $this->_form;
        if ($cform->isSubmitted()){
            foreach ($cform->_cancelButtons as $cancelbutton){
                if (optional_param($cancelbutton, 0, PARAM_RAW)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check that form data is valid.
     * You should almost always use this, rather than {@see validate_defined_fields}
     *
     * @return bool true if form data valid
     */
    public function is_validated()
    {
        //finalize the form definition before any processing
        if (!$this->_definition_finalized) {
            $this->_definition_finalized = true;
            $this->definition_after_data();
        }
        return $this->validate_defined_fields();
    }

    /**
     * Validate the form.
     *
     * You almost always want to call {@see is_validated} instead of this
     * because it calls {@see definition_after_data} first, before validating the form,
     * which is what you want in 99% of cases.
     *
     * This is provided as a separate function for those special cases where
     * you want the form validated before definition_after_data is called
     * for example, to selectively add new elements depending on a no_submit_button press,
     * but only when the form is valid when the no_submit_button is pressed,
     *
     * @param boolean $validateonnosubmit optional, defaults to false.  The default behaviour
     *                is NOT to validate the form when a no submit button has been pressed.
     *                pass true here to override this behaviour
     *
     * @return bool true if form data valid
     */
    public function validate_defined_fields($validateonnosubmit=false)
    {
        static $validated = null; // one validation is enough
        $cform =& $this->_form;

        if ($this->no_submit_button_pressed() && empty($validateonnosubmit)){
            return false;
        } elseif ($validated === null) {
            $internal_val = $cform->validate();

            $files = array();
            $file_val = $this->_validate_files($files);
            if ($file_val !== true) {
                if (!empty($file_val)) {
                    foreach ($file_val as $element => $msg) {
                        $cform->setElementError($element, $msg);
                    }
                }
                $file_val = false;
            }

            $data = $cform->exportValues(null, true);
            $chamilo_val = $this->validation($data, $files);
            if ((is_array($chamilo_val) && count($chamilo_val)!==0)) {
                // non-empty array means errors
                foreach ($chamilo_val as $element => $msg) {
                    $cform->setElementError($element, $msg);
                }
                $chamilo_val = false;

            } else {
                // anything else means validation ok
                $chamilo_val = true;
            }

            $validated = ($internal_val and $chamilo_val and $file_val);
        }
        return $validated;
    }

    public function no_submit_button_pressed()
    {
        static $nosubmit = null; // one check is enough

        if (!is_null($nosubmit)){
            return $nosubmit;
        }

        $cform =& $this->_form;
        $nosubmit = false;
        if (!$this->is_submitted()){
            return false;
        }
        /*
        foreach ($cform->_noSubmitButtons as $nosubmitbutton){
            if (optional_param($nosubmitbutton, 0, PARAM_RAW)){
                $nosubmit = true;
                break;
            }
        }
        return $nosubmit;
        */
        return false;
    }

    /**
     * Load in existing data as form defaults. Usually new entry defaults are stored directly in
     * form definition (new entry form); this function is used to load in data where values
     * already exist and data is being edited (edit entry form).
     *
     * @param mixed $default_values object or array of default values
     * @param bool $slashed true if magic quotes applied to data values
     */
    public function set_data($default_values, $slashed=false)
    {
        if (is_object($default_values)) {
            $default_values = (array)$default_values;
        }
        $filter = $slashed ? 'stripslashes' : NULL;
        $this->_form->setDefaults($default_values, $filter);
    }

    /**
     * Internal method. Validates all uploaded files.
     */
    public function _validate_files(&$files)
    {
        $files = array();

        if (empty($_FILES)) {
            // we do not need to do any checks because no files were submitted
            // note: server side rules do not work for files - use custom verification in validate() instead
            return true;
        }
        $errors = array();
        $mform =& $this->_form;

        // check the files
        $status = $this->_upload_manager->preprocess_files();

        // now check that we really want each file
        foreach ($_FILES as $elname=>$file) {
            if ($mform->elementExists($elname) and $mform->getElementType($elname)=='file') {
                $required = $mform->isElementRequired($elname);
                if (!empty($this->_upload_manager->files[$elname]['uploadlog']) and empty($this->_upload_manager->files[$elname]['clear'])) {
                    if (!$required and $file['error'] == UPLOAD_ERR_NO_FILE) {
                        // file not uploaded and not required - ignore it
                        continue;
                    }
                    $errors[$elname] = $this->_upload_manager->files[$elname]['uploadlog'];

                } else if (!empty($this->_upload_manager->files[$elname]['clear'])) {
                    $files[$elname] = $this->_upload_manager->files[$elname]['tmp_name'];
                }
            } else {
                error('Incorrect upload attempt!');
            }
        }

        // return errors if found
        if ($status and 0 == count($errors)){
            return true;

        } else {
            $files = array();
            return $errors;
        }
    }
}

class InstanceForm extends ChamiloForm
{
    public $_plugin;

    public function __construct($plugin, $mode = 'add', $returnurl = null, $cancelurl = null)
    {
        global $_configuration;

        $this->_plugin = $plugin;

        $returnurl = $_configuration['root_web'].'plugin/vchamilo/views/editinstance.php';
        $cancelurl = $_configuration['root_web'].'plugin/vchamilo/views/manage.php';
        parent::__construct($mode, $returnurl, $cancelurl);
    }

    function definition()
    {
        global $_configuration;

        $cform = $this->_form;

        // Settings variables.
        $size_input_text        = 'size="30"';
        $size_input_text_big    = 'size="60"';

        /*
         * Host's id.
         */
        $cform->addElement('hidden', 'vid');
        $cform->addElement('hidden', 'what', $this->_mode.'instance');
        $cform->addElement('hidden', 'registeronly');

        /*
         * Features fieldset.
         */
        $cform->addElement('header', $this->_plugin->get_lang('hostdefinition'));
        // Name.
        $cform->addElement('text', 'sitename', $this->_plugin->get_lang('sitename'), $size_input_text);
        $cform->applyFilter('sitename', 'trim');

        // Shortname.
        $cform->addElement('text', 'institution', $this->_plugin->get_lang('institution'), ($this->mode == 'edit' ? 'disabled="disabled" ' : ''));
        $cform->applyFilter('institution', 'trim');

        // Host's name.
        $cform->addElement('text', 'root_web', $this->_plugin->get_lang('rootweb'), ($this->mode == 'edit' ? 'disabled="disabled" ' : '').$size_input_text);
        $cform->applyFilter('root_web', 'trim');

        /*
         * Database fieldset.
         */
        $cform->addElement('header', $this->_plugin->get_lang('dbgroup'));

        // Database host.
        $cform->addElement('text', 'db_host', $this->_plugin->get_lang('dbhost'), array('id' => 'id_vdbhost'));
        $cform->applyFilter('db_host', 'trim');

        // Database login.
        $cform->addElement('text', 'db_user', $this->_plugin->get_lang('dbuser'), array('id' => 'id_vdbuser'));
        $cform->applyFilter('db_user', 'trim');

        // Database password.
        $cform->addElement('password', 'db_password', $this->_plugin->get_lang('dbpassword'), array('id' => 'id_vdbpassword'));

        // Button for testing database connection.
        $cform->addElement('button', 'testconnection', $this->_plugin->get_lang('testconnection'), 'onclick="opencnxpopup(\''.$_configuration['root_web'].'\'); return false;"');

        // Database name.
        $cform->addElement('text', 'main_database', $this->_plugin->get_lang('maindatabase'));


        // Table's prefix.
        $cform->addElement('text', 'table_prefix', $this->_plugin->get_lang('tableprefix'));

        // Db's prefix.
        $cform->addElement('text', 'db_prefix', $this->_plugin->get_lang('dbprefix'));

        /*
         * data fieldset.
         */
        $cform->addElement('header', $this->_plugin->get_lang('datalocation'));

        // Path for "moodledata".
        $cform->addElement('text', 'course_folder', $this->_plugin->get_lang('coursefolder'), array('size' => $size_input_text_big, 'id' => 'id_vdatapath'));

        // Button for testing datapath.
        $cform->addElement('button', 'testdatapath', $this->_plugin->get_lang('testdatapath'), 'onclick="opendatapathpopup(\''.$_configuration['root_web'].'\'); return true;"');

        /*
         * Template selection.
         */
        if ($this->is_in_add_mode()) {
            $cform->addElement('header', $this->_plugin->get_lang('templating'));

            $templateoptions = vchamilo_get_available_templates();

            // Template choice
            $cform->addElement('select', 'template', $this->_plugin->get_lang('template'), $templateoptions);
        }

        $submitstr = $this->_plugin->get_lang('savechanges');
        $cancelstr = $this->_plugin->get_lang('cancel');
        $this->add_action_buttons(true, $submitstr, $cancelstr);

        // Rules for the add mode.
        if($this->is_in_add_mode()) {
            $cform->addRule('sitename', $this->_plugin->get_lang('sitenameinputerror'), 'required', null, 'client');
            $cform->addRule('institution', $this->_plugin->get_lang('institutioninputerror'), 'required', null, 'client');
            $cform->addRule('root_web', $this->_plugin->get_lang('rootwebinputerror'), 'required', null, 'client');
            $cform->addRule('main_database', $this->_plugin->get_lang('databaseinputerror'), 'required', null, 'client');
            $cform->addRule('course_folder', $this->_plugin->get_lang('coursefolderinputerror'), 'required', null, 'client');
        }
    }

    function validation($data, $files = null)
    {
        global $plugininstance;

        $errors = array();
        if (!preg_match('/^courses[_-]/', $data['course_folder'])){
            $errors['course_folder'] = $plugininstance->get_lang('errormuststartwithcourses');
        }

        $tablename = Database::get_main_table('vchamilo');
        if($vchamilo = Database::select('*', $tablename, array('where' => array(' root_web = ? ' => array($data->root_web))))){
            $errors['root_web'] = $plugininstance->get_lang('errorrootwebexists');
        }

        if(!empty($errors)){
            return $errors;
        }
    }
}