<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Dotenv\Dotenv;
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
 *
 * @package chamilo.install
 */
$originalDisplayErrors = ini_get('display_errors');
$originalMemoryLimit = ini_get('memory_limit');

ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);
error_reporting(-1);

require_once __DIR__.'/../../vendor/autoload.php';

define('SYSTEM_INSTALLATION', 1);
define('INSTALL_TYPE_UPDATE', 'update');
define('FORM_FIELD_DISPLAY_LENGTH', 40);
define('DATABASE_FORM_FIELD_DISPLAY_LENGTH', 25);
define('MAX_FORM_FIELD_LENGTH', 80);

require_once '../inc/lib/api.lib.php';
require_once '../inc/lib/text.lib.php';

api_check_php_version('../inc/');
ob_implicit_flush(true);

// Defaults settings
putenv('APP_LOCALE=en');
putenv('APP_URL_APPEND=""');
putenv('APP_ENCRYPT_METHOD="bcrypt"');
putenv('DATABASE_HOST=');
putenv('DATABASE_PORT=');
putenv('DATABASE_NAME=');
putenv('DATABASE_USER=');
putenv('DATABASE_PASSWORD=');
putenv('APP_ENV=dev');
putenv('APP_DEBUG=1');

session_start();

require_once api_get_path(LIBRARY_PATH).'database.constants.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'text.lib.php';
require_once api_get_path(LIBRARY_PATH).'banner.lib.php';
require_once 'install.lib.php';

$installationLanguage = 'en';
// Determination of the language during the installation procedure.
if (!empty($_POST['language_list'])) {
    $search = ['../', '\\0'];
    $installationLanguage = str_replace($search, '', urldecode($_POST['language_list']));
//$_SESSION['install_language'] = $installationLanguage;
} else {
    // Trying to switch to the browser's language, it is covenient for most of the cases.
    $installationLanguage = detect_browser_language();
}

// Language validation.
if (!array_key_exists($installationLanguage, get_language_folder_list())) {
    $installationLanguage = 'en';
}

// Set translation
$translator = new Translator($installationLanguage);
$translator->addLoader('po', new PoFileLoader());
$translator->addResource('po', "../../translations/installation.$installationLanguage.po", $installationLanguage);
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
$adminLastName = get_lang('DefaultInstallAdminLastname');
$adminFirstName = get_lang('DefaultInstallAdminFirstname');
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
/*
// Loading language files.
require api_get_path(SYS_LANG_PATH).'english/trad4all.inc.php';
if ($installationLanguage != 'english') {
    include_once api_get_path(SYS_LANG_PATH).$installationLanguage.'/trad4all.inc.php';
    switch ($installationLanguage) {
        case 'french':
            $installationGuideLink = '../../documentation/installation_guide_fr_FR.html';
            break;
        case 'spanish':
            $installationGuideLink = '../../documentation/installation_guide_es_ES.html';
            break;
        case 'italian':
            $installationGuideLink = '../../documentation/installation_guide_it_IT.html';
            break;
        default:
            break;
    }
}*/

// Enables the portability layer and configures PHP for UTF-8
\Patchwork\Utf8\Bootup::initAll();

// Setting the error reporting levels.
error_reporting(E_ALL);

// Overriding the timelimit (for large campusses that have to be migrated).
//@set_time_limit(0);

// Upgrading from any subversion of 1.9
$update_from_version_8 = [
    '1.9.0',
    '1.9.2',
    '1.9.4',
    '1.9.6',
    '1.9.6.1',
    '1.9.8',
    '1.9.8.1',
    '1.9.8.2',
    '1.9.10',
    '1.9.10.2',
    '1.9.10.4',
    '1.9.10.6',
    '1.10.0',
    '1.10.2',
    '1.10.4',
    '1.10.6',
    '1.10.8',
    '1.11.0',
    '1.11.1',
    '1.11.2',
    '1.11.4',
    '1.11.6',
    '1.11.8',
    '1.11.10',
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

require_once __DIR__.'/version.php';

// A protection measure for already installed systems.
if (isAlreadyInstalledSystem()) {
    // The system has already been installed, so block re-installation.
    $global_error_code = 6;
    require '../inc/global_error_message.inc.php';
    exit;
}

/* STEP 1 : INITIALIZES FORM VARIABLES IF IT IS THE FIRST VISIT */
$badUpdatePath = false;
$emptyUpdatePath = true;
$proposedUpdatePath = '';

if (!empty($_POST['updatePath'])) {
    $proposedUpdatePath = $_POST['updatePath'];
}

if (@$_POST['step2_install'] || @$_POST['step2_update_8'] || @$_POST['step2_update_6']) {
    if (@$_POST['step2_install']) {
        $installType = 'new';
        $_POST['step2'] = 1;
    } else {
        $installType = 'update';
        if (@$_POST['step2_update_8']) {
            $emptyUpdatePath = false;
            $proposedUpdatePath = api_add_trailing_slash(empty($_POST['updatePath']) ? api_get_path(SYS_PATH) : $_POST['updatePath']);
            if (file_exists($proposedUpdatePath)) {
                if (in_array($my_old_version, $update_from_version_8)) {
                    $_POST['step2'] = 1;
                } else {
                    $badUpdatePath = true;
                }
            } else {
                $badUpdatePath = true;
            }
        }
    }
} elseif (@$_POST['step1']) {
    $_POST['updatePath'] = '';
    $installType = '';
    $updateFromConfigFile = '';
    unset($_GET['running']);
} else {
    $installType = isset($_GET['installType']) ? $_GET['installType'] : '';
    $updateFromConfigFile = isset($_GET['updateFromConfigFile']) ? $_GET['updateFromConfigFile'] : false;
}
if ($installType === 'update' && in_array($my_old_version, $update_from_version_8)) {
    // This is the main configuration file of the system before the upgrade.
    // Old configuration file.
    // Don't change to include_once
    $oldConfigPath = api_get_path(SYS_CODE_PATH).'inc/conf/configuration.php';
    if (file_exists($oldConfigPath)) {
        include $oldConfigPath;
    }
}

$showEmailNotCheckedToStudent = 1;

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
    if (isset($email_parts[1]) && $email_parts[1] == 'localhost') {
        $emailForm .= '.localdomain';
    }

    $loginForm = 'admin';
    $passForm = api_generate_password();
    $institutionUrlForm = 'http://www.chamilo.org';
    $languageForm = api_get_interface_language();
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
if (!$_POST) {
    $current_step = 1;
} elseif (!empty($_POST['language_list']) or !empty($_POST['step1']) || ((!empty($_POST['step2_update_8']) or (!empty($_POST['step2_update_6']))) && ($emptyUpdatePath or $badUpdatePath))) {
    $current_step = 2;
} elseif (!empty($_POST['step2']) or (!empty($_POST['step2_update_8']) or (!empty($_POST['step2_update_6'])))) {
    $current_step = 3;
} elseif (!empty($_POST['step3'])) {
    $current_step = 4;
} elseif (!empty($_POST['step4'])) {
    $current_step = 5;
} elseif (!empty($_POST['step5'])) {
    $current_step = 6;
} elseif (@$_POST['step6']) {
    $current_step = 7;
}

// Managing the $encryptPassForm
if ($encryptPassForm == '1') {
    $encryptPassForm = 'bcrypt';
} elseif ($encryptPassForm == '0') {
    $encryptPassForm = 'none';
}

$form = '';
$instalation_type_label = '';
if ($installType == 'new') {
    $instalation_type_label = get_lang('NewInstallation');
} elseif ($installType == 'update') {
    $update_from_version = isset($update_from_version) ? $update_from_version : null;
    $instalation_type_label = get_lang('UpdateFromLMSVersion').(is_array($update_from_version) ? implode('|', $update_from_version) : '');
}

if (!empty($instalation_type_label) && empty($_POST['step6'])) {
    $form .= '<div class="page-header"><h2>'.$instalation_type_label.'</h2></div>';
}

if (empty($installationProfile)) {
    $installationProfile = '';
    if (!empty($_POST['installationProfile'])) {
        $installationProfile = api_htmlentities($_POST['installationProfile']);
    }
}

$institutionUrlFormResult = api_stristr($institutionUrlForm, 'http://', false) ? api_htmlentities($institutionUrlForm, ENT_QUOTES) : api_stristr($institutionUrlForm, 'https://', false) ? api_htmlentities($institutionUrlForm, ENT_QUOTES) : 'http://'.api_htmlentities($institutionUrlForm, ENT_QUOTES);

$form .= '<input type="hidden" name="updatePath"  value="'.(!$badUpdatePath ? api_htmlentities($proposedUpdatePath, ENT_QUOTES) : '').'" />';
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

if (@$_POST['step2']) {
    // STEP 3 : LICENSE
    ob_start();
    display_license_agreement();
    $form .= ob_get_contents();
    ob_end_clean();
} elseif (@$_POST['step3']) {
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
} elseif (@$_POST['step4']) {
    //STEP 5 : CONFIGURATION SETTINGS
    //if update, try getting settings from the database...
    if ($installType === 'update') {
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
        if ($encryptPassForm == '1') {
            $encryptPassForm = 'sha1';
        } elseif ($encryptPassForm == '0') {
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
} elseif (@$_POST['step5']) {
    ob_start();
    //STEP 6 : LAST CHECK BEFORE INSTALL?>
    <div class="RequirementHeading">
        <h3><?php echo display_step_sequence().get_lang('LastCheck'); ?></h3>
    </div>
    <div class="RequirementContent">
        <?php echo get_lang('HereAreTheValuesYouEntered'); ?>
    </div>

    <?php
    if ($installType == 'new') {
        echo get_lang('AdminLogin').' : <strong>'.$loginForm.'</strong><br />';
        echo get_lang('AdminPass').' : <strong>'.$passForm.'</strong><br /><br />'; /* TODO: Maybe this password should be hidden too? */
    }
    $allowSelfRegistrationLiteral = ($allowSelfReg == 'true') ? get_lang('Yes') : ($allowSelfReg == 'approval' ? get_lang('Approval') : get_lang('No'));
    echo get_lang('AdminFirstName').' : '.$adminFirstName, '<br />', get_lang('AdminLastName').' : '.$adminLastName, '<br />';
    echo get_lang('AdminEmail').' : '.$emailForm; ?><br />
    <?php echo get_lang('AdminPhone').' : '.$adminPhoneForm; ?><br />
    <?php echo get_lang('MainLang').' : '.$languageForm; ?><br /><br />
    <?php echo get_lang('DBHost').' : '.$dbHostForm; ?><br />
    <?php echo get_lang('DBPort').' : '.$dbPortForm; ?><br />
    <?php echo get_lang('DBLogin').' : '.$dbUsernameForm; ?><br />
    <?php echo get_lang('DBPassword').' : '.str_repeat('*', api_strlen($dbPassForm)); ?><br />
    <?php echo get_lang('MainDB').' : <strong>'.$dbNameForm; ?></strong><br />
    <?php echo get_lang('AllowSelfReg').' : '.$allowSelfRegistrationLiteral; ?><br />
    <?php echo get_lang('EncryptMethodUserPass').' : ';
    echo $encryptPassForm; ?>
    <br /><br />
    <?php echo get_lang('CampusName').' : '.$campusForm; ?><br />
    <?php echo get_lang('InstituteShortName').' : '.$institutionForm; ?><br />
    <?php echo get_lang('InstituteURL').' : '.$institutionUrlForm; ?><br />
    <?php echo get_lang('ChamiloURL').' : '.$urlForm; ?><br /><br />
    <?php
    if ($installType == 'new') {
        echo Display::return_message(
            '<h4 style="text-align: center">'.get_lang(
                'Warning'
            ).'</h4>'.get_lang('TheInstallScriptWillEraseAllTables'),
            'warning',
            false
        );
    } ?>

    <div id="pnl-check-crs-tables" class="alert alert-warning hide">
        <p><?php echo get_lang('CRSTablesIntro'); ?></p>
        <p>
            <button type="button" class="btn btn-warning btn-xs" id="btn-remove-crs-table" data-removing-text="<?php echo get_lang('Removing'); ?>" autocomplete="off">
                <span class="fa-stack" aria-hidden="true">
                    <span class="fa fa-circle-thin fa-stack-2x"></span>
                    <span class="fa fa-trash-o fa-stack-1x"></span>
                </span>
                <?php echo get_lang('CheckForCRSTables'); ?>
            </button>
        </p>
    </div>
    <script>
        $(document).on('ready', function () {
            $.post('<?php echo api_get_path(WEB_CODE_PATH); ?>install/ajax.php', {
                a: 'check_crs_tables',
                db_host: '<?php echo $dbHostForm; ?>',
                db_username: '<?php echo $dbUsernameForm; ?>',
                db_pass: '<?php echo $dbPassForm; ?>',
                db_name: '<?php echo $dbNameForm; ?>',
                db_port: '<?php echo $dbPortForm; ?>',
                install_type: '<?php echo $installType; ?>'
            }, function (response) {
                if (!parseInt(response)) {
                    return;
                }

                $('#pnl-check-crs-tables').removeClass('hide');
                $('#btn-remove-crs-table').on('click', function (e) {
                    e.preventDefault();

                    var sure = confirm('<?php echo get_lang('AreYouSureToDelete'); ?>');

                    if (!sure) {
                        return;
                    }

                    var $btnNext = $('button.btn-success:submit'),
                        $btnRemove = $(this).button('removing');

                    $btnNext.prop('disabled', true);

                    $.post('<?php echo api_get_path(WEB_CODE_PATH); ?>install/ajax.php', {
                        a: 'remove_crs_tables',
                        db_host: '<?php echo $dbHostForm; ?>',
                        db_username: '<?php echo $dbUsernameForm; ?>',
                        db_pass: '<?php echo $dbPassForm; ?>',
                        db_name: '<?php echo $dbNameForm; ?>',
                        db_port: '<?php echo $dbPortForm; ?>'
                    }, function () {
                        $btnRemove.remove();
                        $btnNext.prop('disabled', false);
                    });
                });
            });
        });
    </script>
    <table width="100%">
        <tr>
            <td>
                <button type="submit" class="btn btn-secondary" name="step4" value="&lt; <?php echo get_lang('Previous'); ?>" >
                    <em class="fa fa-backward"> </em> <?php echo get_lang('Previous'); ?>
                </button>
            </td>
            <td align="right">
                <input type="hidden" name="is_executable" id="is_executable" value="-" />
                <input type="hidden" name="step6" value="1" />
                <button id="button_step6" class="btn btn-success" type="submit" name="button_step6" value="<?php echo get_lang('InstallChamilo'); ?>">
                    <em class="fa fa-floppy-o"> </em>
                    <?php echo get_lang('InstallChamilo'); ?>
                </button>
                <button class="btn btn-save" id="button_please_wait"></button>
            </td>
        </tr>
    </table>
    <?php
    $form .= ob_get_contents();
    ob_end_clean();
} elseif (@$_POST['step6']) {
    ob_start();
    //STEP 6 : INSTALLATION PROCESS
    $current_step = 7;
    $msg = get_lang('InstallExecution');
    if ($installType === 'update') {
        $msg = get_lang('UpdateExecution');
    }
    $form .= '<div class="RequirementHeading">
      <h3>'.display_step_sequence().$msg.'</h3>';
    if (!empty($installationProfile)) {
        $form .= '    <h3>('.$installationProfile.')</h3>';
    }
    $form .= '<div id="pleasewait" class="alert alert-success">'.get_lang('PleaseWaitThisCouldTakeAWhile').'
      <div class="progress">
      <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
        <span class="sr-only">100% Complete</span>
      </div>
    </div>
      </div>
      </div>';

    if ($installType === 'update') {
        $database = connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            $dbNameForm,
            $dbPortForm
        );
        $manager = $database->getManager();
        $perm = api_get_permissions_for_new_directories();
        $perm_file = api_get_permissions_for_new_files();
        migrateSwitch($my_old_version, $manager);

        // Create .env file
        $envFile = api_get_path(SYS_PATH).'.env';
        $distFile = api_get_path(SYS_PATH).'.env.dist';

        $params = [
            '{{DATABASE_HOST}}' => $dbHostForm,
            '{{DATABASE_PORT}}' => $dbPortForm,
            '{{DATABASE_NAME}}' => $dbNameForm,
            '{{DATABASE_USER}}' => $dbUsernameForm,
            '{{DATABASE_PASSWORD}}' => $dbPassForm,
            '{{APP_INSTALLED}}' => 1,
            '{{APP_ENCRYPT_METHOD}}' => $encryptPassForm,
            '{{APP_URL_APPEND}}' => $urlAppendPath,
        ];

        error_log('Update env file');
        updateEnvFile($distFile, $envFile, $params);
        (new Dotenv())->load($envFile);

        // Load Symfony Kernel
        $kernel = new Kernel('dev', true);
        $application = new Application($kernel);
        error_log('Set Kernel');
        // Create database
        /*$input = new ArrayInput([]);
        $command = $application->find('doctrine:schema:create');
        $result = $command->run($input, new ConsoleOutput());*/

        session_unset();
        $_SESSION = [];
        session_destroy();

        // No errors
        //if ($result == 0) {
        // Boot kernel and get the doctrine from Symfony container
        $kernel->boot();
        error_log('Boot');
        $containerDatabase = $kernel->getContainer();
        upgradeWithContainer($containerDatabase);
        error_log('Set upgradeWithContainer');
    } else {
        set_file_folder_permissions();
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
        $manager->getConnection()->getSchemaManager()->dropAndCreateDatabase($dbNameForm);

        $database = connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            $dbNameForm,
            $dbPortForm
        );

        $manager = $database->getManager();
        // Create .env file
        $envFile = api_get_path(SYS_PATH).'.env';
        $distFile = api_get_path(SYS_PATH).'.env.dist';

        $params = [
            '{{DATABASE_HOST}}' => $dbHostForm,
            '{{DATABASE_PORT}}' => $dbPortForm,
            '{{DATABASE_NAME}}' => $dbNameForm,
            '{{DATABASE_USER}}' => $dbUsernameForm,
            '{{DATABASE_PASSWORD}}' => $dbPassForm,
            '{{APP_INSTALLED}}' => 1,
            '{{APP_ENCRYPT_METHOD}}' => $encryptPassForm,
            '{{APP_URL_APPEND}}' => $urlAppendPath,
        ];

        updateEnvFile($distFile, $envFile, $params);
        (new Dotenv())->load($envFile);

        // Load Symfony Kernel
        $kernel = new Kernel('dev', true);
        $application = new Application($kernel);

        // Create database
        $input = new ArrayInput([]);
        $command = $application->find('doctrine:schema:create');
        $result = $command->run($input, new ConsoleOutput());

        // No errors
        if ($result == 0) {
            session_unset();
            $_SESSION = [];
            session_destroy();

            // Boot kernel and get the doctrine from Symfony container
            $kernel->boot();
            $containerDatabase = $kernel->getContainer();
            $sysPath = api_get_path(SYS_PATH);

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
            include 'install_files.inc.php';
        }
    }

    $form .= display_after_install_message();

    // Hide the "please wait" message sent previously
    $form .= '<script>$(\'#pleasewait\').hide(\'fast\');</script>';
    $form .= ob_get_contents();
    ob_end_clean();
} elseif (@$_POST['step1'] || $badUpdatePath) {
    //STEP 1 : REQUIREMENTS
    //make sure that proposed path is set, shouldn't be necessary but...
    if (empty($proposedUpdatePath)) {
        $proposedUpdatePath = $_POST['updatePath'];
    }
    ob_start();
    display_requirements($installType, $badUpdatePath, $proposedUpdatePath, $update_from_version_8);
    $form .= ob_get_contents();
    ob_end_clean();
} else {
    ob_start();
    // This is the start screen.
    display_language_selection();

    if (!empty($_GET['profile'])) {
        $installationProfile = api_htmlentities($_GET['profile'], ENT_QUOTES);
    }
    echo '<input type="hidden" name="installationProfile" value="'.api_htmlentities($installationProfile, ENT_QUOTES).'" />';
    $form .= ob_get_contents();
    ob_end_clean();
}

$poweredBy = 'Powered by <a href="http://www.chamilo.org" target="_blank"> Chamilo </a> &copy; '.date('Y');
?>
<!DOCTYPE html>
<head>
    <title>&mdash; <?php echo get_lang('ChamiloInstallation').' &mdash; '.get_lang('Version').' '.$new_version; ?></title>
    <style type="text/css" media="screen, projection">
        @import "../../public/build/css/app.css";
        @import "../../public/build/css/themes/chamilo/default.css";
    </style>
    <script type="text/javascript" src="../../public/build/runtime.js"></script>
    <script type="text/javascript" src="../../public/build/app.js"></script>
    <script type="text/javascript">
        $(document).ready( function() {
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
                $("#button_please_wait").html('<?php echo addslashes(get_lang('PleaseWait')); ?>');
                $("#button_please_wait").show();
                $("#button_please_wait").attr('disabled', true);
                $("#is_executable").attr("value",'step6');
            });
        });

        init_visibility=0;
        $(document).ready( function() {
            $(".advanced_parameters").click(function() {
                if ($("#id_contact_form").css("display") == "none") {
                    $("#id_contact_form").css("display","block");
                    $("#img_plus_and_minus").html(
                        '&nbsp;<i class="fa fa-eye" aria-hidden="true"></i>&nbsp;<?php echo get_lang('ContactInformation'); ?>'
                    );
                } else {
                    $("#id_contact_form").css("display","none");
                    $("#img_plus_and_minus").html(
                        '&nbsp;<i class="fa fa-eye-slash" aria-hidden="true"></i>&nbsp;<?php echo get_lang('ContactInformation'); ?>'
                    );
                }
            });
        });

        function send_contact_information() {
            if (!document.getElementById('accept_licence').checked) {
                alert('<?php echo get_lang('YouMustAcceptLicence'); ?>')
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
                            message = "<?php echo get_lang('FormHasErrorsPleaseComplete'); ?>";
                        } else if (datos == '1') {
                            message = "<?php echo get_lang('ContactInformationHasBeenSent'); ?>";
                        } else {
                            message = "<?php echo get_lang('Error').': '.get_lang('ContactInformationHasNotBeenSent'); ?>";
                        }
                        alert(message);
                        $('#license-next').trigger('click');
                        $('#loader-button').html('');
                    }
                });
            }
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
</head>
<body class="bg-chamilo bg-install" dir="<?php echo api_get_text_direction(); ?>">
<div class="install-box">
    <div class="row">
        <div class="col-md-4">
            <div class="logo-install">
                <img src="header-logo.png" class="img-fluid" alt="Chamilo" />
            </div>
            <div class="install-steps">
                <ol class="list-group">
                    <li class="list-group-item <?php step_active('1'); ?>">
                        <span class="number"> 1 </span>
                        <?php echo get_lang('Installation language'); ?>
                    </li>
                    <li class="list-group-item <?php step_active('2'); ?>">
                        <span class="number"> 2 </span>
                        <?php echo get_lang('Requirements'); ?>
                    </li>
                    <li class="list-group-item <?php step_active('3'); ?>">
                        <span class="number"> 3 </span>
                        <?php echo get_lang('Licence'); ?>
                    </li>
                    <li class="list-group-item <?php step_active('4'); ?>">
                        <span class="number"> 4 </span>
                        <?php echo get_lang('DBSetting'); ?>
                    </li>
                    <li class="list-group-item <?php step_active('5'); ?>">
                        <span class="number"> 5 </span>
                        <?php echo get_lang('CfgSetting'); ?>
                    </li>
                    <li class="list-group-item <?php step_active('6'); ?>">
                        <span class="number"> 6 </span>
                        <?php echo get_lang('PrintOverview'); ?>
                    </li>
                    <li class="list-group-item <?php step_active('7'); ?>">
                        <span class="number"> 7 </span>
                        <?php echo get_lang('Installing'); ?>
                    </li>
                </ol>
            </div>
            <div id="note">
                <a class="btn btn-info btn-block" href="<?php echo $installationGuideLink; ?>" target="_blank">
                    <em class="fa fa-file-text-o"></em> <?php echo get_lang('Read the installation guide'); ?>
                </a>
            </div>
        </div>

        <div class="col-md-8">
            <form class="form-horizontal" id="install_form" method="post"
                  action="<?php echo api_get_self(); ?>?running=1&amp;installType=<?php echo $installType; ?>&amp;updateFromConfigFile=<?php echo urlencode($updateFromConfigFile); ?>">
                <?php echo $form; ?>
            </form>
        </div>
        <footer class="install-footer">
            <?php echo $poweredBy; ?>
        </footer>
    </div>
</div>
</body>
</html>
