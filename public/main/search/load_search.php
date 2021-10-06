<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldSavedSearch;
use ChamiloSession as Session;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

if ('false' === api_get_setting('session.allow_search_diagnostic')) {
    api_not_allowed();
}

$htmlHeadXtra[] = '<script>
$(function() {
    //$("#user_form select").select2();
});
</script>';

api_block_anonymous_users();
$allowToSee = api_is_drh() || api_is_student_boss() || api_is_platform_admin();

if (false === $allowToSee) {
    api_not_allowed(true);
}
$userId = api_get_user_id();
$userInfo = api_get_user_info();

$userToLoad = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

$userToLoadInfo = [];
if ($userToLoad) {
    $userToLoadInfo = api_get_user_info($userToLoad);
}
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'subscribe_user':
        $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : '';
        SessionManager::subscribeUsersToSession(
            $sessionId,
            [$userToLoad],
            SESSION_VISIBLE_READ_ONLY,
            false
        );
        Display::addFlash(Display::return_message(get_lang('UserAdded')));
        header("Location: ".api_get_self().'?user_id='.$userToLoad.'#session-table');
        break;
    case 'unsubscribe_user':
        $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : '';
        SessionManager::unsubscribe_user_from_session($sessionId, $userToLoad);
        Display::addFlash(Display::return_message(get_lang('Unsubscribed')));
        header("Location: ".api_get_self().'?user_id='.$userToLoad.'#session-table');
        break;
}

$em = Database::getManager();

$formSearch = new FormValidator('load', 'get', api_get_self());
$formSearch->addHeader(get_lang('Load Diagnosis'));
if (!empty($userInfo)) {
    $users = [];
    switch ($userInfo['status']) {
        case DRH:
            $users = UserManager::get_users_followed_by_drh(
                $userId,
                0,
                false,
                false,
                false,
                null,
                null,
                null,
                null,
                1
            );
            break;
        case STUDENT_BOSS:
            $users = UserManager::getUsersFollowedByStudentBoss(
                $userId,
                0,
                false,
                false,
                false,
                null,
                null,
                null,
                null,
                1
            );
            break;
    }

    // Allow access for admins.
    if (empty($users) && api_is_platform_admin()) {
        $users[] = $userToLoadInfo;
    }

    if (!empty($users)) {
        $userList = [];
        foreach ($users as $user) {
            $userList[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']);
        }
        $formSearch->addSelect('user_id', get_lang('User'), $userList);
    }
}

$items = [];
if ($userToLoad) {
    $formSearch->setDefaults(['user_id' => $userToLoad]);
    $items = $em->getRepository(ExtraFieldSavedSearch::class)->findBy(['user' => api_get_user_entity($userToLoad)]);
    if (empty($items)) {
        Display::addFlash(Display::return_message('No data found'));
    }
}

$formSearch->addButtonSearch(get_lang('Show Diagnostic'), 'save');

$form = new FormValidator('search', 'post', api_get_self().'?user_id='.$userToLoad.'#session-table');
$form->addHeader(get_lang('Diagnosis'));
$form->addHidden('user_id', $userToLoad);

$defaults = [];
$tagsData = [];
if (!empty($items)) {
    /** @var ExtraFieldSavedSearch $item */
    foreach ($items as $item) {
        $variable = 'extra_'.$item->getField()->getVariable();
        if (ExtraField::FIELD_TYPE_TAG === $item->getField()->getFieldType()) {
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

$extraField = new ExtraField('session');
$extraFieldValue = new ExtraFieldValue('session');
$extraFieldValueUser = new ExtraFieldValue('user');

$theme = 'theme_fr';

$lang = $defaultLangCible = api_get_language_isocode();

if ($userToLoadInfo) {
    $langInfo = api_get_language_from_iso($userToLoadInfo['locale']);
    $lang = $langInfo->getEnglishName();
    $targetLanguageInfo = $extraFieldValueUser->get_values_by_handler_and_field_variable(
        $userToLoad,
        'langue_cible'
    );

    if (!empty($targetLanguageInfo)) {
        $defaultLangCible = $targetLanguageInfo['value'];
    }

    switch ($lang) {
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
}

$jqueryExtra = '';
$extraFieldUser = new ExtraField('user');

$userForm = new FormValidator('user_form', 'post', api_get_self());

$userForm->addStartPanel('filiere', get_lang('Filiere'));
$userForm->addHtml('<p class="text-info">'.get_lang('Filiere Explanation').'</p>');

$fieldsToShow = [
    'statusocial',
    'filiere_user',
    'filiereprecision',
    'filiere_want_stage',
];
$forceShowFields = true;
$filter = false;
$extra = $extraFieldUser->addElements(
    $userForm,
    $userToLoad,
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $forceShowFields, //$forceShowFields = false
    [],
    []
);
$userForm->addEndPanel();

$userForm->addStartPanel('dispo', get_lang('Disponibilite Pendant Mon Stage'));
$userForm->addHtml('<p class="text-info">'.get_lang('Disponibilite Pendant Mon Stage Explanation').'</p>');

$fieldsToShow = [
    'datedebutstage',
    'datefinstage',
    'deja_sur_place',
    'poursuiteapprentissagestage',
    'heures_disponibilite_par_semaine_stage',
];

$extra = $extraFieldUser->addElements(
    $userForm,
    $userToLoad,
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $forceShowFields, //$forceShowFields = false
    [],
    []
);
$userForm->addEndPanel();

$userForm->addStartPanel('objectifs', get_lang('Objectifs Apprentissage'));
$userForm->addHtml('<p class="text-info">'.get_lang('Objectifs Apprentissage Explanation').'</p>');
$fieldsToShow = [
    'objectif_apprentissage',
];
$extra = $extraFieldUser->addElements(
    $userForm,
    $userToLoad,
    [],
    $filter,
    false,
    $fieldsToShow,
    $fieldsToShow,
    $defaults,
    false,
    $forceShowFields,//$forceShowFields = false
    [],
    []
);
$userForm->addEndPanel();

$userForm->addStartPanel('method', get_lang('Méthode de Travail'));
$userForm->addHtml('<p class="text-info">'.get_lang('Method de Travail Explanation').'</p>');

$fieldsToShow = [
    'methode_de_travaille',
    'accompagnement',
];

$extra = $extraFieldUser->addElements(
    $userForm,
    $userToLoad,
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $forceShowFields, //$forceShowFields = false
    [],
    []
);
$userForm->addEndPanel();

if (isset($_POST) && !empty($_POST)) {
    $searchChecked1 = isset($_POST['search_using_1']) ? 'checked' : '';
    $searchChecked2 = isset($_POST['search_using_2']) ? 'checked' : '';
    $searchChecked3 = isset($_POST['search_using_3']) ? 'checked' : '';
    Session::write('search_using_1', $searchChecked1);
    Session::write('search_using_2', $searchChecked2);
    Session::write('search_using_3', $searchChecked3);
} else {
    $searchChecked1 = Session::read('search_using_1');
    $searchChecked1 = null === $searchChecked1 ? 'checked' : $searchChecked1;

    $searchChecked2 = Session::read('search_using_2');
    $searchChecked2 = null === $searchChecked2 ? 'checked' : $searchChecked2;

    $searchChecked3 = Session::read('search_using_3');
    $searchChecked3 = null === $searchChecked3 ? 'checked' : $searchChecked3;
}

$form->addStartPanel('dispo_avant', '<input type="checkbox" name="search_using_1" '.$searchChecked1.' />&nbsp;'.get_lang('Disponibilite Avant'));
$form->addHtml('<p class="text-info">'.get_lang('Disponibilite Avant Explanation').'</p>');

// Session fields
$showOnlyThisFields = [
    'access_start_date',
    'access_end_date',
];

$extra = $extraField->addElements(
    $form,
    '',
    [],
    false, //filter
    true,
    $showOnlyThisFields,
    $showOnlyThisFields,
    $defaults,
    false, //$orderDependingDefaults
    true, // force
    [], // $separateExtraMultipleSelect
    []
);

$fieldsToShow = [
    'heures_disponibilite_par_semaine',
    'moment_de_disponibilite',
    //'langue_cible',
];

$extra = $extraFieldUser->addElements(
    $form,
    $userToLoad,
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $forceShowFields //$forceShowFields = false
);

$form->addEndPanel();

$form->addStartPanel('theme_obj', '<input type="checkbox" name="search_using_2" '.$searchChecked2.' />&nbsp;'.get_lang('Themes Objectifs'));
$form->addHtml('<p class="text-info">'.get_lang('Themes Objectifs Explanation').'</p>');

$showOnlyThisFields = [
    'domaine',
    'filiere',
    $theme,
];

$extra = $extraField->addElements(
    $form,
    '',
    [],
    false, //filter
    true,
    $showOnlyThisFields,
    $showOnlyThisFields,
    $defaults,
    false, //$orderDependingDefaults
    true, // force
    ['domaine' => 3, $theme => 5], // $separateExtraMultipleSelect
    [
        'domaine' => [
            get_lang('Domaine').' 1',
            get_lang('Domaine').' 2',
            get_lang('Domaine').' 3',
        ],
        $theme => [
            get_lang('Theme Field').' 1',
            get_lang('Theme Field').' 2',
            get_lang('Theme Field').' 3',
            get_lang('Theme Field').' 4',
            get_lang('Theme Field').' 5',
        ],
    ],
    true
);

// Commented because BT#15776
$fieldsToShow = [
    'langue_cible',
];

$extra = $extraFieldUser->addElements(
    $form,
    $userToLoad,
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $forceShowFields //$forceShowFields = false
);

$form->addEndPanel();

$form->addStartPanel('niveau_langue', '<input type="checkbox" name="search_using_3" '.$searchChecked3.' />&nbsp;'.get_lang('Niveau Langue'));
$form->addHtml('<p class="text-info">'.get_lang('Niveau Langue Explanation').'</p>');

$showOnlyThisFields = [
    'ecouter',
    'lire',
    'participer_a_une_conversation',
    's_exprimer_oralement_en_continu',
    'ecrire',
];

$extra = $extraField->addElements(
    $form,
    '',
    [],
    false, //filter
    true,
    $showOnlyThisFields,
    $showOnlyThisFields,
    $defaults,
    false, //$orderDependingDefaults
    true, // force
    ['domaine' => 3, $theme => 5], // $separateExtraMultipleSelect
    [
        'domaine' => [
            get_lang('Domaine').' 1',
            get_lang('Domaine').' 2',
            get_lang('Domaine').' 3',
        ],
        $theme => [
            get_lang('Theme').' 1',
            get_lang('Theme').' 2',
            get_lang('Theme').' 3',
            get_lang('Theme').' 4',
            get_lang('Theme').' 5',
        ],
    ]
);

$form->addEndPanel();

$userForm->addStartPanel('environnement_travail', get_lang('Mon Environnement De Travail'));
$userForm->addHtml('<p class="text-info">'.get_lang('Mon Environnement De Travail Explanation').'</p>');

$fieldsToShow = [
    'outil_de_travail_ordinateur',
    'outil_de_travail_ordinateur_so',
    'outil_de_travail_tablette',
    'outil_de_travail_tablette_so',
    'outil_de_travail_smartphone',
    'outil_de_travail_smartphone_so',
];

$userForm->addLabel(null, get_lang('Mon Environnement De Travail Explanation Intro1'));

$extra = $extraFieldUser->addElements(
    $userForm,
    $userToLoad,
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $forceShowFields
);

$userForm->addLabel(null, get_lang('Mon Environnement De Travail Explanation Intro2'));

$jqueryExtra .= $extra['jquery_ready_content'];

$fieldsToShow = [
    'browser_platforme',
    'browser_platforme_autre',
    'browser_platforme_version',
];
$extra = $extraFieldUser->addElements(
    $userForm,
    $userToLoad,
    [],
    $filter,
    true,
    $fieldsToShow,
    $fieldsToShow,
    [],
    false,
    $forceShowFields, //$forceShowFields = false
    [],
    []
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addHtml('<p class="text-info">'.get_lang('Mon Environnement De Travail Renvoi FAQ').'</p>');

$userForm->addButtonSave(get_lang('Save'), 'submit_partial[collapseEight]');

$userForm->addEndPanel();

$form->addButtonSave(get_lang('Save Diagnostic Changes'), 'save');
$form->addButtonSearch(get_lang('Search Sessions'), 'search');

$extraFieldsToFilter = $extraField->get_all(['variable = ?' => 'temps_de_travail']);
$extraFieldToSearch = [];
if (!empty($extraFieldsToFilter)) {
    foreach ($extraFieldsToFilter as $filter) {
        $extraFieldToSearch[] = $filter['id'];
    }
}
$extraFieldListToString = implode(',', $extraFieldToSearch);
$result = SessionManager::getGridColumns('simple', $extraFieldsToFilter);
$columns = $result['columns'];
$columnModel = $result['column_model'];

$form->setDefaults($defaults);

/** @var HTML_QuickForm_select $element */
$domaine1 = $form->getElementByName('extra_domaine[0]');
$domaine2 = $form->getElementByName('extra_domaine[1]');
$domaine3 = $form->getElementByName('extra_domaine[2]');
$userForm->setDefaults($defaults);

$domainList = array_merge(
    is_object($domaine1) && !empty($domaine1->getValue()) ? $domaine1->getValue() : [],
    is_object($domaine3) && !empty($domaine3->getValue()) ? $domaine3->getValue() : [],
    is_object($domaine2) && !empty($domaine2->getValue()) ? $domaine2->getValue() : []
);

$themeList = [];
$extraField = new ExtraField('session');
$resultOptions = $extraField->searchOptionsFromTags('extra_domaine', 'extra_'.$theme, $domainList);

if ($resultOptions) {
    $resultOptions = array_column($resultOptions, 'tag', 'id');
    $resultOptions = array_filter($resultOptions);

    for ($i = 0; $i < 5; $i++) {
        /** @var HTML_QuickForm_select $theme */
        $themeElement = $form->getElementByName('extra_'.$theme.'['.$i.']');
        foreach ($resultOptions as $key => $value) {
            $themeElement->addOption($value, $value);
        }
    }
}

$filterToSend = '';
if ($formSearch->validate()) {
    $formSearchParams = $formSearch->getSubmitValues();
}

// Search filter
$filters = [];
foreach ($defaults as $key => $value) {
    if ('extra_' !== substr($key, 0, 6) && '_extra_' !== substr($key, 0, 7)) {
        continue;
    }
    if (!empty($value)) {
        $filters[$key] = $value;
    }
}

$filterToSend = [];
if (!empty($filters)) {
    $filterToSend = ['groupOp' => 'AND'];
    if ($filters) {
        $count = 1;
        $countExtraField = 1;
        foreach ($result['column_model'] as $column) {
            if ($count > 5) {
                if (isset($filters[$column['name']])) {
                    $defaultValues['jqg'.$countExtraField] = $filters[$column['name']];
                    /*switch ($column['name']) {
                        case 'extra_theme_it':
                        case 'extra_theme_de':
                        case 'extra_theme_es':
                        case 'extra_theme_fr':
                            break;
                        case 'extra_domaine':
                            break;
                        case '':
                            break;
                    }*/
                    $filterToSend['rules'][] = [
                        'field' => $column['name'],
                        'op' => 'cn',
                        'data' => $filters[$column['name']],
                    ];
                }
                $countExtraField++;
            }
            $count++;
        }
    }
}

$params = [];
if ($form->validate()) {
    $params = $form->getSubmitValues();
    $save = false;
    $search = false;
    if (isset($params['search'])) {
        unset($params['search']);
        $search = true;
    }

    if (isset($params['save'])) {
        $save = true;
        unset($params['save']);
    }

    $form->setDefaults($params);

    $filters = [];

    // Search
    if ($search) {
        // Parse params.
        foreach ($params as $key => $value) {
            if ('extra_' !== substr($key, 0, 6) && '_extra_' !== substr($key, 0, 7)) {
                continue;
            }
            if (!empty($value)) {
                $filters[$key] = $value;
            }
        }

        $filterToSend = [];
        if (!empty($filters)) {
            $filterToSend = ['groupOp' => 'AND'];
            if ($filters) {
                $count = 1;
                $countExtraField = 1;
                foreach ($result['column_model'] as $column) {
                    if ($count > 5) {
                        if (isset($filters[$column['name']])) {
                            $defaultValues['jqg'.$countExtraField] = $filters[$column['name']];
                            $filterToSend['rules'][] = [
                                'field' => $column['name'],
                                'op' => 'cn',
                                'data' => $filters[$column['name']],
                            ];
                        }
                        $countExtraField++;
                    }
                    $count++;
                }
            }
        }
    }

    if ($save) {
        $userData = $params;
        // Update extra_heures_disponibilite_par_semaine
        $extraFieldValue = new ExtraFieldValue('user');
        $userDataToSave = [
            'item_id' => $userToLoad,
            'extra_heures_disponibilite_par_semaine' => $userData['extra_heures_disponibilite_par_semaine'] ?? '',
            'extra_langue_cible' => $userData['extra_langue_cible'] ?? '',
        ];
        $extraFieldValue->saveFieldValues(
            $userDataToSave,
            true,
            false,
            ['heures_disponibilite_par_semaine', 'langue_cible'],
            [],
            true
        );

        $extraFieldValueSession = new ExtraFieldValue('session');
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
                ->get_handler_field_info_by_field_variable($field_variable);

            if (!$extraFieldInfo) {
                continue;
            }

            $extraFieldObj = $em->getRepository(\Chamilo\CoreBundle\Entity\ExtraField::class)->find(
                $extraFieldInfo['id']
            );

            $search = [
                'field' => $extraFieldObj,
                'user' => $userToLoad,
            ];

            /** @var ExtraFieldSavedSearch $saved */
            $saved = $em->getRepository(ExtraFieldSavedSearch::class)->findOneBy($search);

            if (empty($value)) {
                $value = [];
            }

            if (is_string($value)) {
                $value = [$value];
            }

            if ($saved) {
                $saved
                    ->setField($extraFieldObj)
                    ->setUser(api_get_user_entity($userToLoad))
                    ->setValue($value)
                ;
            } else {
                $saved = new ExtraFieldSavedSearch();
                $saved
                    ->setField($extraFieldObj)
                    ->setUser(api_get_user_entity($userToLoad))
                    ->setValue($value)
                ;
            }
            $em->persist($saved);
            $em->flush();
        }
        Display::addFlash(Display::return_message(get_lang('Saved'), 'success'));
        header('Location: '.api_get_self().'?user_id='.$userToLoad);
        exit;
    }
}

$view = $form->returnForm();

$jsTag = '';
if (!empty($tagsData)) {
    foreach ($tagsData as $extraField => $tags) {
        foreach ($tags as $tag) {
            $tag = api_htmlentities($tag);
        }
    }
}

$htmlHeadXtra[] = '<script>
$(function() {
    '.$jqueryExtra.'
    '.$jsTag.'
});
</script>';

if (!empty($filterToSend)) {
    if (isset($params['search_using_1'])) {
        // Get start and end date from ExtraFieldSavedSearch
        $defaultExtraStartDate = isset($defaults['extra_access_start_date']) ? $defaults['extra_access_start_date'] : '';
        $defaultExtraEndDate = isset($defaults['extra_access_end_date']) ? $defaults['extra_access_end_date'] : '';

        $userStartDate = isset($params['extra_access_start_date']) ? $params['extra_access_start_date'] : $defaultExtraStartDate;
        $userEndDate = isset($params['extra_access_end_date']) ? $params['extra_access_end_date'] : $defaultExtraEndDate;

        // Minus 3 days
        $date = new DateTime($userStartDate);
        $date->sub(new DateInterval('P3D'));
        $userStartDateMinus = $date->format('Y-m-d h:i:s');

        // Plus 2 days
        $date = new DateTime($userEndDate);
        $date->add(new DateInterval('P2D'));
        $userEndDatePlus = $date->format('Y-m-d h:i:s');

        // Ofaj fix
        $userStartDateMinus = api_get_utc_datetime(substr($userStartDateMinus, 0, 11).'00:00:00');
        $userEndDatePlus = api_get_utc_datetime(substr($userEndDatePlus, 0, 11).'23:59:59');

        // Special OFAJ date logic
        if ('' == $userEndDate) {
            $sql = " AND (
                (s.access_start_date >= '$userStartDateMinus') OR
                ((s.access_start_date = '' OR s.access_start_date IS NULL) AND (s.access_end_date = '' OR s.access_end_date IS NULL))
            )";
        } else {
            $sql = " AND (
                (s.access_start_date >= '$userStartDateMinus' AND s.access_end_date < '$userEndDatePlus') OR
                (s.access_start_date >= '$userStartDateMinus' AND (s.access_end_date = '' OR s.access_end_date IS NULL)) OR
                ((s.access_start_date = '' OR s.access_start_date IS NULL) AND (s.access_end_date = '' OR s.access_end_date IS NULL))
            )";
        }
    }

    $deleteFiliere = false;
    $extraFieldOptions = new ExtraFieldOption('session');
    $extraFieldSession = new ExtraField('session');

    // Special filters
    // see https://task.beeznest.com/issues/10849#change-81902
    foreach ($filterToSend['rules'] as &$filterItem) {
        if (!isset($filterItem)) {
            continue;
        }

        if (isset($filterItem['field'])) {
            switch ($filterItem['field']) {
                case 'extra_filiere':
                case 'extra_domaine':
                case 'extra_theme_it':
                case 'extra_theme_fr':
                case 'extra_theme_de':
                case 'extra_theme_pl':
                    if (!isset($params['search_using_2'])) {
                        $filterItem = null;
                    }
                    break;
            }
        }

        if (isset($filterItem['field'])) {
            switch ($filterItem['field']) {
                case 'extra_ecouter':
                case 'extra_lire':
                case 'extra_participer_a_une_conversation':
                case 'extra_s_exprimer_oralement_en_continu':
                case 'extra_ecrire':
                    if (!isset($params['search_using_3'])) {
                        $filterItem = null;
                        break;
                    }
                    $selectedValue = '';
                    $fieldExtra = str_replace('extra_', '', $filterItem['field']);
                    $extraFieldSessionData = $extraFieldSession->get_handler_field_info_by_field_variable($fieldExtra);

                    if (is_array($filterItem['data'])) {
                        $myOrder = [];
                        foreach ($filterItem['data'] as $option) {
                            foreach ($extraFieldSessionData['options'] as $optionValue) {
                                if ($option == $optionValue['option_value']) {
                                    $myOrder[$optionValue['option_order']] = $optionValue['option_value'];
                                }
                            }
                        }

                        if (!empty($myOrder)) {
                            // Taking last from list
                            $selectedValue = end($myOrder);
                        }
                    } else {
                        $selectedValue = $filterItem['data'];
                    }

                    $newOptions = array_column(
                        $extraFieldSessionData['options'],
                        'option_value',
                        'option_order'
                    );

                    $searchOptions = [];
                    for ($i = 1; $i < count($newOptions); $i++) {
                        if ($selectedValue == $newOptions[$i]) {
                            if (isset($newOptions[$i - 1])) {
                                $searchOptions[] = $newOptions[$i - 1];
                            }
                            if (isset($newOptions[$i])) {
                                $searchOptions[] = $newOptions[$i];
                            }
                            if (isset($newOptions[$i + 1])) {
                                $searchOptions[] = $newOptions[$i + 1];
                            }
                            break;
                        }
                    }

                    $filterItem['data'] = $searchOptions;
                    break;
                case 'extra_domaine':
                    if (!isset($params['search_using_2'])) {
                        break;
                    }
                    // Special condition see:
                    // https://task.beeznest.com/issues/10849#note-218
                    // Remove filiere
                    $list = [
                        'vie-quotidienne',
                        //'competente-dans-mon-domaine-de-specialite',
                        'arrivee-sur-mon-poste-de-travail',
                    ];

                    $deleteFiliere = false;
                    if (is_array($filterItem['data'])) {
                        $myOrder = [];
                        foreach ($filterItem['data'] as $option) {
                            if (in_array($option, $list)) {
                                $deleteFiliere = true;
                                break;
                            }
                        }
                    } else {
                        if (in_array($filterItem['data'], $list)) {
                            $deleteFiliere = true;
                        }
                    }
                    break;
            }
        }

        if ($deleteFiliere) {
            foreach ($filterToSend['rules'] as &$filterItem) {
                if (isset($filterItem['field']) && 'extra_filiere' == $filterItem['field']) {
                    $filterItem = [];
                }
            }
        }
    }

    // Language
    $lang = isset($params['extra_langue_cible']) ? $params['extra_langue_cible'] : $defaultLangCible;
    $lang = strtolower($lang);

    if (isset($params['search_using_1'])) {
        if ($userStartDate && !empty($userStartDate)) {
            $filterToSend['custom_dates'] = $sql;
        }
    }

    $filterToSend = json_encode($filterToSend);
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&_search=true&load_extra_field='.
        $extraFieldListToString.'&_force_search=true&rows=20&page=1&sidx=&sord=asc&filters2='.$filterToSend;
    if (isset($params['search_using_2'])) {
        $url .= '&lang='.$lang;
    }
} else {
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&_search=true&load_extra_field='.
        $extraFieldListToString.'&_force_search=true&rows=20&page=1&sidx=&sord=asc';
}

// Autowidth
$extra_params['autowidth'] = 'true';

// height auto
$extra_params['height'] = 'auto';
$extraParams['postData'] = [
    'filters' => [
        'groupOp' => 'AND',
        'rules' => $result['rules'],
    ],
];

$sessionByUserList = SessionManager::get_sessions_by_user($userToLoad, true, true);

$sessionUserList = [];
if (!empty($sessionByUserList)) {
    foreach ($sessionByUserList as $sessionByUser) {
        $sessionUserList[] = (string) $sessionByUser['session_id'];
    }
}

$actionLinks = 'function action_formatter(cellvalue, options, rowObject) {
    var sessionList = '.json_encode($sessionUserList).';
    var id = options.rowId.toString();
    if (sessionList.indexOf(id) == -1) {
        return \'<a href="'.api_get_self(
    ).'?action=subscribe_user&user_id='.$userToLoad.'&session_id=\'+id+\'">'.Display::return_icon(
        'add.png',
        addslashes(get_lang('Subscribe')),
        '',
        ICON_SIZE_SMALL
    ).'</a>'.'\';
    } else {
        return \'<a href="'.api_get_self(
    ).'?action=unsubscribe_user&user_id='.$userToLoad.'&session_id=\'+id+\'">'.Display::return_icon(
        'delete.png',
        addslashes(get_lang('Delete')),
        '',
        ICON_SIZE_SMALL
    ).'</a>'.'\';
    }
}';

$htmlHeadXtra[] = api_get_jqgrid_js();

$griJs = Display::grid_js(
    'sessions',
    $url,
    $columns,
    $columnModel,
    $extraParams,
    [],
    $actionLinks,
    true
);

$grid = '<div id="session-table" class="table-responsive">';
$grid .= Display::grid_html('sessions');
$grid .= '</div>';

$htmlHeadXtra[] = '<style>
	.control-label {
	    width: 25% !important;
	}
</style>';

$htmlHeadXtra[] = '<script>
$(function() {
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
        //$(value).collapse("hide");
    });
});
</script>';

$tpl = new Template(get_lang('Diagnosis'));

if (empty($items)) {
    $view = '';
    $grid = '';
    $griJs = '';
}
$tpl->assign('form', $view);
$tpl->assign('form_search', $formSearch->returnForm().$userForm->returnForm());

$table = new HTML_Table(['class' => 'data_table']);
$column = 0;
$row = 0;

$total = 0;
$sumHours = 0;
$numHours = 0;

$field = 'heures_disponibilite_par_semaine';
$extraField = new ExtraFieldValue('user');
$data = $extraField->get_values_by_handler_and_field_variable($userToLoad, $field);

$availableHoursPerWeek = 0;

function dateDiffInWeeks($date1, $date2)
{
    if (empty($date1) || empty($date2)) {
        return 0;
    }
    // it validates a correct date format Y-m-d
    if (false === DateTime::createFromFormat('Y-m-d', $date1) || false === DateTime::createFromFormat('Y-m-d', $date2)) {
        return 0;
    }

    if ($date1 > $date2) {
        return dateDiffInWeeks($date2, $date1);
    }
    $first = new \DateTime($date1);
    $second = new \DateTime($date2);

    return floor($first->diff($second)->days / 7);
}

if ($data) {
    $availableHoursPerWeek = (int) $data['value'];
    $numberWeeks = 0;
    if ($form->validate()) {
        $formData = $form->getSubmitValues();

        if (isset($formData['extra_access_start_date']) && isset($formData['extra_access_end_date'])) {
            $startDate = $formData['extra_access_start_date'];
            $endDate = $formData['extra_access_end_date'];
            $numberWeeks = dateDiffInWeeks($startDate, $endDate);
        }
    } else {
        if ($defaults) {
            if (isset($defaults['extra_access_start_date']) && isset($defaults['extra_access_end_date'])) {
                $startDate = $defaults['extra_access_start_date'];
                $endDate = $defaults['extra_access_end_date'];
                $numberWeeks = dateDiffInWeeks($startDate, $endDate);
            }
        }
    }

    $total = $numberWeeks * $availableHoursPerWeek;
    $sessions = SessionManager::getSessionsFollowedByUser($userToLoad);

    if ($sessions) {
        $sessionFieldValue = new ExtraFieldValue('session');

        foreach ($sessions as $session) {
            $sessionId = $session['id'];
            $dataTravails = $sessionFieldValue->get_values_by_handler_and_field_variable(
                $sessionId,
                'temps_de_travail'
            );
            if ($dataTravails) {
                $sumHours += (int) $dataTravails['value'];
            }
        }
    }
}

$numHours = $total - $sumHours;
$headers = [
    get_lang('Total Available Hours') => $total,
    get_lang('Sum Hours Sessions Subscribed') => $sumHours,
    get_lang('Count Hours Available') => $numHours,
];
foreach ($headers as $header => $value) {
    $table->setCellContents($row, 0, $header);
    $table->updateCellAttributes($row, 0, 'width="250px"');
    $table->setCellContents($row, 1, $value);
    $row++;
}

$button = '';
$userReportButton = '';
if ($userToLoad) {
    $button = Display::url(
        get_lang('Ofaj End Of LearnPath'),
        api_get_path(WEB_PATH).'resources/messages/new',
        ['class' => 'btn btn-default']
    );
    $button .= '<br /><br />';
    $userReportButton = Display::url(
        get_lang('Diagnostic Validate LearningPath'),
        api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$userToLoad,
        ['class' => 'btn btn-primary']
    );
}

$tpl->assign('grid', $grid.$button.$table->toHtml().$userReportButton);
$tpl->assign('grid_js', $griJs);
$templateName = $tpl->get_template('search/search_extra_field.html.twig');
$contentTemplate = $tpl->fetch($templateName);
$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();
