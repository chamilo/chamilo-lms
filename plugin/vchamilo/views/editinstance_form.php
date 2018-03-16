<?php
/* For licensing terms, see /license.txt */

/**
 * Class ChamiloForm.
 */
abstract class ChamiloForm
{
    public $_definition_finalized;
    protected $_form;
    protected $_mode;
    protected $_cancelurl;
    protected $_customdata;

    /**
     * ChamiloForm constructor.
     *
     * @param $mode
     * @param $returnurl
     * @param $cancelurl
     * @param $customdata
     */
    public function __construct($mode, $returnurl, $cancelurl, $customdata = [])
    {
        global $text_dir;
        $this->_mode = $mode;
        $this->_cancelurl = $cancelurl;
        $this->_customdata = $customdata;

        $attributes = ['style' => 'width: 60%; float: '.($text_dir == 'rtl' ? 'right;' : 'left;')];
        $this->_form = new FormValidator(
            $mode.'_instance',
            'post',
            $returnurl,
            '',
            $attributes
        );
    }

    abstract public function definition();

    abstract public function validation($data, $files = null);

    public function validate()
    {
        return $this->_form->validate();
    }

    public function display()
    {
        return $this->_form->display();
    }

    public function definition_after_data()
    {
    }

    public function return_form()
    {
        return $this->_form->toHtml();
    }

    public function is_in_add_mode()
    {
        return $this->_mode == 'add';
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @param bool $slashed true means return data with addslashes applied
     *
     * @return object submitted data; NULL if not valid or not submitted
     */
    public function get_data($slashed = true)
    {
        $cform = &$this->_form;

        if ($this->is_submitted() and $this->is_validated()) {
            $data = $cform->exportValues(null, $slashed);
            unset($data['sesskey']); // we do not need to return sesskey
            if (empty($data)) {
                return null;
            } else {
                return (object) $data;
            }
        } else {
            return null;
        }
    }

    /**
     * Return submitted data without validation or NULL if there is no submitted data.
     *
     * @param bool $slashed true means return data with addslashes applied
     *
     * @return object submitted data; NULL if not submitted
     */
    public function get_submitted_data($slashed = true)
    {
        $cform = &$this->_form;

        if ($this->is_submitted()) {
            $data = $cform->exportValues(null, $slashed);
            unset($data['sesskey']); // we do not need to return sesskey
            if (empty($data)) {
                return null;
            } else {
                return (object) $data;
            }
        } else {
            return null;
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
        $cform = &$this->_form;
        if ($cform->isSubmitted()) {
            foreach ($cform->_cancelButtons as $cancelbutton) {
                if (optional_param($cancelbutton, 0, PARAM_RAW)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check that form data is valid.
     * You should almost always use this, rather than {@see validate_defined_fields}.
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
     * @param bool $validateonnosubmit optional, defaults to false.  The default behaviour
     *                                 is NOT to validate the form when a no submit button has been pressed.
     *                                 pass true here to override this behaviour
     *
     * @return bool true if form data valid
     */
    public function validate_defined_fields($validateonnosubmit = false)
    {
        static $validated = null; // one validation is enough
        $cform = &$this->_form;

        if ($this->no_submit_button_pressed() && empty($validateonnosubmit)) {
            return false;
        } elseif ($validated === null) {
            $internal_val = $cform->validate();

            $files = [];
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
            if ((is_array($chamilo_val) && count($chamilo_val) !== 0)) {
                // non-empty array means errors
                foreach ($chamilo_val as $element => $msg) {
                    $cform->setElementError($element, $msg);
                }
                $chamilo_val = false;
            } else {
                // anything else means validation ok
                $chamilo_val = true;
            }

            $validated = ($internal_val && $chamilo_val && $file_val);
        }

        return $validated;
    }

    public function no_submit_button_pressed()
    {
        static $nosubmit = null; // one check is enough

        if (!is_null($nosubmit)) {
            return $nosubmit;
        }

        $cform = &$this->_form;
        $nosubmit = false;
        if (!$this->is_submitted()) {
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
     * @param bool  $slashed        true if magic quotes applied to data values
     */
    public function set_data($default_values, $slashed = false)
    {
        if (is_object($default_values)) {
            $default_values = (array) $default_values;
        }
        $filter = $slashed ? 'stripslashes' : null;
        $this->_form->setDefaults($default_values, $filter);
    }

    /**
     * Internal method. Validates all uploaded files.
     */
    public function _validate_files(&$files)
    {
        $files = [];

        if (empty($_FILES)) {
            // we do not need to do any checks because no files were submitted
            // note: server side rules do not work for files - use custom verification in validate() instead
            return true;
        }

        $errors = [];
        $mform = &$this->_form;

        // check the files
        $status = $this->_upload_manager->preprocess_files();

        // now check that we really want each file
        foreach ($_FILES as $elname => $file) {
            if ($mform->elementExists($elname) and $mform->getElementType($elname) == 'file') {
                $required = $mform->isElementRequired($elname);
                if (!empty($this->_upload_manager->files[$elname]['uploadlog']) &&
                    empty($this->_upload_manager->files[$elname]['clear'])
                ) {
                    if (!$required and $file['error'] == UPLOAD_ERR_NO_FILE) {
                        // file not uploaded and not required - ignore it
                        continue;
                    }
                    $errors[$elname] = $this->_upload_manager->files[$elname]['uploadlog'];
                } elseif (!empty($this->_upload_manager->files[$elname]['clear'])) {
                    $files[$elname] = $this->_upload_manager->files[$elname]['tmp_name'];
                }
            } else {
                error('Incorrect upload attempt!');
            }
        }

        // return errors if found
        if ($status && 0 == count($errors)) {
            return true;
        } else {
            $files = [];

            return $errors;
        }
    }
}

/**
 * Class InstanceForm.
 */
class InstanceForm extends ChamiloForm
{
    /** @var Plugin */
    public $_plugin;
    public $instance;

    /**
     * InstanceForm constructor.
     *
     * @param $plugin
     * @param string $mode
     */
    public function __construct($plugin, $mode = 'add', $instance = [])
    {
        global $_configuration;

        $this->_plugin = $plugin;
        $returnUrl = $_configuration['root_web'].'plugin/vchamilo/views/editinstance.php';
        if ($mode == 'update') {
            $returnUrl = $_configuration['root_web'].'plugin/vchamilo/views/editinstance.php?vid='.intval($_GET['vid']);
        }

        $cancelurl = $_configuration['root_web'].'plugin/vchamilo/views/manage.php';
        parent::__construct($mode, $returnUrl, $cancelurl);
        $this->instance = $instance;
        $this->definition();
    }

    public function definition()
    {
        global $_configuration;

        $form = $this->_form;
        $plugin = $this->_plugin;

        $form->addElement('hidden', 'vid');
        $form->addElement('hidden', 'what', $this->_mode.'instance');
        $form->addElement('hidden', 'registeronly');

        $form->addHeader($plugin->get_lang('hostdefinition'));
        $form->addText(
            'sitename',
            [
                $plugin->get_lang('sitename'),
                $plugin->get_lang('SiteNameExample'),
            ]
        );
        $form->applyFilter('sitename', 'trim');
        $form->addText(
            'institution',
            [
                $plugin->get_lang('institution'),
                $plugin->get_lang('InstitutionExample'),
            ]
        );
        $form->applyFilter('institution', 'trim');

        // Host's name.
        $elementWeb = $form->addElement(
            'text',
            'root_web',
            [$this->_plugin->get_lang('rootweb'), $plugin->get_lang('RootWebExample')]
        );
        $form->applyFilter('root_web', 'trim');

        $form->addElement(
            'text',
            'url_append',
            ['url_append', $plugin->get_lang('UrlAppendExample')]
        );

        if ($this->_mode == 'update') {
            $encryptList = Virtual::getEncryptList();
            $encryptMethod = $form->addElement(
                'select',
                'password_encryption',
                get_lang('EncryptMethodUserPass'),
                $encryptList
            );
            $encryptMethod->freeze();
            $elementWeb->freeze();
        }

        /*
         * Database fieldset.
         */
        $form->addElement('header', $plugin->get_lang('dbgroup'));

        // Database host.
        $form->addElement(
            'text',
            'db_host',
            $this->_plugin->get_lang('dbhost'),
            ['id' => 'id_vdbhost']
        );
        $form->applyFilter('db_host', 'trim');

        // Database login.
        $form->addElement(
            'text',
            'db_user',
            $this->_plugin->get_lang('dbuser'),
            ['id' => 'id_vdbuser']
        );
        $form->applyFilter('db_user', 'trim');

        // Database password.
        $form->addElement(
            'password',
            'db_password',
            $this->_plugin->get_lang('dbpassword'),
            ['id' => 'id_vdbpassword']
        );

        // Database name.
        $form->addText(
            'main_database',
            [
                $plugin->get_lang('maindatabase'),
                $plugin->get_lang('DatabaseDescription'),
            ]
        );

        // Button for testing database connection.
        $form->addElement(
            'button',
            'testconnection',
            $this->_plugin->get_lang('testconnection'),
            'check',
            'default',
            'default',
            '',
            'onclick="opencnxpopup(\''.$_configuration['root_web'].'\'); return false;"'
        );

        $form->addText('archive_url', $this->_plugin->get_lang('ArchiveUrl'));
        $form->addText('home_url', $this->_plugin->get_lang('HomeUrl'));
        $form->addText('upload_url', $this->_plugin->get_lang('UploadUrl'));
        $form->addText(
            'css_theme_folder',
            [
                $this->_plugin->get_lang('ThemeFolder'),
                $this->_plugin->get_lang('ThemeFolderExplanation'),
            ],
            false
        );
        //$form->addText('course_url', $this->_plugin->get_lang('CourseUrl'));

        /**
         * Template selection.
         */
        if ($this->is_in_add_mode()) {
            $form->addElement('header', $this->_plugin->get_lang('templating'));

            $templateoptions = Virtual::getAvailableTemplates();

            // Template choice
            $form->addSelect(
                'template',
                $this->_plugin->get_lang('template'),
                $templateoptions
            );
        } else {
            if ($this->instance) {
                $form->addLabel(
                    'slug',
                    $this->instance['slug']
                );

                $form->addLabel(
                    'archive_real_root',
                    api_add_trailing_slash(Virtual::getConfig('vchamilo', 'archive_real_root')).
                    $this->instance['slug']
                );

                $form->addLabel(
                    'course_real_root',
                    api_add_trailing_slash(Virtual::getConfig('vchamilo', 'course_real_root')).
                        $this->instance['slug']
                );

                $form->addLabel(
                    'home_real_root',
                    api_add_trailing_slash(Virtual::getConfig('vchamilo', 'home_real_root')).$this->instance['slug']
                );

                $form->addLabel(
                    'upload_real_root',
                    api_add_trailing_slash(Virtual::getConfig('vchamilo', 'upload_real_root')).$this->instance['slug']
                );

                $form->addLabel(
                    $this->_plugin->get_lang('template'),
                    $this->instance['template']
                );
            }
        }

        $form->addButtonSave(
            $this->_plugin->get_lang('savechanges'),
            'submitbutton'
        );

        // Rules
        $form->addRule(
            'sitename',
            $this->_plugin->get_lang('sitenameinputerror'),
            'required',
            null,
            'client'
        );
        $form->addRule(
            'institution',
            $this->_plugin->get_lang('institutioninputerror'),
            'required',
            null,
            'client'
        );

        $form->addRule(
            'root_web',
            $this->_plugin->get_lang('rootwebinputerror'),
            'required',
            null,
            'client'
        );
        $form->addRule(
            'main_database',
            $this->_plugin->get_lang('databaseinputerror'),
            'required',
            null,
            'client'
        );
    }

    /**
     * @param array $data
     * @param null  $files
     *
     * @return array
     */
    public function validation($data, $files = null)
    {
        global $plugin;

        $errors = [];
        $tablename = Database::get_main_table('vchamilo');
        $vchamilo = Database::select(
            '*',
            $tablename,
            ['where' => [' root_web = ? ' => [$data['root_web']]]],
            'first'
        );

        if ($vchamilo && isset($data['vid']) && $data['vid'] != $vchamilo['id']) {
            $errors['root_web'] = $plugin->get_lang('RootWebExists');
        }

        if (!empty($errors)) {
            return $errors;
        }
    }
}
