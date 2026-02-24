<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ScimHelper;
use Chamilo\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
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

// Create .env file
/*$envFile = api_get_path(SYMFONY_SYS_PATH).'.env';
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

Container::$session = new HttpSession();

require_once 'install.lib.php';

$envFile = api_get_path(SYMFONY_SYS_PATH).'.env';
$versionInfo = require __DIR__.'/version.php';
$installerVersion = $versionInfo['new_version'] ?? null;

if (file_exists($envFile)) {
    $dotenv = new Dotenv();
    try {
        // Load .env without crashing if incomplete
        $dotenv->loadEnv($envFile);
    } catch (\Throwable $e) {
        // Ignore and let the wizard continue
    }

    $appInstalled = (string) (
            $_SERVER['APP_INSTALLED']
            ?? $_ENV['APP_INSTALLED']
            ?? getenv('APP_INSTALLED')
            ?? ''
        ) === '1';

    if ($appInstalled && $installerVersion) {
        $dbVersion = null;
        $dbLooksInitialized = false;

        try {
            $dbHost = (string) ($_SERVER['DATABASE_HOST'] ?? $_ENV['DATABASE_HOST'] ?? getenv('DATABASE_HOST') ?? 'localhost');
            $dbUser = (string) ($_SERVER['DATABASE_USER'] ?? $_ENV['DATABASE_USER'] ?? getenv('DATABASE_USER') ?? '');
            $dbPass = (string) ($_SERVER['DATABASE_PASSWORD'] ?? $_ENV['DATABASE_PASSWORD'] ?? getenv('DATABASE_PASSWORD') ?? '');
            $dbName = (string) ($_SERVER['DATABASE_NAME'] ?? $_ENV['DATABASE_NAME'] ?? getenv('DATABASE_NAME') ?? '');
            $dbPort = (int) ($_SERVER['DATABASE_PORT'] ?? $_ENV['DATABASE_PORT'] ?? getenv('DATABASE_PORT') ?? 3306);

            // Connect using the legacy installer helpers
            connectToDatabase($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

            $conn = Database::getManager()->getConnection();

            // Fast "is initialized?" proof:
            // - if settings_current (or settings) exists AND has at least 1 row, we treat it as initialized.
            // Avoid schema introspection for performance and reliability.
            try {
                $hasAnySetting = $conn->fetchOne('SELECT 1 FROM settings_current LIMIT 1');
                if ($hasAnySetting !== false && $hasAnySetting !== null) {
                    $dbLooksInitialized = true;

                    $dbVersion = $conn->fetchOne(
                        "SELECT selected_value FROM settings_current WHERE variable = 'chamilo_database_version' LIMIT 1"
                    );
                }
            } catch (\Throwable $e) {
                // Ignore and try legacy table
            }

            if (!$dbLooksInitialized) {
                try {
                    $hasAnySetting = $conn->fetchOne('SELECT 1 FROM settings LIMIT 1');
                    if ($hasAnySetting !== false && $hasAnySetting !== null) {
                        $dbLooksInitialized = true;

                        $dbVersion = $conn->fetchOne(
                            "SELECT selected_value FROM settings WHERE variable = 'chamilo_database_version' LIMIT 1"
                        );
                    }
                } catch (\Throwable $e) {
                    // No settings tables -> DB is not initialized
                }
            }
        } catch (\Throwable $e) {
            // If we cannot connect, do not block the wizard
            $dbLooksInitialized = false;
            $dbVersion = null;
        }

        // Block ONLY if DB is initialized AND version is up-to-date.
        $dbVersion = is_string($dbVersion) ? trim($dbVersion) : '';
        if ($dbLooksInitialized && $dbVersion !== '' && version_compare($dbVersion, $installerVersion, '>=')) {
            header('HTTP/1.1 409 Conflict');
            echo '<!doctype html><meta charset="utf-8"><title>Chamilo already installed</title>';
            echo '<div style="font-family:system-ui;max-width:760px;margin:64px auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px">';
            echo '<h1>Chamilo is already installed</h1>';
            echo '<p>The install wizard is disabled because the platform is already installed and up-to-date.</p>';
            echo '<p>If you need a fresh install, set <code>APP_INSTALLED=0</code> or remove <code>.env</code> first.</p>';
            echo '</div>';
            exit;
        }

        // If APP_INSTALLED=1 but DB is NOT initialized, we intentionally allow the wizard to run.
    }
}

$httpRequest = Request::createFromGlobals();
$installationLanguage = 'en_US';

$langParam = $httpRequest->get('language_list');
if ($langParam !== null && $langParam !== '') {
    $search = ['../', '\\0'];
    $installationLanguage = str_replace($search, '', urldecode($langParam));
    ChamiloSession::write('install_language', $installationLanguage);
} elseif (ChamiloSession::has('install_language')) {
    $installationLanguage = ChamiloSession::read('install_language');
} else {
    $installationLanguage = detectBrowserLanguage($httpRequest);
}

// Set translation
$translator = new Translator($installationLanguage);
$translator->addLoader('po', new PoFileLoader());

$langResourceFile = api_get_path(SYMFONY_SYS_PATH).'translations/messages.'.(explode('_', $installationLanguage, 2)[0]).'.po';

if (file_exists($langResourceFile)) {
    $translator->addResource('po', $langResourceFile, $installationLanguage);
}

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
$institutionUrlForm = 'https://chamilo.org';
$languageForm = $installationLanguage;
$campusForm = 'My campus';
$educationForm = 'Albert Einstein';
$adminPhoneForm = '(000) 001 02 03';
$institutionForm = 'My Organisation';
$session_lifetime = 360000;
$installationGuideLink = '../../documentation/installation_guide.html';
$mailerFromEmail = $_POST['mailerFromEmail'] ?? '';
$mailerFromName = $_POST['mailerFromName'] ?? '';
$mailerDsn = $_POST['mailerDsn'] ?? ($_POST['mailer_dsn'] ?? '');

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
    '1.11.16',
    '1.11.18',
    '1.11.20',
    '1.11.22',
    '1.11.24',
    '1.11.26',
    '1.11.28',
    '1.11.30',
    '1.11.32',
    '1.11.34',
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

$checkMigrationStatus = [];
$isUpdateAvailable = isUpdateAvailable();
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
    $installType = $_GET['installType'] ?? '';
    $updateFromConfigFile = '';
    unset($_GET['running']);
} else {
    $installType = $_GET['installType'] ?? '';
    $updateFromConfigFile = $_GET['updateFromConfigFile'] ?? false;
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
    $passForm = api_generate_password(12, false);
    $institutionUrlForm = 'https://chamilo.org';
    $checkEmailByHashSent = 0;
    $userMailCanBeEmpty = 1;
    $allowSelfReg = 'approval';
    $allowSelfRegProf = 1; //by default, a user can register as teacher (but moderation might be in place)
    if (!empty($_GET['profile'])) {
        $installationProfile = htmlentities($_GET['profile']);
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
} elseif ($httpRequest->request->get('language_list') || !empty($_POST['step1']) || ((!empty($_POST['step2_update_8']) || (!empty($_POST['step2_update_6']))) && ($emptyUpdatePath || $badUpdatePath))) {
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

if (empty($installationProfile)) {
    $installationProfile = '';
    if (!empty($_POST['installationProfile'])) {
        $installationProfile = htmlentities($_POST['installationProfile']);
    }
}

$institutionUrlFormResult = '';
$institutionUrlFormResult = $institutionUrlForm;

$stepData = [];

if (isset($_POST['step2'])) {
    // STEP 3 : LICENSE
    $current_step = 3;
    $stepData = display_license_agreement();
} elseif (isset($_POST['step3'])) {
    $current_step = 4;
    // STEP 4 : MYSQL DATABASE SETTINGS
    $stepData = display_database_settings_form(
        $installType,
        $dbHostForm,
        $dbUsernameForm,
        $dbPassForm,
        $dbNameForm,
        $dbPortForm
    );
} elseif (isset($_POST['step4'])) {
    $current_step = 5;
    // STEP 5 : CONFIGURATION SETTINGS
    if ('update' === $installType) {
        // Create .env file
        $envFile = api_get_path(SYMFONY_SYS_PATH) . '.env';
        $distFile = api_get_path(SYMFONY_SYS_PATH) . '.env.dist';
        $params = [
            '{{DATABASE_HOST}}' => $dbHostForm,
            '{{DATABASE_PORT}}' => $dbPortForm,
            '{{DATABASE_NAME}}' => $dbNameForm,
            '{{DATABASE_USER}}' => $dbUsernameForm,
            '{{DATABASE_PASSWORD}}' => $dbPassForm,
            '{{APP_INSTALLED}}' => 1,
            '{{APP_ENCRYPT_METHOD}}' => $encryptPassForm,
            '{{APP_SECRET}}' => generateRandomToken(),
            '{{DB_MANAGER_ENABLED}}' => '0',
            '{{SOFTWARE_NAME}}' => 'Chamilo',
            '{{SOFTWARE_URL}}' => $institutionUrlForm,
            '{{DENY_DELETE_USERS}}' => '0',
            '{{HOSTING_TOTAL_SIZE_LIMIT}}' => '0',
            '{{THEME_FALLBACK}}' => 'chamilo',
            '{{PACKAGER}}' => 'chamilo',
            '{{DEFAULT_TEMPLATE}}' => 'default',
            '{{ADMIN_CHAMILO_ANNOUNCEMENTS_DISABLE}}' => '0',
            '{{SCIM_TOKEN}}' => ScimHelper::createToken(),
        ];
        error_log('Update env file');
        updateEnvFile($distFile, $envFile, $params);
        (new Dotenv())->load($envFile);

        $db_name = $dbNameForm;
        connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            $dbNameForm,
            $dbPortForm
        );
        $manager = Database::getManager();

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

    $stepData = display_configuration_settings_form(
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
} elseif (isset($_POST['step5'])) {
    $current_step = 6;
    //STEP 6 : LAST CHECK BEFORE INSTALL

    if ('new' === $installType) {
        $stepData['loginForm'] = $loginForm;
        $stepData['passForm'] = $passForm;
    }

    $stepData['adminFirstName'] = $adminFirstName;
    $stepData['adminLastName'] = $adminLastName;
    $stepData['emailForm'] = $emailForm;
    $stepData['adminPhoneForm'] = $adminPhoneForm;
    $stepData['mailerFromEmail'] = $mailerFromEmail;
    $stepData['mailerFromName'] = $mailerFromName;
    $stepData['mailerDsn'] = $mailerDsn;

    $allowSelfRegistrationLiteral = match ($allowSelfReg) {
        'true' => get_lang('Yes'),
        'approval' => get_lang('Approval'),
        default => get_lang('No'),
    };

    if ('update' === $installType) {
        $urlForm = get_config_param('root_web');
    }

    $stepData['campusForm'] = $campusForm;
    $stepData['languageForm'] = $languageForm;
    $stepData['allowSelfRegistrationLiteral'] = $allowSelfRegistrationLiteral;
    $stepData['institutionForm'] = $institutionForm;
    $stepData['institutionUrlForm'] = $institutionUrlForm;
    $stepData['encryptPassForm'] = $encryptPassForm;

    if ($isUpdateAvailable) {
        $envFile = api_get_path(SYMFONY_SYS_PATH) . '.env';
        $dotenv = new Dotenv();
        $envFile = api_get_path(SYMFONY_SYS_PATH) . '.env';
        $dotenv->loadEnv($envFile);
        $stepData['dbHostForm'] = $_ENV['DATABASE_HOST'];
        $stepData['dbPortForm'] = $_ENV['DATABASE_PORT'];
        $stepData['dbUsernameForm'] = $_ENV['DATABASE_USER'];
        $stepData['dbPassForm'] = str_repeat('*', api_strlen($_ENV['DATABASE_PASSWORD']));
        $stepData['dbNameForm'] = $_ENV['DATABASE_NAME'];
    } else {
        $stepData['dbHostForm'] = $dbHostForm;
        $stepData['dbPortForm'] = $dbPortForm;
        $stepData['dbUsernameForm'] = $dbUsernameForm;
        $stepData['dbPassForm'] = str_repeat('*', api_strlen($dbPassForm));
        $stepData['dbNameForm'] = $dbNameForm;
    }
} elseif (isset($_POST['step6'])) {
    //STEP 6 : INSTALLATION PROCESS
    $current_step = 7;

    if ('update' === $installType) {
        // The migration process for updates has been moved to migrate.php and is now
        // handled via AJAX requests from Vue.js. This section of the code is no longer
        // necessary and has been removed to streamline the update process.

        error_log('Migration process moved to migrate.php');
        error_log('Upgrade 2.0.0 process concluded!  ('.date('Y-m-d H:i:s').')');
    } else {
        error_log('------------------------------');
        $start = date('Y-m-d H:i:s');
        error_log('Chamilo installation starts: (' . $start . ')');

        set_file_folder_permissions();

        // Sanitize database name: only letters, numbers and underscore
        $dbNameForm = preg_replace('/[^a-zA-Z0-9_]/', '', $dbNameForm);

        error_log("Connect to DB server as user {$dbUsernameForm}");

        // Connect to DB server (without selecting a database)
        connectToDatabase(
            $dbHostForm,
            $dbUsernameForm,
            $dbPassForm,
            null,
            $dbPortForm
        );

        $manager = Database::getManager();
        $schemaManager = $manager->getConnection()->createSchemaManager();

        // Create database if it does not exist (reuse if it exists)
        error_log("Ensure database exists: {$dbNameForm}");

        $dbExists = false;
        try {
            // Some platforms/drivers support listing databases
            $databases = $schemaManager->listDatabases();
            $dbExists = in_array($dbNameForm, $databases, true);
        } catch (\Throwable $e) {
            // If listing is not supported, we'll try to create and ignore "already exists" errors
            error_log('Database listing not supported, falling back to createDatabase() attempt.');
        }

        if (!$dbExists) {
            try {
                $schemaManager->createDatabase($dbNameForm);
                error_log("Database created: {$dbNameForm}");
            } catch (\Throwable $e) {
                // If it already exists or we lack permissions, we will try to continue anyway
                error_log("Could not create database (may already exist): {$dbNameForm} - " . $e->getMessage());
            }
        } else {
            error_log("Database already exists, it will be reused: {$dbNameForm}");
        }

        // Connect to the target database
        error_log("Connect to database {$dbNameForm} with user {$dbUsernameForm}");
        try {
            connectToDatabase(
                $dbHostForm,
                $dbUsernameForm,
                $dbPassForm,
                $dbNameForm,
                $dbPortForm
            );
        } catch (\Throwable $e) {
            error_log('ERROR: Could not connect to target database: ' . $e->getMessage());
            $result = 1;
        }

        if (empty($result)) {
            $manager = Database::getManager();
            $conn = $manager->getConnection();
            $dbSchemaManager = $conn->createSchemaManager();
            $platform = $conn->getDatabasePlatform();

            // If there are tables, drop them (no DROP DATABASE required)
            try {
                $tables = $dbSchemaManager->listTableNames();

                if (count($tables) > 0) {
                    error_log('Existing tables detected. Cleaning database (dropping tables)...');

                    // MySQL / MariaDB: disable FK checks for easier cleanup
                    if ($platform instanceof \Doctrine\DBAL\Platforms\MySQLPlatform) {
                        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
                    }

                    // PostgreSQL: easiest clean is dropping public schema (if permitted)
                    if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
                        try {
                            $conn->executeStatement('DROP SCHEMA public CASCADE');
                            $conn->executeStatement('CREATE SCHEMA public');
                            error_log('PostgreSQL public schema recreated.');
                        } catch (\Throwable $e) {
                            // Fallback: try dropping tables one by one (may fail if constraints exist)
                            error_log('Could not recreate public schema, falling back to per-table drop: ' . $e->getMessage());
                            foreach ($tables as $t) {
                                try {
                                    $dbSchemaManager->dropTable($t);
                                } catch (\Throwable $dropError) {
                                    error_log("Could not drop table {$t}: " . $dropError->getMessage());
                                }
                            }
                        }
                    } else {
                        // Default behavior: drop tables one by one
                        foreach ($tables as $t) {
                            try {
                                $dbSchemaManager->dropTable($t);
                            } catch (\Throwable $dropError) {
                                error_log("Could not drop table {$t}: " . $dropError->getMessage());
                            }
                        }
                    }

                    if ($platform instanceof \Doctrine\DBAL\Platforms\MySQLPlatform) {
                        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
                    }

                    error_log('Database cleanup completed.');
                } else {
                    error_log('Database is empty. No cleanup needed.');
                }
            } catch (\Throwable $e) {
                error_log('Database cleanup check failed (continuing): ' . $e->getMessage());
            }

            // Create .env file
            $envFile = api_get_path(SYMFONY_SYS_PATH) . '.env';
            $distFile = api_get_path(SYMFONY_SYS_PATH) . '.env.dist';

            $params = [
                '{{DATABASE_HOST}}' => $dbHostForm,
                '{{DATABASE_PORT}}' => $dbPortForm,
                '{{DATABASE_NAME}}' => $dbNameForm,
                '{{DATABASE_USER}}' => $dbUsernameForm,
                '{{DATABASE_PASSWORD}}' => $dbPassForm,
                '{{APP_INSTALLED}}' => 1,
                '{{APP_ENCRYPT_METHOD}}' => $encryptPassForm,
                '{{APP_SECRET}}' => generateRandomToken(),
                '{{DB_MANAGER_ENABLED}}' => '0',
                '{{SOFTWARE_NAME}}' => 'Chamilo',
                '{{SOFTWARE_URL}}' => $institutionUrlForm,
                '{{DENY_DELETE_USERS}}' => '0',
                '{{HOSTING_TOTAL_SIZE_LIMIT}}' => '0',
                '{{THEME_FALLBACK}}' => 'chamilo',
                '{{PACKAGER}}' => 'chamilo',
                '{{DEFAULT_TEMPLATE}}' => 'default',
                '{{ADMIN_CHAMILO_ANNOUNCEMENTS_DISABLE}}' => '0',
                '{{SCIM_TOKEN}}' => ScimHelper::createToken(),
            ];

            updateEnvFile($distFile, $envFile, $params);
            (new Dotenv())->load($envFile);

            error_log('Load kernel');
            // Load Symfony Kernel
            $kernel = new Kernel('dev', true);
            $application = new Application($kernel);

            // Create database schema
            error_log('Create database schema');
            $input = new ArrayInput([]);
            $command = $application->find('doctrine:schema:create');
            $result = $command->run($input, new ConsoleOutput());

            // Load fixtures (no errors)
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
                    $installationProfile,
                    $mailerDsn,
                    $mailerFromEmail,
                    $mailerFromName,
                    $kernel
                );

                error_log('Finish installation');
            } else {
                error_log('ERROR during installation.');
            }
        }
    }
} elseif (isset($_POST['step1']) || $badUpdatePath) {
    //STEP 1 : REQUIREMENTS
    //make sure that proposed path is set, shouldn't be necessary but...
    if (empty($proposedUpdatePath)) {
        $proposedUpdatePath = $_POST['updatePath'];
    }

    $stepData = display_requirements(
        $installType,
        $badUpdatePath,
        $proposedUpdatePath,
        $upgradeFromVersion
    );
} else {
    // This is the start screen.
    if (!empty($_GET['profile'])) {
        $installationProfile = $_GET['profile'];
    }

    $stepData['installationProfile'] = $installationProfile;
}

if ($isUpdateAvailable) {
    $installType = 'update';
}
$installerData = [
    'poweredBy' => 'Powered by <a href="https://chamilo.org" target="_blank">Chamilo</a> &copy; '.date('Y'),

    'phpRequiredVersion' => REQUIRED_PHP_VERSION,

    'installType' => $installType,

    'badUpdatePath' => $badUpdatePath,

    'upgradeFromVersion' => $upgradeFromVersion,

    'langIso' => $installationLanguage,

    'formAction' => api_get_self().'?'.http_build_query([
            'running' => 1,
            'installType' => $installType,
            'updateFromConfigFile' => $updateFromConfigFile,
        ]),

    'updatePath' => !$badUpdatePath ? $proposedUpdatePath : '',
    'urlAppendPath' => $urlAppendPath,
    'pathForm' => $pathForm,
    'urlForm' => $urlForm,
    'dbHostForm' => $dbHostForm,
    'dbPortForm' => $dbPortForm,
    'dbUsernameForm' => $dbUsernameForm,
    'dbPassForm' => $dbPassForm,
    'dbNameForm' => $dbNameForm,
    'allowSelfReg' => $allowSelfReg,
    'allowSelfRegProf' => $allowSelfRegProf,
    'emailForm' => $emailForm,
    'adminLastName' => $adminLastName,
    'adminFirstName' => $adminFirstName,
    'adminPhoneForm' => $adminPhoneForm,
    'loginForm' => $loginForm,
    'passForm' => $passForm,
    'languageForm' => $languageForm,
    'campusForm' => $campusForm,
    'educationForm' => $educationForm,
    'institutionForm' => $institutionForm,
    'institutionUrlForm' => $institutionUrlFormResult,
    'checkEmailByHashSent' => $checkEmailByHashSent,
    'showEmailNotCheckedToStudent' => $showEmailNotCheckedToStudent,
    'userMailCanBeEmpty' => $userMailCanBeEmpty,
    'encryptPassForm' => $encryptPassForm,
    'session_lifetime' => $session_lifetime,
    'old_version' => $my_old_version,
    'new_version' => $new_version,
    'installationProfile' => $installationProfile,
    'currentStep' => $current_step,
    'isUpdateAvailable' => $isUpdateAvailable,
    'checkMigrationStatus' => $checkMigrationStatus,
    'logUrl' => '/main/install/get_migration_status.php',
    'stepData' => $stepData,
];

function getEncoreAssetFromManifest(string $assetName): ?string
{
    $manifestFilePath = __DIR__.'/../../../public/build/manifest.json';

    if (!file_exists($manifestFilePath)) {
        return null;
    }


    $manifestPlain = file_get_contents($manifestFilePath);
    $manifestJson = json_decode($manifestPlain, true);

    if (isset($manifestJson[$assetName])) {
        return $manifestJson[$assetName];
    }

    return null;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $installationLanguage ?>" class="no-js h-100">
<head>
    <title>
        &mdash; <?php echo $translator->trans('Chamilo installation').' &mdash; '.$translator->trans('Version').' '.$new_version; ?>
    </title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <style>
        :root {
            --color-primary-base: 46 117 163;
            --color-primary-gradient: 36 77 103;
            --color-primary-button-text: 46 117 163;
            --color-primary-button-alternative-text: 255 255 255;

            --color-secondary-base: 243 126 47;
            --color-secondary-gradient: 224 100 16;
            --color-secondary-button-text: 255 255 255;

            --color-tertiary-base: 51 51 51;
            --color-tertiary-gradient: 0 0 0;
            --color-tertiary-button-text: 255 255 255;

            --color-success-base: 119 170 12;
            --color-success-gradient: 83 127 0;
            --color-success-button-text: 255 255 255;

            --color-info-base: 13 123 253;
            --color-info-gradient: 0 84 211;
            --color-info-button-text: 255 255 255;

            --color-warning-base: 245 206 1;
            --color-warning-gradient: 186 152 0;
            --color-warning-button-text: 0 0 0;

            --color-danger-base: 223 59 59;
            --color-danger-gradient: 180 0 21;
            --color-danger-button-text: 255 255 255;

            --color-form-base: 46 117 163;
        }
    </style>
    <link rel="stylesheet" href="<?php echo getEncoreAssetFromManifest('public/build/app.css'); ?>">
    <link rel="stylesheet" href="<?php echo getEncoreAssetFromManifest('public/build/vue.css'); ?>">
</head>
<body class="flex min-h-screen p-2 md:px-16 md:py-8 xl:px-32 xl:py-16 bg-gradient-to-br from-primary to-primary-gradient">
<div id="app" class="m-auto"></div>
<script>
  var installerData = <?php echo json_encode($installerData) ?>;
</script>
<script type="text/javascript" src="<?php echo getEncoreAssetFromManifest('public/build/runtime.js'); ?>"></script>
<script type="text/javascript" src="<?php echo getEncoreAssetFromManifest('public/build/vue_installer.js'); ?>"></script>
</body>
</html>
