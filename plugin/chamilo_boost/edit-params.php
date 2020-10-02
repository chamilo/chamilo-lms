<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once 'inc/langfrench.php';
require_once 'inc/functions.php';
require_once 'inc/boost-form.php';
require_once 'boostTitle.php';

api_protect_admin_script();

$plugin = boostTitle::create();

$awp = api_get_path(WEB_PATH);

$htmlHeadXtra[] = '<script src="resources/js/edit-options.js" type="text/javascript" ></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.$awp.'vendor/studio-42/elfinder/js/elfinder.full.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.$awp.'vendor/studio-42/elfinder/css/elfinder.full.css">';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.$awp.'web/assets/jquery-ui/jquery-ui.min.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.$awp.'web/assets/jquery-ui/themes/smoothness/jquery-ui.min.css">';

$urlIdFinal = api_get_current_access_url_id();
if($urlIdFinal==1){$urlIdFinal = '';}

$fileNameParams = __DIR__.'/params/params'.$urlIdFinal.'.xml';

$config = ['rows' => '10'];

$Btitle = 'Your title here';
$Blogo = '';
$BlogoTop = '';
$Bextracode1 = '';
$Bextracode2 = '';

$BactiveSearch = 0;
$BactiveSkills = 0;

$BbtnSuscribe = 0;
$BlabelSuscribe = '';

$BbtnBuy = 0;
$BlabelBuy = '';
$BlinkBuy = '';

$BlateralMenu = 0;

$Bstylecourses = 0;

if(file_exists($fileNameParams)){

    $xml = simplexml_load_file($fileNameParams);

    $Btitle =  $xml->param[0]->title;
    $Blogo =  $xml->param[0]->logo;
    $BlogoTop =  $xml->param[0]->logotop;
    $Bextracode1 =  $xml->param[0]->extracode1;
    $Bextracode2 =  $xml->param[0]->extracode2;

    $BactiveSearch = intVal($xml->param[0]->activeSearch);
    $BactiveSkills = intVal($xml->param[0]->activeSkills);

    $BbtnSuscribe = intVal($xml->param[0]->btnSuscribe);
    $BlabelSuscribe = $xml->param[0]->labelSuscribe;

    if($BlabelSuscribe==''){
        $BlabelSuscribe = "S'inscrire";
    }

    $BlateralMenu = intVal($xml->param[0]->lateralMenu);

    $BbtnBuy = intVal($xml->param[0]->btnBuy);
    $BlabelBuy = $xml->param[0]->labelBuy;
    if($BlabelBuy==''){
        $BlabelBuy = "Acheter";
    }

    $BlinkBuy = $xml->param[0]->linkBuy;

    $Bstylecourses = $xml->param[0]->stylecourses;

}

$form = new FormValidator('boostTitle', 'post', api_get_self());

$sty = "<style>";
$sty .= "h3{border-bottom:solid 2px GRAY;}";
$sty .= "</style>";

$form->addElement('static','','',$sty.'<h3>Paramètres</h3>');

$Ftitle = $form->addText('title',getLangBoost('Title',$plugin),[],false);
$Ftitle->setValue($Btitle);

$Flogo = $form->addText('logo','Logo Home (55px * 45px)',[],false);
$Flogo->setValue($Blogo);

$FlogoTop = $form->addText('logotop','Logo Top (40px * 30px)',[],false);
$FlogoTop->setValue($BlogoTop);

$FactiveSearch = $form->addElement('checkbox','activeSearch',null,getLangBoost('activeSearch',$plugin));
$FactiveSearch->setValue($BactiveSearch);

$FactiveSkills = $form->addElement('checkbox','activeSkills',null,getLangBoost('activeSkills',$plugin));
$FactiveSkills->setValue($BactiveSkills);

$FLateralMenu = $form->addElement('checkbox','lateralMenu',null,getLangBoost('lateralMenu',$plugin));
$FLateralMenu->setValue($BlateralMenu);

$form->addButtonSave($plugin->get_lang('Save'));

$form->addElement('static','','','<h3>Liens dans les éléments catalogue</h3>');

$FbtnSuscribe = $form->addElement('checkbox','btnSuscribe',null,getLangBoost('btnSuscribe',$plugin));
$FbtnSuscribe->setValue($BbtnSuscribe);

$FlabelSuscribe = $form->addText('labelSuscribe',getLangBoost('labelSuscribe',$plugin),[],false);
$FlabelSuscribe->setValue($BlabelSuscribe);

$FbtnBuy = $form->addElement('checkbox','btnBuy',null,getLangBoost('btnBuy',$plugin));
$FbtnBuy->setValue($BbtnBuy);

$FlabelBuy = $form->addText('labelBuy',getLangBoost('labelBuy',$plugin),[],false);
$FlabelBuy->setValue($BlabelBuy);

$FlinkBuy = $form->addText('linkBuy',getLangBoost('linkBuy',$plugin),[],false);
$FlinkBuy->setValue($BlinkBuy);

$form->addButtonSave(getLangBoost('Save',$plugin));

$form->addElement('static','','','<h3>Style des cours</h3>');

 //Liste of Style
 $optionsStyle = array(
    '0' => getLangBoost('stylecourse0',$plugin),
    '1' => getLangBoost('stylecourse1',$plugin)
);
$select = $form->addElement('select','stylecourses',$plugin->get_lang('stylecourses'), $optionsStyle);
$select->setSelected($Bstylecourses);

$form->addElement('static','','','<h3>Extra code</h3>');

$config = ['rows' => '30'];
$Fextracode1 = $form->addTextArea('extracode1',"Extra&nbsp;DOM",$config,false);
$Fextracode1->setValue($Bextracode1);

$Fextracode2 = $form->addTextArea('extracode2',"Extra&nbsp;CSS",$config,false);
$Fextracode2->setValue($Bextracode2);

$css = '<style>';
$css .= '#boostTitle_extracode1{background:black;color:white;}';
$css .= '#boostTitle_extracode2{background:black;color:white;}';
$css .= '</style>';

$form->addElement('static','','',$css);

$form->addButtonSave(getLangBoost('Save',$plugin));

//Save the XML
if ($form->validate()){

    $values = $form->getSubmitValues();

    if(!array_key_exists('lateralMenu', $values)) {
        $values['lateralMenu'] = 0;
    }

    if(!array_key_exists('btnSuscribe', $values)) {
        $values['btnSuscribe'] = 0;
    }
    if(!array_key_exists('btnBuy', $values)) {
        $values['btnBuy'] = 0;
    }
    if(!array_key_exists('linkBuy', $values)) {
        $values['linkBuy'] = '-';
    }
    if(!array_key_exists('labelBuy', $values)) {
        $values['labelBuy'] = '-';
    }
    
    if(!array_key_exists('labelSuscribe', $values)) {
        $values['labelSuscribe'] = '';
    }
    
    $params = [
        'title' => $values['title'],
        'logo' => $values['logo'],
        'logotop' => $values['logotop'],
        'extracode1' => $values['extracode1'],
        'extracode2' => $values['extracode2'],  
        'linkBuy' => $values['linkBuy'],
        'labelBuy' => $values['labelBuy'],
        'labelSuscribe' => $values['labelSuscribe'],
        'stylecourses' => $values['stylecourses']
    ];
    
    $params['btnSuscribe'] = 0;
    if (array_key_exists('btnSuscribe', $values)) {
        $params['btnSuscribe'] = intVal($values['btnSuscribe']);
    }

    $params['lateralMenu'] = 0;
    if (array_key_exists('lateralMenu', $values)) {
        $params['lateralMenu'] = intVal($values['lateralMenu']);
    }

    $params['btnBuy'] = 0;
    if (array_key_exists('btnBuy', $values)) {
        $params['btnBuy'] = intVal($values['btnBuy']);
    }
    
    $params['activeSearch'] = 0;
    if (array_key_exists('activeSearch', $values)) {
        $params['activeSearch'] = intVal($values['activeSearch']);
    }

    $params['activeSkills'] = 0;
    if (array_key_exists('activeSkills', $values)) {
        $params['activeSkills'] = intVal($values['activeSkills']);
    }

    $xmlstr = '<?xml version="1.0" encoding="UTF-8" ?>';
    $xmlstr .= "<params><param>";
    
    $xmlstr .= "<title><![CDATA[".$values['title']."]]></title>";
    $xmlstr .= "<logo><![CDATA[".$values['logo']."]]></logo>";
    $xmlstr .= "<logotop><![CDATA[".$values['logotop']."]]></logotop>";
    $xmlstr .= "<extracode1><![CDATA[".$values['extracode1']."]]></extracode1>";
    $xmlstr .= "<extracode2><![CDATA[".$values['extracode2']."]]></extracode2>";

    $xmlstr .= "<activeSearch><![CDATA[".$values['activeSearch']."]]></activeSearch>";
    $xmlstr .= "<activeSkills><![CDATA[".$values['activeSkills']."]]></activeSkills>";
    
    $xmlstr .= "<btnSuscribe><![CDATA[".$params['btnSuscribe']."]]></btnSuscribe>";

    $xmlstr .= "<btnBuy><![CDATA[".$params['btnBuy']."]]></btnBuy>";

    $xmlstr .= "<labelBuy><![CDATA[".$values['labelBuy']."]]></labelBuy>";
    $xmlstr .= "<labelSuscribe><![CDATA[".$values['labelSuscribe']."]]></labelSuscribe>";

    $xmlstr .= "<linkBuy><![CDATA[".$values['linkBuy']."]]></linkBuy>";
    $xmlstr .= "<lateralMenu><![CDATA[".$values['lateralMenu']."]]></lateralMenu>";
    
    $xmlstr .= "<stylecourses><![CDATA[".$values['stylecourses']."]]></stylecourses>";

    $xmlstr .= "</param></params>";
    
    $fd = fopen($fileNameParams,'w');	
    fwrite($fd,$xmlstr);
    fclose($fd);
    
    unset($_SESSION['Btitle'.$urlIdFinal]);
    unset($_SESSION['Blogo'.$urlIdFinal]);
    unset($_SESSION['BlogoTop'.$urlIdFinal]);
    
    $filereturn = api_get_self();
    
    Display::addFlash(Display::return_message(get_lang('Updated')));

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
