<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldSavedSearch;
use Chamilo\CoreBundle\Entity\User;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

if ('false' === api_get_setting('session.allow_search_diagnostic')) {
    api_not_allowed();
}

api_block_anonymous_users();

$userId = api_get_user_id();
$userInfo = api_get_user_info();

$em = Database::getManager();

$adminPermissions = true;
$extraFieldSavedSearchRepo = $em->getRepository(ExtraFieldSavedSearch::class);
$items = $extraFieldSavedSearchRepo->findBy(['user' => api_get_user_entity($userId)]);

$extraFieldSession = new ExtraField('session');
$extraFieldValueSession = new ExtraFieldValue('session');

$filter = false;
$extraFieldValue = new ExtraFieldValue('user');
$wantStage = $extraFieldValue->get_values_by_handler_and_field_variable(api_get_user_id(), 'filiere_want_stage');

$diagnosisComplete = $extraFieldValue->get_values_by_handler_and_field_variable(
    api_get_user_id(),
    'diagnosis_completed'
);
$diagnosisComplete = false;
if ($diagnosisComplete && isset($diagnosisComplete['value']) && $diagnosisComplete['value'] == 1) {
    if (!isset($_GET['result'])) {
        header('Location:'.api_get_self().'?result=1');
        exit;
    }
}

$hide = true;
if ($wantStage !== false) {
    $hide = $wantStage['value'] === 'yes';
}

$defaultValueStatus = 'extraFiliere.hide()';
if ($hide === false) {
    $defaultValueStatus = '';
}

$url = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?a=order&user_id='.$userId;

// Use current user language
$langInfo = api_get_language_from_iso($userInfo['language']);
$targetLanguage =  $langInfo->getEnglishName();
$theme = 'theme_fr';
switch ($targetLanguage) {
    case 'italian':
        $theme = 'theme_it';
        break;
    case 'polish':
        $theme = 'theme_pl';
        break;
    case 'spanish':
        $theme = 'theme_es';
        break;
    case 'french2':
    case 'french':
        $theme = 'theme_fr';
        break;
    case 'german2':
    case 'german':
        $theme = 'theme_de';
        break;
}

$htmlHeadXtra[] = '
<style>
input[type="checkbox"], input[type="radio"] {
    appearance: auto !important;
    font-size: 14px;
    margin-left: 0px !important;
    height: auto !important;
    width: auto !important;
}
form label, input {
    font-size: 15px !important;
    font-weight: normal !important;
}
.btn {
    padding: 0px 12px !important;
}
</style>
<link href="bootstrap/bootstrap.min.css" rel="stylesheet" media="screen" type="text/css" />
<script src="bootstrap/bootstrap.min.js"></script>
<link href="select2/select2.min.css" rel="stylesheet" media="screen" type="text/css" />
<script src="select2/select2.min.js"></script>
';

$htmlHeadXtra[] = '<script>
$(function() {
    var themeDefault = "extra_'.$theme.'";
    var extraFiliere = $("input[name=\'extra_filiere[extra_filiere]\']").parent().parent().parent();
    '.$defaultValueStatus.'

    $("input[name=\'extra_filiere_want_stage[extra_filiere_want_stage]\']").change(function() {
        if ($(this).val() == "no") {
            extraFiliere.show();
        } else {
            extraFiliere.hide();
        }
    });

    $("#extra_theme").parent().append(
        $("<a>", {
            "class": "btn ajax btn-default",
            "href": "'.$url.'&field_variable=extra_theme",
            "text": "'.get_lang('Order').'"
        })
    );

    $("#extra_theme_fr").parent().append(
        $("<a>", {
            "class": "btn ajax btn-default",
            "href": "'.$url.'&field_variable=extra_theme_fr",
            "text": "'.get_lang('Order').'"
        })
    );

    $("#extra_theme_de").parent().append(
        $("<a>", {
            "class": "btn ajax btn-default",
            "href": "'.$url.'&field_variable=extra_theme_de",
            "text": "'.get_lang('Order').'"
        })
    );

    $("#extra_theme_it").parent().append(
        $("<a>", {
            "class": "btn ajax btn-default",
            "href": "'.$url.'&field_variable=extra_theme_it",
            "text": "'.get_lang('Order').'"
        })
    );

    $("#extra_theme_es").parent().append(
        $("<a>", {
            "class": "btn ajax btn-default",
            "href": "'.$url.'&field_variable=extra_theme_es",
            "text": "'.get_lang('Order').'"
        })
    );

     $("#extra_theme_pl").parent().append(
        $("<a>", {
            "class": "btn ajax btn-default",
            "href": "'.$url.'&field_variable=extra_theme_pl",
            "text": "'.get_lang('Order').'"
        })
    );


    $("#extra_domaine_0, #extra_domaine_1, #extra_domaine_2").on("change", function() {
        var domainList = [];
        $("#extra_domaine_0 option:selected").each(function() {
            domainList.push($(this).val());
        });
        $("#extra_domaine_1 option:selected").each(function() {
            domainList.push($(this).val());
        });
        $("#extra_domaine_2 option:selected").each(function() {
            domainList.push($(this).val());
        });

        var domainListToString = JSON.stringify(domainList);

        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            type: "GET",
            url: "'.api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?a=search_options_from_tags&type=session&from=extra_domaine&search="+themeDefault+"&options="+domainListToString,
            success: function(data) {
                var selectToString = "";
                selectToString += "<option></option>";
                jQuery.each(JSON.parse(data), function(i, item) {
                    selectToString += "<optgroup label=\'"+item.text+"\'>";
                    // Add empty value
                    jQuery.each(item.children, function(j, data) {
                        if (data.text != "") {
                            selectToString += "<option value=\'"+data.text+"\'> " +data.text+"</option>"
                        }
                    });
                    selectToString += "</optgroup>";
                });

                for (i = 0; i <= 5; i++) {
                    var themeId = "#"+themeDefault+"_"+i;
                    var beforeValue = $(themeId).find(":selected").val();
                    $(themeId).find("option").remove().end();
                    $(themeId).empty();
                    $(themeId).html(selectToString);
                    $(themeId).val(beforeValue);
                    //$(themeId).selectpicker("refresh");
                }
            }
         });
    });
});
</script>';

$result = SessionManager::getGridColumns('simple');
$columns = $result['columns'];
$column_model = $result['column_model'];

$defaults = [];
$tagsData = [];

if (!empty($items)) {
    /** @var ExtraFieldSavedSearch $item */
    foreach ($items as $item) {
        $variable = 'extra_'.$item->getField()->getVariable();
        if ($item->getField()->getFieldType() == Extrafield::FIELD_TYPE_TAG) {
            $tagsData[$variable] = $item->getValue();
        }
        $defaults[$variable] = $item->getValue();
    }
}

$extraField = new ExtraField('user');
$userForm = new FormValidator('user_form');
$jqueryExtra = '';


$htmlHeadXtra[] = '<script>

$(function() {

    $("#user_form select").select2();

	var blocks = [
        "#collapseOne",
        "#collapseTwo",
        "#collapseThree",
        "#collapseFour",
        "#collapseFive",
        "#collapseSix",
        "#collapseSeven",
        "#collapseEight"
    ];

    $.each(blocks, function( index, value ) {
        if (window.location.hash == value) {
            return true;
        }
        $(value).collapse("hide");
    });


    $("#filiere").on("click", function() {
        $("#filiere_panel").toggle();
        return false;
    });

    $("#dispo").on("click", function() {
        $("#dispo_panel").toggle();
        return false;
    });

    $("#dispo_pendant").on("click", function() {
        $("#dispo_pendant_panel").toggle();
        return false;
    });

    $("#niveau").on("click", function() {
        $("#niveau_panel").toggle();
        return false;
    });

    $("#methode").on("click", function() {
        $("#methode_panel").toggle();
        return false;
    });

    $("#enviroment").on("click", function() {
        $("#enviroment_panel").toggle();
        return false;
    });

    $("#themes").on("click", function() {
        $("#themes_panel").toggle();
        return false;
    });

    $("#objectifs").on("click", function() {
        $("#objectifs_panel").toggle();
        return false;
    });
});
</script>';

$userForm->addHtml('<div class="panel-group" id="search_extrafield" role="tablist" aria-multiselectable="true">');
$userForm->addHtml('<div class="panel panel-default">');
$userForm->addHtml('<div class="panel-heading"><a role="button" data-toggle="collapse" data-parent="#search_extrafield" href="#collapseZero" aria-expanded="true" aria-controls="collapseZero">'.
    get_lang('Diagnostic Form').'</a></div>');
$userForm->addHtml('<div id="collapseZero" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingZero">');
$userForm->addHtml('<div class="panel-body"><p class="text-info">');
$userForm->addHtml(get_lang('Diagnostic Introduction'));
$userForm->addHtml('</p></div></div></div></div>');

$userForm->addHtml('<div class="panel-group" id="search_extrafield" role="tablist" aria-multiselectable="true">');
$userForm->addHtml('<div class="panel panel-default">');
$userForm->addHtml('<div class="panel-heading"><a role="button" data-toggle="collapse" data-parent="#search_extrafield" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">'.get_lang('Filiere').'</a></div>');
$userForm->addHtml('<div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">');
$userForm->addHtml('<div class="panel-body"><p class="text-info">'.get_lang('Filiere Explanation').'</p>');

$fieldsToShow = [
    'statusocial',
    'filiere_user',
    'filiereprecision',
    'filiere_want_stage',
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $adminPermissions
);

$jqueryExtra .= $extra['jquery_ready_content'];

$fieldsToShow = [
    'filiere',
];

$extra = $extraFieldSession->addElements(
    $userForm,
    api_get_user_id(),
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $adminPermissions
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addButtonSave(get_lang('Save'), 'submit_partial[collapseOne]');

$userForm->addHtml('</div></div></div>');


$userForm->addHtml('<div class="panel panel-default">');
$userForm->addHtml(
    '<div class="panel-heading"><a role="button" data-toggle="collapse" data-parent="#search_extrafield" href="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">'.
    get_lang('Disponibilite Avant').'</a></div>'
);
$userForm->addHtml('<div id="collapseTwo" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingTwo">');
$userForm->addHtml('<div class="panel-body"><p class="text-info">'.get_lang('Disponibilite Avant Explanation').'</p>');

$extra = $extraFieldSession->addElements(
    $userForm,
    '',
    [],
    $filter,
    true,
    ['access_start_date', 'access_end_date'],
    [],
    [],
    false,
    $adminPermissions
);

$userForm->addRule(
    ['extra_access_start_date', 'extra_access_end_date'],
    get_lang('StartDateMustBeBeforeTheEndDate'),
    'compare_datetime_text',
    '< allow_empty'
);

$jqueryExtra .= $extra['jquery_ready_content'];

$elements = $userForm->getElements();
$variables = ['access_start_date', 'access_end_date'];
foreach ($elements as $element) {
    $element->setAttribute('extra_label_class', 'red_underline');
}

$fieldsToShow = [
    'heures_disponibilite_par_semaine',
    'moment_de_disponibilite',
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $adminPermissions
);

$userForm->addButtonSave(get_lang('Save'), 'submit_partial[collapseTwo]');

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addHtml('</div></div></div>');

$userForm->addHtml('<div class="panel panel-default">');
$userForm->addHtml('<div class="panel-heading"><a role="button" data-toggle="collapse" data-parent="#search_extrafield" href="#collapseThree" aria-expanded="true" aria-controls="collapseThree">'.get_lang('Disponibilite Pendant Mon Stage').'</a></div>');
$userForm->addHtml('<div id="collapseThree" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingThree">');
$userForm->addHtml('<div class="panel-body"><p class="text-info">'.get_lang('Disponibilite Pendant Mon Stage Explanation').'</p>');

$fieldsToShow = [
    'datedebutstage',
    'datefinstage',
    'je_ne_connais_pas_encore_mes_dates_de_stage',
    'deja_sur_place',
    'poursuiteapprentissagestage',
    'heures_disponibilite_par_semaine_stage',
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $adminPermissions
);

$userForm->addRule(
    ['extra_datedebutstage', 'extra_datefinstage'],
    get_lang('StartDateMustBeBeforeTheEndDate'),
    'compare_datetime_text',
    '< allow_empty'
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addButtonSave(get_lang('Save'), 'submit_partial[collapseThree]');
$userForm->addHtml('</div></div></div>');

$userForm->addHtml('<div class="panel panel-default">');
$userForm->addHtml('<div class="panel-heading"><a role="button" data-toggle="collapse" data-parent="#search_extrafield" href="#collapseFour" aria-expanded="true" aria-controls="collapseFour">'.get_lang('Themes Objectifs').'</a></div>');
$userForm->addHtml('<div id="collapseFour" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingFour">');
$userForm->addHtml('<div class="panel-body"><p class="text-info">'.get_lang('Themes Objectifs Explanation').'</p>');

$introductionTextList = [
    'domaine' => get_lang('Domaine Introduction'),
    $theme => get_lang('Theme Field Introduction'),
];

$fieldsToShow = [
    'domaine',
    $theme,
];

$extra = $extraFieldSession->addElements(
    $userForm,
    api_get_user_id(),
    [],
    $filter,
    false, //tag as select
    $fieldsToShow,
    $fieldsToShow,
    $defaults,
    true,
    $adminPermissions,
    ['domaine' => 3, $theme => 5], // $separateExtraMultipleSelect
    [
        'domaine' => [
            get_lang('Domaine').' 1',
            get_lang('Domaine').' 2',
            get_lang('Domaine').' 3',
        ],
        $theme => [
            get_lang('ThemeField').' 1',
            get_lang('ThemeField').' 2',
            get_lang('ThemeField').' 3',
            get_lang('ThemeField').' 4',
            get_lang('ThemeField').' 5',
        ],
    ],
    true, //$addEmptyOptionSelects
    $introductionTextList
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addButtonSave(get_lang('Save'), 'submit_partial[collapseFour]');
$userForm->addHtml('</div></div></div>');

$userForm->addHtml('<div class="panel panel-default">');
$userForm->addHtml('<div class="panel-heading"><a role="button" data-toggle="collapse" data-parent="#search_extrafield" href="#collapseFive" aria-expanded="true" aria-controls="collapseFive">'.get_lang('Niveau Langue').'</a></div>');
$userForm->addHtml('<div id="collapseFive" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingFive">');
$userForm->addHtml('<div class="panel-body"><p class="text-info">'.get_lang('Niveau Langue Explanation').'</p>');

$fieldsToShow = [
    //'competenceniveau'
    'ecouter',
    'lire',
    'participer_a_une_conversation',
    's_exprimer_oralement_en_continu',
    'ecrire',
];

$extra = $extraFieldSession->addElements(
    $userForm,
    api_get_user_id(),
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    $defaults,
    false, //$orderDependingDefaults = false,
    $adminPermissions
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addButtonSave(get_lang('Save'), 'submit_partial[collapseFive]');
$userForm->addHtml('</div></div></div>');

$userForm->addHtml('<div class="panel panel-default">');
$userForm->addHtml('<div class="panel-heading"><a role="button" data-toggle="collapse" data-parent="#search_extrafield" href="#collapseSix" aria-expanded="true" aria-controls="collapseSix">'.get_lang('Objectifs Apprentissage').'</a></div>');
$userForm->addHtml('<div id="collapseSix" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingSix">');
$userForm->addHtml('<div class="panel-body"><p class="text-info">'.get_lang('Objectifs Apprentissage Explanation').'</p>');

$fieldsToShow = [
    'objectif_apprentissage',
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    $filter,
    false,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $adminPermissions
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addButtonSave(get_lang('Save'), 'submit_partial[collapseSix]');
$userForm->addHtml('</div></div></div>');

$userForm->addHtml('<div class="panel panel-default">');
$userForm->addHtml('<div class="panel-heading"><a role="button" data-toggle="collapse" data-parent="#search_extrafield" href="#collapseSeven" aria-expanded="true" aria-controls="collapseSeven">'.get_lang('Methode Travail').'</a></div>');
$userForm->addHtml('<div id="collapseSeven" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingSeven">');
$userForm->addHtml('<div class="panel-body"><p class="text-info">'.get_lang('Methode Travail Explanation').'</p>');

$fieldsToShow = [
    'methode_de_travaille',
    'accompagnement',
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $adminPermissions
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addButtonSave(get_lang('Save'), 'submit_partial[collapseSeven]');
$userForm->addHtml('</div></div></div>');

// Enviroment
$userForm->addHtml('<div class="panel panel-default">');
$userForm->addHtml(
    '<div class="panel-heading">
    <a role="button" data-toggle="collapse" data-parent="#search_extrafield" href="#collapseEight" aria-expanded="true" aria-controls="collapseEight">'.
    get_lang('Mon Environnement De Travail').'</a></div>');
$userForm->addHtml('<div id="collapseEight" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingEight">');
$userForm->addHtml('<div class="panel-body"><p class="text-info">'.get_lang('Mon Environnement De Travail').'</p>');

$fieldsToShow = [
    'outil_de_travail_ordinateur',
    'outil_de_travail_ordinateur_so',
    'outil_de_travail_tablette',
    'outil_de_travail_tablette_so',
    'outil_de_travail_smartphone',
    'outil_de_travail_smartphone_so',
];

$userForm->addLabel(null, get_lang('Mon Environnement De Travail Explanation Intro1'));

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $adminPermissions
);

$userForm->addLabel(null, get_lang('Mon Environnement De Travail Explanation Intro2'));

$jqueryExtra .= $extra['jquery_ready_content'];

$fieldsToShow = [
    'browser_platforme',
    'browser_platforme_autre',
    'browser_platforme_version',
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $adminPermissions
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addHtml('<p class="text-info">'.get_lang('Mon Environnement De Travail Renvoi FAQ').'</p>');

$userForm->addButtonSave(get_lang('Save'), 'submit_partial[collapseEight]');
$userForm->addHtml('</div></div></div>');
$userForm->addHtml('</div>');

//$userForm->addHtml('</div>');


$htmlHeadXtra[] = '<script>
$(function () {
	'.$jqueryExtra.'
});
</script>';

$userForm->addButtonSave(get_lang('Send'));
$userForm->setDefaults($defaults);

$domaine1 = $userForm->getElementByName('extra_domaine[0]');
$domaine2 = $userForm->getElementByName('extra_domaine[1]');
$domaine3 = $userForm->getElementByName('extra_domaine[2]');

$domainList = array_merge(
    is_object($domaine1) ? $domaine1->getValue() : [],
    is_object($domaine3) ? $domaine3->getValue() : [],
    is_object($domaine2) ? $domaine2->getValue() : []
);

$themeList = [];

$resultOptions = $extraFieldSession->searchOptionsFromTags(
    'extra_domaine',
    'extra_'.$theme,
    $domainList
);

if ($resultOptions) {
    $resultOptions = array_column($resultOptions, 'tag', 'id');
    $resultOptions = array_filter($resultOptions);

    for ($i = 0; $i < 5; $i++) {
        $themeElement = $userForm->getElementByName('extra_'.$theme.'['.$i.']');
        foreach ($resultOptions as $key => $value) {
            $themeElement->addOption($value, $value);
        }
    }
}

if ($userForm->validate()) {
    // Saving to user extra fields
    $extraFieldValue = new ExtraFieldValue('user');
    $userData = $userForm->getSubmitValues();

    $isPartial = false;
    $block = '';
    if (isset($userData['submit_partial'])) {
        $block = key($userData['submit_partial']);
        $isPartial = true;
    }

    if ($isPartial === false) {
        $userData['extra_diagnosis_completed'] = 1;
    }

    $extraFieldValue->saveFieldValues(
        $userData,
        true,
        false,
        [],
        ['legal_accept'],
        true
    );

    // Saving to extra_field_saved_search

    $user = $em->getRepository(User::class)->find($userId);

    $sessionFields = [
        'extra_access_start_date',
        'extra_access_end_date',
        'extra_filiere',
        'extra_domaine',
        'extra_domaine[0]',
        'extra_domaine[1]',
        'extra_domaine[3]',
        'extra_temps_de_travail',
        //'extra_competenceniveau',
        'extra_'.$theme,
        'extra_'.$theme.'[0]',
        'extra_'.$theme.'[1]',
        'extra_'.$theme.'[2]',
        'extra_'.$theme.'[3]',
        'extra_'.$theme.'[4]',
        'extra_ecouter',
        'extra_lire',
        'extra_participer_a_une_conversation',
        'extra_s_exprimer_oralement_en_continu',
        'extra_ecrire',
    ];

    foreach ($userData as $key => $value) {
        $found = strpos($key, '__persist__');
        if ($found === false) {
            continue;
        }
    }

    if (isset($userData['extra_filiere_want_stage']) &&
        isset($userData['extra_filiere_want_stage']['extra_filiere_want_stage'])
    ) {
        $wantStage = $userData['extra_filiere_want_stage']['extra_filiere_want_stage'];

        if ($wantStage === 'yes') {
            if (isset($userData['extra_filiere_user'])) {
                $userData['extra_filiere'] = [];
                $userData['extra_filiere']['extra_filiere'] = $userData['extra_filiere_user']['extra_filiere_user'];
            }
        }
    }

    // save in ExtraFieldSavedSearch.
    $extraFieldRepo = $em->getRepository(\Chamilo\CoreBundle\Entity\ExtraField::class);

    foreach ($userData as $key => $value) {
        if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
            continue;
        }

        if (!in_array($key, $sessionFields)) {
            continue;
        }

        $field_variable = substr($key, 6);
        $extraFieldInfo = $extraFieldValueSession
            ->getExtraField()
            ->get_handler_field_info_by_field_variable($field_variable)
        ;

        if (!$extraFieldInfo) {
            continue;
        }

        $extraFieldObj = $extraFieldRepo->find($extraFieldInfo['id']);
        $search = [
            'field' => $extraFieldObj,
            'user' => $user,
        ];

        /** @var ExtraFieldSavedSearch $saved */
        $saved = $extraFieldSavedSearchRepo->findOneBy($search);

        if (empty($value)) {
            $value = [];
        }

        if ($saved) {
            $saved
                ->setField($extraFieldObj)
                ->setUser($user)
                ->setValue($value)
            ;
        } else {
            $saved = (new ExtraFieldSavedSearch())
                ->setField($extraFieldObj)
                ->setUser($user)
                ->setValue($value)
            ;
        }
        $em->persist($saved);
        $em->flush();
    }

    $superiorUserList = UserManager::getStudentBossList($userInfo['user_id']);

    if ($superiorUserList && $isPartial == false) {
        $url = api_get_path(WEB_PATH).'load_search.php?user_id='.$userInfo['user_id'];
        $urlContact = api_get_path(WEB_CODE_PATH).'messages/inbox.php?f=social';
        $subject = sprintf(get_lang('Diagnosis From User %s'), $userInfo['complete_name']);
        $message = sprintf(get_lang('Diagnosis From User %s lang %s with link %s Contact at %s'), $userInfo['complete_name'], $userInfo['language'], $url, $urlContact);
        foreach ($superiorUserList as $bossData) {
            $bossId = $bossData['boss_id'];
            MessageManager::send_message_simple(
                $bossId,
                $subject,
                $message
            );
        }
    }

    if ($isPartial) {
        header('Location:'.api_get_self().'#'.$block);
    } else {
        header('Location:'.api_get_self().'?result=1');
    }
    exit;
}

$userFormToString = $userForm->returnForm();

$result = isset($_GET['result']) ? true : false;
$tpl = new Template(get_lang('Diagnosis'));
$tpl->assign('grid', '');
$tpl->assign('grid_js', '');
$tpl->assign('form_search', '');
$tpl->assign('form', '');
if ($result === false) {
    $tpl->assign('form', $userFormToString);
} else {
    Display::addFlash(Display::return_message(get_lang('Your session search diagnosis is saved')));
}

$content = $tpl->fetch($tpl->get_template('search/search_extra_field.tpl'));
$tpl->assign('content', $content);
$tpl->display_one_col_template();



