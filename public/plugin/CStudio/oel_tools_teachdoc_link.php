<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Symfony\Component\HttpFoundation\RedirectResponse;

require_once __DIR__.'/0_dal/dal.global_lib.php';

require_once __DIR__.'/0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/teachdoc_hub.php';

require_once __DIR__.'/ajax/inc/functions.php';

require_once __DIR__.'/inc/csrf_token.php';

require_once __DIR__.'/0_dal/dal.insert.php';

$language = 'en';
$iso = api_get_language_isocode();

if (!$VDB->w_api_is_anonymous()) {
    $user = $VDB->w_api_get_user_info();

    if (isset($user['status'])) {
        if (SESSIONADMIN == $user['status']
        || COURSEMANAGER == $user['status']
        || PLATFORM_ADMIN == $user['status']) {
        } else {
            Display::addFlash(
                Display::return_message(get_lang('NotAllowed'), 'error')
            );

            (new RedirectResponse('../../index.php'))->send();

            exit;
        }
    }
} else {
    Display::addFlash(
        Display::return_message(get_lang('NotAllowed'), 'error')
    );

    (new RedirectResponse('../../index.php'))->send();

    exit;
}

$userId = $VDB->w_api_get_user_id();

$cotk = generateCSRFToken($userId);

$vers = 6;

$plugin = teachdoc_hub::create();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$table = 'plugin_oel_tools_teachdoc';

$action = isset($_GET['action']) ? $VDB->remove_XSS($_GET['action']) : 'add';
$cid = isset($_GET['cid']) ? (int) $VDB->remove_XSS($_GET['cid']) : '';
$idLudiLP = isset($_GET['idLudiLP']) ? (int) $_GET['idLudiLP'] : 0;

if (0 == $idLudiLP) {
    if (!isset($_GET['cotk']) || $_GET['cotk'] != $cotk) {
        $redirurl = $VDB->w_get_path(WEB_PLUGIN_PATH)."CStudio/oel_tools_teachdoc_link.php?action=$action&cid=$cid&cotk=$cotk";

        (new RedirectResponse($redirurl))->send();

        exit;
    }

    if (false == validateCSRFToken($cotk, $VDB->w_api_get_user_id())) {
        if (false == $VDB->w_is_platform_admin()) {
            Display::display_header();
            echo 'CSRF token is not valid or has expired.';
            Display::display_footer();

            exit;
        }
    }
}

$urlForm = api_get_self().'?action='.$action.'&id='.$id.'&cid='.$cid;

if (isset($_GET['cstudiolog'])) {
    $urlForm .= '&cstudiolog=1';
}

if (isset($_GET['cotk'])) {
    $urlForm .= '&cotk='.$cotk;
}

$form = new FormValidator('dictionary', 'post', $urlForm);

$haveerror = false;

if (isset($_GET['cstudioerror']) || isset($_GET['cstudiolog'])) {
    $form->addHtml('&nbsp;&nbsp;ERROR &nbsp; <span style="color:red;" >initial sco folder error (cstudiolog=1)</span></br></br>');
}

// Basic resources control
$dirPlug = $VDB->w_get_path(SYS_PLUGIN_PATH).'/CStudio';
$filePathManifest = $dirPlug.'/resources/imsmanifest.xml';
if (!file_exists($filePathManifest)) {
    $haveerror = true;
    $form->addHtml('ERROR <span style="color:red;" >initial imsmanifest.xml is lost !</span></br>');
}
$filePathjq = $dirPlug.'/resources/jq.js';
if (!file_exists($filePathjq)) {
    $haveerror = true;
    $form->addHtml('ERROR <span style="color:red;" >initial jq.js is lost !</span></br>');
}
// Basic resources control

if (0 == $idLudiLP) {
    $form->addText('title', get_lang('Title'), true);
    if (false == $haveerror) {
        $form->addButtonSave('&nbsp;&nbsp;'.get_lang('Add').'&nbsp;&nbsp;');
    }
} else {
    $idurl = api_get_current_access_url_id();
    $UrlWhere = '';
    if (($VDB->w_is_platform_admin() || $VDB->w_is_session_admin()) && $VDB->w_get_multiple_access_url()) {
        $UrlWhere = " AND id_url = $idurl ";
    }

    $sql = "SELECT id FROM $table ";
    $sql .= " WHERE lp_id = $idLudiLP AND id_parent = 0 ";
    $sql .= $UrlWhere;

    $idLudiProject = $VDB->get_value_by_query($sql, 'id');

    if ('' != $idLudiProject && 0 != $idLudiProject) {
        if (isset($_GET['first'])) {
            (new RedirectResponse('editor/index.php?id='.$idLudiProject.'&cotk='.$cotk.'&first=1'))->send();
        } else {
            if ('' != $cotk) {
                (new RedirectResponse('editor/index.php?id='.$idLudiProject.'&cotk='.$cotk))->send();
            } else {
                Display::display_header();
                echo "<div style='color:red;' >CSRF Token Error !</div>";
                Display::display_footer();
            }
        }
    } else {
        Display::display_header();
        echo "<div style='color:red;' >Error !</div>";
        Display::display_footer();
    }

    exit;
}

switch ($action) {
    case 'add':
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $title = $values['title'];

            if (isset($_GET['cstudiolog'])) {
                $objectId = insertNewProject($title, $userId, true);
            } else {
                $objectId = insertNewProject($title, $userId);
            }

            if ($objectId > 0) {
                $urlM = api_get_self().'?idLudiLP='.$objectId.'&first=1&cotk='.$cotk;
                Display::addFlash(Display::return_message(get_lang('Added'), 'success'));
                (new RedirectResponse($urlM))->send();

                exit;
            }
            $neoLink = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/oel_tools_teachdoc_link.php?cstudioerror=6&cotk='.$cotk;
            if (!isset($_GET['cstudiolog'])) {
                Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                (new RedirectResponse($neoLink))->send();

                exit;
            }
        }

        break;
}

Display::display_header();

$h = homeScreenChamidoc($VDB, $cid, $cotk);

$h .= $form->returnForm();
echo $h;
Display::display_footer();

function homeScreenChamidoc($VDB, $cid, $cotk)
{
    $html = '';
    $html .= '<header class="header-chamidoc">';
    $html .= '    <div class="logo-container">';
    $html .= '		<img src="img/base/oel_tools.jpg" />';
    $html .= '    </div>';
    $html .= '</header>';

    $html .= '<main class="main-content-chamidoc">';
    $html .= '    <div class="welcome-section">';
    $html .= '        <p class="welcome-subtitle">Choisissez une action pour commencer</p>';
    $html .= '    </div>';

    $html .= '    <div class="actions-container">';
    $html .= '        <div class="action-card" onclick="createProject()">';
    $html .= '            <div class="action-icon">';
    $html .= '                <svg viewBox="0 0 24 24">';
    $html .= '                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>';
    $html .= '                </svg>';
    $html .= '            </div>';
    $html .= '            <h2 class="action-title">Créer un Projet</h2>';
    $html .= '            <p class="action-description">';
    $html .= '                Démarrez un nouveau projet à partir de zéro.';
    $html .= '            </p>';
    $html .= '        </div>';

    $html .= '        <a class="action-card" href="/plugin/CStudio/editor/import-project/import.php?cid='.$cid.'&cotk='.$cotk.'" >';
    $html .= '            <div class="action-icon">';
    $html .= '                <svg viewBox="0 0 24 24">';
    $html .= '                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>';
    $html .= '                    <path d="M12,12L16,16H13V19H11V16H8L12,12Z"/>';
    $html .= '                </svg>';
    $html .= '            </div>';
    $html .= '            <h2 class="action-title">Importer un Projet</h2>';
    $html .= '            <p class="action-description">';
    $html .= '                Importez un projet existant depuis votre ordinateur.';
    $html .= '            </p>';
    $html .= '        </a>';
    $html .= '    </div>';

    $html .= '    <div class="welcome-section">';
    $html .= '        <p class="welcome-subtitle"><br><br>Version '.teachdoc_hub::create()->get_version().'</p>';
    $html .= '    </div>';

    $html .= '</main>';

    $html .= '<link rel="stylesheet" href="resources/css/home/s.css" />';

    $html .= '<script>
		function createProject() {
			$(".form-horizontal").css("display","block");
			$("#dictionary").css("display","block");
			$(".main-content-chamidoc").css("display","none");
		}
		$(".form-horizontal").css("display","none");
		$(document).ready(function() {
			$(".form-horizontal").css("display","none");
			$("#dictionary").css("display","none");
		});
	</script>';
    $html .= '<style>
		#dictionary {
			display: none;
			padding-top: 50px;
			padding-bottom: 60px;
		}
	</style>';

    return $html;
}
