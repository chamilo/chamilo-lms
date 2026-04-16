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

/** @var array<int, User> $users */
$users = Container::getUserRepository()->findAll();
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

$customImageUrl = $plugin->getServiceImageUrl('simg-'.$serviceId.'.png');

$formDefaultValues = array_merge(
    $service,
    $plugin->buildBenefitFormDefaults($serviceId)
);

if (!isset($formDefaultValues['visibility'])) {
    $formDefaultValues['visibility'] = !empty($service['visibility']);
}

$form = new FormValidator(
    'Services',
    'post',
    api_get_self().'?id='.$serviceId
);

$form->addText('name', $plugin->get_lang('ServiceName'));
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');
$form->addHtmlEditor('description', $plugin->get_lang('Description'));
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
$form->addElement(
    'radio',
    'applies_to',
    $plugin->get_lang('AppliesTo'),
    get_lang('None'),
    0
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('User'),
    1
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('Course'),
    2
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('Session'),
    3
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('TemplateTitleCertificate'),
    4
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
$form->addHtmlEditor('service_information', $plugin->get_lang('ServiceInformation'), false);

$form->addHtml('<div class="buycourses-benefits-section">');
$form->addHeader($plugin->get_lang('GrantedBenefits'));

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

$form->addHtml('</div>');

$form->addHidden('id', (string) $serviceId);
$form->addButtonSave(get_lang('Edit'));
$form->addButtonDelete($plugin->get_lang('DeleteThisService'), 'delete_service');
$form->setDefaults($formDefaultValues);

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $values = buycoursesBuildPostedServicePayload($serviceId);

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
                Display::return_message('Service update failed.', 'error')
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
    'Update service details, pricing, media, and catalog visibility.',
    (string) ($currency['iso_code'] ?? ''),
    $defaultGlobalTax,
    $customImageUrl
);

$tpl->assign('header', $templateName);
$tpl->assign('content', $pageContent);
$tpl->display_one_col_template();

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
    ?string $previewImageUrl = null
): string {
    $safePageTitle = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
    $safePluginTitle = htmlspecialchars($pluginTitle, ENT_QUOTES, 'UTF-8');
    $safeSubtitle = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
    $safeBackUrl = htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8');
    $safeCurrencyCode = htmlspecialchars($currencyCode, ENT_QUOTES, 'UTF-8');

    $previewHtml = '';
    if (!empty($previewImageUrl)) {
        $safePreviewImageUrl = htmlspecialchars($previewImageUrl, ENT_QUOTES, 'UTF-8');
        $previewHtml = <<<HTML
            <div class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                <div class="border-b border-gray-20 px-5 py-4">
                    <h3 class="text-body-1 font-semibold text-gray-90">Current image</h3>
                </div>
                <div class="bg-support-2 p-4">
                    <img
                        src="{$safePreviewImageUrl}"
                        alt="Service image"
                        class="h-auto w-full rounded-2xl border border-gray-20 object-cover shadow-sm"
                    >
                </div>
            </div>
        HTML;
    }

    $enhancerScript = buycoursesBuildServiceFormEnhancerScript();

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
                            Back to services
                        </a>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
                <aside class="space-y-6">
                    <div class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                        <div class="border-b border-gray-20 px-5 py-4">
                            <h2 class="text-body-1 font-semibold text-gray-90">Quick summary</h2>
                        </div>
                        <dl class="space-y-3 bg-white p-5">
                            <div class="rounded-2xl border border-gray-20 bg-support-2 px-4 py-3">
                                <dt class="text-tiny font-semibold uppercase tracking-wide text-primary">Currency</dt>
                                <dd class="mt-1 text-body-2 font-semibold text-gray-90">{$safeCurrencyCode}</dd>
                            </div>
                            <div class="rounded-2xl border border-gray-20 bg-support-2 px-4 py-3">
                                <dt class="text-tiny font-semibold uppercase tracking-wide text-primary">Default tax</dt>
                                <dd class="mt-1 text-body-2 font-semibold text-gray-90">{$defaultGlobalTax}%</dd>
                            </div>
                            <div class="rounded-2xl border border-gray-20 bg-support-2 px-4 py-3">
                                <dt class="text-tiny font-semibold uppercase tracking-wide text-primary">Image crop</dt>
                                <dd class="mt-1 text-body-2 font-semibold text-gray-90">16:9</dd>
                            </div>
                        </dl>
                    </div>

                    {$previewHtml}
                </aside>

                <section class="overflow-hidden rounded-3xl border border-gray-25 bg-white shadow-sm">
                    <div class="border-b border-gray-20 px-6 py-5 lg:px-8">
                        <h2 class="text-body-1 font-semibold text-gray-90">Service form</h2>
                        <p class="mt-1 text-body-2 text-gray-50">
                            Review the existing information and update only what you need.
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
function buycoursesBuildServiceFormEnhancerScript(): string
{
    return <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', function () {
    const shell = document.querySelector('.buycourses-service-shell');
    if (!shell) {
        return;
    }

    const form = shell.querySelector('form');
    if (!form) {
        return;
    }

    const richFieldNames = ['description', 'service_information'];

    function styleStandardField(field) {
        if (field.classList.contains('select2-search__field')) {
            return;
        }

        field.classList.add(
            'mt-2',
            'block',
            'w-full',
            'rounded-2xl',
            'border',
            'border-gray-25',
            'bg-white',
            'px-4',
            'py-3',
            'text-body-2',
            'text-gray-90',
            'shadow-sm',
            'transition',
            'placeholder:text-gray-50',
            'focus:border-primary',
            'focus:outline-none',
            'focus:ring-2',
            'focus:ring-primary/20'
        );
    }

    function findFieldRow(element) {
        return element.closest('.form-group, .row, fieldset, td, .col-md-9, .col-sm-9, .col-lg-9') || element.parentElement;
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
            '.ck',
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

        return nodes.find((node) => node !== textarea && !textarea.contains(node)) || null;
    }

    function hideEditorSourceTextarea(fieldName) {
        const textarea = form.querySelector(`textarea[name="${fieldName}"]`);
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
        richFieldNames.forEach((fieldName) => {
            hideEditorSourceTextarea(fieldName);
        });
    }

    form.classList.add('space-y-8');

    shell.querySelectorAll('.form_required').forEach((requiredText) => {
        requiredText.classList.add('text-body-2', 'font-semibold', 'text-primary');
    });

    shell.querySelectorAll('.alert, .warning-message').forEach((alertBox) => {
        alertBox.classList.add(
            'mb-6',
            'rounded-2xl',
            'border',
            'border-warning',
            'bg-support-6',
            'px-4',
            'py-3',
            'text-body-2',
            'text-gray-90'
        );
    });

    shell.querySelectorAll('.form-group, .row, fieldset').forEach((block) => {
        block.classList.add('space-y-2');
    });

    shell.querySelectorAll('fieldset').forEach((fieldset) => {
        fieldset.classList.remove('border', 'border-0');
        fieldset.classList.add('rounded-2xl', 'border', 'border-gray-20', 'bg-support-2', 'p-4');
    });

    shell.querySelectorAll('label, .form-label, .col-form-label, legend').forEach((label) => {
        label.classList.add('mb-2', 'block', 'text-body-2', 'font-semibold', 'text-primary');
    });

    shell.querySelectorAll('input[type="text"], input[type="number"], input[type="url"], select').forEach((field) => {
        styleStandardField(field);
    });

    shell.querySelectorAll('textarea').forEach((field) => {
        if (richFieldNames.includes(field.name)) {
            return;
        }

        styleStandardField(field);
    });

    shell.querySelectorAll('input[type="file"]').forEach((field) => {
        field.classList.add(
            'mt-2',
            'block',
            'w-full',
            'rounded-2xl',
            'border',
            'border-gray-25',
            'bg-white',
            'px-4',
            'py-3',
            'text-body-2',
            'text-gray-90',
            'shadow-sm'
        );
    });

    shell.querySelectorAll('.checkbox, .radio').forEach((item) => {
        item.classList.add('flex', 'items-center', 'gap-3', 'py-1');
    });

    shell.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach((field) => {
        field.classList.add('cursor-pointer', 'align-middle');

        const wrapper = field.closest('.checkbox, .radio');
        if (wrapper) {
            wrapper.querySelectorAll('label').forEach((label) => {
                label.classList.add('mb-0', 'cursor-pointer', 'text-body-2', 'font-medium', 'text-gray-90');
            });
        }
    });

    shell.querySelectorAll('.tox-tinymce, .cke, .cke_chrome, .ck-editor, .ql-toolbar, .ql-container, .fr-box, .jodit-container, .sun-editor, .note-editor').forEach((editor) => {
        editor.classList.add('mt-2', 'overflow-hidden', 'rounded-2xl', 'border', 'border-gray-25', 'shadow-sm');
    });

    shell.querySelectorAll('.help-block, .form-text, .text-muted, .comment').forEach((helpText) => {
        helpText.classList.add('mt-2', 'text-caption', 'text-gray-50');
    });

    syncRichEditorsVisibility();
    window.requestAnimationFrame(syncRichEditorsVisibility);
    setTimeout(syncRichEditorsVisibility, 250);
    setTimeout(syncRichEditorsVisibility, 1000);

    const appliesToUserRadio = form.querySelector('input[name="applies_to"][value="1"]');
    const appliesToRadios = form.querySelectorAll('input[name="applies_to"]');
    const benefitsSection = form.querySelector('.buycourses-benefits-section');

    function toggleBenefitsSection() {
        if (!benefitsSection || !appliesToUserRadio) {
            return;
        }

        const isUserService = appliesToUserRadio.checked;
        benefitsSection.classList.toggle('hidden', !isUserService);

        benefitsSection.querySelectorAll('input, select, textarea').forEach((field) => {
            field.disabled = !isUserService;
        });
    }

    appliesToRadios.forEach((radio) => {
        radio.addEventListener('change', toggleBenefitsSection);
    });

    toggleBenefitsSection();

    const fileInput = form.querySelector('input[type="file"][name="picture"], input[type="file"]');
    const cropButton = Array.from(form.querySelectorAll('button, .btn, input[type="button"], input[type="submit"]')).find((element) => {
        const text = (element.textContent || element.value || '').toLowerCase();
        return text.includes('crop your picture');
    });
    const hasCropDataInput = form.querySelector('input[name="picture_crop_result"], input[name="picture_crop_image_base_64"]');

    if (fileInput && cropButton) {
        const imageRow = findFieldRow(fileInput);

        const actionsWrap = document.createElement('div');
        actionsWrap.className = 'mt-3 flex items-center gap-3 buycourses-image-actions';

        cropButton.classList.remove('btn', 'btn-primary', 'btn-default');
        cropButton.classList.add(
            'inline-flex',
            'items-center',
            'justify-center',
            'rounded-2xl',
            'bg-primary',
            'px-5',
            'py-3',
            'text-body-2',
            'font-semibold',
            'text-white',
            'shadow-sm',
            'transition',
            'hover:opacity-90',
            'focus:outline-none',
            'focus:ring-2',
            'focus:ring-primary/20',
            'focus:ring-offset-2'
        );

        actionsWrap.appendChild(cropButton);

        if (imageRow) {
            imageRow.appendChild(actionsWrap);
        }

        function shouldShowCropButton() {
            const hasSelectedFile = !!(fileInput.files && fileInput.files.length > 0);
            const hasExistingCropData = !!(hasCropDataInput && hasCropDataInput.value && hasCropDataInput.value.trim() !== '');
            actionsWrap.classList.toggle('hidden', !(hasSelectedFile || hasExistingCropData));
        }

        fileInput.addEventListener('change', shouldShowCropButton);
        shouldShowCropButton();
    }

    const buttons = Array.from(shell.querySelectorAll('input[type="submit"], button, .btn')).filter((button) => button.closest('form'));

    buttons.forEach((button) => {
        const label = (button.textContent || button.value || '').toLowerCase();
        const isDelete = button.name === 'delete_service' || label.includes('delete');
        const isCrop = label.includes('crop your picture');

        if (isCrop) {
            return;
        }

        button.classList.remove('btn', 'btn-primary', 'btn-default', 'btn-danger', 'btn-outline-danger');
        button.classList.add(
            'inline-flex',
            'items-center',
            'justify-center',
            'rounded-2xl',
            'px-5',
            'py-3',
            'text-body-2',
            'font-semibold',
            'shadow-sm',
            'transition',
            'focus:outline-none',
            'focus:ring-2',
            'focus:ring-offset-2'
        );

        if (isDelete) {
            button.classList.add(
                'bg-danger',
                'text-danger-button-text',
                'hover:opacity-90',
                'focus:ring-danger/20'
            );
        } else {
            button.classList.add(
                'bg-primary',
                'text-white',
                'hover:opacity-90',
                'focus:ring-primary/20'
            );
        }
    });

    const deleteButton = buttons.find((button) => button.name === 'delete_service');
    const primaryButtons = buttons.filter((button) => {
        const label = (button.textContent || button.value || '').toLowerCase();
        return button.name !== 'delete_service' && !label.includes('crop your picture');
    });

    if (primaryButtons.length > 0) {
        const primaryRow = document.createElement('div');
        primaryRow.className = 'mt-8 flex flex-wrap items-center justify-end gap-3';
        primaryButtons.forEach((button) => primaryRow.appendChild(button));
        form.appendChild(primaryRow);
    }

    if (deleteButton) {
        const deleteRow = document.createElement('div');
        deleteRow.className = 'mt-3 flex flex-wrap items-center justify-end gap-3';
        deleteRow.appendChild(deleteButton);
        form.appendChild(deleteRow);
    }
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
    $benefitMaxCourses = isset($values['benefit_max_courses']) ? (int) ($values['benefit_max_courses'] ?? 0) : 0;
    $benefitHostingLimit = isset($values['benefit_hosting_limit']) ? (int) ($values['benefit_hosting_limit'] ?? 0) : 0;
    $benefitDocumentQuota = isset($values['benefit_document_quota']) ? (int) ($values['benefit_document_quota'] ?? 0) : 0;

    $hasAnyBenefit = $benefitMaxCourses > 0
        || $benefitHostingLimit > 0
        || $benefitDocumentQuota > 0;

    if ('' === $name) {
        $errors[] = get_lang('ThisFieldIsRequired').': '.$plugin->get_lang('ServiceName');
    }

    if ($hasAnyBenefit && BuyCoursesPlugin::SERVICE_TYPE_USER !== $appliesTo) {
        $errors[] = 'Granted benefits are only available for services that apply to User.';
    }

    if ($hasAnyBenefit && $durationDays <= 0) {
        $errors[] = 'Duration must be greater than 0 when the service grants benefits.';
    }

    return $errors;
}

function buycoursesBuildPostedServicePayload(int $serviceId): array
{
    return [
        'id' => $serviceId,
        'name' => trim((string) ($_POST['name'] ?? '')),
        'description' => (string) ($_POST['description'] ?? ''),
        'price' => (string) ($_POST['price'] ?? ''),
        'tax_perc' => (string) ($_POST['tax_perc'] ?? ''),
        'duration_days' => (string) ($_POST['duration_days'] ?? ''),
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
}
