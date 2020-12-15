<?php
/* For licensing terms, see /license.txt */

/**
 * This script displays error messages on fatal errors during initialization.
 *
 * @package chamilo.include
 *
 * @author Ivan Tcholakov, 2009-2010
 */
$Organisation = '<a href="http://www.chamilo.org" target="_blank">Chamilo Homepage</a>';
$PoweredBy = 'Powered by <a href="http://www.chamilo.org" target="_blank"> Chamilo </a> &copy; '.date('Y');

/**
 * English language variables.
 */

// Sections.
$SectionSystemRequirementsProblem = 'System requirements problem';
$SectionInstallation = 'Installation';
$SectionDatabaseUnavailable = 'Database is unavailable';
$SectionTechnicalIssues = 'Technical issues';
$SectionProtection = 'Protection measure';

// Error code.
$ErrorCode = 'Error code';

// Error code 1.
$IncorrectPhpVersionTitle = 'Incorrect PHP version';
$IncorrectPhpVersionDescription = 'Warning: we have detected that your version of PHP is %s1. To install Chamilo, you need to have PHP %s2 or superior. If you don\'t know what we\'re talking about, please contact your hosting provider or your support team.
    %s3 Read the installation guide.';

// Error code 2.
$InstallationTitle = 'Chamilo has not been installed';
$InstallationDescription = 'Click to INSTALL Chamilo %s or read the installation guide';

// Error code 3.
// Error code 4.
// Error code 5.
$DatabaseUnavailableTitle = 'Database is unavailable';
$DatabaseUnavailableDescription = 'This portal is currently experiencing database issues. Please report this to the portal administrator. Thank you for your help.';

// Error code 6.
$AlreadyInstalledTitle = 'Chamilo has already been installed';
$AlreadyInstalledDescription = 'The system has already been installed. In order to protect its contents, we have to prevent you from starting the installation script again. Please return to the main page.';

// Unspecified error.
$TechnicalIssuesTitle = 'Technical issues';
$TechnicalIssuesDescription = 'This portal is currently experiencing technical issues. Please report this to the portal administrator. Thank you for your help.';

if (is_int($global_error_code) && $global_error_code > 0) {
    if (class_exists('Template') && function_exists('api_get_configuration_value')) {
        $theme = Template::getThemeFallback().'/';
    } else {
        $theme = 'chamilo';
    }

    $root_rel = '';
    $installation_guide_url = $root_rel.'documentation/installation_guide.html';

    $css_path = 'app/Resources/public/css/';
    $css_web_assets = 'web/assets/';
    $css_web_path = 'web/css/';
    $themePath = $css_path.'themes/'.$theme.'/default.css';
    $bootstrap_file = $css_web_assets.'bootstrap/dist/css/bootstrap.min.css';
    $css_base_file = $css_web_path.'base.css';

    $css_list = [$bootstrap_file, $css_base_file, $themePath];

    $web_img = 'main/img';
    $root_sys = str_replace('\\', '/', realpath(__DIR__.'/../../')).'/';

    $css_def = '';
    foreach ($css_list as $cssFile) {
        $cssFile = $root_sys.$cssFile;
        if (file_exists($cssFile)) {
            $css_def .= file_get_contents($cssFile);
        }
    }

    $css_def = str_replace("themes/$theme/", $css_web_path."themes/$theme/", $css_def);

    $global_error_message = [];

    switch ($global_error_code) {
        case 1:
            $global_error_message['section'] = $SectionSystemRequirementsProblem;
            $global_error_message['title'] = $IncorrectPhpVersionTitle;
            $php_version = function_exists('phpversion') ? phpversion() : (defined('PHP_VERSION') ? PHP_VERSION : '');
            $php_version = empty($php_version) ? '' : '(PHP '.$php_version.')';
            $IncorrectPhpVersionDescription = str_replace('%s1', $php_version, $IncorrectPhpVersionDescription);
            $IncorrectPhpVersionDescription = str_replace('%s2', REQUIRED_PHP_VERSION, $IncorrectPhpVersionDescription);
            $pos = strpos($IncorrectPhpVersionDescription, '%s3');
            if ($pos !== false) {
                $length = strlen($IncorrectPhpVersionDescription);
                $read_installation_guide = substr($IncorrectPhpVersionDescription, $pos + 3, $length);
                $IncorrectPhpVersionDescription = substr($IncorrectPhpVersionDescription, 0, $pos);
                $IncorrectPhpVersionDescription .= '<br /><a class="btn btn-default" href="'.$installation_guide_url.'" target="_blank">'.$read_installation_guide.'</a>';
            }
            $global_error_message['description'] = $IncorrectPhpVersionDescription;
            break;
        case 2:
            require __DIR__.'/../install/version.php';
            $global_error_message['section'] = $SectionInstallation;
            $global_error_message['title'] = $InstallationTitle;
            if (($pos = strpos($InstallationDescription, '%s')) === false) {
                $InstallationDescription = 'Click to INSTALL Chamilo %s or read the installation guide';
            }
            $read_installation_guide = substr($InstallationDescription, $pos + 2);
            $versionStatus = (!empty($new_version_status) && $new_version_status != 'stable' ? $new_version_status : '');
            $InstallationDescription = '<form action="'.$root_rel.'main/install/index.php" method="get">
            <div class="row">
                    <div class="col-md-12">
                    <div class="office">
                    <h2 class="title">Welcome to the Chamilo '.$new_version.' '.$new_version_status.' installation wizard</h2>
                    <p class="text">Let\'s start hunting skills down with Chamilo LMS! This wizard will guide you through the Chamilo installation and configuration process.</p>
                          <p class="download-info">
                              <button class="btn btn-primary btn-lg" type="submit" value="INSTALL Chamilo" ><i class="fa fa-download" aria-hidden="true"></i> Install Chamilo</button>
                              <a class="btn btn-success btn-lg" href="'.$installation_guide_url.'" target="_blank"> '.$read_installation_guide.'</a>
                          </p>
                    </div>
                </div>
            </div>
            </form>';
            $global_error_message['description'] = $InstallationDescription;
            break;
        case 3:
        case 4:
        case 5:
            $global_error_message['section'] = $SectionDatabaseUnavailable;
            $global_error_message['title'] = $DatabaseUnavailableTitle;
            $global_error_message['description'] = $DatabaseUnavailableDescription;
            break;
        case 6:
            $global_error_message['section'] = $SectionProtection;
            $global_error_message['title'] = $AlreadyInstalledTitle;
            $global_error_message['description'] = $AlreadyInstalledDescription;
            break;
        default:
            $global_error_message['section'] = $SectionTechnicalIssues;
            $global_error_message['title'] = $TechnicalIssuesTitle;
            $global_error_message['description'] = $TechnicalIssuesDescription;
            break;
    }

    $show_error_codes = defined('SHOW_ERROR_CODES') && SHOW_ERROR_CODES && $global_error_code != 2;
    $global_error_message['code'] = $show_error_codes ? $ErrorCode.': '.$global_error_code.'<br /><br />' : '';
    $global_error_message['details'] = empty($global_error_message['details']) ? '' : ($show_error_codes ? ': '.$global_error_message['details'] : $global_error_message['details']);
    $global_error_message['organisation'] = $Organisation;
    $global_error_message['powered_by'] = $PoweredBy;
    $global_error_message['encoding'] = 'UTF-8';
    $global_error_message['chamilo_logo'] = "data:image/png;base64,".base64_encode(file_get_contents($root_sys.'web/css/themes/'.$theme.'/images/header-logo.png'));
    $bgImage = base64_encode(file_get_contents("$root_sys/main/img/bg_space.png"));
    $bgMoon = base64_encode(file_get_contents("$root_sys/main/img/bg_moon_two.png"));
    $installChamiloImage = "data:image/png;base64,".base64_encode(file_get_contents("$root_sys/main/img/mr_chamilo_install.png"));
    $global_error_message['mr_chamilo'] = $installChamiloImage;

    if ($global_error_code == 2) {
        $global_error_message_page =
<<<EOM
<!DOCTYPE html>
<html>
		<head>
			<title>{TITLE}</title>
            <meta charset="{ENCODING}" />

            <style>
                $css_def
                html, body {min-height:100%; padding:0; margin:0;}

                #wrapper {padding:0; position:absolute; top:0; bottom:0; left:0; right:0;}
                @keyframes animatedBackground {
                    from { background-position: 0 0; }
                    to { background-position: 100% 0; }
                }
                @-webkit-keyframes animatedBackground {
                    from { background-position: 0 0; }
                    to { background-position: 100% 0; }
                }
                @-ms-keyframes animatedBackground {
                    from { background-position: 0 0; }
                    to { background-position: 100% 0; }
                }
                @-moz-keyframes animatedBackground {
                    from { background-position: 0 0; }
                    to { background-position: 100% 0; }
                }
                .install-home{
                    background-image: url("data:image/png;base64,$bgImage");
                    background-position: 0px 0px;
                    background-repeat: repeat;
                    animation: animatedBackground 40s linear infinite;
                    -ms-animation: animatedBackground 40s linear infinite;
                    -moz-animation: animatedBackground 40s linear infinite;
                    -webkit-animation: animatedBackground 40s linear infinite;
                }
                .installer{
                    background: url("data:image/png;base64,$bgMoon") no-repeat center 390px;
                }
                .avatar{
                    text-align: center;
                }
                .avatar .img-responsive{
                    display: initial;
                }
                .office{
                    padding: 10px 20px;
                    //background-color: rgba(35, 40, 56, 0.7);
                    background-color: rgba(0, 22, 48, 0.8);
                    border-radius: 5px;
                }
                @media (max-width: 480px) {
                    .download-info .btn-success{
                        margin-top: 10px;
                    }
                }
            </style>
		</head>
		<body class="install-home">
        <div id="wrapper" class="installer">
            <header>
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="logo">
			                <img src="{CHAMILO_LOGO}"/>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <div id="content">
                <div class="container">
                    <div class="welcome-install">
                        <div class="avatar">
                            <img class="img-responsive" src="{MR_CHAMILO}"/>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="office">
                                    <p class="text">
                                    {DESCRIPTION}
                                    {CODE}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		</body>
</html>
EOM;
    } else {
        $global_error_message_page =
<<<EOM
    <!DOCTYPE html>
        <html>
        <head>
            <title>{TITLE}</title>
            <meta charset="{ENCODING}" />
            <style>
            $css_def
            </style>
        </head>
        <body>
        <div id="page-error">
            <div class="page-wrap">
                <header>
                    <div class="container">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="logo">
                                    <img src="{CHAMILO_LOGO}"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>
                <section id="menu-bar">
                <nav class="navbar navbar-default">
                    <div class="container">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#menuone" aria-expanded="false">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                    </div>
                    <div class="collapse navbar-collapse" id="menuone">
                        <ul class="nav navbar-nav">
                            <li id="current" class="active tab-homepage"><a href="#" target="_self">Homepage</a></li>
                        </ul>
                    </div>
                    </div>
                </nav>
                </section>

                <section id="content-error">
                    <div class="container">
                        <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="alert alert-danger" role="alert">
                                {DESCRIPTION}
                                {CODE}
                            </div>
                        </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        </body>
</html>
EOM;
    }
    foreach ($global_error_message as $key => $value) {
        $global_error_message_page = str_replace('{'.strtoupper($key).'}', $value, $global_error_message_page);
    }
    header('Content-Type: text/html; charset='.$global_error_message['encoding']);
    exit($global_error_message_page);
}
