<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/* For Chamilo */
require_once __DIR__.'/../0_dal/dal.global_lib.php';
api_protect_admin_script();

$fileNameOpts = __DIR__.'/options.xml';

$VactiveLogsCreator = '';
$VactiveLogsLearning = '';
$VdisplayTemplateArea = '';
$VonlyUserTemplates = '';
$VcustomDefaultTemplates = '';
$VlistDefaultTemplates = '';
$Vtab = 1;

if (isset($_GET['tab'])) {
    $Vtab = (int) $_GET['tab'];
}

if (file_exists($fileNameOpts)) {
    $xml = simplexml_load_file($fileNameOpts);
    $VactiveLogsCreator = $xml->param[0]->activeLogsCreator;
    $VactiveLogsLearning = $xml->param[0]->activeLogsLearning;
    $VdisplayTemplateArea = $xml->param[0]->displayTemplateArea;
    $VonlyUserTemplates = $xml->param[0]->onlyUserTemplates;
    $VcustomDefaultTemplates = $xml->param[0]->customDefaultTemplates;
    $VlistDefaultTemplates = $xml->param[0]->listDefaultTemplates;
}

$formOptions = '<!doctype html>
<html lang="en" >
<head>
    <script src="../editor/jscss/jquery.js"></script>
    <script src="../resources/js/cstudio-i18n.js?v=5"></script>
    <script src="js/edit-options-engine.js?v=5"></script>
    <link href="../editor/jscss/oel-teachdoc.css?v=5" rel="stylesheet" />
    <title>Options For Studio</title>
    <link rel="icon" type="image/png" sizes="192x192" href="../img/base/newfav/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../img/base/newfav/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="../img/base/newfav/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../img/base/newfav/favicon-16x16.png">
</head>';
$formOptions .= "<body style='background-color:#808B96;' >";

$formOptions .= '<form action="edit-options.php?send=1" style="border: solid 10px #E6E6E6;" class="form-options-studio" method="post">';
$formOptions .= '<div class="luditopheader" style="border:solid 5px #E6E6E6;border-top:solid 20px #E6E6E6;border-bottom:solid 20px #E6E6E6;background-color:#E6E6E6;width: 80%;margin-left:10%;" ></div>';

$menuStr = '<div class="containermenu"><div class="tabsmenu">';

$menuStr .= '<input type="radio" id="radio-1" name="tabs" ';
if (1 == $Vtab) {
    $menuStr .= ' checked />';
} else {
    $menuStr .= ' />';
}
$menuStr .= '<label onClick="showTabProcess(1);" class="tab" for="radio-1">Parameters</label>';
$menuStr .= '<input type="radio" id="radio-2" name="tabs" ';
if (2 == $Vtab) {
    $menuStr .= ' checked />';
} else {
    $menuStr .= ' />';
}
$menuStr .= '<label onClick="showTabProcess(2);" class="tab" for="radio-2">Logs</label>';
$menuStr .= '<input type="radio" id="radio-3" name="tabs" ';
if (3 == $Vtab) {
    $menuStr .= ' checked />';
} else {
    $menuStr .= ' />';
}
$menuStr .= '<label onClick="showTabProcess(3);" class="tab" for="radio-3">Cache</label>';
$menuStr .= '<input type="radio" id="radio-4" name="tabs" ';
if (4 == $Vtab) {
    $menuStr .= ' checked />';
} else {
    $menuStr .= ' />';
}
$menuStr .= '<label onClick="showTabProcess(4);" class="tab" for="radio-4">Update</label>';

$menuStr .= '<span class="glider"></span>';

$menuStr .= '</div>';
$menuStr .= '</div>';

$formOptions .= $menuStr;

$formOptions .= '<p id="changetabload" style="text-align:center;display:none;"  >';
$formOptions .= '<img src="../editor/img/cube-oe.gif" style="display:inline-block;" />';
$formOptions .= '</p>';

// TAB 1
if (1 == $Vtab) {
    $formOptions .= "<h3 class='maskpartform trd' style='text-align:center;' >Parameters</h3>";

    if (!isset($_GET['send'])) {
        $formOptions .= '<p>';
        if (1 == $VactiveLogsCreator) {
            $formOptions .= '<input type="checkbox" class="maskpartform" id="activeLogsCreator" value="1" name="activeLogsCreator" checked />';
        } else {
            $formOptions .= '<input type="checkbox" class="maskpartform"  id="activeLogsCreator" value="1" name="activeLogsCreator" />';
        }
        $formOptions .= '<label for="activeLogsCreator noselect" class="maskpartform trd" >Logs for creator interactions</label>';
        $formOptions .= '</p>';

        $formOptions .= '<p>';
        if (1 == $VactiveLogsLearning) {
            $formOptions .= '<input type="checkbox" class="maskpartform" id="activeLogsLearning" value="1" name="activeLogsLearning" checked />';
        } else {
            $formOptions .= '<input type="checkbox" class="maskpartform" id="activeLogsLearning" value="1" name="activeLogsLearning" />';
        }
        $formOptions .= '<label for="activeLogsLearning" class="maskpartform noselect trd" >Logs for reader interactions</label>';
        $formOptions .= '</p>';

        $formOptions .= "<p class='maskpartform' style='font-size:15px;background:#FCF3CF;padding:5px;text-align:center' >";
        $formOptions .= 'sent logs to xapi_shared_statement with cron [\ajax\xapi\cron.sent_log_to_xapi.php]';
        $formOptions .= '</p>';

        $formOptions .= '<p>';
        if (1 == $VdisplayTemplateArea) {
            $formOptions .= '<input type="checkbox" class="maskpartform" id="displayTemplateArea" value="1" name="displayTemplateArea" checked />';
        } else {
            $formOptions .= '<input type="checkbox" class="maskpartform" id="displayTemplateArea" value="1" name="displayTemplateArea" />';
        }
        $formOptions .= '<label for="displayTemplateArea" class="maskpartform noselect trd" >View custom page templates</label>';
        $formOptions .= '</p>';

        $formOptions .= "<p style='margin-left:24px;margin-top:0px;margin-bottom:5px;' >";
        if (1 == $VonlyUserTemplates) {
            $formOptions .= '<input type="checkbox" class="maskpartform" id="onlyUserTemplates" value="1" name="onlyUserTemplates" checked />';
        } else {
            $formOptions .= '<input type="checkbox" class="maskpartform" id="onlyUserTemplates" value="1" name="onlyUserTemplates" />';
        }
        $formOptions .= '<label for="onlyUserTemplates" style="font-style:italic;" class="maskpartform noselect trd" >Only user templates</label>';
        $formOptions .= '</p>';

        $formOptions .= '<p>';
        if (1 == $VcustomDefaultTemplates) {
            $formOptions .= '<input type="checkbox" class="maskpartform" id="customDefaultTemplates" value="1" name="customDefaultTemplates" checked />';
        } else {
            $formOptions .= '<input type="checkbox" class="maskpartform" id="customDefaultTemplates" value="1" name="customDefaultTemplates" />';
        }
        $formOptions .= '<label for="customDefaultTemplates" class="maskpartform noselect trd" >Adding default templates (Upper section)</label>';
        $formOptions .= '</p>';

        $formOptions .= '<p>';
        $formOptions .= '<label for="listDefaultTemplates" style="margin-left:24px;font-style:italic;" ';
        $formOptions .= ' class="maskpartform noselect trd" >List of templates id : </label>';
        $formOptions .= '<input type="text" class="maskpartform" id="listDefaultTemplates" value="'.$VlistDefaultTemplates.'" name="listDefaultTemplates" />';
        $formOptions .= '</p>';

        $formOptions .= '<p class="maskpartform" style="text-align:center;" >';
        $formOptions .= '<img id="loadgearoe" class="maskpartform" src="../editor/img/cube-oe.gif" style="display:none;" />';
        $formOptions .= '<input onClick="this.style.display=\'none\';$(\'#loadgearoe\').css(\'display\',\'inline-block\')" type="submit"  ';
        $formOptions .= ' class="maskpartform ludiButtonSave trd" style="padding:5px;cursor:pointer;" name="options" value="Valid" /></p>';
    } else {
        $formOptions .= '<p style="text-align:center;"  >';
        $formOptions .= '<img id="loadgearoe" src="../editor/img/cube-oe.gif" style="display:inline-block;" />';
        $formOptions .= '</p>';
    }

    $formOptions .= '</form>';

    // Save the XML
    if (isset($_GET['send'])) {
        $values = ['activeLogsCreator', 'activeLogsLearning', 'displayTemplateArea', 'onlyUserTemplates', 'customDefaultTemplates', 'listDefaultTemplates'];

        $values['activeLogsCreator'] = 0;
        $values['activeLogsLearning'] = 0;
        $values['displayTemplateArea'] = 0;
        $values['onlyUserTemplates'] = 0;
        $values['customDefaultTemplates'] = '';
        $values['listDefaultTemplates'] = '';

        if (isset($_POST['activeLogsCreator']) && '1' == $_POST['activeLogsCreator']) {
            $values['activeLogsCreator'] = 1;
        }
        if (isset($_POST['activeLogsLearning']) && '1' == $_POST['activeLogsLearning']) {
            $values['activeLogsLearning'] = 1;
        }
        if (isset($_POST['displayTemplateArea']) && '1' == $_POST['displayTemplateArea']) {
            $values['displayTemplateArea'] = 1;
        }
        if (isset($_POST['onlyUserTemplates']) && '1' == $_POST['onlyUserTemplates']) {
            $values['onlyUserTemplates'] = 1;
        }
        if (isset($_POST['customDefaultTemplates']) && '1' == $_POST['customDefaultTemplates']) {
            $values['customDefaultTemplates'] = 1;
        }
        if (isset($_POST['listDefaultTemplates'])) {
            $values['listDefaultTemplates'] = $_POST['listDefaultTemplates'];
        }

        $xmlstr = '<?xml version="1.0" encoding="UTF-8" ?>';
        $xmlstr .= '<params><param>';
        $xmlstr .= '<activeLogsCreator><![CDATA['.$values['activeLogsCreator'].']]></activeLogsCreator>';
        $xmlstr .= '<activeLogsLearning><![CDATA['.$values['activeLogsLearning'].']]></activeLogsLearning>';

        $xmlstr .= '<displayTemplateArea><![CDATA['.$values['displayTemplateArea'].']]></displayTemplateArea>';
        $xmlstr .= '<onlyUserTemplates><![CDATA['.$values['onlyUserTemplates'].']]></onlyUserTemplates>';

        $xmlstr .= '<customDefaultTemplates><![CDATA['.$values['customDefaultTemplates'].']]></customDefaultTemplates>';
        $xmlstr .= '<listDefaultTemplates><![CDATA['.$values['listDefaultTemplates'].']]></listDefaultTemplates>';

        $xmlstr .= '</param></params>';

        $fd = fopen($fileNameOpts, 'w');
        fwrite($fd, $xmlstr);
        fclose($fd);

        $options_studio = '';
        if (1 == (int) $values['activeLogsCreator']) {
            $options_studio .= (string) 'ALC;';
        }
        if (1 == (int) $values['activeLogsLearning']) {
            $options_studio .= (string) 'ALL;';
        }
        if (1 == (int) $values['displayTemplateArea']) {
            $options_studio .= (string) 'DTA;';
        }
        if (1 == (int) $values['onlyUserTemplates']) {
            $options_studio .= (string) 'OUT;';
        }
        if (1 == (int) $values['customDefaultTemplates']) {
            $options_studio .= (string) 'CDT;';
            $_SESSION['options-studio-cdt'] = (string) $values['listDefaultTemplates'];
        } else {
            $_SESSION['options-studio-cdt'] = '';
        }

        $_SESSION['options-studio'] = (string) $options_studio;

        echo '<script>setTimeout(function(){';
        echo "location.href = 'edit-options.php';";
        echo '},30);';
        echo '</script>';
    }
}

// TAB 2
if (2 == $Vtab) {
    $formOptions .= "<h3 class='maskpartform' style='text-align:center;' >Logs & Activity</h3>";
    $formOptions .= "<div class='form-studiotablelogs maskpartform' >";
    $formOptions .= '<br/><br/>';
    $formOptions .= '<img src="../editor/img/cube-oe.gif" style="display:inline-block;" /><br/>';
    $formOptions .= '</div>';

    $formOptions .= '<script>setTimeout(function(){loadTableLogs();},1000);</script>';
}

// TAB 3
if (3 == $Vtab) {
    $formOptions .= "<h3 class='maskpartform' style='text-align:center;' >Cache</h3>";
}

// TAB 4
if (4 == $Vtab) {
    $formOptions .= "<h3 class='maskpartform' style='text-align:center;' >Update</h3>";
    $formOptions .= "<div class='form-studio-update maskpartform' >";
    $formOptions .= '<br/><br/>';

    $formOptions .= "<div class='form-progress-update' >";
    $formOptions .= "<div class='form-progress-update-bar' ></div>";
    $formOptions .= '</div>';

    $formOptions .= '<p class="buttonProgressSave" style="text-align:center;margin-top:80px;" >';
    $formOptions .= '<a onClick="loadUpdateProcess();" type="submit" class="ludiButtonSave trd" ';
    $formOptions .= ' style="padding:10px;cursor:pointer;" >Start</a></p>';

    $formOptions .= '<p class="buttonProgressSaveLog" style="text-align:center;font-size:14px;margin-top:70px;display:none;" ></p>';

    $formOptions .= '<br/><br/>';
    $formOptions .= '</div>';
}

$formOptions .= '<script>setTimeout(function(){traductAll();},10);</script>';

$formOptions .= '</body></html>';

echo $formOptions;
