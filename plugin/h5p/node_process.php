<?php

/* For licensing terms, see /license.txt */
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/h5p_plugin.class.php';

$awp = api_get_path(WEB_PATH);
$pathPlugH5P = api_get_path(WEB_PLUGIN_PATH).'h5p/';

if (api_is_anonymous()) {
    header('Location: '.$awp.'index.php');
    exit;
}

$version = 6;

$htmlHeadXtra[] = '<script src="resources/js/interface.js?v='.$version.'" type="text/javascript" language="javascript"></script>';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$nodeType = isset($_GET['node_type']) ? Security::remove_XSS($_GET['node_type']) : '';

$termA = '';
$termB = '';
$termC = '';
$termD = '';
$termE = '';
$termF = '';

$option1 = '';
$option2 = '';
$option3 = '';

$description = '';

$term = null;

$contentForm = '<p>Error</p>';

if ($id > 0) {
    $sql = "SELECT * FROM plugin_h5p WHERE id = $id ";
    $result = Database::query($sql);

    while ($rowP = Database::fetch_array($result)) {
        $nodeType = $rowP['node_type'];

        $termA = $rowP['terms_a'];
        $termB = $rowP['terms_b'];
        $termC = $rowP['terms_c'];

        $termD = $rowP['terms_d'];
        $termE = $rowP['terms_e'];
        $termF = $rowP['terms_f'];

        $option1 = $rowP['opt_1'];
        $option2 = $rowP['opt_2'];
        $option3 = $rowP['opt_3'];

        $description = $rowP['descript'];
    }

    $fieldId = 'source-'.$id;

    $h5pSource = 'cache-h5p/launch/source-'.$nodeType;
    $h5pDestination = 'cache-h5p/launch/'.$fieldId;

    //mkdir($dest_h5p);
    $installH5P = true;

    if (!file_exists('cache-h5p/launch/img/dialogcard.jpg')) {
        $installH5P = false;
    }
    if (!file_exists('cache-h5p/launch/images/dialogcard.jpg')) {
        $installH5P = false;
    }
    if (!file_exists($h5pSource)) {
        $installH5P = false;
    } else {
        copyr($h5pSource, $h5pDestination);
    }

    $contentSource = 'cache-h5p/launch/'.$fieldId.'/content/content.json';
    $contentFlx = file_get_contents($contentSource);

    if (($nodeType == 'dialogcard' || $nodeType == 'memory') && $installH5P) {
        if (controlSourceCards($termA)) {
            $baseFlx = getSourceCards($termA, $nodeType);
        }
        if (controlSourceCards($termB)) {
            $baseFlx .= ',';
            $baseFlx .= getSourceCards($termB, $nodeType);
        }
        if (controlSourceCards($termC)) {
            $baseFlx .= ',';
            $baseFlx .= getSourceCards($termC, $nodeType);
        }
        if (controlSourceCards($termD)) {
            $baseFlx .= ',';
            $baseFlx .= getSourceCards($termD, $nodeType);
        }
        if (controlSourceCards($termE)) {
            $baseFlx .= ',';
            $baseFlx .= getSourceCards($termE, $nodeType);
        }
        if (controlSourceCards($termF)) {
            $baseFlx .= ',';
            $baseFlx .= getSourceCards($termF, $nodeType);
        }

        $contentFlx = str_replace("\"@base_cards@\"", $baseFlx, $contentFlx);
    }

    if ($nodeType == 'guesstheanswer' && $installH5P) {
        $extractImgData = "images/dialogcard.jpg";
        $pathParts = pathinfo($termB);
        $fileN = $pathParts['filename'];
        $fileE = $pathParts['extension'];
        if ($fileN != '') {
            $fileN = $fileN.'.'.$fileE;
            $p1 = $termB;
            if ($p1 != '' && $p1 != 'dialogcard.jpg' && $p1 != 'img/dialogcard.jpg' && $p1 != 'images/dialogcard.jpg') {
                $p2 = api_get_path(SYS_PLUGIN_PATH).'h5p/cache-h5p/launch/img/'.$fileN;
                copy($p1, $p2);
                $extractImgData = "../../img/".$fileN;
            } else {
                $extractImgData = $pathPlugH5P.'cache-h5p/launch/img/dialogcard.jpg';
            }
        }

        $contentFlx = str_replace("@image_b@", json_encode($extractImgData), $contentFlx);
    }

    $contentFlx = str_replace("@terms_a@", $termA, $contentFlx);
    $contentFlx = str_replace("@terms_b@", $termB, $contentFlx);
    $contentFlx = str_replace("@terms_c@", $termC, $contentFlx);

    $contentFlx = str_replace("@descript@", $description, $contentFlx);

    $interfaceLanguage = api_get_language_isocode();

    // @TODO support translations in a better way (only en/fr now)
    if ($interfaceLanguage == 'fr' || $interfaceLanguage == 'french') {
        $contentFlx = str_replace("\"solution label\"", "\"Voir la solution\"", $contentFlx);
        $contentFlx = str_replace("\"Turn\"", "\"Tourner\"", $contentFlx);
        $contentFlx = str_replace("\"Check\"", "\"Vérifier\"", $contentFlx);
        $contentFlx = str_replace("\"Retry\"", "\"Recommencer\"", $contentFlx);
        $contentFlx = str_replace("\"Correct!\"", "\"Bravo!\"", $contentFlx);
        $contentFlx = str_replace("\"Incorrect!\"", "\"Réponse incorrecte!\"", $contentFlx);
        $contentFlx = str_replace("\"Answer not found!\"", "\"Réponse non trouvée!\"", $contentFlx);
        $contentFlx = str_replace("\"You got :num out of :total points\"",
            "\"Votre score :num out sur :total points\"", $contentFlx);
        $contentFlx = str_replace("\"Show solution\"", "\"Voir la solution\"", $contentFlx);
        $contentFlx = str_replace("\"Match found.\"", "\"Correspondance trouvée.\"", $contentFlx);
        $contentFlx = str_replace("\"Reset\"", "\"Recommencer\"", $contentFlx);
        $contentFlx = str_replace("\"Close\"", "\"Fermer\"", $contentFlx);
        $contentFlx = str_replace("\"Time spent\"", "\"Temps\"", $contentFlx);
    } elseif ($interfaceLanguage == 'es') {
        $contentFlx = str_replace("\"solution label\"", "\"Ver la solución\"", $contentFlx);
        $contentFlx = str_replace("\"Turn\"", "\"Girar\"", $contentFlx);
        $contentFlx = str_replace("\"Check\"", "\"Verificar\"", $contentFlx);
        $contentFlx = str_replace("\"Retry\"", "\"Volver a intentar\"", $contentFlx);
        $contentFlx = str_replace("\"Correct!\"", "\"¡Bravo!\"", $contentFlx);
        $contentFlx = str_replace("\"Incorrect!\"", "\"¡Respuesta incorrecta!\"", $contentFlx);
        $contentFlx = str_replace("\"Answer not found!\"", "\"¡Respuesta no encontrada!\"", $contentFlx);
        $contentFlx = str_replace("\"You got :num out of :total points\"",
            "\"Su nota es de :num sobre :total puntos\"", $contentFlx);
        $contentFlx = str_replace("\"Show solution\"", "\"Ver la solución\"", $contentFlx);
        $contentFlx = str_replace("\"Match found.\"", "\"Coincidencia encontrada.\"", $contentFlx);
        $contentFlx = str_replace("\"Reset\"", "\"Reiniciar\"", $contentFlx);
        $contentFlx = str_replace("\"Close\"", "\"Cerrar\"", $contentFlx);
        $contentFlx = str_replace("\"Time spent\"", "\"El tiempo pasado\"", $contentFlx);
    }

    $fp = fopen($contentSource, 'w');
    fwrite($fp, $contentFlx);
    fclose($fp);

    $htmlFile = 'cache-h5p/launch/'.$fieldId.'.html';
    $h5pSource = file_get_contents('cache-h5p/launch/source-h.html');

    $h5pSource = str_replace("{folder}", $fieldId, $h5pSource);

    //{folder}
    $fp = fopen($htmlFile, 'w');
    fwrite($fp, $h5pSource);
    fclose($fp);

    $contentForm = '<iframe frameborder="0" width="100%" height="600px" ';
    $contentForm .= ' style="width:100%;height:600px;" ';
    $contentForm .= ' src="'.$pathPlugH5P.$htmlFile.'" >';
    $contentForm .= '</iframe>';
}

$contentForm .= '<h3 style="text-align:center;" >Embedded code</h3>';
$contentForm .= '<textarea rows=5 style="margin-left:10%;width:80%;margin-right:10%;" >';
$contentForm .= $contentForm;
$contentForm .= '</textarea>';
$contentForm .= '<p style="text-align:center;" >';
$contentForm .= '<a href="list.php" class="btn btn-primary">';
$contentForm .= '<em class="fa"></em>'.get_lang('Close').'</a>';
$contentForm .= '</p>';

if ($installH5P == false) {
    $contentForm = Display::return_message(get_lang('FolderDoesntExistsInFileSystem'), 'error');
}

$tpl = new Template("H5P");
$tpl->assign('form', $contentForm);
$content = $tpl->fetch('/h5p/view/view.tpl');
$tpl->assign('content', $content);

$tpl->display_one_col_template();

/**
 * Check if a source card is not empty.
 *
 * @param string $termData A string from which we extract the first part
 *
 * @return bool False on empty, true otherwise
 */
function controlSourceCards($termData)
{
    if ($termData == '') {
        return false;
    }
    $partTerm = explode("|", $termData);
    $txtWarp = $partTerm[0];
    $txtWarp = strip_tags($txtWarp);
    if ($txtWarp != '') {
        return true;
    }

    return false;
}

/**
 * Parse and reformat base image tags with the given terms string.
 *
 * @param string $termData A string from which we only take the first elements (we split it on | )
 * @param string $nodeType The type of node ('memory', 'cards', etc)
 *
 * @return string|string[]
 */
function getSourceCards($termData, $nodeType)
{
    $baseFlx = '{"tips":{},
		"text": @text_a@ ,
		"answer": @answer_a@ ,
		"image":{"path":@image_a@,
		"mime":"image\/jpg",
		"copyright":{"license":"U"},
		"width":100,
		"height":100
		}}';

    if ($nodeType == 'memory') {
        $baseFlx = '{"image":{"path":@image_a@,
			"mime":"image\/jpeg",
			"copyright":{"license":"U"},
			"width":100,"height":100},
			"description":@text_a@,"matchAlt":@text_a@,"imageAlt":@text_a@}';
    }

    $partTerm = explode("|", $termData);
    $baseFlx = str_replace("@text_a@", json_encode($partTerm[0]), $baseFlx);
    $baseFlx = str_replace("@answer_a@", json_encode($partTerm[1]), $baseFlx);

    $extractImg = "images/dialogcard.jpg";

    $pathParts = pathinfo($partTerm[2]);
    $fileN = $pathParts['filename'];
    $fileE = $pathParts['extension'];

    if ($fileN != '') {
        $fileN = $fileN.'.'.$fileE;
        $p1 = $partTerm[2];
        if ($p1 != '' && $p1 != 'dialogcard.jpg' && $p1 != 'img/dialogcard.jpg' && $p1 != 'images/dialogcard.jpg') {
            $p2 = api_get_path(SYS_PLUGIN_PATH).'h5p/cache-h5p/launch/img/'.$fileN;
            copy($p1, $p2);
            $extractImg = '../../img/'.$fileN;
        } else {
            $extractImg = api_get_path(WEB_PLUGIN_PATH).'h5p/cache-h5p/launch/img/dialogcard.jpg';
        }
    }
    $baseFlx = str_replace("@image_a@", json_encode($extractImg), $baseFlx);

    return $baseFlx;
}
