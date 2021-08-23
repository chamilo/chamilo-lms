<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldSavedSearch;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;

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
if ($diagnosisComplete && isset($diagnosisComplete['value']) && 1 == $diagnosisComplete['value']) {
    if (!isset($_GET['result'])) {
        header('Location:'.api_get_self().'?result=1');
        exit;
    }
}

$hide = true;
if (false !== $wantStage) {
    $hide = 'yes' === $wantStage['value'];
}

$defaultValueStatus = 'extraFiliere.hide()';
if (false === $hide) {
    $defaultValueStatus = '';
}

$url = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?a=order&user_id='.$userId;

// Use current user language
$langInfo = api_get_language_from_iso($userInfo['language']);
$targetLanguage = $langInfo->getEnglishName();
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
        if (Extrafield::FIELD_TYPE_TAG === $item->getField()->getFieldType()) {
            $tagsData[$variable] = $item->getValue();
        }
        $defaults[$variable] = $item->getValue();
    }
}

if (isset($defaults['extra_access_start_date']) && isset($defaults['extra_access_start_date'][0])) {
    $defaults['extra_access_start_date'] = $defaults['extra_access_start_date'][0];
}

if (isset($defaults['extra_access_end_date']) && isset($defaults['extra_access_end_date'][0])) {
    $defaults['extra_access_end_date'] = $defaults['extra_access_end_date'][0];
}

$extraField = new ExtraField('user');
$userForm = new FormValidator('user_form');
$jqueryExtra = '';
$htmlHeadXtra[] = '<script>
$(function() {
    //$("#user_form select").select2();
});
</script>';

$userForm->addStartPanel('diagnostic', get_lang('Diagnostic Form'));
$userForm->addHtml(get_lang('Diagnostic Introduction'));
$userForm->addEndPanel();

$userForm->addStartPanel('filiere', get_lang('Filiere'));
$userForm->addHtml('<p class="text-info">'.get_lang('Filiere Explanation').'</p>');

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
$userForm->addEndPanel();

$userForm->addStartPanel('dispo', get_lang('Disponibilite Avant'));
$userForm->addHtml('<p class="text-info">'.get_lang('Disponibilite Avant Explanation').'</p>');

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
$userForm->addEndPanel();

$userForm->addStartPanel('dispo_pendant_stage', get_lang('Disponibilite Pendant Mon Stage'));
$userForm->addHtml('<p class="text-info">'.get_lang('Disponibilite Pendant Mon Stage Explanation').'</p>');

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
$userForm->addEndPanel();

$userForm->addStartPanel('theme_obj', get_lang('Themes Objectifs'));
$userForm->addHtml('<p class="text-info">'.get_lang('Themes Objectifs Explanation').'</p>');

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
$userForm->addEndPanel();

$userForm->addStartPanel('niveau_langue', get_lang('Niveau Langue'));
$userForm->addHtml('<p class="text-info">'.get_lang('Niveau Langue Explanation').'</p>');

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
$userForm->addEndPanel();

$userForm->addStartPanel('obj_apprentissage', get_lang('Objectifs Apprentissage'));
$userForm->addHtml('<p class="text-info">'.get_lang('Objectifs Apprentissage Explanation').'</p>');
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
$userForm->addEndPanel();

$userForm->addStartPanel('methode_travail', get_lang('Methode Travail'));
$userForm->addHtml('<p class="text-info">'.get_lang('Methode Travail Explanation').'</p>');

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
$userForm->addEndPanel();

$userForm->addStartPanel('environnement', get_lang('Mon Environnement De Travail'));
$userForm->addHtml('<p class="text-info">'.get_lang('Mon Environnement De Travail').'</p>');

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
$userForm->addEndPanel();

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

    if (false === $isPartial) {
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
    $user = api_get_user_entity($userId);

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
        if (false === $found) {
            continue;
        }
    }

    if (isset($userData['extra_filiere_want_stage']) &&
        isset($userData['extra_filiere_want_stage']['extra_filiere_want_stage'])
    ) {
        $wantStage = $userData['extra_filiere_want_stage']['extra_filiere_want_stage'];

        if ('yes' === $wantStage) {
            if (isset($userData['extra_filiere_user'])) {
                $userData['extra_filiere'] = [];
                $userData['extra_filiere']['extra_filiere'] = $userData['extra_filiere_user']['extra_filiere_user'];
            }
        }
    }

    // save in ExtraFieldSavedSearch.
    $extraFieldRepo = Container::getExtraFieldRepository();

    foreach ($userData as $key => $value) {
        if ('extra_' !== substr($key, 0, 6) && '_extra_' !== substr($key, 0, 7)) {
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

        if (is_string($value)) {
            $value = [$value];
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

    Display::addFlash(Display::return_message('Updated'));

    $superiorUserList = UserManager::getStudentBossList($userInfo['user_id']);
    if ($superiorUserList && false === $isPartial) {
        $url = api_get_path(WEB_PATH).'load_search.php?user_id='.$userInfo['user_id'];
        $urlContact = api_get_path(WEB_CODE_PATH).'messages/inbox.php?f=social';
        $subject = sprintf(get_lang('Diagnosis From User %s'), $userInfo['complete_name']);
        $message = sprintf(
            get_lang('Diagnosis From User %s lang %s with link %s Contact at %s'),
            $userInfo['complete_name'],
            $userInfo['language'],
            $url,
            $urlContact
        );
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

$result = isset($_GET['result']);
$tpl = new Template(get_lang('Diagnosis'));
$tpl->assign('grid', '');
$tpl->assign('grid_js', '');
$tpl->assign('form_search', '');
$tpl->assign('form', '');
if (false === $result) {
    $tpl->assign('form', $userFormToString);
} else {
    Display::addFlash(Display::return_message(get_lang('Your session search diagnosis is saved')));
}

$content = $tpl->fetch($tpl->get_template('search/search_extra_field.tpl'));
$tpl->assign('content', $content);
$tpl->display_one_col_template();
