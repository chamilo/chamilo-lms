<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

/**
 * Edit services for the Buy Courses plugin.
 */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once '../../../main/inc/global.inc.php';

api_protect_admin_script(true);

$serviceId = isset($_GET['id'])
    ? (int) $_GET['id']
    : (isset($_POST['id']) ? (int) $_POST['id'] : 0);

if ($serviceId <= 0) {
    header('Location: list_service.php');
    exit;
}

$plugin = BuyCoursesPlugin::create();
$currency = $plugin->getSelectedCurrency();
$globalSettingsParams = $plugin->getGlobalParameters();
$defaultGlobalTax = (int) ($globalSettingsParams['global_tax_perc'] ?? 0);
$upsaleOptions = $plugin->getServiceUpsaleOptions($serviceId);

/** @var array<int, User> $users */
$users = Container::getUserRepository()->findByRoleList(
    ['ROLE_TEACHER', 'ROLE_ADMIN', 'ROLE_SESSION_MANAGER', 'ROLE_GLOBAL_ADMIN'],
    ''
);
$userOptions = [];

if (!empty($users)) {
    foreach ($users as $user) {
        $userOptions[$user->getId()] = $user->getFullNameWithUsername();
    }
}

$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');

// View
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php',
    'name' => $plugin->get_lang('plugin_title'),
];
$interbreadcrumb[] = [
    'url' => 'list_service.php',
    'name' => $plugin->get_lang('Services'),
];

$service = $plugin->getService($serviceId);
if (empty($service)) {
    header('Location: list_service.php');
    exit;
}

$currentOwnerId = (int) ($service['owner_id'] ?? 0);
if ($currentOwnerId > 0 && !isset($userOptions[$currentOwnerId])) {
    $currentOwner = Container::getUserRepository()->find($currentOwnerId);
    if (null !== $currentOwner) {
        $userOptions[$currentOwnerId] = $currentOwner->getFullNameWithUsername();
    }
}

$currentAppliesTo = isset($service['applies_to'])
    ? (int) $service['applies_to']
    : BuyCoursesPlugin::SERVICE_TYPE_NONE;

$appliesToLabels = [
    BuyCoursesPlugin::SERVICE_TYPE_NONE => get_lang('None'),
    BuyCoursesPlugin::SERVICE_TYPE_USER => get_lang('User'),
    BuyCoursesPlugin::SERVICE_TYPE_COURSE => get_lang('Course'),
    BuyCoursesPlugin::SERVICE_TYPE_SESSION => get_lang('Session'),
    BuyCoursesPlugin::SERVICE_TYPE_TEMPLATE_CERTIFICATE => get_lang('TemplateTitleCertificate'),
];

$currentAppliesToLabel = $appliesToLabels[$currentAppliesTo] ?? get_lang('None');

$customImageUrl = $plugin->getServiceImageUrl('simg-'.$serviceId.'.png');

$translatableHtmlEditorConfig = buycoursesBuildTranslatableHtmlEditorConfig();

$buildCheckboxContent = static function (string $title, string $description = ''): string {
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

    $content = '<span class="buycourses-checkbox-copy">'.
        '<span class="buycourses-checkbox-copy__title">'.$title.'</span>';

    if ('' !== $description) {
        $content .= '<span class="buycourses-checkbox-copy__description">'.$description.'</span>';
    }

    return $content.'</span>';
};

$formDefaultValues = array_merge(
    $service,
    $plugin->buildBenefitFormDefaults($serviceId)
);

if (!isset($formDefaultValues['visibility'])) {
    $formDefaultValues['visibility'] = !empty($service['visibility']);
}

$formDefaultValues['renewable'] = !empty($service['renewable']);
$formDefaultValues['total_charges'] = isset($service['total_charges']) ? (int) $service['total_charges'] : 0;
$formDefaultValues['allow_trial'] = !empty($service['allow_trial']);
$formDefaultValues['trial_period'] = !empty($service['trial_period']) ? (string) $service['trial_period'] : 'Day';
$formDefaultValues['trial_frequency'] = isset($service['trial_frequency']) ? (int) $service['trial_frequency'] : 0;
$formDefaultValues['trial_total_charges'] = isset($service['trial_total_charges']) ? (int) $service['trial_total_charges'] : 0;
$formDefaultValues['max_subscribers'] = isset($service['max_subscribers']) ? (int) $service['max_subscribers'] : 0;
$formDefaultValues['subscription_behavior_json'] = (string) ($service['subscription_behavior_json'] ?? '');
$formDefaultValues['stripe_price_id'] = (string) ($service['stripe_price_id'] ?? '');
$formDefaultValues['display_on_course_creation_page'] = !empty($service['display_on_course_creation_page']);
$formDefaultValues['upsale_from_id'] = (int) ($service['upsale_from_id'] ?? 0);

$form = new FormValidator(
    'Services',
    'post',
    api_get_self().'?id='.$serviceId
);

$form->addText('name', $plugin->get_lang('ServiceName'));
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');
$form->addHtmlEditor('description', $plugin->get_lang('Description'), true, false, $translatableHtmlEditorConfig);
$form->addElement(
    'number',
    'price',
    [$plugin->get_lang('Price'), null, $currency['iso_code']],
    ['step' => 0.01]
);
$form->addElement(
    'number',
    'tax_perc',
    [$plugin->get_lang('TaxPerc'), $plugin->get_lang('TaxPercDescription'), '%'],
    ['step' => 1, 'placeholder' => $defaultGlobalTax.'% '.$plugin->get_lang('ByDefault')]
);
$form->addElement(
    'number',
    'duration_days',
    [$plugin->get_lang('Duration'), null, get_lang('Days')],
    ['step' => 1]
);
$form->addSelect(
    'upsale_from_id',
    [$plugin->get_lang('Upsale'), $plugin->get_lang('UpsaleHelp')],
    $upsaleOptions
);

$form->addHtml(
    '<div class="buycourses-fallback-section-heading">'.$plugin->get_lang('RecurringPayments').'</div>'
);
$form->addCheckBox('renewable', $plugin->get_lang('RenewableService'));
$form->addElement(
    'number',
    'total_charges',
    [$plugin->get_lang('TotalCharges'), $plugin->get_lang('TotalChargesHelp')],
    ['step' => 1, 'min' => 0]
);
$form->addCheckBox('allow_trial', $plugin->get_lang('AllowTrial'));
$form->addSelect(
    'trial_period',
    $plugin->get_lang('TrialPeriod'),
    [
        'Day' => $plugin->get_lang('PeriodDay'),
        'Week' => $plugin->get_lang('PeriodWeek'),
        'Month' => $plugin->get_lang('PeriodMonth'),
        'Year' => $plugin->get_lang('PeriodYear'),
    ]
);
$form->addElement(
    'number',
    'trial_frequency',
    [$plugin->get_lang('TrialFrequency'), $plugin->get_lang('TrialFrequencyHelp')],
    ['step' => 1, 'min' => 0]
);
$form->addElement(
    'number',
    'trial_total_charges',
    [$plugin->get_lang('TrialTotalCharges'), $plugin->get_lang('TrialTotalChargesHelp')],
    ['step' => 1, 'min' => 0]
);
$form->addElement(
    'number',
    'max_subscribers',
    [$plugin->get_lang('MaxSubscribers'), $plugin->get_lang('MaxSubscribersHelp')],
    ['step' => 1, 'min' => 0]
);
$form->addTextarea(
    'subscription_behavior_json',
    [$plugin->get_lang('SubscriptionBehaviorJson'), $plugin->get_lang('SubscriptionBehaviorJsonHelp')],
    ['rows' => 6]
);
$form->addText(
    'stripe_price_id',
    [$plugin->get_lang('StripePriceId'), $plugin->get_lang('StripePriceIdHelp')],
    false
);
$form->addCheckBox(
    'display_on_course_creation_page',
    '',
    $buildCheckboxContent(
        $plugin->get_lang('DisplayServiceOnCourseCreationPage'),
        $plugin->get_lang('DisplayServiceOnCourseCreationPageHelp')
    )
);

$form->addHidden('applies_to', (string) $currentAppliesTo);
$form->addHtml(
    '<div class="buycourses-applies-to-card rounded-2xl border border-gray-20 bg-support-2 p-4">'.
    '<div class="text-body-2 font-semibold text-primary">'.$plugin->get_lang('AppliesTo').'</div>'.
    '<div class="mt-2 text-body-2 font-medium text-gray-90">'.$currentAppliesToLabel.'</div>'.
    '<div class="mt-1 text-caption text-gray-50">'.$plugin->get_lang('ServiceAppliesToReadOnlyHelp').'</div>'.
    '</div>'
);

$form->addSelect(
    'owner_id',
    get_lang('Owner'),
    $userOptions
);
$form->addCheckBox('visibility', $plugin->get_lang('VisibleInCatalog'));
$form->addFile(
    'picture',
    $customImageUrl ? get_lang('UpdateImage') : get_lang('AddImage'),
    ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '16 / 9']
);
$form->addText('video_url', get_lang('VideoUrl'), false);
$form->addHtmlEditor('service_information', $plugin->get_lang('ServiceInformation'), false, false, $translatableHtmlEditorConfig);

$form->addHtml(
    '<div class="buycourses-fallback-section-heading">'.$plugin->get_lang('GrantedBenefits').'</div>'
);

$form->addElement(
    'number',
    'benefit_max_courses',
    [
        $plugin->get_lang('BenefitMaxCoursesTitle'),
        $plugin->get_lang('BenefitMaxCoursesDescription'),
        $plugin->get_lang('BenefitCoursesUnit'),
    ],
    ['step' => 1, 'min' => 0]
);

$form->addElement(
    'number',
    'benefit_hosting_limit',
    [
        $plugin->get_lang('BenefitHostingLimitTitle'),
        $plugin->get_lang('BenefitHostingLimitDescription'),
        $plugin->get_lang('BenefitUsersUnit'),
    ],
    ['step' => 1, 'min' => 0]
);

$form->addElement(
    'number',
    'benefit_document_quota',
    [
        $plugin->get_lang('BenefitDocumentQuotaTitle'),
        $plugin->get_lang('BenefitDocumentQuotaDescription'),
        $plugin->get_lang('BenefitMegabytesUnit'),
    ],
    ['step' => 1, 'min' => 0]
);

$form->addHtml(
    '<div class="buycourses-ai-intro mt-4 rounded-2xl border border-gray-20 bg-support-2 p-4">'.
    '<div class="text-body-2 font-semibold text-primary">'.$plugin->get_lang('AiCourseFeaturesGranted').'</div>'.
    '<div class="mt-1 text-caption text-gray-50">'.$plugin->get_lang('AiCourseFeaturesGrantedHelp').'</div>'.
    '</div>'
);

foreach ($plugin->getAiCourseFeatureDefinitions() as $feature => $definition) {
    $description = (string) ($definition['description'] ?? '');
    if (!empty($definition['expensive'])) {
        $description .= ' '.$plugin->get_lang('AiCourseFeatureVideoWarning');
    }

    $form->addCheckBox(
        $plugin->getAiCourseFeatureFormField((string) $feature),
        '',
        $buildCheckboxContent(
            (string) ($definition['title'] ?? $feature),
            $description
        )
    );
}

$form->addHidden('id', (string) $serviceId);
$form->addButtonSave(get_lang('Edit'));
$form->addButtonDelete($plugin->get_lang('DeleteThisService'), 'delete_service');
$form->setDefaults($formDefaultValues);

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $values = buycoursesBuildPostedServicePayload($serviceId, $plugin);

    if (isset($_POST['delete_service'])) {
        $plugin->deleteService($serviceId);

        Display::addFlash(
            Display::return_message($plugin->get_lang('ServiceDeleted'), 'error')
        );

        header('Location: list_service.php');
        exit;
    }

    $errors = buycoursesValidateServicePayload($values, $plugin);

    if (empty($errors)) {
        $updated = $plugin->updateService($values, $serviceId);

        if (false === $updated) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('ServiceUpdateFailed'), 'error')
            );
        } else {
            Display::addFlash(
                Display::return_message($plugin->get_lang('ServiceEdited'), 'success')
            );

            header('Location: list_service.php');
            exit;
        }
    }

    foreach ($errors as $errorMessage) {
        Display::addFlash(Display::return_message($errorMessage, 'warning'));
    }

    $form->setDefaults(array_replace($formDefaultValues, $values));
}

$templateName = $plugin->get_lang('EditService');
$tpl = new Template($templateName);

$pageContent = buycoursesBuildServiceFormShell(
    $templateName,
    $form->returnForm(),
    'list_service.php',
    $plugin->get_lang('plugin_title'),
    $plugin->get_lang('ServiceEditSubtitle'),
    (string) ($currency['iso_code'] ?? ''),
    $defaultGlobalTax,
    $plugin,
    $customImageUrl
);

$tpl->assign('header', $templateName);
$tpl->assign('content', $pageContent);
$tpl->display_one_col_template();


function buycoursesBuildTranslatableHtmlEditorConfig(): array
{
    $config = [
        'ToolbarSet' => 'TestQuestionDescription',
    ];

    if ('true' === api_get_setting('editor.translate_html')) {
        $config['extraPlugins'] = 'translatehtml';
        $config['extended_valid_elements'] = 'span[lang|class|style],div[lang|class|style]';
        $config['toolbar'] = 'undo redo | translatehtml | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | fontfamily fontsize | forecolor backcolor removeformat | link image media table | emoticons preview print code fullscreen | ltr rtl';
    }

    return $config;
}

/**
 * Build the service form page shell.
 */
function buycoursesBuildServiceFormShell(
    string $pageTitle,
    string $formHtml,
    string $backUrl,
    string $pluginTitle,
    string $subtitle,
    string $currencyCode,
    int $defaultGlobalTax,
    BuyCoursesPlugin $plugin,
    ?string $previewImageUrl = null
): string {
    $safePageTitle = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
    $safePluginTitle = htmlspecialchars($pluginTitle, ENT_QUOTES, 'UTF-8');
    $safeSubtitle = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
    $safeFormSubtitle = htmlspecialchars($plugin->get_lang('ServiceFormSubtitle'), ENT_QUOTES, 'UTF-8');
    $safeBackUrl = htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8');
    $safeCurrencyCode = htmlspecialchars($currencyCode, ENT_QUOTES, 'UTF-8');

    $previewHtml = '';
    if (!empty($previewImageUrl)) {
        $safePreviewImageUrl = htmlspecialchars($previewImageUrl, ENT_QUOTES, 'UTF-8');
        $previewHtml = <<<HTML
            <div class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="border-b border-gray-20 px-5 py-4">
                    <h3 class="text-body-1 font-semibold text-gray-90">{$plugin->get_lang('CurrentImageLabel')}</h3>
                </div>
                <div class="bg-support-2 p-4">
                    <img
                        src="{$safePreviewImageUrl}"
                        alt="{$plugin->get_lang('CurrentImageLabel')}"
                        class="h-auto w-full rounded-2xl border border-gray-20 object-cover shadow-sm"
                    >
                </div>
            </div>
        HTML;
    }

    $enhancerScript = buycoursesBuildServiceFormEnhancerScript($plugin);

    return <<<HTML
        <div class="buycourses-service-shell mx-auto w-full space-y-6 px-4 pb-10">
            <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm lg:p-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-3">
                        <span class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-caption font-semibold uppercase tracking-wide text-primary">
                            {$safePluginTitle}
                        </span>
                        <div>
                            <h1 class="text-3xl font-semibold tracking-tight text-gray-90">
                                {$safePageTitle}
                            </h1>
                            <p class="mt-2 text-body-2 text-gray-50">
                                {$safeSubtitle}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a
                            href="{$safeBackUrl}"
                            class="inline-flex items-center justify-center rounded-2xl border border-gray-25 bg-white px-5 py-3 text-body-2 font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:bg-support-1 hover:text-primary"
                        >
                            {$plugin->get_lang('BackToServices')}
                        </a>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
                <aside class="space-y-6">
                    <div class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                        <div class="border-b border-gray-20 px-5 py-4">
                            <h2 class="text-body-1 font-semibold text-gray-90">{$plugin->get_lang('QuickSummary')}</h2>
                        </div>
                        <dl class="space-y-3 bg-white p-5">
                            <div class="rounded-2xl border border-gray-20 bg-support-2 px-4 py-3">
                                <dt class="text-tiny font-semibold uppercase tracking-wide text-primary">{$plugin->get_lang('ServiceCurrencyLabel')}</dt>
                                <dd class="mt-1 text-body-2 font-semibold text-gray-90">{$safeCurrencyCode}</dd>
                            </div>
                            <div class="rounded-2xl border border-gray-20 bg-support-2 px-4 py-3">
                                <dt class="text-tiny font-semibold uppercase tracking-wide text-primary">{$plugin->get_lang('DefaultTaxLabel')}</dt>
                                <dd class="mt-1 text-body-2 font-semibold text-gray-90">{$defaultGlobalTax}%</dd>
                            </div>
                            <div class="rounded-2xl border border-gray-20 bg-support-2 px-4 py-3">
                                <dt class="text-tiny font-semibold uppercase tracking-wide text-primary">{$plugin->get_lang('ImageCropLabel')}</dt>
                                <dd class="mt-1 text-body-2 font-semibold text-gray-90">16:9</dd>
                            </div>
                        </dl>
                    </div>

                    {$previewHtml}
                </aside>

                <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                    <div class="border-b border-gray-20 px-6 py-5 lg:px-8">
                        <h2 class="text-body-1 font-semibold text-gray-90">{$plugin->get_lang('ServiceFormTitle')}</h2>
                        <p class="mt-1 text-body-2 text-gray-50">
                            {$safeFormSubtitle}
                        </p>
                    </div>
                    <div class="px-6 py-6 lg:px-8">
                        {$formHtml}
                    </div>
                </section>
            </div>
        </div>
        {$enhancerScript}
    HTML;
}

/**
 * Build the form enhancer script.
 */
function buycoursesBuildServiceFormEnhancerScript(BuyCoursesPlugin $plugin): string
{
    $labels = [
        'generalTitle' => $plugin->get_lang('ServiceGeneralSectionTitle'),
        'generalHelp' => $plugin->get_lang('ServiceGeneralSectionHelp'),
        'pricingTitle' => $plugin->get_lang('ServicePricingSectionTitle'),
        'pricingHelp' => $plugin->get_lang('ServicePricingSectionHelp'),
        'recurringTitle' => $plugin->get_lang('RecurringPayments'),
        'recurringHelp' => $plugin->get_lang('ServiceRecurringSectionHelp'),
        'publishingTitle' => $plugin->get_lang('ServicePublishingSectionTitle'),
        'publishingHelp' => $plugin->get_lang('ServicePublishingSectionHelp'),
        'mediaTitle' => $plugin->get_lang('ServiceMediaSectionTitle'),
        'mediaHelp' => $plugin->get_lang('ServiceMediaSectionHelp'),
        'benefitsTitle' => $plugin->get_lang('GrantedBenefits'),
        'benefitsHelp' => $plugin->get_lang('ServiceBenefitsSectionHelp'),
        'aiTitle' => $plugin->get_lang('AiCourseFeaturesGranted'),
        'aiHelp' => $plugin->get_lang('AiCourseFeaturesGrantedHelp'),
        'dangerTitle' => $plugin->get_lang('ServiceDangerZoneTitle'),
        'dangerHelp' => $plugin->get_lang('ServiceDangerZoneHelp'),
    ];

    $aiFieldNames = [];
    foreach (array_keys($plugin->getAiCourseFeatureDefinitions()) as $feature) {
        $aiFieldNames[] = $plugin->getAiCourseFeatureFormField((string) $feature);
    }

    $labelsJson = json_encode(
        $labels,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    $aiFieldNamesJson = json_encode(
        $aiFieldNames,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    return <<<HTML
<style>
.buycourses-service-shell .buycourses-form-layout {
    display: grid;
    gap: 1.5rem;
}

.buycourses-service-shell .buycourses-form-section {
    overflow: hidden;
    border: 1px solid #dfe5ec;
    border-radius: 1.25rem;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.buycourses-service-shell .buycourses-form-section__header {
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid #e8edf3;
    background: #f8fafc;
}

.buycourses-service-shell .buycourses-form-section__title {
    margin: 0;
    color: #1f2937;
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.4;
}

.buycourses-service-shell .buycourses-form-section__help {
    margin: 0.3rem 0 0;
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.45;
}

.buycourses-service-shell .buycourses-form-section__body,
.buycourses-service-shell .buycourses-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.15rem;
    padding: 1.25rem;
}

.buycourses-service-shell .buycourses-form-grid--three {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.buycourses-service-shell .buycourses-form-field {
    min-width: 0;
    margin: 0 !important;
    padding: 0 !important;
}

.buycourses-service-shell .buycourses-form-field.row {
    display: block;
}

.buycourses-service-shell .buycourses-form-field > [class*="col-"] {
    width: 100%;
    max-width: none;
    padding-right: 0;
    padding-left: 0;
    flex: 0 0 100%;
}

.buycourses-service-shell .buycourses-form-field--full {
    grid-column: 1 / -1;
}

.buycourses-service-shell .buycourses-form-field--compact {
    padding: 1rem !important;
    border: 1px solid #e5eaf0;
    border-radius: 1rem;
    background: #fbfcfe;
}

.buycourses-service-shell .buycourses-form-field--compact .checkbox,
.buycourses-service-shell .buycourses-form-field--compact .radio {
    margin: 0;
}

.buycourses-service-shell .buycourses-label,
.buycourses-service-shell .buycourses-form-field > label,
.buycourses-service-shell .buycourses-form-field .control-label,
.buycourses-service-shell .buycourses-form-field .col-form-label {
    display: block;
    margin: 0 0 0.45rem;
    color: #1d6fa5;
    font-size: 0.9rem;
    font-weight: 700;
    line-height: 1.35;
    text-align: left !important;
}

.buycourses-service-shell .buycourses-control {
    width: 100% !important;
    min-height: 2.85rem;
    border: 1px solid #cfd8e3 !important;
    border-radius: 0.9rem !important;
    background: #ffffff !important;
    color: #1f2937 !important;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.buycourses-service-shell textarea.buycourses-control {
    min-height: 8rem;
    padding: 0.8rem 0.95rem !important;
}

.buycourses-service-shell .buycourses-control:focus {
    border-color: #2f80b9 !important;
    outline: 0 !important;
    box-shadow: 0 0 0 3px rgba(47, 128, 185, 0.15) !important;
}

.buycourses-service-shell .buycourses-editor {
    overflow: hidden;
    border: 1px solid #cfd8e3;
    border-radius: 0.9rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.buycourses-service-shell .buycourses-help,
.buycourses-service-shell .help-block,
.buycourses-service-shell .form-text,
.buycourses-service-shell .text-muted,
.buycourses-service-shell .comment {
    display: block;
    margin-top: 0.4rem;
    color: #64748b !important;
    font-size: 0.8rem;
    line-height: 1.45;
}

.buycourses-service-shell .buycourses-check-row {
    display: block;
    margin: 0;
}

.buycourses-service-shell .buycourses-check-row > label,
.buycourses-service-shell .buycourses-check-row .checkbox > label,
.buycourses-service-shell .buycourses-check-row .radio > label,
.buycourses-service-shell .buycourses-form-field--compact .checkbox > label,
.buycourses-service-shell .buycourses-form-field--compact .radio > label {
    display: flex !important;
    align-items: flex-start;
    gap: 0.75rem;
    margin: 0 !important;
    color: #1f2937 !important;
    font-weight: 600;
    line-height: 1.45;
    cursor: pointer;
}

.buycourses-service-shell .buycourses-check-row input[type="checkbox"],
.buycourses-service-shell .buycourses-check-row input[type="radio"],
.buycourses-service-shell .buycourses-form-field--compact .checkbox input[type="checkbox"],
.buycourses-service-shell .buycourses-form-field--compact .radio input[type="radio"] {
    margin: 0.2rem 0 0 !important;
    flex: 0 0 auto;
}

.buycourses-service-shell .buycourses-empty-label {
    display: none !important;
}

.buycourses-service-shell .buycourses-checkbox-copy {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 0.25rem;
}

.buycourses-service-shell .buycourses-checkbox-copy__title {
    color: #1d6fa5;
    font-size: 0.9rem;
    font-weight: 700;
    line-height: 1.35;
}

.buycourses-service-shell .buycourses-checkbox-copy__description {
    color: #334155;
    font-size: 0.84rem;
    font-weight: 500;
    line-height: 1.45;
}

.buycourses-service-shell .buycourses-ai-block {
    grid-column: 1 / -1;
    overflow: hidden;
    margin-top: 0.25rem;
    border: 1px solid #dfe5ec;
    border-radius: 1rem;
    background: #fbfcfe;
}

.buycourses-service-shell .buycourses-ai-block__header {
    padding: 1rem 1.1rem;
    border-bottom: 1px solid #e8edf3;
    background: #f3f7fb;
}

.buycourses-service-shell .buycourses-ai-block__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
    padding: 1rem;
}

.buycourses-service-shell .buycourses-ai-block__grid .buycourses-form-field {
    padding: 0.9rem !important;
    border: 1px solid #e5eaf0;
    border-radius: 0.9rem;
    background: #ffffff;
}

.buycourses-service-shell .buycourses-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1.1rem 1.25rem;
    border: 1px solid #dfe5ec;
    border-radius: 1.25rem;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.buycourses-service-shell .buycourses-action-button {
    display: inline-flex !important;
    min-height: 2.75rem;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    padding: 0.7rem 1.15rem !important;
    border: 0 !important;
    border-radius: 0.85rem !important;
    font-weight: 700 !important;
    line-height: 1.2;
    text-decoration: none !important;
    cursor: pointer;
}

.buycourses-service-shell .buycourses-action-button--primary {
    background: #2f80b9 !important;
    color: #ffffff !important;
}

.buycourses-service-shell .buycourses-action-button--secondary {
    border: 1px solid #2f80b9 !important;
    background: #ffffff !important;
    color: #1d6fa5 !important;
}

.buycourses-service-shell .buycourses-action-button--danger {
    background: #dc3545 !important;
    color: #ffffff !important;
}

.buycourses-service-shell .buycourses-action-button:hover {
    opacity: 0.9;
}

.buycourses-service-shell .buycourses-danger-zone {
    border-color: #f2c7cc;
}

.buycourses-service-shell .buycourses-danger-zone .buycourses-form-section__header {
    border-bottom-color: #f2c7cc;
    background: #fff7f8;
}

.buycourses-service-shell .buycourses-danger-zone .buycourses-form-section__title {
    color: #b42332;
}

.buycourses-service-shell .buycourses-danger-zone__body {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.1rem 1.25rem;
}

.buycourses-service-shell .buycourses-applies-to-card {
    height: 100%;
}

.buycourses-service-shell .buycourses-fallback-section-heading,
.buycourses-service-shell .buycourses-ai-intro {
    display: none;
}

@media (max-width: 900px) {
    .buycourses-service-shell .buycourses-form-section__body,
    .buycourses-service-shell .buycourses-form-grid,
    .buycourses-service-shell .buycourses-form-grid--three,
    .buycourses-service-shell .buycourses-ai-block__grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .buycourses-service-shell .buycourses-danger-zone__body {
        align-items: stretch;
        flex-direction: column;
    }

    .buycourses-service-shell .buycourses-action-button {
        width: 100%;
    }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = {$labelsJson};
    const aiFieldNames = {$aiFieldNamesJson};
    const shell = document.querySelector('.buycourses-service-shell');
    if (!shell) {
        return;
    }

    const form = shell.querySelector('form');
    if (!form) {
        return;
    }

    const richFieldNames = ['description', 'service_information'];

    function findField(name) {
        return form.querySelector('[name="' + name + '"]:not([type="hidden"])')
            || form.querySelector('[name="' + name + '"]');
    }

    function findFieldRow(element) {
        if (!element) {
            return null;
        }

        const structuredRow = element.closest('.form-group.row, .row.mb-3');
        if (structuredRow) {
            return structuredRow;
        }

        const formGroup = element.closest('.form-group');
        if (formGroup) {
            const parentStructuredRow = formGroup.parentElement
                ? formGroup.parentElement.closest('.form-group.row, .row.mb-3')
                : null;

            return parentStructuredRow || formGroup;
        }

        return element.closest('tr')
            || element.closest('.field')
            || element.parentElement;
    }

    function findCustomBlock(element) {
        if (!element) {
            return null;
        }

        const row = element.closest('.form-group.row, .row.mb-3')
            || element.closest('.form-group')
            || element.closest('tr');

        return row || element;
    }

    function createSection(title, help, extraClass) {
        const section = document.createElement('section');
        section.className = 'buycourses-form-section' + (extraClass ? ' ' + extraClass : '');

        const header = document.createElement('div');
        header.className = 'buycourses-form-section__header';

        const heading = document.createElement('h3');
        heading.className = 'buycourses-form-section__title';
        heading.textContent = title;
        header.appendChild(heading);

        if (help) {
            const description = document.createElement('p');
            description.className = 'buycourses-form-section__help';
            description.textContent = help;
            header.appendChild(description);
        }

        const body = document.createElement('div');
        body.className = 'buycourses-form-section__body';

        section.appendChild(header);
        section.appendChild(body);

        return {section: section, body: body};
    }

    function moveField(name, destination, options) {
        const field = findField(name);
        if (!field) {
            return null;
        }

        const row = findFieldRow(field);
        if (!row || row === form || row.dataset.buycoursesMoved === '1') {
            return null;
        }

        row.dataset.buycoursesMoved = '1';
        row.classList.add('buycourses-form-field');

        if (options && options.full) {
            row.classList.add('buycourses-form-field--full');
        }

        if (options && options.compact) {
            row.classList.add('buycourses-form-field--compact');
        }

        destination.appendChild(row);

        return row;
    }

    function moveCustomBlock(selector, destination, full) {
        const element = form.querySelector(selector);
        if (!element) {
            return null;
        }

        const block = findCustomBlock(element);
        if (!block || block === form || block.dataset.buycoursesMoved === '1') {
            return null;
        }

        block.dataset.buycoursesMoved = '1';
        block.classList.add('buycourses-form-field');
        if (full) {
            block.classList.add('buycourses-form-field--full');
        }

        destination.appendChild(block);

        return block;
    }

    function styleControl(field) {
        if (field.classList.contains('select2-search__field')) {
            return;
        }

        field.classList.add('buycourses-control');
    }

    function findRichEditorNode(row, textarea) {
        if (!row) {
            return null;
        }

        const selectors = [
            '.tox-tinymce',
            '.cke',
            '.cke_chrome',
            '.ck-editor',
            '.ql-toolbar',
            '.ql-container',
            '.fr-box',
            '.jodit-container',
            '.sun-editor',
            '.note-editor',
            'iframe',
            '[contenteditable="true"]'
        ];

        const nodes = Array.from(row.querySelectorAll(selectors.join(', ')));

        return nodes.find(function (node) {
            return node !== textarea && !textarea.contains(node);
        }) || null;
    }

    function hideEditorSourceTextarea(fieldName) {
        const textarea = form.querySelector('textarea[name="' + fieldName + '"]');
        if (!textarea) {
            return;
        }

        const row = findFieldRow(textarea);
        const editorNode = findRichEditorNode(row, textarea);

        if (!editorNode) {
            return;
        }

        textarea.classList.add('buycourses-editor-source');
        textarea.style.setProperty('display', 'none', 'important');
        textarea.style.setProperty('visibility', 'hidden', 'important');
        textarea.style.setProperty('height', '0', 'important');
        textarea.style.setProperty('min-height', '0', 'important');
        textarea.style.setProperty('padding', '0', 'important');
        textarea.style.setProperty('margin', '0', 'important');
        textarea.style.setProperty('border', '0', 'important');
        textarea.setAttribute('aria-hidden', 'true');
        textarea.setAttribute('tabindex', '-1');
    }

    function syncRichEditorsVisibility() {
        richFieldNames.forEach(function (fieldName) {
            hideEditorSourceTextarea(fieldName);
        });
    }

    const layoutHost = form.querySelector(':scope > fieldset') || form;
    const layout = document.createElement('div');
    layout.className = 'buycourses-form-layout';

    const directLegend = layoutHost.querySelector(':scope > legend');
    if (directLegend) {
        directLegend.insertAdjacentElement('afterend', layout);
    } else {
        layoutHost.prepend(layout);
    }

    const general = createSection(labels.generalTitle, labels.generalHelp, 'buycourses-general-section');
    const pricing = createSection(labels.pricingTitle, labels.pricingHelp, 'buycourses-pricing-section');
    const recurring = createSection(labels.recurringTitle, labels.recurringHelp, 'buycourses-recurring-section');
    const publishing = createSection(labels.publishingTitle, labels.publishingHelp, 'buycourses-publishing-section');
    const media = createSection(labels.mediaTitle, labels.mediaHelp, 'buycourses-media-section');
    const benefits = createSection(labels.benefitsTitle, labels.benefitsHelp, 'buycourses-benefits-section');

    layout.appendChild(general.section);
    layout.appendChild(pricing.section);
    layout.appendChild(recurring.section);
    layout.appendChild(publishing.section);
    layout.appendChild(media.section);
    layout.appendChild(benefits.section);

    moveField('name', general.body, {full: true});
    moveField('description', general.body, {full: true});

    moveField('price', pricing.body);
    moveField('tax_perc', pricing.body);
    moveField('duration_days', pricing.body);
    moveField('upsale_from_id', pricing.body);

    moveField('renewable', recurring.body, {compact: true});
    moveField('total_charges', recurring.body);
    moveField('allow_trial', recurring.body, {compact: true});
    moveField('trial_period', recurring.body);
    moveField('trial_frequency', recurring.body);
    moveField('trial_total_charges', recurring.body);
    moveField('max_subscribers', recurring.body);
    moveField('subscription_behavior_json', recurring.body, {full: true});
    moveField('stripe_price_id', recurring.body, {full: true});

    moveField('display_on_course_creation_page', publishing.body, {full: true, compact: true});
    moveCustomBlock('.buycourses-applies-to-card', publishing.body, true);
    moveField('owner_id', publishing.body);
    moveField('visibility', publishing.body, {compact: true});

    moveField('picture', media.body, {full: true});
    moveField('video_url', media.body, {full: true});
    moveField('service_information', media.body, {full: true});

    benefits.body.classList.add('buycourses-form-grid--three');
    moveField('benefit_max_courses', benefits.body);
    moveField('benefit_hosting_limit', benefits.body);
    moveField('benefit_document_quota', benefits.body);

    const aiBlock = document.createElement('div');
    aiBlock.className = 'buycourses-ai-block';

    const aiHeader = document.createElement('div');
    aiHeader.className = 'buycourses-ai-block__header';

    const aiTitle = document.createElement('h4');
    aiTitle.className = 'buycourses-form-section__title';
    aiTitle.textContent = labels.aiTitle;
    aiHeader.appendChild(aiTitle);

    if (labels.aiHelp) {
        const aiHelp = document.createElement('p');
        aiHelp.className = 'buycourses-form-section__help';
        aiHelp.textContent = labels.aiHelp;
        aiHeader.appendChild(aiHelp);
    }

    const aiGrid = document.createElement('div');
    aiGrid.className = 'buycourses-ai-block__grid';

    aiBlock.appendChild(aiHeader);
    aiBlock.appendChild(aiGrid);
    benefits.body.appendChild(aiBlock);

    aiFieldNames.forEach(function (fieldName) {
        moveField(fieldName, aiGrid, {compact: true});
    });

    form.querySelectorAll('.buycourses-fallback-section-heading, .buycourses-ai-intro').forEach(function (element) {
        const block = findCustomBlock(element);
        if (block && block !== form && block !== layoutHost && !block.contains(layout)) {
            block.remove();
        }
    });

    shell.querySelectorAll('.form_required').forEach(function (requiredText) {
        requiredText.style.color = '#b42332';
        requiredText.style.fontWeight = '700';
    });

    shell.querySelectorAll('.alert, .warning-message').forEach(function (alertBox) {
        alertBox.style.borderRadius = '0.9rem';
    });

    shell.querySelectorAll('label, .form-label, .col-form-label, legend').forEach(function (label) {
        label.classList.add('buycourses-label');
    });

    shell.querySelectorAll('.buycourses-form-field > label.col-form-label').forEach(function (label) {
        if ('' === label.textContent.trim()) {
            label.classList.add('buycourses-empty-label');
        }
    });

    shell.querySelectorAll('input[type="text"], input[type="number"], input[type="url"], select, textarea').forEach(function (field) {
        if (richFieldNames.includes(field.name)) {
            return;
        }

        styleControl(field);
    });

    shell.querySelectorAll('input[type="file"]').forEach(function (field) {
        field.classList.add('buycourses-control');
    });

    shell.querySelectorAll('.checkbox, .radio').forEach(function (item) {
        item.classList.add('buycourses-check-row');
    });

    shell.querySelectorAll('.tox-tinymce, .cke, .cke_chrome, .ck-editor, .ql-toolbar, .ql-container, .fr-box, .jodit-container, .sun-editor, .note-editor').forEach(function (editor) {
        editor.classList.add('buycourses-editor');
    });

    shell.querySelectorAll('.help-block, .form-text, .text-muted, .comment').forEach(function (helpText) {
        helpText.classList.add('buycourses-help');
    });

    syncRichEditorsVisibility();
    window.requestAnimationFrame(syncRichEditorsVisibility);
    setTimeout(syncRichEditorsVisibility, 250);
    setTimeout(syncRichEditorsVisibility, 1000);

    const appliesToField = form.querySelector('input[name="applies_to"]');
    const appliesToRadios = form.querySelectorAll('input[name="applies_to"][type="radio"]');
    const benefitsSection = form.querySelector('.buycourses-benefits-section');

    function toggleBenefitsSection() {
        if (!benefitsSection || !appliesToField) {
            return;
        }

        let appliesToValue = appliesToField.value;

        if (appliesToRadios.length > 0) {
            const selectedRadio = form.querySelector('input[name="applies_to"][type="radio"]:checked');
            appliesToValue = selectedRadio ? selectedRadio.value : appliesToValue;
        }

        const isUserService = '1' === String(appliesToValue);
        benefitsSection.hidden = !isUserService;

        benefitsSection.querySelectorAll('input, select, textarea').forEach(function (field) {
            field.disabled = !isUserService;
        });
    }

    appliesToRadios.forEach(function (radio) {
        radio.addEventListener('change', toggleBenefitsSection);
    });

    toggleBenefitsSection();

    const fileInput = form.querySelector('input[type="file"][name="picture"], input[type="file"]');
    const cropGroup = form.querySelector('#picture-form-group');
    const cropButton = form.querySelector('[name="cropButton"], #picture_crop_button');

    if (fileInput && cropGroup) {
        const imageRow = findFieldRow(fileInput);

        cropGroup.classList.add('buycourses-form-field', 'buycourses-form-field--full');

        if (imageRow && imageRow.parentElement) {
            imageRow.insertAdjacentElement('afterend', cropGroup);
        }
    }

    if (cropButton) {
        cropButton.classList.add('buycourses-action-button', 'buycourses-action-button--secondary');
    }

    const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
    const deleteButton = submitButtons.find(function (button) {
        return button.name === 'delete_service';
    });
    const primaryButtons = submitButtons.filter(function (button) {
        return button !== deleteButton;
    });

    primaryButtons.forEach(function (button) {
        button.classList.add('buycourses-action-button', 'buycourses-action-button--primary');
    });

    if (primaryButtons.length > 0) {
        const actions = document.createElement('div');
        actions.className = 'buycourses-actions';
        primaryButtons.forEach(function (button) {
            actions.appendChild(button);
        });
        layout.appendChild(actions);
    }

    if (deleteButton) {
        deleteButton.classList.add('buycourses-action-button', 'buycourses-action-button--danger');

        const danger = createSection(labels.dangerTitle, labels.dangerHelp, 'buycourses-danger-zone');
        danger.body.className = 'buycourses-danger-zone__body';
        danger.body.appendChild(deleteButton);
        layout.appendChild(danger.section);
    }

    form.querySelectorAll('.form-group, .row.mb-3').forEach(function (row) {
        if (row.dataset.buycoursesMoved === '1') {
            return;
        }

        const hasVisibleControl = row.querySelector('input:not([type="hidden"]), select, textarea, button, a');
        const hasVisibleText = row.textContent.trim() !== '';
        if (!hasVisibleControl && !hasVisibleText && !row.contains(layout)) {
            row.remove();
        }
    });
});
</script>
HTML;
}


function buycoursesValidateServicePayload(array $values, BuyCoursesPlugin $plugin): array
{
    $errors = [];

    $name = trim((string) ($values['name'] ?? ''));
    $appliesTo = isset($values['applies_to']) ? (int) ($values['applies_to'] ?? BuyCoursesPlugin::SERVICE_TYPE_NONE) : BuyCoursesPlugin::SERVICE_TYPE_NONE;
    $durationDays = isset($values['duration_days']) ? (int) ($values['duration_days'] ?? 0) : 0;
    $renewable = !empty($values['renewable']);
    $allowTrial = !empty($values['allow_trial']);
    $trialFrequency = isset($values['trial_frequency']) ? (int) ($values['trial_frequency'] ?? 0) : 0;
    $subscriptionBehaviorJson = trim((string) ($values['subscription_behavior_json'] ?? ''));
    $benefitMaxCourses = isset($values['benefit_max_courses']) ? (int) ($values['benefit_max_courses'] ?? 0) : 0;
    $benefitHostingLimit = isset($values['benefit_hosting_limit']) ? (int) ($values['benefit_hosting_limit'] ?? 0) : 0;
    $benefitDocumentQuota = isset($values['benefit_document_quota']) ? (int) ($values['benefit_document_quota'] ?? 0) : 0;
    $aiCourseFeatures = $plugin->getAiCourseFeaturesFromServiceData($values);

    $hasAnyBenefit = $benefitMaxCourses > 0
        || $benefitHostingLimit > 0
        || $benefitDocumentQuota > 0
        || !empty($aiCourseFeatures);

    if ('' === $name) {
        $errors[] = get_lang('ThisFieldIsRequired').': '.$plugin->get_lang('ServiceName');
    }

    if ($hasAnyBenefit && BuyCoursesPlugin::SERVICE_TYPE_USER !== $appliesTo) {
        $errors[] = $plugin->get_lang('GrantedBenefitsOnlyForUserServices');
    }

    if ($hasAnyBenefit && $durationDays <= 0) {
        $errors[] = $plugin->get_lang('DurationMustBePositiveForBenefits');
    }

    if ($allowTrial && $trialFrequency <= 0) {
        $errors[] = $plugin->get_lang('TrialFrequencyMustBePositive');
    }

    if (!$renewable && $allowTrial) {
        $errors[] = $plugin->get_lang('TrialRequiresRenewableService');
    }

    if ('' !== $subscriptionBehaviorJson) {
        json_decode($subscriptionBehaviorJson, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $errors[] = $plugin->get_lang('SubscriptionBehaviorJsonInvalid');
        }
    }

    $upsaleError = $plugin->getServiceUpsaleValidationError(
        $values,
        (int) ($values['id'] ?? 0)
    );
    if (null !== $upsaleError) {
        $errors[] = $upsaleError;
    }

    return $errors;
}

function buycoursesBuildPostedServicePayload(int $serviceId, BuyCoursesPlugin $plugin): array
{
    $payload = [
        'id' => $serviceId,
        'name' => trim((string) ($_POST['name'] ?? '')),
        'description' => (string) ($_POST['description'] ?? ''),
        'price' => (string) ($_POST['price'] ?? ''),
        'tax_perc' => (string) ($_POST['tax_perc'] ?? ''),
        'duration_days' => (string) ($_POST['duration_days'] ?? ''),
        'renewable' => isset($_POST['renewable']) ? 1 : 0,
        'total_charges' => isset($_POST['total_charges']) ? (int) $_POST['total_charges'] : 0,
        'allow_trial' => isset($_POST['allow_trial']) ? 1 : 0,
        'trial_period' => (string) ($_POST['trial_period'] ?? 'Day'),
        'trial_frequency' => isset($_POST['trial_frequency']) ? (int) $_POST['trial_frequency'] : 0,
        'trial_total_charges' => isset($_POST['trial_total_charges']) ? (int) $_POST['trial_total_charges'] : 0,
        'max_subscribers' => isset($_POST['max_subscribers']) ? (int) $_POST['max_subscribers'] : 0,
        'subscription_behavior_json' => (string) ($_POST['subscription_behavior_json'] ?? ''),
        'stripe_price_id' => trim((string) ($_POST['stripe_price_id'] ?? '')),
        'display_on_course_creation_page' => isset($_POST['display_on_course_creation_page']) ? 1 : 0,
        'upsale_from_id' => isset($_POST['upsale_from_id']) ? (int) $_POST['upsale_from_id'] : 0,
        'applies_to' => isset($_POST['applies_to']) ? (int) $_POST['applies_to'] : BuyCoursesPlugin::SERVICE_TYPE_NONE,
        'owner_id' => isset($_POST['owner_id']) ? (int) $_POST['owner_id'] : 0,
        'visibility' => isset($_POST['visibility']) ? 1 : 0,
        'video_url' => trim((string) ($_POST['video_url'] ?? '')),
        'service_information' => (string) ($_POST['service_information'] ?? ''),
        'benefit_max_courses' => isset($_POST['benefit_max_courses']) ? (int) $_POST['benefit_max_courses'] : 0,
        'benefit_hosting_limit' => isset($_POST['benefit_hosting_limit']) ? (int) $_POST['benefit_hosting_limit'] : 0,
        'benefit_document_quota' => isset($_POST['benefit_document_quota']) ? (int) $_POST['benefit_document_quota'] : 0,
        'picture_crop_image_base_64' => (string) ($_POST['picture_crop_image_base_64'] ?? ''),
        'picture_crop_result' => (string) ($_POST['picture_crop_result'] ?? ''),
    ];

    foreach ($plugin->getAiCourseFeatureDefinitions() as $feature => $definition) {
        $formField = $plugin->getAiCourseFeatureFormField((string) $feature);
        $payload[$formField] = isset($_POST[$formField]) ? 1 : 0;
    }

    return $payload;
}
