<?php
/* For license terms, see /license.txt */

/**
 * Configuration page for subscriptions for the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_admin_script(true);

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$type = isset($_REQUEST['type']) ? (int) $_REQUEST['type'] : 0;

if (empty($id) || empty($type)) {
    api_not_allowed();
}

$queryString = 'id='.intval($_REQUEST['id']).'&type='.intval($_REQUEST['type']);

$editingCourse = $type === BuyCoursesPlugin::PRODUCT_TYPE_COURSE;
$editingSession = $type === BuyCoursesPlugin::PRODUCT_TYPE_SESSION;

$plugin = BuyCoursesPlugin::create();

$includeSession = $plugin->get('include_sessions') === 'true';

$entityManager = Database::getManager();
$userRepo = UserManager::getRepository();
$currency = $plugin->getSelectedCurrency();

if (empty($currency)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('CurrencyIsNotConfigured'), 'error')
    );
}

$currencyIso = null;

if ($editingCourse) {
    $course = $entityManager->find('ChamiloCoreBundle:Course', $id);
    if (!$course) {
        api_not_allowed(true);
    }

    $courseItem = $plugin->getCourseForConfiguration($course, $currency);

    $currencyIso = $courseItem['currency'];
    $formDefaults = [
        'product_type' => get_lang('Course'),
        'id' => $courseItem['course_id'],
        'type' => BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
        'name' => $courseItem['course_title'],
        'visible' => $courseItem['visible'],
        'price' => $courseItem['price'],
        'tax_perc' => $courseItem['tax_perc'],
        'currency_id' => $currency['id'],
    ];
} elseif ($editingSession) {
    if (!$includeSession) {
        api_not_allowed(true);
    }

    $session = $entityManager->find('ChamiloCoreBundle:Session', $id);
    if (!$session) {
        api_not_allowed(true);
    }

    $sessionItem = $plugin->getSessionForConfiguration($session, $currency);

    $currencyIso = $sessionItem['currency'];
    $formDefaults = [
        'product_type' => get_lang('Session'),
        'id' => $session->getId(),
        'type' => BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
        'name' => $sessionItem['session_name'],
        'visible' => $sessionItem['visible'],
        'price' => $sessionItem['price'],
        'tax_perc' => $sessionItem['tax_perc'],
        'currency_id' => $currency['id'],
    ];
} else {
    api_not_allowed(true);
}

$globalSettingsParams = $plugin->getGlobalParameters();

$form = new FormValidator('add_subscription');

$form->addText('product_type', $plugin->get_lang('ProductType'), false);
$form->addText('name', get_lang('Name'), false);

$form->freeze(['product_type', 'name']);

$form->addElement(
    'number',
    'tax_perc',
    [$plugin->get_lang('TaxPerc'), $plugin->get_lang('TaxPercDescription'), '%'],
    ['step' => 1, 'placeholder' => $globalSettingsParams['global_tax_perc'].'% '.$plugin->get_lang('ByDefault')]
);

$frequencies = $plugin->getFrequencies();

$selectOptions = '';
foreach ($frequencies as $key => $frequency) {
    $selectOptions .= '<option value="'.$key.'">'.$frequency.'</option>';
}

if (empty($frequencies)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('FrequencyIsNotConfigured'), 'error')
    );
}

$platformCommission = $plugin->getPlatformCommission();
$form->addHtml(
    '
    <div class="form-group">
        <div class="col-sm-11">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">'.$plugin->get_lang('FrequencyConfig').'</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="col-sm-5">
                            <div class="form-group ">
                                <label for="duration" class="col-sm-3 control-label">
                                '.$plugin->get_lang('Duration').'
                                </label>
                                <div class="col-sm-8">
                                    <div class="dropdown bootstrap-select form-control bs3 dropup">
                                        <select class="selectpicker form-control"
                                            data-live-search="true" name="duration" id="duration" tabindex="null">
                                            '.$selectOptions.'
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-1"></div>
                            </div>
                            <div class="form-group ">
                                <label for="price" class="col-sm-3 control-label">
                                    '.$plugin->get_lang('Price').'
                                </label>
                                <div class="col-sm-8">
                                    <input class="form-control" name="price" type="number" step="0.01" id="price">
                                </div>
                                <div class="col-sm-1">
                                    '.$currencyIso.'
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <a class=" btn btn-primary " name="add" type="submit"><em class="fa fa-plus"></em> Add</a>
                                </div>
                                <div class="col-sm-2"></div>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th>'.$plugin->get_lang('Duration').'</th>
                                        <th>'.$plugin->get_lang('Price').'</th>
                                        <th>'.$plugin->get_lang('Actions').'</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-2">
        </div>
    </div>
    '
);

$form->addHidden('type', null);
$form->addHidden('id', null);
$button = $form->addButtonSave(get_lang('Save'));

if (empty($currency) || empty($frequencies)) {
    $button->setAttribute('disabled');
}

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    $subscription['product_id'] = $formValues['id'];
    $subscription['product_type'] = $formValues['type'];
    $subscription['currency_id'] = $currency['id'];
    $subscription['tax_perc'] = $formValues['tax_perc'] != '' ? (int) $formValues['tax_perc'] : null;
    $subscription['frequencies'] = isset($formValues['frequencies']) ? $formValues['frequencies'] : [];

    $result = $plugin->addNewSubscription($subscription);

    if ($result) {
        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/subscriptions_courses.php');
    } else {
        header('Location:'.api_get_self().'?'.$queryString);
    }

    exit;
}

$form->setDefaults($formDefaults);

$templateName = $plugin->get_lang('SubscriptionAdd');
$interbreadcrumb[] = [
    'url' => 'subscriptions_courses.php',
    'name' => get_lang('Configuration'),
];
$interbreadcrumb[] = [
    'url' => 'subscriptions_courses.php',
    'name' => $plugin->get_lang('SubscriptionList'),
];

$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('items_form', $form->returnForm());
$template->assign('currencyIso', $currencyIso);

$content = $template->fetch('buycourses/view/subscription_add.tpl');
$template->assign('content', $content);

$template->display_one_col_template();
