<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Chamilo installation.
 *
 * As seen from the user, the installation proceeds in 6 steps.
 * The user is presented with several pages where he/she has to make choices
 * and/or fill in data.
 *
 * The aim is, as always, to have good default settings and suggestions.
 *
 * @todo reduce high level of duplication in this code
 * @todo (busy) organise code into functions
 */
$originalDisplayErrors = ini_get('display_errors');
$originalMemoryLimit = ini_get('memory_limit');

ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);
error_reporting(-1);

require_once __DIR__.'/../../../vendor/autoload.php';

define('SYSTEM_INSTALLATION', 1);
define('INSTALL_TYPE_UPDATE', 'update');
define('FORM_FIELD_DISPLAY_LENGTH', 40);
define('DATABASE_FORM_FIELD_DISPLAY_LENGTH', 25);
define('MAX_FORM_FIELD_LENGTH', 80);

api_check_php_version();
ob_implicit_flush();
Debug::enable();

// Create .env.local file
/*$envFile = api_get_path(SYMFONY_SYS_PATH).'.env.local';
if (file_exists($envFile)) {
    echo "Chamilo is already installed. File $envFile exists.";
    exit;
}*/

// Defaults settings
putenv('APP_LOCALE=en_US');
putenv('APP_ENCRYPT_METHOD="bcrypt"');
putenv('DATABASE_HOST=');
putenv('DATABASE_PORT=');
putenv('DATABASE_NAME=');
putenv('DATABASE_USER=');
putenv('DATABASE_PASSWORD=');
putenv('APP_ENV=dev');
putenv('APP_DEBUG=1');

session_start();

require_once 'install.lib.php';
$installationLanguage = 'en_US';

// Set translation
$translator = new Translator($installationLanguage);
$translator->addLoader('po', new PoFileLoader());
$translator->addResource(
    'po',
    "../../../var/translations/installation.$installationLanguage.po",
    $installationLanguage
);
Container::$translator = $translator;

// The function api_get_setting() might be called within the installation scripts.
// We need to provide some limited support for it through initialization of the
// global array-type variable $_setting.
$_setting = [
    'platform_charset' => 'UTF-8',
    'server_type' => 'production', // 'production' | 'test'
    'permissions_for_new_directories' => '0770',
    'permissions_for_new_files' => '0660',
    'stylesheets' => 'chamilo',
];

$encryptPassForm = 'bcrypt';
$urlAppendPath = '';
$urlForm = '';
$pathForm = '';
$emailForm = '';
$dbHostForm = 'localhost';
$dbUsernameForm = 'root';
$dbPassForm = '';
$dbNameForm = 'chamilo';
$dbPortForm = 3306;
$allowSelfReg = 'approval';
$allowSelfRegProf = 1;
$adminLastName = get_lang('Doe');
$adminFirstName = get_lang('John');
$loginForm = 'admin';
$passForm = '';
$institutionUrlForm = 'http://www.chamilo.org';
$languageForm = '';
$campusForm = 'My campus';
$educationForm = 'Albert Einstein';
$adminPhoneForm = '(000) 001 02 03';
$institutionForm = 'My Organisation';
$session_lifetime = 360000;
//$installLanguage = isset($_SESSION['install_language']) ? $_SESSION['install_language'] : 'english';
$installLanguage = '';
$installationGuideLink = '../../documentation/installation_guide.html';

// Setting the error reporting levels.
error_reporting(E_ALL);

// Upgrading from any subversion of 1.11.x
$upgradeFromVersion = [
    '1.11.0',
    '1.11.1',
    '1.11.2',
    '1.11.4',
    '1.11.6',
    '1.11.8',
    '1.11.10',
    '1.11.11',
    '1.11.12',
    '1.11.14',
];

$my_old_version = '';
if (empty($tmp_version)) {
    $tmp_version = get_config_param('system_version');
}

if (!empty($_POST['old_version'])) {
    $my_old_version = $_POST['old_version'];
} elseif (!empty($tmp_version)) {
    $my_old_version = $tmp_version;
}

$versionData = require __DIR__.'/version.php';
$new_version = $versionData['new_version'];

// A protection measure for already installed systems.
/*if (isAlreadyInstalledSystem()) {
    echo 'Portal already installed';
    exit;
}*/

/* STEP 1 : INITIALIZES FORM VARIABLES IF IT IS THE FIRST VISIT */
$badUpdatePath = false;
$emptyUpdatePath = true;
$proposedUpdatePath = '';

if (!empty($_POST['updatePath'])) {
    $proposedUpdatePath = $_POST['updatePath'];
}

if (isset($_POST['step2_install']) || isset($_POST['step2_update_8']) || isset($_POST['step2_update_6'])) {
    if (isset($_POST['step2_install'])) {
        $installType = 'new';
        $_POST['step2'] = 1;
    } else {
        $installType = 'update';
        if (isset($_POST['step2_update_8'])) {
            $emptyUpdatePath = false;
            $proposedUpdatePath = api_add_trailing_slash(empty($_POST['updatePath']) ? api_get_path(SYMFONY_SYS_PATH) : $_POST['updatePath']);

            if (file_exists($proposedUpdatePath)) {
                if (in_array($my_old_version, $upgradeFromVersion)) {
                    $_POST['step2'] = 1;
                } else {
                    $badUpdatePath = true;
                }
            } else {
                $badUpdatePath = true;
            }
        }
    }
} elseif (isset($_POST['step1'])) {
    $_POST['updatePath'] = '';
    $installType = '';
    $updateFromConfigFile = '';
    unset($_GET['running']);
} else {
    $installType = isset($_GET['installType']) ? $_GET['installType'] : '';
    $updateFromConfigFile = isset($_GET['updateFromConfigFile']) ? $_GET['updateFromConfigFile'] : false;
}
if ('update' === $installType && in_array($my_old_version, $upgradeFromVersion)) {
    // This is the main configuration file of the system before the upgrade.
    // Old configuration file.
    // Don't change to include_once
    $oldConfigPath = api_get_path(SYS_CODE_PATH).'inc/conf/configuration.php';
    if (file_exists($oldConfigPath)) {
        include $oldConfigPath;
    }
}

$showEmailNotCheckedToStudent = 1;
$userMailCanBeEmpty = null;
$checkEmailByHashSent = null;

if (!isset($_GET['running'])) {
    // Extract the path to append to the url if Chamilo is not installed on the web root directory.
    $urlAppendPath = api_remove_trailing_slash(api_get_path(REL_PATH));
    $urlForm = api_get_path(WEB_PATH);
    $pathForm = api_get_path(SYS_PATH);
    $emailForm = 'webmaster@localhost';
    if (!empty($_SERVER['SERVER_ADMIN'])) {
        $emailForm = $_SERVER['SERVER_ADMIN'];
    }
    $email_parts = explode('@', $emailForm);
    if (isset($email_parts[1]) && 'localhost' === $email_parts[1]) {
        $emailForm .= '.localdomain';
    }

    $loginForm = 'admin';
    $passForm = api_generate_password();
    $institutionUrlForm = 'http://www.chamilo.org';
    $languageForm = api_get_language_isocode();
    $checkEmailByHashSent = 0;
    $userMailCanBeEmpty = 1;
    $allowSelfReg = 'approval';
    $allowSelfRegProf = 1; //by default, a user can register as teacher (but moderation might be in place)
    if (!empty($_GET['profile'])) {
        $installationProfile = api_htmlentities($_GET['profile'], ENT_QUOTES);
    }
} else {
    foreach ($_POST as $key => $val) {
        if (is_string($val)) {
            $val = trim($val);
            $_POST[$key] = $val;
        } elseif (is_array($val)) {
            foreach ($val as $key2 => $val2) {
                $val2 = trim($val2);
                $_POST[$key][$key2] = $val2;
            }
        }
        $GLOBALS[$key] = $_POST[$key];
    }
}

/* NEXT STEPS IMPLEMENTATION */
$total_steps = 7;
$current_step = 1;
if (!$_POST) {
    $current_step = 1;
} elseif (!empty($_POST['language_list']) || !empty($_POST['step1']) || ((!empty($_POST['step2_update_8']) || (!empty($_POST['step2_update_6']))) && ($emptyUpdatePath || $badUpdatePath))) {
    $current_step = 2;
} elseif (!empty($_POST['step2']) || (!empty($_POST['step2_update_8']) || (!empty($_POST['step2_update_6'])))) {
    $current_step = 3;
} elseif (!empty($_POST['step3'])) {
    $current_step = 4;
} elseif (!empty($_POST['step4'])) {
    $current_step = 5;
} elseif (!empty($_POST['step5'])) {
    $current_step = 6;
} elseif (isset($_POST['step6'])) {
    $current_step = 7;
}

error_log("Step: $current_step");

// Managing the $encryptPassForm
if ('1' == $encryptPassForm) {
    $encryptPassForm = 'bcrypt';
} elseif ('0' == $encryptPassForm) {
    $encryptPassForm = 'none';
}

$form = '';
$label = '';
if ('new' === $installType) {
    $label = get_lang('New installation');
} elseif ('update' === $installType) {
    $update_from_version = isset($update_from_version) ? $update_from_version : null;
    $label = get_lang('Update from Chamilo').(is_array($update_from_version) ? implode('|', $update_from_version) : '');
}

if (!empty($label) && empty($_POST['step6'])) {
    $form .= '<div class="page-header"><h2>'.$label.'</h2></div>';
}

if (empty($installationProfile)) {
    $installationProfile = '';
    if (!empty($_POST['installationProfile'])) {
        $installationProfile = api_htmlentities($_POST['installationProfile']);
    }
}

$institutionUrlFormResult = '';
if (api_stristr($institutionUrlForm, 'http://') || api_stristr($institutionUrlForm, 'https://')) {
    $institutionUrlFormResult = api_htmlentities($institutionUrlForm, ENT_QUOTES);
} else {
    $institutionUrlFormResult = api_htmlentities($institutionUrlForm, ENT_QUOTES);
}

$form .= '<input type="hidden" name="updatePath" value="'.(!$badUpdatePath ? api_htmlentities($proposedUpdatePath, ENT_QUOTES) : '').'" />';
$form .= '<input type="hidden" name="urlAppendPath"      value="'.api_htmlentities($urlAppendPath, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="pathForm"           value="'.api_htmlentities($pathForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="urlForm"            value="'.api_htmlentities($urlForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="dbHostForm"         value="'.api_htmlentities($dbHostForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="dbPortForm"         value="'.api_htmlentities($dbPortForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="dbUsernameForm"     value="'.api_htmlentities($dbUsernameForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="dbPassForm"         value="'.api_htmlentities($dbPassForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="dbNameForm"         value="'.api_htmlentities($dbNameForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="allowSelfReg"       value="'.api_htmlentities($allowSelfReg, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="allowSelfRegProf"   value="'.api_htmlentities($allowSelfRegProf, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="emailForm"          value="'.api_htmlentities($emailForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="adminLastName"      value="'.api_htmlentities($adminLastName, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="adminFirstName"     value="'.api_htmlentities($adminFirstName, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="adminPhoneForm"     value="'.api_htmlentities($adminPhoneForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="loginForm"          value="'.api_htmlentities($loginForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="passForm"           value="'.api_htmlentities($passForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="languageForm"       value="'.api_htmlentities($languageForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="campusForm"         value="'.api_htmlentities($campusForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="educationForm"      value="'.api_htmlentities($educationForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="institutionForm"    value="'.api_htmlentities($institutionForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="institutionUrlForm" value="'.$institutionUrlFormResult.'"/>';
$form .= '<input type="hidden" name="checkEmailByHashSent" value="'.api_htmlentities($checkEmailByHashSent, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="ShowEmailNotCheckedToStudent" value="'.api_htmlentities($showEmailNotCheckedToStudent, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="userMailCanBeEmpty" value="'.api_htmlentities($userMailCanBeEmpty, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="encryptPassForm"    value="'.api_htmlentities($encryptPassForm, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="session_lifetime"   value="'.api_htmlentities($session_lifetime, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="old_version"        value="'.api_htmlentities($my_old_version, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="new_version"        value="'.api_htmlentities($new_version, ENT_QUOTES).'"/>';
$form .= '<input type="hidden" name="installationProfile" value="'.api_htmlentities($installationProfile, ENT_QUOTES).'"/>';

if (isset($_POST['step2'])) {
    // STEP 3 : LICENSE
    ob_start();
    display_license_agreement();
    $form .= ob_get_contents();
    ob_end_clean();
} elseif (isset($_POST['step3'])) {
    // STEP 4 : MYSQL DATABASE SETTINGS
    ob_start();
    display_database_settings_form(
        $installType,
        $dbHostForm,
        $dbUsernameForm,
        $dbPassForm,
        $dbNameForm,
        $dbPortForm,
        $installationProfile
    );
    $form .= ob_get_contents();
    ob_end_clean();
} elseif (isset($_POST['step4'])) {
    // STEP 5 : CONFIGURATION SETTINGS
    if ('update' === $installType) {
        $db_name = $dbNameForm;
        $database = connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            $dbNameForm,
            $dbPortForm
        );
        $manager = $database->getManager();

        $tmp = get_config_param_from_db('platformLanguage');
        if (!empty($tmp)) {
            $languageForm = $tmp;
        }

        $tmp = get_config_param_from_db('emailAdministrator');
        if (!empty($tmp)) {
            $emailForm = $tmp;
        }

        $tmp = get_config_param_from_db('administratorName');
        if (!empty($tmp)) {
            $adminFirstName = $tmp;
        }

        $tmp = get_config_param_from_db('administratorSurname');
        if (!empty($tmp)) {
            $adminLastName = $tmp;
        }

        $tmp = get_config_param_from_db('administratorTelephone');
        if (!empty($tmp)) {
            $adminPhoneForm = $tmp;
        }

        $tmp = get_config_param_from_db('siteName');
        if (!empty($tmp)) {
            $campusForm = $tmp;
        }

        $tmp = get_config_param_from_db('Institution');
        if (!empty($tmp)) {
            $institutionForm = $tmp;
        }

        $tmp = get_config_param_from_db('InstitutionUrl');
        if (!empty($tmp)) {
            $institutionUrlForm = $tmp;
        }

        // For version 1.9
        $encryptPassForm = get_config_param('password_encryption');
        // Managing the $encryptPassForm
        if ('1' == $encryptPassForm) {
            $encryptPassForm = 'sha1';
        } elseif ('0' == $encryptPassForm) {
            $encryptPassForm = 'none';
        }

        $allowSelfReg = 'approval';
        $tmp = get_config_param_from_db('allow_registration');
        if (!empty($tmp)) {
            $allowSelfReg = $tmp;
        }

        $allowSelfRegProf = false;
        $tmp = get_config_param_from_db('allow_registration_as_teacher');
        if (!empty($tmp)) {
            $allowSelfRegProf = $tmp;
        }
    }

    ob_start();
    display_configuration_settings_form(
        $installType,
        $urlForm,
        $languageForm,
        $emailForm,
        $adminFirstName,
        $adminLastName,
        $adminPhoneForm,
        $campusForm,
        $institutionForm,
        $institutionUrlForm,
        $encryptPassForm,
        $allowSelfReg,
        $allowSelfRegProf,
        $loginForm,
        $passForm
    );
    $form .= ob_get_contents();
    ob_end_clean();
} elseif (isset($_POST['step5'])) {
    ob_start();
    //STEP 6 : LAST CHECK BEFORE INSTALL?>
    <div class="RequirementHeading">
        <h3><?php echo display_step_sequence().get_lang('Last check before install'); ?></h3>
    </div>
    <div class="RequirementContent">
        <?php echo get_lang('Here are the values you entered'); ?>
    </div>
    <?php
    $params = [];
    if ('new' === $installType) {
        $params[] = get_lang('Administrator login').' : <strong>'.$loginForm.'</strong>';
        $params[] = get_lang('Administrator password (<font color="red">you may want to change this</font>)').' : <strong>'.$passForm.'</strong>';
    }

    $params[] = get_lang('Administrator first name').' : '.$adminFirstName;
    $params[] = get_lang('Administrator last name').' : '.$adminLastName;
    $params[] = get_lang('Administrator e-mail').' : '.$emailForm;
    $params[] = get_lang('Administrator telephone').' : '.$adminPhoneForm;

    $content = implode('<br />', $params);
    echo Display::panel($content);
    $allowSelfRegistrationLiteral = get_lang('No');
    if ('true' === $allowSelfReg) {
        $allowSelfRegistrationLiteral = get_lang('Yes');
    }
    if ('approval' === $allowSelfReg) {
        $allowSelfRegistrationLiteral = get_lang('Approval');
    }

    if ('update' === $installType) {
        $urlForm = get_config_param('root_web');
    }

    $params = [
        get_lang('Your portal name').' : '.$campusForm,
        get_lang('Main language').' : '.$languageForm,
        get_lang('Allow self-registration').' : '.$allowSelfRegistrationLiteral,
        get_lang('Your company short name').' : '.$institutionForm,
        get_lang('URL of this company').' : '.$institutionUrlForm,
        //get_lang('Chamilo URL').' : '.$urlForm,
        //get_lang('Encryption method').' : '.$encryptPassForm,
    ];
    $content = implode('<br />', $params);
    echo Display::panel($content);

    $params = [
        get_lang('Database Host').' : '.$dbHostForm,
        get_lang('Port').' : '.$dbPortForm,
        get_lang('Database Login').' : '.$dbUsernameForm,
        get_lang('Database Password').' : '.str_repeat('*', api_strlen($dbPassForm)),
        get_lang('Database name').' : <strong>'.$dbNameForm.'</strong>',
    ];
    $content = implode('<br />', $params);
    echo Display::panel($content);

    if ('new' === $installType) {
        echo Display::return_message(
            '<h4 style="text-align: center">'.get_lang(
                'Warning'
            ).'</h4>'.
            get_lang('The install script will erase all tables of the selected database. We heavily recommend you do a full backup of them before confirming this last install step.'),
            'warning',
            false
        );
    } ?>
    <table width="100%">
        <tr>
            <td>
                <button type="submit"
                        class="btn btn-secondary" name="step4" value="&lt; <?php echo get_lang('Previous'); ?>" >
                    <em class="fa fa-backward"> </em> <?php echo get_lang('Previous'); ?>
                </button>
            </td>
            <td align="right">
                <input type="hidden" name="is_executable" id="is_executable" value="-" />
                <input type="hidden" name="step6" value="1" />
                <button
                        id="button_step6"
                        class="btn btn-success"
                        type="submit"
                        name="button_step6" value="<?php echo get_lang('Install Chamilo'); ?>">
                    <em class="fa fa-check"> </em>
                    <?php echo get_lang('Install chamilo'); ?>
                </button>
                <button class="btn btn-save" id="button_please_wait"></button>
            </td>
        </tr>
    </table>
    <?php
    $form .= ob_get_contents();
    ob_end_clean();
} elseif (isset($_POST['step6'])) {
    ob_start();
    //STEP 6 : INSTALLATION PROCESS
    $current_step = 7;
    $msg = get_lang('Installation process execution');
    if ('update' === $installType) {
        $msg = get_lang('Update process execution');
    }
    $form .= '<div class="RequirementHeading">
                <h3>'.display_step_sequence().$msg.'</h3>';
    if (!empty($installationProfile)) {
        $form .= '    <h3>('.$installationProfile.')</h3>';
    }
    $form .= '<div id="pleasewait" class="alert alert-success">'.
                    get_lang('Please wait. This could take a while...').'
                  <div class="progress">
                    <div
                        class="progress-bar progress-bar-striped active"
                        role="progressbar"
                        aria-valuenow="100"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        style="width: 100%">
                    <span class="sr-only">100% Complete</span>
                  </div>
                </div>
              </div>
            </div>';

    if ('update' === $installType) {
        $database = connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            $dbNameForm,
            $dbPortForm
        );
        $manager = $database->getManager();
        //$perm = api_get_permissions_for_new_directories();
        //$perm_file = api_get_permissions_for_new_files();
        // @todo fix permissions.
        $perm = octdec('0777');
        $perm_file = octdec('0777');

        // Create .env.local file
        $envFile = api_get_path(SYMFONY_SYS_PATH).'.env.local';
        $distFile = api_get_path(SYMFONY_SYS_PATH).'.env';

        $params = [
            '{{DATABASE_HOST}}' => $dbHostForm,
            '{{DATABASE_PORT}}' => $dbPortForm,
            '{{DATABASE_NAME}}' => $dbNameForm,
            '{{DATABASE_USER}}' => $dbUsernameForm,
            '{{DATABASE_PASSWORD}}' => $dbPassForm,
            '{{APP_INSTALLED}}' => 1,
            '{{APP_ENCRYPT_METHOD}}' => $encryptPassForm,
            '{{APP_SECRET}}' => generateRandomToken(),
        ];

        error_log('Update env file');
        updateEnvFile($distFile, $envFile, $params);
        (new Dotenv())->load($envFile);

        // Load Symfony Kernel
        $kernel = new Kernel('dev', true);
        $application = new Application($kernel);
        error_log('Set Kernel');

        session_unset();
        $_SESSION = [];
        session_destroy();

        // No errors
        //if ($result == 0) {
        // Boot kernel and get the doctrine from Symfony container
        $kernel->boot();
        error_log('Boot');
        $container = $kernel->getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);

        $manager = $container->get('doctrine')->getManager();

        migrateSwitch($my_old_version, $manager);
        upgradeWithContainer($container);
        error_log('Set upgradeWithContainer');
        error_log('------------------------------');
        error_log('Upgrade 2.0.0 process concluded!  ('.date('Y-m-d H:i:s').')');
    } else {
        error_log('------------------------------');
        $start = date('Y-m-d H:i:s');
        error_log('Chamilo installation starts:  ('.$start.')');
        set_file_folder_permissions();
        error_log("connectToDatabase as user $dbUsernameForm");

        $database = connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            null,
            $dbPortForm
        );
        $manager = $database->getManager();
        $dbNameForm = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbNameForm);

        // Drop and create the database anyways
        error_log("Drop database $dbNameForm");
        $manager->getConnection()->getSchemaManager()->dropAndCreateDatabase($dbNameForm);

        error_log("Connect to database $dbNameForm with user $dbUsernameForm");
        $database = connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            $dbNameForm,
            $dbPortForm
        );

        $manager = $database->getManager();
        // Create .env.local file
        $envFile = api_get_path(SYMFONY_SYS_PATH).'.env.local';
        $distFile = api_get_path(SYMFONY_SYS_PATH).'.env';

        $params = [
            '{{DATABASE_HOST}}' => $dbHostForm,
            '{{DATABASE_PORT}}' => $dbPortForm,
            '{{DATABASE_NAME}}' => $dbNameForm,
            '{{DATABASE_USER}}' => $dbUsernameForm,
            '{{DATABASE_PASSWORD}}' => $dbPassForm,
            '{{APP_INSTALLED}}' => 1,
            '{{APP_ENCRYPT_METHOD}}' => $encryptPassForm,
            '{{APP_SECRET}}' => generateRandomToken(),
        ];

        updateEnvFile($distFile, $envFile, $params);
        (new Dotenv())->load($envFile);

        error_log('Load kernel');
        // Load Symfony Kernel
        $kernel = new Kernel('dev', true);
        $application = new Application($kernel);

        // Create database
        error_log('Create database');
        $input = new ArrayInput([]);
        $command = $application->find('doctrine:schema:create');
        $result = $command->run($input, new ConsoleOutput());

        // No errors
        if (0 === $result) {
            $input = new ArrayInput([]);
            $input->setInteractive(false);
            $command = $application->find('doctrine:fixtures:load');
            $result = $command->run($input, new ConsoleOutput());

            error_log('Delete PHP Session');
            session_unset();
            $_SESSION = [];
            session_destroy();
            error_log('Boot kernel');

            // Boot kernel and get the doctrine from Symfony container
            $kernel->boot();
            $containerDatabase = $kernel->getContainer();
            $sysPath = api_get_path(SYMFONY_SYS_PATH);

            finishInstallationWithContainer(
                $containerDatabase,
                $sysPath,
                $encryptPassForm,
                $passForm,
                $adminLastName,
                $adminFirstName,
                $loginForm,
                $emailForm,
                $adminPhoneForm,
                $languageForm,
                $institutionForm,
                $institutionUrlForm,
                $campusForm,
                $allowSelfReg,
                $allowSelfRegProf,
                $installationProfile
            );
            writeSystemConfigFile(api_get_path(SYMFONY_SYS_PATH).'config/configuration.php');
            error_log('Finish installation');
        } else {
            error_log('ERROR during installation.');
        }
    }

    $form .= display_after_install_message();

    // Hide the "please wait" message sent previously
    $form .= '<script>$(\'#pleasewait\').hide(\'fast\');</script>';
    $form .= ob_get_contents();
    ob_end_clean();
} elseif (isset($_POST['step1']) || $badUpdatePath) {
    //STEP 1 : REQUIREMENTS
    //make sure that proposed path is set, shouldn't be necessary but...
    if (empty($proposedUpdatePath)) {
        $proposedUpdatePath = $_POST['updatePath'];
    }

    ob_start();
    display_requirements($installType, $badUpdatePath, $proposedUpdatePath, $upgradeFromVersion);
    $form .= ob_get_contents();
    ob_end_clean();
} else {
    ob_start();
    // This is the start screen.
    display_language_selection();

    if (!empty($_GET['profile'])) {
        $installationProfile = api_htmlentities($_GET['profile'], ENT_QUOTES);
    }
    echo '<input
        type="hidden"
        name="installationProfile"
        value="'.api_htmlentities($installationProfile, ENT_QUOTES).'" />';
    $form .= ob_get_contents();
    ob_end_clean();
}

$poweredBy = 'Powered by <a href="http://www.chamilo.org" target="_blank"> Chamilo </a> &copy; '.date('Y');
?>
<!DOCTYPE html>
<head>
    <title>
        &mdash; <?php echo $translator->trans('Chamilo installation').' &mdash; '.$translator->trans('Version').' '.$new_version; ?>
    </title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="../../build/css/app.css">
    <link rel="stylesheet" href="../../build/vue.css">
    <link rel="stylesheet" href="../../build/css/bootstrap.css">
    <script type="text/javascript" src="../../../build/runtime.js"></script>
    <script type="text/javascript" src="../../../build/app.js"></script>
    <script>
        $(function() {
            $("#details_button").click(function() {
                $( "#details" ).toggle("slow", function() {
                });
            });

            $("#button_please_wait").hide();
            $("button").addClass('btn btn-secondary');

            // Allow Chamilo install in IE
            $("button").click(function() {
                $("#is_executable").attr("value",$(this).attr("name"));
            });

            //Blocking step6 button
            $("#button_step6").click(function() {
                $("#button_step6").hide();
                $("#button_please_wait").html('<?php echo addslashes($translator->trans('Continue')); ?>');
                $("#button_please_wait").show();
                $("#button_please_wait").attr('disabled', true);
                $("#is_executable").attr("value",'step6');
            });

            $(".advanced_parameters").click(function() {
                if ($("#id_contact_form").css("display") == "none") {
                    $("#id_contact_form").css("display","block");
                    $("#img_plus_and_minus").html(
                        '&nbsp;<i class="fa fa-eye" aria-hidden="true"></i>&nbsp;<?php echo $translator->trans('Contact information'); ?>'
                    );
                } else {
                    $("#id_contact_form").css("display","none");
                    $("#img_plus_and_minus").html(
                        '&nbsp;<i class="fa fa-eye-slash" aria-hidden="true"></i>&nbsp;<?php echo $translator->trans('Contact information'); ?>'
                    );
                }
            });
        });

        function send_contact_information() {
            if (!document.getElementById('accept_licence').checked) {
                alert('<?php echo $translator->trans('You must accept the licence'); ?>')
                ;return false;
            } else {
                var data_post = "";
                data_post += "person_name="+$("#person_name").val()+"&";
                data_post += "person_email="+$("#person_email").val()+"&";
                data_post += "company_name="+$("#company_name").val()+"&";
                data_post += "company_activity="+$("#company_activity option:selected").val()+"&";
                data_post += "person_role="+$("#person_role option:selected").val()+"&";
                data_post += "company_country="+$("#country option:selected").val()+"&";
                data_post += "company_city="+$("#company_city").val()+"&";
                data_post += "language="+$("#language option:selected").val()+"&";
                data_post += "financial_decision="+$("input[name='financial_decision']:checked").val();

                $.ajax({
                    contentType: "application/x-www-form-urlencoded",
                    beforeSend: function(objeto) {},
                    type: "POST",
                    url: "<?php echo api_get_path(WEB_AJAX_PATH); ?>install.ajax.php?a=send_contact_information",
                    beforeSend : function() {
                        $('#loader-button').append('  <em class="fa fa-spinner fa-pulse fa-fw"></em>');
                    },
                    data: data_post,
                    success: function(datos) {
                        if (datos == 'required_field_error') {
                            message = "<?php echo $translator->trans('The form contains incorrect or incomplete data. Please check your input.'); ?>";
                        } else if (datos == '1') {
                            message = "<?php echo $translator->trans('Contact informationHasBeenSent'); ?>";
                        } else {
                            message = "<?php echo $translator->trans('Error').': '.$translator->trans('Contact informationHasNotBeenSent'); ?>";
                        }
                        alert(message);
                        $('#license-next').trigger('click');
                        $('#loader-button').html('');
                    }
                });
            }
        }
    </script>
</head>
<body class="w-full justify-center bg-gradient-to-r from-blue-400 to-blue-600">
    <div class="flex flex-col items-center justify-center ">
        <div class="rounded p-4 m-8 w-3/5 bg-white flex">
            <div class="w-1/3 p-4">
                <div class="logo-install mb-4">
                    <a href="index.php">
                    <img src="../../build/css/themes/chamilo/images/header-logo.png"
                         class="img-fluid" alt="Chamilo" />
                    </a>
                </div>
                <div class="install-steps">
                    <ol class="list-group">
                        <li class="list-group-item <?php step_active('1'); ?>">
                            <span class="number"> 1 </span>
                            <?php echo $translator->trans('Installation language'); ?>
                        </li>
                        <li class="list-group-item <?php step_active('2'); ?>">
                            <span class="number"> 2 </span>
                            <?php echo $translator->trans('Requirements'); ?>
                        </li>
                        <li class="list-group-item <?php step_active('3'); ?>">
                            <span class="number"> 3 </span>
                            <?php echo $translator->trans('Licence'); ?>
                        </li>
                        <li class="list-group-item <?php step_active('4'); ?>">
                            <span class="number"> 4 </span>
                            <?php echo $translator->trans('Database settings'); ?>
                        </li>
                        <li class="list-group-item <?php step_active('5'); ?>">
                            <span class="number"> 5 </span>
                            <?php echo $translator->trans('Config settings'); ?>
                        </li>
                        <li class="list-group-item <?php step_active('6'); ?>">
                            <span class="number"> 6 </span>
                            <?php echo $translator->trans('Show Overview'); ?>
                        </li>
                        <li class="list-group-item <?php step_active('7'); ?>">
                            <span class="number"> 7 </span>
                            <?php echo $translator->trans('Install'); ?>
                        </li>
                    </ol>
                </div>
                <div id="note">
                    <a class="btn btn-info btn-block" href="<?php echo $installationGuideLink; ?>" target="_blank">
                        <em class="fa fa-file-alt"></em>
                        <?php echo $translator->trans('Read the installation guide'); ?>
                    </a>
                </div>
            </div>
            <div class="w-2/3 p-4 prose">
                <form
                    class="form-horizontal" id="install_form" method="post"
                      action="<?php echo api_get_self(); ?>?running=1&amp;installType=<?php echo $installType; ?>&amp;updateFromConfigFile=<?php echo urlencode($updateFromConfigFile); ?>">
                    <?php echo $form; ?>
                </form>
            </div>
        </div>
        <footer class="install-footer">
            <?php echo $poweredBy; ?>
        </footer>
    </div>
</body>
</html>
