<?php

require_once(api_get_path(SYS_CODE_PATH).'inc/lib/pear/HTML/QuickForm.php');
require_once(api_get_path(SYS_CODE_PATH).'inc/lib/formvalidator/FormValidator.class.php');
require_once($_configuration['root_sys'].'local/classes/formslib.php');

class InstanceForm extends ChamiloForm{

    var $_plugin;

    function __construct($plugin, $mode = 'add', $returnurl = null, $cancelurl = null){
        global $_configuration;

        $this->_plugin = $plugin;

        $returnurl = $_configuration['root_web'].'plugin/vchamilo/views/editinstance.php';
        $cancelurl = $_configuration['root_web'].'plugin/vchamilo/views/manage.php';
        parent::__construct($mode, $returnurl, $cancelurl);
    }

    function definition(){
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
        $cform->applyFilter('sitename', 'html_filter');
        $cform->applyFilter('sitename', 'trim');

        // Shortname.
        $cform->addElement('text', 'institution', $this->_plugin->get_lang('institution'), ($this->mode == 'edit' ? 'disabled="disabled" ' : ''));
        $cform->applyFilter('institution', 'html_filter');
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

        // Database name.
        $cform->addElement('text', 'statistics_database', $this->_plugin->get_lang('statisticsdatabase'));

        // Database name.
        $cform->addElement('text', 'user_personal_database', $this->_plugin->get_lang('userpersonaldatabase'));

        // tracking_enabled
        $yesnooptions = array('0' => $this->_plugin->get_lang('no'), '1' => $this->_plugin->get_lang('yes'));
        $cform->addElement('select', 'tracking_enabled', $this->_plugin->get_lang('trackingenabled'), $yesnooptions);

        // Single database
        $cform->addElement('select', 'single_database', $this->_plugin->get_lang('singledatabase'), $yesnooptions);

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

    function validation($data, $files = null){
        global $plugininstance;

        $errors = array();
        var_dump($data);
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