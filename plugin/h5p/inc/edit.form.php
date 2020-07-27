<?php

/* For license terms, see /license.txt */

$form = new FormValidator('dictionary', 'post', api_get_self().'?action='.$action.'&id='.$id);

$tableOfnodes = '';
if ($nodeType == '') {
    $tableOfnodes = "<table id='nodeselection' class='styleOfPages' ";
    $tableOfnodes .= " style='max-width:840px;width:98%;";
    $tableOfnodes .= "margin-left:auto;margin-right:auto;margin-bottom:2%;' >";

    $tableOfnodes .= "<tr><td colspan=6 style='text-align:center;padding-bottom:10px;padding-top:10px;border:solid 1px gray;' colspan=3 >";
    $tableOfnodes .= $plugin->get_lang('TypeContent')."</td></tr>";

    $tableOfnodes .= "<tr style='width:98%;background-color:#D8D8D8;padding-top:10px;border-bottom:solid 2px white;' >";

    $tableOfnodes .= "<td style='text-align:center;padding:5px;' >";
    $tableOfnodes .= "<img class=toolimage src='resources/img/wordsmatch.png' /></td>";
    $tableOfnodes .= "<td style='text-align:center;width:20%;' >Find the words</td>";
    $tableOfnodes .= "<td style='text-align:center;width:20%;'>";
    $tableOfnodes .= "<a href='list.php?node_type=wordsmatch' class='btn btn-primary' >";
    $tableOfnodes .= "<em class='fa'></em>&nbsp;".$plugin->get_lang('Use')."&nbsp;</a>";
    $tableOfnodes .= "</td>";

    $tableOfnodes .= "<td style='text-align:center;padding:5px;' >";
    $tableOfnodes .= "<img class=toolimage src='resources/img/dragthewords.png' /></td>";
    $tableOfnodes .= "<td style='text-align:center;width:20%;' >Drag the words</td>";
    $tableOfnodes .= "<td style='text-align:center;width:20%;'>";
    $tableOfnodes .= "<a href='list.php?node_type=dragthewords' class='btn btn-primary' >";
    $tableOfnodes .= "<em class='fa'></em>&nbsp;".$plugin->get_lang('Use')."&nbsp;</a>";
    $tableOfnodes .= "</td>";

    $tableOfnodes .= "</tr>";

    $tableOfnodes .= "<tr style='width:98%;background-color:#D8D8D8;padding-top:10px;border-bottom:solid 2px white;' >";

    $tableOfnodes .= "<td style='text-align:center;padding:5px;' >";
    $tableOfnodes .= "<img class=toolimage src='resources/img/dialogcard.png' /></td>";
    $tableOfnodes .= "<td style='text-align:center;' >Dialog card</td>";
    $tableOfnodes .= "<td style='text-align:center;' >";
    $tableOfnodes .= "<a href='list.php?node_type=dialogcard' class='btn btn-primary' >";
    $tableOfnodes .= "<em class='fa'></em>&nbsp;".$plugin->get_lang('Use')."&nbsp;</a>";
    $tableOfnodes .= "</td>";

    $tableOfnodes .= "<td style='text-align:center;padding:5px;' >";
    $tableOfnodes .= "<img class=toolimage src='resources/img/memory.png' /></td>";
    $tableOfnodes .= "<td style='text-align:center;' >Memory</td>";
    $tableOfnodes .= "<td style='text-align:center;' >";
    $tableOfnodes .= "<a href='list.php?node_type=memory' class='btn btn-primary' >";
    $tableOfnodes .= "<em class='fa'></em>&nbsp;".$plugin->get_lang('Use')."&nbsp;</a>";
    $tableOfnodes .= "</td>";

    $tableOfnodes .= "</tr>";

    $tableOfnodes .= "<tr style='width:98%;background-color:#D8D8D8;";
    $tableOfnodes .= "padding-top:10px;border-bottom:solid 2px white;' >";

    $tableOfnodes .= "<td style='text-align:center;padding:5px;' >";
    $tableOfnodes .= "<img class=toolimage src='resources/img/markthewords.png' /></td>";
    $tableOfnodes .= "<td style='text-align:center;' >Mark the words</td>";
    $tableOfnodes .= "<td style='text-align:center;' >";
    $tableOfnodes .= "<a href='list.php?node_type=markthewords' class='btn btn-primary' >";
    $tableOfnodes .= "<em class='fa'></em>&nbsp;".$plugin->get_lang('Use')."&nbsp;</a>";
    $tableOfnodes .= "</td>";

    $tableOfnodes .= "<td style='text-align:center;padding:5px;' >";
    $tableOfnodes .= "<img class=toolimage src='resources/img/guesstheanswer.png' /></td>";
    $tableOfnodes .= "<td style='text-align:center;' >Guess the answer</td>";
    $tableOfnodes .= "<td style='text-align:center;' >";
    $tableOfnodes .= "<a href='list.php?node_type=guesstheanswer' class='btn btn-primary' >";
    $tableOfnodes .= "<em class='fa'></em>&nbsp;".$plugin->get_lang('Use')."&nbsp;</a>";
    $tableOfnodes .= "</td>";
    $tableOfnodes .= "</tr>";
    $tableOfnodes .= "</table>";
}

// Use $tds defined in translate.php
$html = $translations.'<link href="resources/css/H5P.css"  rel="stylesheet" type="text/css" />';

$form->addText('title', get_lang('Title'), false);

$form->addText('descript', get_lang('Description'), false);

$nodeTypeForm = $form->addText('node_type', 'node_type', false);
$nodeTypeForm->setValue($nodeType);

$form->addText('terms_a', 'terms_a', false);
$form->addText('terms_b', 'terms_b', false);
$form->addText('terms_c', 'terms_c', false);
$form->addText('terms_d', 'terms_d', false);
$form->addText('terms_e', 'terms_e', false);
$form->addText('terms_f', 'terms_f', false);

$form->addElement('static', '', '', $html);

$form->addButtonSave(get_lang('Save'));
