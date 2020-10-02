<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once 'inc/langfrench.php';
require_once 'inc/functions.php';
require_once 'inc/boost-form.php';
require_once 'inc/process-menu.php';
require_once 'boostTitle.php';

//api_protect_admin_script();

$plugin = boostTitle::create();

$awp = api_get_path(WEB_PATH);

$htmlHeadXtra[] = '<script src="resources/js/edit-menu.js" type="text/javascript" ></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.$awp.'vendor/studio-42/elfinder/js/elfinder.full.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.$awp.'vendor/studio-42/elfinder/css/elfinder.full.css">';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.$awp.'web/assets/jquery-ui/jquery-ui.min.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.$awp.'web/assets/jquery-ui/themes/smoothness/jquery-ui.min.css">';


$urlIdFinal = api_get_current_access_url_id();
if($urlIdFinal==1){
	$urlIdFinal = '';
}

$fileNameMenu = __DIR__.'/params/menu'.$urlIdFinal.'.xml';

$config = ['rows' => '10'];

$Mtitle = 'Your title here';
$Mitems= '';
$MTopLogo = 0;
$MTopLogin = 0;
$MpathLogo = '';
$Mtopnavigationoff = 0;

$MColor1 = '#23282e';
$MColor2 = '#2e353d';
$MColorText = '#e1ffff';
$MtopnavigationColor = 0;

$Mbottommessage = '';
$Mhavebottommessage = 0;

if(file_exists($fileNameMenu)){

    $xml = simplexml_load_file($fileNameMenu);
    $Mtitle = $xml->param[0]->title;
    $Mitems = $xml->param[0]->itemslist;
    $MtopCtn = $xml->param[0]->topContent;
    $MTopLogo = intVal($xml->param[0]->toplogo);
    $MTopLogin = intVal($xml->param[0]->toplogin);
    $Mtopnavigationoff = intVal($xml->param[0]->topnavigationoff);

    $MColor1 = $xml->param[0]->Color1;
    $MColor2 = $xml->param[0]->Color2;
    $MColorText = $xml->param[0]->ColorText;

    $MtopnavigationColor = intVal($xml->param[0]->topnavigationColor);

    $MpathLogo = $xml->param[0]->pathlogo;

    $Mbottommessage = $xml->param[0]->bottommessage;
    $Mhavebottommessage = intVal($xml->param[0]->havebottommessage);

}

if(!$Mhavebottommessage){
    $Mhavebottommessage = 0;
}

if($MColor1==''){
    $MColor1 = '#23282e';
}
if($MColor2==''){
    $MColor2 = '#2e353d';
}
if($MColorText==''){
    $MColorText = '#e1ffff';
}


$form = new FormValidator('boostTitle', 'post', api_get_self());

$sty = "<style>h3{border-bottom:solid 2px gray;}</style>";

$form->addElement('static','','',$sty.'<h3>Menu</h3>');

//$FtopCtn = $form->addHtmlEditor('topContent','topContent', false, false, ['ToolbarSet' => 'Work'], true);
//$FtopCtn->setValue($MtopCtn);


$Ftitle = $form->addText('title',getLangBoost('Title',$plugin),[],false);
$Ftitle->setValue($Mtitle);

$FtopLogo = $form->addElement('checkbox','topLogo',null,getLangBoost('topLogo',$plugin));
$FtopLogo->setValue($MTopLogo);

$FpathLogo = $form->addText('pathLogo',getLangBoost('pathLogo',$plugin),[],false);
$FpathLogo->setValue($MpathLogo);

$FtopLogin = $form->addElement('checkbox','topLogin',null,getLangBoost('topLogin',$plugin));
$FtopLogin->setValue($MTopLogin);

$Ftopnavigationoff = $form->addElement('checkbox','topnavigationoff',null,getLangBoost('topnavigationoff',$plugin));
if(isset($Mtopnavigationoff)){
    $Ftopnavigationoff->setValue($Mtopnavigationoff);
}

$form->addElement('static','','Menu','<div id="menueditor" >menueditor</div>');

$Ftitle = $form->addText('itemsList',getLangBoost('items',$plugin),[],false);
$Ftitle->setValue($Mitems);

$form->addButtonSave(getLangBoost('Save',$plugin));

$tHe = "<table style='width:99%;' ><tr>";
$tHe .= "<th>Icons</th>";
$tHe .= "<th>Links</th>";
$tHe .= "<th>Params</th>";
$tHe .= "</tr>";
$tHe .= "<tr>";

$tHe .= "<td style='border:solid 1px gray;padding:5px;' >";
$tHe .= "fa-dashboard<br>";
$tHe .= "fa-gift<br>";
$tHe .= "fa-book<br>";
$tHe .= "fa-circle<br>";
$tHe .= "fa-handshake-o<br>";
$tHe .= "fa-bar-chart<br>";
$tHe .= "fa-bullseye<br>";
$tHe .= "fa-envelope<br>";
$tHe .= "fa-gear<br>";
$tHe .= "fa-institution<br>";

$tHe .= "fa-globe<br>";
$tHe .= "</td>";

$tHe .= "<td style='border:solid 1px gray;padding:5px;' >";
$tHe .= "#home<br>";
$tHe .= "#courses<br>";
$tHe .= "#stat<br>";
$tHe .= "#admin-userslist<br>";
$tHe .= "#admin-courseslist<br>";
$tHe .= "#admin-skillviewer<br>";
$tHe .= "#course-list (submenu list)<br>";
$tHe .= "#session-list (submenu list)<br>";

$tHe .= "</td>";

$tHe .= "<td style='border:solid 1px gray;padding:5px;' >";
$tHe .= "adminonly<br>";
$tHe .= "havelogin<br>";
$tHe .= "nologin<br>";
$tHe .= "teacheronly<br>";
$tHe .= "</td>";

$tHe .= "</tr>";
$tHe .= "</table>";

$form->addElement('static','','Helpers',$tHe);


$form->addElement('static','','',$sty.'<h3>Colors</h3>');

$FColor1 = $form->addText('Color1',getLangBoost('Color1',$plugin),[],false);
$FColor1->setValue($MColor1);

$FColor2 = $form->addText('Color2',getLangBoost('Color2',$plugin),[],false);
$FColor2->setValue($MColor2);

$FColorText = $form->addText('ColorText',getLangBoost('ColorText',$plugin),[],false);
$FColorText->setValue($MColorText);

$FtopnavigationColor = $form->addElement('checkbox','topnavigationColor',null,getLangBoost('topnavigationColor',$plugin));
if(isset($MtopnavigationColor)){
    $FtopnavigationColor->setValue($MtopnavigationColor);
}

$form->addElement('static','','',$sty.'<h3>Extra Message</h3>');

$Fhavebottommessage = $form->addElement('checkbox','havebottommessage',null,getLangBoost('havebottommessage',$plugin));
if(isset($Mhavebottommessage)){
    $Fhavebottommessage->setValue($Mhavebottommessage);
}

$Fbottommessage = $form->addHtmlEditor('bottommessage',getLangBoost('bottommessage',$plugin), false, false, ['ToolbarSet' => 'Work'], true);

if(isset($Mbottommessage)&&$Mbottommessage!=''){

    $hoverBottomMessage = "<script>$(document).ready(function(){";
    $hoverBottomMessage .= "var contentbottommessage = '";
    $hoverBottomMessage .= sanitize_output_tojsvar($Mbottommessage);
    $hoverBottomMessage .= "';";
    $hoverBottomMessage .= "$('#bottommessage').val(contentbottommessage);";
    $hoverBottomMessage .= "});</script>";
    $htmlHeadXtra[] = $hoverBottomMessage;
    //CKEDITOR.instances.bottommessage.insertHtml("coucou");

}

$form->addButtonSave(getLangBoost('Save',$plugin));

//Save the XML
if ($form->validate()){

    $values = $form->getSubmitValues();

    $params = [
        'title' => $values['title'],
        'itemsList' => $values['itemsList'],
        'topLogin' => $values['topLogin'],
        'topLogo' => $values['topLogo']
    ];
    
    //,'topContent' => $values['topContent']
    
    $xmlstr = '<?xml version="1.0" encoding="UTF-8" ?>';
    $xmlstr .= "<params><param>";
    
    $xmlstr .= "<title><![CDATA[".$values['title']."]]></title>";
    $xmlstr .= "<itemslist><![CDATA[".$values['itemsList']."]]></itemslist>";
    $xmlstr .= "<toplogin><![CDATA[".$values['topLogin']."]]></toplogin>";
    $xmlstr .= "<toplogo><![CDATA[".$values['topLogo']."]]></toplogo>";
    $xmlstr .= "<pathlogo><![CDATA[".$values['pathLogo']."]]></pathlogo>";
    
    $xmlstr .= "<topnavigationoff><![CDATA[".$values['topnavigationoff']."]]></topnavigationoff>";

    $xmlstr .= "<Color1><![CDATA[".$values['Color1']."]]></Color1>";
    $xmlstr .= "<Color2><![CDATA[".$values['Color2']."]]></Color2>";
    $xmlstr .= "<ColorText><![CDATA[".$values['ColorText']."]]></ColorText>";
    $xmlstr .= "<topnavigationColor><![CDATA[".$values['topnavigationColor']."]]></topnavigationColor>";

    $fileN = '';
    
    if($values['pathLogo']!=''){
        $path_parts = pathinfo($values['pathLogo']);
        $fileN = $path_parts['filename'];
        $fileE = $path_parts['extension'];
        if($fileN!=''){
            $fileN = $fileN.'.'.$fileE;
            $p1 = $values['pathLogo'];
            $p2 = api_get_path(SYS_PATH).'plugin/chamilo_boost/img/'.$fileN;
            copy($p1,$p2);
            $xmlstr .= "<imglogo><![CDATA[".$fileN."]]></imglogo>";
        }
    }else{
        $xmlstr .= "<imglogo></imglogo>";
    }

    $xmlstr .= "<havebottommessage><![CDATA[".$values['havebottommessage']."]]></havebottommessage>";
    $xmlstr .= "<bottommessage><![CDATA[".$values['bottommessage']."]]></bottommessage>";
    
    $xmlstr .= "</param></params>";
    
    $fd = fopen($fileNameMenu,'w');	
    fwrite($fd,$xmlstr);
    fclose($fd);

    unset($_SESSION['Btitle'.$urlIdFinal]);
    unset($_SESSION['Blogo'.$urlIdFinal]);
    unset($_SESSION['BlogoTop'.$urlIdFinal]);

    $bottomMessage = '';
    
    if($values['havebottommessage']==1){
        $bottomMessage = $values['bottommessage'];
    }
    saveRenderMenu($values['title'],$values['itemsList'],$values['topLogin'],$values['topLogo'],$fileN,$bottomMessage,$urlIdFinal);

    $filereturn = api_get_self();
    
    Display::addFlash(Display::return_message(get_lang('Updated').'('.$urlIdFinal.')'));

    header('Location: '.$filereturn);
	
	exit;
	
}

$tpl = new Template('');
$tpl->assign('form', $form->returnForm());

$content = $tpl->fetch('/chamilo_boost/view/options-v09.tpl');

// Assign into content
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();
