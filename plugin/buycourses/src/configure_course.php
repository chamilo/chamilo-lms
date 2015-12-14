<?php
/* For license terms, see /license.txt */
/**
 * Configuration script for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */
$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

if (!isset($_REQUEST['t'], $_REQUEST['i'])) {
    die;
}

$plugin = BuyCoursesPlugin::create();

$comissionsEnable = $plugin->get('comissions_enable');

if ($comissionsEnable == "true") {
    
    $htmlHeadXtra[] = '<script type="text/javascript" src="' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/resources/js/comissions.js"></script>';
    $defaultComissions = [];
    $comissions = "";
    
}

$includeSession = $plugin->get('include_sessions') === 'true';

$editingCourse = intval($_REQUEST['t']) === BuyCoursesPlugin::PRODUCT_TYPE_COURSE;
$editingSession = intval($_REQUEST['t']) === BuyCoursesPlugin::PRODUCT_TYPE_SESSION;

$entityManager = Database::getManager();
$userRepo = $entityManager->getRepository('ChamiloUserBundle:User');

$currency = $plugin->getSelectedCurrency();
$currencyIso = null;

if ($editingCourse) {
    $course = $entityManager->find('ChamiloCoreBundle:Course', $_REQUEST['i']);

    if (!$course) {
        api_not_allowed(true);
    }

    if (!$plugin->isValidCourse($course)) {
        api_not_allowed(true);
    }

    $courseItem = $plugin->getCourseForConfiguration($course, $currency);
    $defaultBeneficiaries = [];
    $teachers = $course->getTeachers();
    $teachersOptions = [];

    foreach ($teachers as $courseTeacher) {
        $teacher = $courseTeacher->getUser();

        $teachersOptions[] = [
            'text' => $teacher->getCompleteName(),
            'value' => $teacher->getId()
        ];

        $defaultBeneficiaries[] = $teacher->getId();
    }

    $currentBeneficiaries = $plugin->getItemBeneficiaries($courseItem['item_id']);

    if (!empty($currentBeneficiaries)) {
        $defaultBeneficiaries = array_column($currentBeneficiaries, 'user_id');
        
        if ($comissionsEnable == "true") {
            $defaultComissions = array_column($currentBeneficiaries, 'comissions');
        
            foreach ($defaultComissions as $defaultComission) {
                $comissions .= $defaultComission.',';
            }

            $comissions = substr($comissions, 0, -1);
        }
    }

    $currencyIso = $courseItem['currency'];
    $formDefaults = [
        'product_type' => get_lang('Course'),
        'i' => $courseItem['course_id'],
        't' => BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
        'name' => $courseItem['course_title'],
        'visible' => $courseItem['visible'],
        'price' => $courseItem['price'],
        'beneficiaries' => $defaultBeneficiaries,
        ($comissionsEnable == "true") ? 'comissions' : '' => ($comissionsEnable == "true") ? $comissions : ''
    ];
} elseif ($editingSession) {
    if (!$includeSession) {
        api_not_allowed(true);
    }

    $session = $entityManager->find('ChamiloCoreBundle:Session', $_REQUEST['i']);

    if (!$session) {
        api_not_allowed(true);
    }

    $sessionItem = $plugin->getSessionForConfiguration($session, $currency);
    $generalCoach = $session->getGeneralCoach();
    $generalCoachOption = [
        'text' => $generalCoach->getCompleteName(),
        'value' => $generalCoach->getId()
    ];
    $defaultBeneficiaries = [
        $generalCoach->getId()
    ];
    $courseCoachesOptions = [];
    $sessionCourses = $session->getCourses();

    foreach ($sessionCourses as $sessionCourse) {
        $courseCoaches = $userRepo->getCoachesForSessionCourse($session, $sessionCourse->getCourse());

        foreach ($courseCoaches as $courseCoach) {
            if ($generalCoach->getId() === $courseCoach->getId()) {
                continue;
            }

            $courseCoachesOptions[] = [
                'text' => $courseCoach->getCompleteName(),
                'value' => $courseCoach->getId()
            ];
            $defaultBeneficiaries[] = $courseCoach->getId();
        }
    }

    $currentBeneficiaries = $plugin->getItemBeneficiaries($sessionItem['item_id']);

    if (!empty($currentBeneficiaries)) {
        $defaultBeneficiaries = array_column($currentBeneficiaries, 'user_id');
        
        if ($comissionsEnable == "true") {
            $defaultComissions = array_column($currentBeneficiaries, 'comissions');
        
            foreach ($defaultComissions as $defaultComission) {
                $comissions .= $defaultComission.',';
            }

            $comissions = substr($comissions, 0, -1);
        }
    }

    $currencyIso = $sessionItem['currency'];
    $formDefaults = [
        'product_type' => get_lang('Session'),
        'i' => $session->getId(),
        't' => BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
        'name' => $sessionItem['session_name'],
        'visible' => $sessionItem['visible'],
        'price' => $sessionItem['price'],
        'beneficiaries' => $defaultBeneficiaries,
        ($comissionsEnable == "true") ? 'comissions' : '' => ($comissionsEnable == "true") ? $comissions : ''
    ];
} else {
    api_not_allowed(true);
}

if ($comissionsEnable == "true") {

    $htmlHeadXtra[] = ''
    . '<script>'
        . '$(function(){'
            . 'if ($("[name=\'comissions\']").val() === "") {'
                . '$("#panelSliders").html("<button id=\"setComissionsButton\" class=\"btn btn-warning\">' . get_plugin_lang("SetComissions", "BuyCoursesPlugin") . '</button>");'
            . '} else {'
                . 'showSliders(100, "default", "' . $comissions . '");'
            . '}'
        . '});'

        . '$(document).ready(function() {'
            . 'var maxPercentage = 100;'
            . '$("#selectBox").on("change", function() {'
                . ' $("#panelSliders").html("");'
                . 'showSliders(maxPercentage, "renew");'
            . '});'

            . '$("#setComissionsButton").on("click", function() {'
                . ' $("#panelSliders").html("");'
                . 'showSliders(maxPercentage, "renew");'
            . '});'
        . '});'
    . '</script>';

}

$form = new FormValidator('beneficiaries');
$form->addText('product_type', $plugin->get_lang('ProductType'), false);
$form->addText('name', get_lang('Name'), false);
$visibleCheckbox = $form->addCheckBox(
    'visible',
    $plugin->get_lang('VisibleInCatalog'),
    $plugin->get_lang('ShowOnCourseCatalog')
);
$form->addElement(
    'number',
    'price',
    [$plugin->get_lang('Price'), null, $currencyIso],
    ['step' => 0.01]
);
$beneficiariesSelect = $form->addSelect(
    'beneficiaries',
    $plugin->get_lang('Beneficiaries'),
    null,
    ['multiple' => 'multiple', 'id' => 'selectBox']
);

if ($editingCourse) {
    $teachersOptions = api_unique_multidim_array($teachersOptions, 'value');
    $beneficiariesSelect->addOptGroup($teachersOptions, get_lang('Teachers'));
} elseif ($editingSession) {
    $courseCoachesOptions = api_unique_multidim_array($courseCoachesOptions, 'value');
    $beneficiariesSelect->addOptGroup([$generalCoachOption], get_lang('SessionGeneralCoach'));
    $beneficiariesSelect->addOptGroup($courseCoachesOptions, get_lang('SessionCourseCoach'));
}

if ($comissionsEnable == "true") {

    $form->addHtml( ''
            . '<div class="form-group">'
                . '<label for="sliders" class="col-sm-2 control-label">'
                    . get_plugin_lang('Comissions', 'BuyCoursesPlugin')
                . '</label>'
                . '<div class="col-sm-8">'
                    . '<div class="" id="panelSliders"></div>'
                . '</div>'
            . '</div>'
    );
    
    $form->addHidden('comissions', '');
    
}

$form->addHidden('t', null);
$form->addHidden('i', null);
$form->addButtonSave(get_lang('Save'));
$form->freeze(['product_type', 'name']);

if ($form->validate()) {
    $formValues = $form->exportValues();
    $productItem = $plugin->getItemByProduct($formValues['i'], $formValues['t']);

    if (isset($formValues['visible'])) {
        if (!empty($productItem)) {
            $plugin->updateItem(
                ['price' => floatval($formValues['price'])],
                $formValues['i'],
                $formValues['t']
            );
        } else {
            $itemId = $plugin->registerItem([
                'currency_id' => $currency['id'],
                'product_type' => $formValues['t'],
                'product_id' => intval($formValues['i']),
                'price' => floatval($_POST['price'])
            ]);
            $productItem['id'] = $itemId;
        }

        $plugin->deleteItemBeneficiaries($productItem['id']);

        if (isset($formValues['beneficiaries'])) {
            if ($comissionsEnable == "true") {
                $usersId = $formValues['beneficiaries'];
                $comissions = explode(",", $formValues['comissions']);
                $comissions = (count($usersId) != count($comissions)) ? array_fill(0, count($usersId), 0) : $comissions;
                $beneficiaries = array_combine($usersId, $comissions);
            } else {
                $usersId = $formValues['beneficiaries'];
                $comissions = array_fill(0, count($usersId), 0);
                $beneficiaries = array_combine($usersId, $comissions);
            }
            
            $plugin->registerItemBeneficiaries($productItem['id'], $beneficiaries);
        }
    } else {
        $plugin->deleteItem($productItem['id']);
    }

    header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/configuration.php');
    exit;
}

$form->setDefaults($formDefaults);

//View
$templateName = $plugin->get_lang('AvailableCourse');

$interbreadcrumb[] = [
    'url' => 'paymentsetup.php',
    'name' => get_lang('Configuration')
];
$interbreadcrumb[] = [
    'url' => 'configuration.php',
    'name' => $plugin->get_lang('AvailableCourses')
];

$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('content', $form->returnForm());
$template->display_one_col_template();
