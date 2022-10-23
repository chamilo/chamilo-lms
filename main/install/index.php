<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Chamilo installation.
 *
 * As seen from the user, the installation proceeds in 6 steps.
 * The user is presented with several web pages where he/she has to make
 * choices and/or fill in data.
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

// Including necessary libraries.
require_once '../inc/lib/api.lib.php';
require_once '../inc/lib/text.lib.php';

api_check_php_version('../inc/');
ob_implicit_flush(true);
session_start();
require_once api_get_path(LIBRARY_PATH).'database.constants.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'text.lib.php';
require_once api_get_path(LIBRARY_PATH).'banner.lib.php';
require_once 'install.lib.php';

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

// Determination of the language during the installation procedure.
if (!empty($_POST['language_list'])) {
    $search = ['../', '\\0'];
    $install_language = str_replace($search, '', urldecode($_POST['language_list']));
    Session::write('install_language', $install_language);
} elseif (isset($_SESSION['install_language']) && $_SESSION['install_language']) {
    $install_language = $_SESSION['install_language'];
} else {
    // Trying to switch to the browser's language, it is covenient for most of the cases.
    $install_language = detect_browser_language();
}

// Language validation.
if (!array_key_exists($install_language, get_language_folder_list())) {
    $install_language = 'english';
}

$installationGuideLink = '../../documentation/installation_guide.html';

// Loading language files.
require api_get_path(SYS_LANG_PATH).'english/trad4all.inc.php';
if ($install_language != 'english') {
    include_once api_get_path(SYS_LANG_PATH).$install_language.'/trad4all.inc.php';
    switch ($install_language) {
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
}

// These global variables must be set for proper working of the function get_lang(...) during the installation.
$language_interface = $install_language;
$language_interface_initial_value = $install_language;

// Character set during the installation, it is always to be 'UTF-8'.
$charset = 'UTF-8';

// Enables the portability layer and configures PHP for UTF-8
\Patchwork\Utf8\Bootup::initAll();

// Page encoding initialization.
header('Content-Type: text/html; charset='.$charset);

// Setting the error reporting levels.
error_reporting(E_ALL);

// Overriding the timelimit (for large campusses that have to be migrated).
@set_time_limit(0);

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

// Try to delete old symfony folder (generates conflicts with composer)
$oldSymfonyFolder = '../inc/lib/symfony';
if (is_dir($oldSymfonyFolder)) {
    @rmdir($oldSymfonyFolder);
}

// A protection measure for already installed systems.
if (isAlreadyInstalledSystem()) {
    // The system has already been installed, so block re-installation.
    $global_error_code = 6;
    require '../inc/global_error_message.inc.php';
    exit;
}

/* STEP 1 : INITIALIZES FORM VARIABLES IF IT IS THE FIRST VISIT */

// Is valid request
$is_valid_request = isset($_REQUEST['is_executable']) ? $_REQUEST['is_executable'] : null;
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
    $installType = isset($_GET['installType']) ? $_GET['installType'] : null;
    $updateFromConfigFile = isset($_GET['updateFromConfigFile']) ? $_GET['updateFromConfigFile'] : false;
}

if ($installType == 'update' && in_array($my_old_version, $update_from_version_8)) {
    // This is the main configuration file of the system before the upgrade.
    // Old configuration file.
    // Don't change to include_once
    $oldConfigPath = api_get_path(SYS_CODE_PATH).'inc/conf/configuration.php';
    if (file_exists($oldConfigPath)) {
        include $oldConfigPath;
    }
}

$session_lifetime = 360000;
$institutionUrlForm = 'http://www.chamilo.org';

if (!isset($_GET['running'])) {
    $dbHostForm = 'localhost';
    $dbUsernameForm = 'root';
    $dbPassForm = '';
    $dbNameForm = 'chamilo';
    $dbPortForm = 3306;

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
    $adminLastName = get_lang('DefaultInstallAdminLastname');
    $adminFirstName = get_lang('DefaultInstallAdminFirstname');
    $loginForm = 'admin';
    $passForm = api_generate_password();

    $campusForm = 'My campus';
    $educationForm = 'Albert Einstein';
    $adminPhoneForm = '(000) 001 02 03';
    $institutionForm = 'My Organisation';
    $institutionUrlForm = 'https://chamilo.org';
    $languageForm = api_get_interface_language();

    $checkEmailByHashSent = 0;
    $ShowEmailNotCheckedToStudent = 1;
    $userMailCanBeEmpty = 1;
    $allowSelfReg = 'approval';
    $allowSelfRegProf = 1; //by default, a user can register as teacher (but moderation might be in place)
    $encryptPassForm = 'bcrypt';
    if (!empty($_GET['profile'])) {
        $installationProfile = api_htmlentities($_GET['profile'], ENT_QUOTES);
    }
} else {
    foreach ($_POST as $key => $val) {
        $magic_quotes_gpc = ini_get('magic_quotes_gpc');
        if (is_string($val)) {
            if ($magic_quotes_gpc) {
                $val = stripslashes($val);
            }
            $val = trim($val);
            $_POST[$key] = $val;
        } elseif (is_array($val)) {
            foreach ($val as $key2 => $val2) {
                if ($magic_quotes_gpc) {
                    $val2 = stripslashes($val2);
                }
                $val2 = trim($val2);
                $_POST[$key][$key2] = $val2;
            }
        }
        $GLOBALS[$key] = $_POST[$key];
    }
}
$dbPortForm = (int) $dbPortForm;

/* NEXT STEPS IMPLEMENTATION */

$total_steps = 7;
if (!$_POST) {
    $current_step = 1;
} elseif (!empty($_POST['language_list']) or !empty($_POST['step1']) or ((!empty($_POST['step2_update_8']) or (!empty($_POST['step2_update_6']))) && ($emptyUpdatePath or $badUpdatePath))) {
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

?>
<!DOCTYPE html>
<head>
    <title>&mdash; <?php echo get_lang('ChamiloInstallation').' &mdash; '.get_lang('Version_').' '.$new_version; ?></title>
    <style type="text/css" media="screen, projection">
        @import "../../web/assets/bootstrap/dist/css/bootstrap.min.css";
        @import "../../web/assets/bootstrap-select/dist/css/bootstrap-select.min.css";
        @import "../../web/assets/fontawesome/css/font-awesome.min.css";
        @import "../../web/css/base.css";
        @import "../../web/css/themes/chamilo/default.css";
        .inputShowPwd > input
        {
            width: 90%;
            display: initial;
        }

    </style>
    <script type="text/javascript" src="../../web/assets/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="../../web/assets/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="../../web/assets/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script type="text/javascript">
        $(function() {
            $("#details_button").click(function() {
                $( "#details" ).toggle("slow", function() {
                });
            });

            $("#button_please_wait").hide();
            $("button").addClass('btn btn-default');

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
        $(function() {
            $(".advanced_parameters").click(function() {
                if ($("#id_contact_form").css("display") == "none") {
                    $("#id_contact_form").css("display","block");
                    $("#img_plus_and_minus").html('&nbsp;<img src="<?php echo Display::returnIconPath('div_hide.gif'); ?>" alt="<?php echo get_lang('Hide'); ?>" title="<?php echo get_lang('Hide'); ?>" style ="vertical-align:middle" >&nbsp;<?php echo get_lang('ContactInformation'); ?>');
                } else {
                    $("#id_contact_form").css("display","none");
                    $("#img_plus_and_minus").html('&nbsp;<img src="<?php echo Display::returnIconPath('div_show.gif'); ?>" alt="<?php echo get_lang('Show'); ?>" title="<?php echo get_lang('Show'); ?>" style ="vertical-align:middle" >&nbsp;<?php echo get_lang('ContactInformation'); ?>');
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
                    beforeSend: function(myObject) {},
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
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
</head>
<body dir="<?php echo api_get_text_direction(); ?>">

<div id="page-install">
<div id="main" class="container">
    <div class="row">
        <div class="panel panel-default">
        <div class="panel-body">

        <div class="col-md-8">
        <form class="form-horizontal" id="install_form" method="post" action="<?php echo api_get_self(); ?>?running=1&amp;installType=<?php echo $installType; ?>&amp;updateFromConfigFile=<?php echo urlencode($updateFromConfigFile); ?>">
<?php

$instalation_type_label = '';
if ($installType == 'new') {
    $instalation_type_label = get_lang('NewInstallation');
} elseif ($installType == 'update') {
    $update_from_version = isset($update_from_version) ? $update_from_version : null;
    $instalation_type_label = get_lang('UpdateFromLMSVersion').(is_array($update_from_version) ? implode('|', $update_from_version) : '');
}

if (!empty($instalation_type_label) && empty($_POST['step6'])) {
    echo '<div class="page-header"><h2>'.$instalation_type_label.'</h2></div>';
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

    ?>
    <input type="hidden" name="updatePath"         value="<?php if (!$badUpdatePath) {
        echo api_htmlentities($proposedUpdatePath, ENT_QUOTES);
    } ?>" />
    <input type="hidden" name="urlAppendPath"      value="<?php echo api_htmlentities($urlAppendPath, ENT_QUOTES); ?>" />
    <input type="hidden" name="pathForm"           value="<?php echo api_htmlentities($pathForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="urlForm"            value="<?php echo api_htmlentities($urlForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="dbHostForm"         value="<?php echo api_htmlentities($dbHostForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="dbPortForm"         value="<?php echo api_htmlentities($dbPortForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="dbUsernameForm"     value="<?php echo api_htmlentities($dbUsernameForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="dbPassForm"         value="<?php echo api_htmlentities($dbPassForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="dbNameForm"         value="<?php echo api_htmlentities($dbNameForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="allowSelfReg"       value="<?php echo api_htmlentities($allowSelfReg, ENT_QUOTES); ?>" />
    <input type="hidden" name="allowSelfRegProf"   value="<?php echo api_htmlentities($allowSelfRegProf, ENT_QUOTES); ?>" />
    <input type="hidden" name="emailForm"          value="<?php echo api_htmlentities($emailForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="adminLastName"      value="<?php echo api_htmlentities($adminLastName, ENT_QUOTES); ?>" />
    <input type="hidden" name="adminFirstName"     value="<?php echo api_htmlentities($adminFirstName, ENT_QUOTES); ?>" />
    <input type="hidden" name="adminPhoneForm"     value="<?php echo api_htmlentities($adminPhoneForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="loginForm"          value="<?php echo api_htmlentities($loginForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="passForm"           value="<?php echo api_htmlentities($passForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="languageForm"       value="<?php echo api_htmlentities($languageForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="campusForm"         value="<?php echo api_htmlentities($campusForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="educationForm"      value="<?php echo api_htmlentities($educationForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="institutionForm"    value="<?php echo api_htmlentities($institutionForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="institutionUrlForm" value="<?php echo $institutionUrlFormResult; ?>" />
    <input type="hidden" name="checkEmailByHashSent" value="<?php echo api_htmlentities($checkEmailByHashSent, ENT_QUOTES); ?>" />
    <input type="hidden" name="ShowEmailNotCheckedToStudent" value="<?php echo api_htmlentities($ShowEmailNotCheckedToStudent, ENT_QUOTES); ?>" />
    <input type="hidden" name="userMailCanBeEmpty" value="<?php echo api_htmlentities($userMailCanBeEmpty, ENT_QUOTES); ?>" />
    <input type="hidden" name="encryptPassForm"    value="<?php echo api_htmlentities($encryptPassForm, ENT_QUOTES); ?>" />
    <input type="hidden" name="session_lifetime"   value="<?php echo api_htmlentities($session_lifetime, ENT_QUOTES); ?>" />
    <input type="hidden" name="old_version"        value="<?php echo api_htmlentities($my_old_version, ENT_QUOTES); ?>" />
    <input type="hidden" name="new_version"        value="<?php echo api_htmlentities($new_version, ENT_QUOTES); ?>" />
    <input type="hidden" name="installationProfile" value="<?php echo api_htmlentities($installationProfile, ENT_QUOTES); ?>" />
<?php

if (@$_POST['step2']) {
    //STEP 3 : LICENSE
    display_license_agreement();
} elseif (@$_POST['step3']) {
    //STEP 4 : MYSQL DATABASE SETTINGS
    display_database_settings_form(
        $installType,
        $dbHostForm,
        $dbUsernameForm,
        $dbPassForm,
        $dbNameForm,
        $dbPortForm,
        $installationProfile
    );
} elseif (@$_POST['step4']) {
    //STEP 5 : CONFIGURATION SETTINGS

    //if update, try getting settings from the database...
    if ($installType == 'update') {
        $db_name = $dbNameForm;

        $manager = connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            $dbNameForm,
            $dbPortForm
        );

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
        $urlForm = $_configuration['root_web'];
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
} elseif (@$_POST['step5']) {
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
        $(function() {
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
                <button type="submit" class="btn btn-default" name="step4" value="&lt; <?php echo get_lang('Previous'); ?>" >
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
} elseif (@$_POST['step6']) {
        //STEP 6 : INSTALLATION PROCESS
        $current_step = 7;
        $msg = get_lang('InstallExecution');
        if ($installType == 'update') {
            $msg = get_lang('UpdateExecution');
        }
        echo '<div class="RequirementHeading">
          <h3>'.display_step_sequence().$msg.'</h3>';
        if (!empty($installationProfile)) {
            echo '    <h3>('.$installationProfile.')</h3>';
        }
        echo '    <div id="pleasewait" class="alert alert-success">'.get_lang('PleaseWaitThisCouldTakeAWhile').'

          <div class="progress">
          <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
            <span class="sr-only">100% Complete</span>
          </div>
        </div>
          </div>
          </div>';

        // Push the web server to send these strings before we start the real
        // installation process
        flush();
        $f = ob_get_contents();
        if (!empty($f)) {
            ob_flush(); //#5565
        }

        if ($installType == 'update') {
            $manager = connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            $dbNameForm,
            $dbPortForm
        );

            $perm = api_get_permissions_for_new_directories();
            $perm_file = api_get_permissions_for_new_files();

            migrateSwitch($my_old_version, $manager);
        } else {
            set_file_folder_permissions();

            $manager = connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            null,
            $dbPortForm
        );

            $dbNameForm = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbNameForm);

            // Drop and create the database anyways
            $manager->getConnection()->getSchemaManager()->dropAndCreateDatabase($dbNameForm);

            $manager = connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            $dbNameForm,
            $dbPortForm
        );

            $sql = getVersionTable();
            $manager->getConnection()->executeQuery($sql);

            $metadataList = $manager->getMetadataFactory()->getAllMetadata();
            $schema = $manager->getConnection()->getSchemaManager()->createSchema();

            // Create database schema
            $tool = new \Doctrine\ORM\Tools\SchemaTool($manager);
            $tool->createSchema($metadataList);

            $connection = $manager->getConnection();
            /*
            $connection->executeQuery(
                'CREATE TABLE page__site (id INT AUTO_INCREMENT NOT NULL, enabled TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, relative_path VARCHAR(255) DEFAULT NULL, host VARCHAR(255) NOT NULL, enabled_from DATETIME DEFAULT NULL, enabled_to DATETIME DEFAULT NULL, is_default TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, locale VARCHAR(6) DEFAULT NULL, title VARCHAR(64) DEFAULT NULL, meta_keywords VARCHAR(255) DEFAULT NULL, meta_description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $connection->executeQuery(
                'CREATE TABLE page__page (id INT AUTO_INCREMENT NOT NULL, site_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, target_id INT DEFAULT NULL, route_name VARCHAR(255) NOT NULL, page_alias VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, position INT NOT NULL, enabled TINYINT(1) NOT NULL, decorate TINYINT(1) NOT NULL, edited TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, slug LONGTEXT DEFAULT NULL, url LONGTEXT DEFAULT NULL, custom_url LONGTEXT DEFAULT NULL, request_method VARCHAR(255) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, meta_keyword VARCHAR(255) DEFAULT NULL, meta_description VARCHAR(255) DEFAULT NULL, javascript LONGTEXT DEFAULT NULL, stylesheet LONGTEXT DEFAULT NULL, raw_headers LONGTEXT DEFAULT NULL, template VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_2FAE39EDF6BD1646 (site_id), INDEX IDX_2FAE39ED727ACA70 (parent_id), INDEX IDX_2FAE39ED158E0B66 (target_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $connection->executeQuery(
                'CREATE TABLE page__snapshot (id INT AUTO_INCREMENT NOT NULL, site_id INT DEFAULT NULL, page_id INT DEFAULT NULL, route_name VARCHAR(255) NOT NULL, page_alias VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, position INT NOT NULL, enabled TINYINT(1) NOT NULL, decorate TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, url LONGTEXT DEFAULT NULL, parent_id INT DEFAULT NULL, target_id INT DEFAULT NULL, content LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', publication_date_start DATETIME DEFAULT NULL, publication_date_end DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_3963EF9AF6BD1646 (site_id), INDEX IDX_3963EF9AC4663E4 (page_id), INDEX idx_snapshot_dates_enabled (publication_date_start, publication_date_end, enabled), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $connection->executeQuery(
                'CREATE TABLE page__bloc (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, page_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, type VARCHAR(64) NOT NULL, settings LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', enabled TINYINT(1) DEFAULT NULL, position INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_FCDC1A97727ACA70 (parent_id), INDEX IDX_FCDC1A97C4663E4 (page_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $connection->executeQuery(
                'CREATE TABLE classification__category (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, context VARCHAR(255) DEFAULT NULL, media_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, position INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_43629B36727ACA70 (parent_id), INDEX IDX_43629B36E25D857E (context), INDEX IDX_43629B36EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $connection->executeQuery(
                'CREATE TABLE classification__context (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $connection->executeQuery(
                'CREATE TABLE classification__tag (id INT AUTO_INCREMENT NOT NULL, context VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_CA57A1C7E25D857E (context), UNIQUE INDEX tag_context (slug, context), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $connection->executeQuery(
                'CREATE TABLE classification__collection (id INT AUTO_INCREMENT NOT NULL, context VARCHAR(255) DEFAULT NULL, media_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_A406B56AE25D857E (context), INDEX IDX_A406B56AEA9FDD75 (media_id), UNIQUE INDEX tag_collection (slug, context), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $connection->executeQuery(
                'CREATE TABLE media__gallery (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, context VARCHAR(64) NOT NULL, default_format VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, updated_at DATETIME NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $connection->executeQuery(
                'CREATE TABLE media__media (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, enabled TINYINT(1) NOT NULL, provider_name VARCHAR(255) NOT NULL, provider_status INT NOT NULL, provider_reference VARCHAR(255) NOT NULL, provider_metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', width INT DEFAULT NULL, height INT DEFAULT NULL, length NUMERIC(10, 0) DEFAULT NULL, content_type VARCHAR(255) DEFAULT NULL, content_size INT DEFAULT NULL, copyright VARCHAR(255) DEFAULT NULL, author_name VARCHAR(255) DEFAULT NULL, context VARCHAR(64) DEFAULT NULL, cdn_is_flushable TINYINT(1) DEFAULT NULL, cdn_flush_identifier VARCHAR(64) DEFAULT NULL, cdn_flush_at DATETIME DEFAULT NULL, cdn_status INT DEFAULT NULL, updated_at DATETIME NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_5C6DD74E12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $connection->executeQuery(
                'CREATE TABLE media__gallery_media (id INT AUTO_INCREMENT NOT NULL, gallery_id INT DEFAULT NULL, media_id INT DEFAULT NULL, position INT NOT NULL, enabled TINYINT(1) NOT NULL, updated_at DATETIME NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_80D4C5414E7AF8F (gallery_id), INDEX IDX_80D4C541EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
            );
            $connection->executeQuery(
                'ALTER TABLE page__page ADD CONSTRAINT FK_2FAE39EDF6BD1646 FOREIGN KEY (site_id) REFERENCES page__site (id) ON DELETE CASCADE'
            );
            $connection->executeQuery(
                'ALTER TABLE page__page ADD CONSTRAINT FK_2FAE39ED727ACA70 FOREIGN KEY (parent_id) REFERENCES page__page (id) ON DELETE CASCADE'
            );
            $connection->executeQuery(
                'ALTER TABLE page__page ADD CONSTRAINT FK_2FAE39ED158E0B66 FOREIGN KEY (target_id) REFERENCES page__page (id) ON DELETE CASCADE'
            );
            $connection->executeQuery(
                'ALTER TABLE page__snapshot ADD CONSTRAINT FK_3963EF9AF6BD1646 FOREIGN KEY (site_id) REFERENCES page__site (id) ON DELETE CASCADE'
            );
            $connection->executeQuery(
                'ALTER TABLE page__snapshot ADD CONSTRAINT FK_3963EF9AC4663E4 FOREIGN KEY (page_id) REFERENCES page__page (id) ON DELETE CASCADE'
            );
            $connection->executeQuery(
                'ALTER TABLE page__bloc ADD CONSTRAINT FK_FCDC1A97727ACA70 FOREIGN KEY (parent_id) REFERENCES page__bloc (id) ON DELETE CASCADE'
            );
            $connection->executeQuery(
                'ALTER TABLE page__bloc ADD CONSTRAINT FK_FCDC1A97C4663E4 FOREIGN KEY (page_id) REFERENCES page__page (id) ON DELETE CASCADE'
            );
            $connection->executeQuery(
                'ALTER TABLE classification__category ADD CONSTRAINT FK_43629B36727ACA70 FOREIGN KEY (parent_id) REFERENCES classification__category (id) ON DELETE CASCADE'
            );
            $connection->executeQuery(
                'ALTER TABLE classification__category ADD CONSTRAINT FK_43629B36E25D857E FOREIGN KEY (context) REFERENCES classification__context (id)'
            );
            $connection->executeQuery(
                'ALTER TABLE classification__category ADD CONSTRAINT FK_43629B36EA9FDD75 FOREIGN KEY (media_id) REFERENCES media__media (id) ON DELETE SET NULL'
            );
            $connection->executeQuery(
                'ALTER TABLE classification__tag ADD CONSTRAINT FK_CA57A1C7E25D857E FOREIGN KEY (context) REFERENCES classification__context (id)'
            );
            $connection->executeQuery(
                'ALTER TABLE classification__collection ADD CONSTRAINT FK_A406B56AE25D857E FOREIGN KEY (context) REFERENCES classification__context (id)'
            );
            $connection->executeQuery(
                'ALTER TABLE classification__collection ADD CONSTRAINT FK_A406B56AEA9FDD75 FOREIGN KEY (media_id) REFERENCES media__media (id) ON DELETE SET NULL'
            );
            $connection->executeQuery(
                'ALTER TABLE media__media ADD CONSTRAINT FK_5C6DD74E12469DE2 FOREIGN KEY (category_id) REFERENCES classification__category (id) ON DELETE SET NULL'
            );
            $connection->executeQuery(
                'ALTER TABLE media__gallery_media ADD CONSTRAINT FK_80D4C5414E7AF8F FOREIGN KEY (gallery_id) REFERENCES media__gallery (id)'
            );
            $connection->executeQuery(
                'ALTER TABLE media__gallery_media ADD CONSTRAINT FK_80D4C541EA9FDD75 FOREIGN KEY (media_id) REFERENCES media__media (id)'
            );

            $connection->executeQuery("CREATE TABLE timeline__timeline (id INT AUTO_INCREMENT NOT NULL, action_id INT DEFAULT NULL, subject_id INT DEFAULT NULL, context VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_FFBC6AD59D32F035 (action_id), INDEX IDX_FFBC6AD523EDC87 (subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
            $connection->executeQuery("CREATE TABLE timeline__component (id INT AUTO_INCREMENT NOT NULL, model VARCHAR(255) NOT NULL, identifier LONGTEXT NOT NULL COMMENT '(DC2Type:array)', hash VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1B2F01CDD1B862B8 (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
            $connection->executeQuery("CREATE TABLE timeline__action (id INT AUTO_INCREMENT NOT NULL, verb VARCHAR(255) NOT NULL, status_current VARCHAR(255) NOT NULL, status_wanted VARCHAR(255) NOT NULL, duplicate_key VARCHAR(255) DEFAULT NULL, duplicate_priority INT DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
            $connection->executeQuery("CREATE TABLE timeline__action_component (id INT AUTO_INCREMENT NOT NULL, action_id INT DEFAULT NULL, component_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, text VARCHAR(255) DEFAULT NULL, INDEX IDX_6ACD1B169D32F035 (action_id), INDEX IDX_6ACD1B16E2ABAFFF (component_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
            $connection->executeQuery("ALTER TABLE timeline__timeline ADD CONSTRAINT FK_FFBC6AD59D32F035 FOREIGN KEY (action_id) REFERENCES timeline__action (id);");
            $connection->executeQuery("ALTER TABLE timeline__timeline ADD CONSTRAINT FK_FFBC6AD523EDC87 FOREIGN KEY (subject_id) REFERENCES timeline__component (id) ON DELETE CASCADE;");
            $connection->executeQuery("ALTER TABLE timeline__action_component ADD CONSTRAINT FK_6ACD1B169D32F035 FOREIGN KEY (action_id) REFERENCES timeline__action (id) ON DELETE CASCADE;");
            $connection->executeQuery("ALTER TABLE timeline__action_component ADD CONSTRAINT FK_6ACD1B16E2ABAFFF FOREIGN KEY (component_id) REFERENCES timeline__component (id) ON DELETE CASCADE;");
            //$connection->executeQuery("CREATE UNIQUE INDEX UNIQ_8D93D649A0D96FBF ON user (email_canonical);");
            $connection->executeQuery("ALTER TABLE fos_group ADD name VARCHAR(255) NOT NULL;");
            $connection->executeQuery("ALTER TABLE fos_group ADD roles LONGTEXT NOT NULL COMMENT '(DC2Type:array)'");

            $connection->executeQuery("CREATE TABLE faq_question_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT DEFAULT NULL, headline VARCHAR(255) NOT NULL, body LONGTEXT DEFAULT NULL, slug VARCHAR(50) NOT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_C2D1A2C2AC5D3 (translatable_id), UNIQUE INDEX faq_question_translation_unique_translation (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
            $connection->executeQuery("CREATE TABLE faq_category_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT DEFAULT NULL, headline VARCHAR(255) NOT NULL, body LONGTEXT DEFAULT NULL, slug VARCHAR(50) NOT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_5493B0FC2C2AC5D3 (translatable_id), UNIQUE INDEX faq_category_translation_unique_translation (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
            $connection->executeQuery("CREATE TABLE faq_category (id INT AUTO_INCREMENT NOT NULL, rank INT NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX is_active_idx (is_active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
            $connection->executeQuery("CREATE TABLE faq_question (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, is_active TINYINT(1) NOT NULL, rank INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, only_auth_users TINYINT(1) NOT NULL, INDEX IDX_4A55B05912469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
            $connection->executeQuery("ALTER TABLE faq_question_translation ADD CONSTRAINT FK_C2D1A2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES faq_question (id) ON DELETE CASCADE;");
            $connection->executeQuery("ALTER TABLE faq_category_translation ADD CONSTRAINT FK_5493B0FC2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES faq_category (id) ON DELETE CASCADE;");
            $connection->executeQuery("ALTER TABLE faq_question ADD CONSTRAINT FK_4A55B05912469DE2 FOREIGN KEY (category_id) REFERENCES faq_category (id);");
            */

            $sysPath = api_get_path(SYS_PATH);

            finishInstallation(
            $manager,
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

        display_after_install_message($installType);

        // Hide the "please wait" message sent previously
        echo '<script>$(\'#pleasewait\').hide(\'fast\');</script>';
    } elseif (@$_POST['step1'] || $badUpdatePath) {
        //STEP 1 : REQUIREMENTS
        //make sure that proposed path is set, shouldn't be necessary but...
        if (empty($proposedUpdatePath)) {
            $proposedUpdatePath = $_POST['updatePath'];
        }
        display_requirements($installType, $badUpdatePath, $proposedUpdatePath, $update_from_version_8);
    } else {
        // This is the start screen.
        display_language_selection();
        if (!empty($_GET['profile'])) {
            $installationProfile = api_htmlentities($_GET['profile'], ENT_QUOTES);
        }
        echo '<input type="hidden" name="installationProfile" value="'.api_htmlentities($installationProfile, ENT_QUOTES).'" />';
    }

$poweredBy = 'Powered by <a href="http://www.chamilo.org" target="_blank"> Chamilo </a> &copy; '.date('Y');
?>
          </form>
        </div>
        <div class="col-md-4">
            <div class="logo-install">
                <img src="<?php echo api_get_path(WEB_CSS_PATH); ?>themes/chamilo/images/header-logo.png" hspace="10" vspace="10" alt="Chamilo" />
            </div>
            <div class="well install-steps-menu">
                <ol>
                    <li <?php step_active('1'); ?>><?php echo get_lang('InstallationLanguage'); ?></li>
                    <li <?php step_active('2'); ?>><?php echo get_lang('Requirements'); ?></li>
                    <li <?php step_active('3'); ?>><?php echo get_lang('Licence'); ?></li>
                    <li <?php step_active('4'); ?>><?php echo get_lang('DBSetting'); ?></li>
                    <li <?php step_active('5'); ?>><?php echo get_lang('CfgSetting'); ?></li>
                    <li <?php step_active('6'); ?>><?php echo get_lang('PrintOverview'); ?></li>
                    <li <?php step_active('7'); ?>><?php echo get_lang('Installing'); ?></li>
                </ol>
            </div>
            <div id="note">
                <a class="btn btn-primary btn-block" href="<?php echo $installationGuideLink; ?>" target="_blank">
                    <em class="fa fa-file-text-o"></em> <?php echo get_lang('ReadTheInstallationGuide'); ?>
                </a>
            </div>
        </div>
        </div>
        </div>

        <div class="panel panel-default">
        <div class="panel-body">
            <div class="col-md-12">
                <div style="text-align: center;">
                    <?php echo $poweredBy; ?>
                </div>
            </div>
        </div>
        </div>
    </div>
  </div>
    <script type="text/javascript">
        $('#showPassword').on('change',function(){
            if($(this).prop('checked')){
                $('.form-control[name="passForm"]').attr('type','text');
                $('.showPasswordEye').removeClass('fa-eye').addClass('fa-eye-slash');

            }else{
                $('.form-control[name="passForm"]').attr('type','password');
                $('.showPasswordEye').addClass('fa-eye').removeClass('fa-eye-slash');
            }
        });
    </script>
</body>
</html>
