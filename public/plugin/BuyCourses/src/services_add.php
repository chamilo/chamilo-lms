<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

/**
 * Create new Services for the Buy Courses plugin.
 */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once '../../../main/inc/global.inc.php';

api_protect_admin_script(true);

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

$formDefaultValues = [
    'price' => 0,
    'tax_perc' => $defaultGlobalTax,
    'duration_days' => 0,
    'applies_to' => 0,
    'owner_id' => api_get_user_id(),
    'visibility' => true,
];

$form = new FormValidator('Services');
$form->addText('name', $plugin->get_lang('ServiceName'));
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
    get_lang('AddImage'),
    ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '16 / 9']
);
$form->addText('video_url', get_lang('VideoUrl'), false);
$form->addHtmlEditor('service_information', $plugin->get_lang('ServiceInformation'), false);
$form->addButtonSave(get_lang('Add'));
$form->setDefaults($formDefaultValues);

if ($form->validate()) {
    $values = $form->getSubmitValues();

    $plugin->storeService($values);

    Display::addFlash(
        Display::return_message($plugin->get_lang('ServiceAdded'), 'success')
    );

    header('Location: list_service.php');
    exit;
}

$templateName = $plugin->get_lang('NewService');
$tpl = new Template($templateName);

$pageContent = buycoursesBuildServiceFormShell(
    $templateName,
    $form->returnForm(),
    'list_service.php',
    $plugin->get_lang('plugin_title'),
    'Create a service, define pricing, and choose who it applies to.',
    (string) ($currency['iso_code'] ?? ''),
    $defaultGlobalTax
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
        <div class="buycourses-service-shell mx-auto max-w-7xl space-y-6 px-4 pb-10">
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
                            Fill in the basic information, price, visibility, and optional media.
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

    form.classList.add('space-y-8');

    shell.querySelectorAll('.form_required').forEach((requiredText) => {
        requiredText.classList.add('text-body-2', 'font-semibold', 'text-primary');
    });

    shell.querySelectorAll('.alert, .warning-message').forEach((alertBox) => {
        alertBox.classList.add('mb-6', 'rounded-2xl', 'border', 'border-warning', 'bg-support-6', 'px-4', 'py-3', 'text-body-2', 'text-gray-90');
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

    shell.querySelectorAll('input[type="text"], input[type="number"], input[type="url"], select, textarea').forEach((field) => {
        if (field.closest('.tox, .tox-tinymce') || field.classList.contains('select2-search__field')) {
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

    shell.querySelectorAll('.tox-tinymce, .cke').forEach((editor) => {
        editor.classList.add('mt-2', 'overflow-hidden', 'rounded-2xl', 'border', 'border-gray-25', 'shadow-sm');
    });

    shell.querySelectorAll('.help-block, .form-text, .text-muted, .comment').forEach((helpText) => {
        helpText.classList.add('mt-2', 'text-caption', 'text-gray-50');
    });

    const buttons = Array.from(shell.querySelectorAll('input[type="submit"], button, .btn')).filter((button) => button.closest('form'));
    buttons.forEach((button) => {
        const label = (button.textContent || button.value || '').toLowerCase();
        const isDelete = button.name === 'delete_service' || label.includes('delete');

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
                'text-white',
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
    const primaryButtons = buttons.filter((button) => button.name !== 'delete_service');

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

    shell.querySelectorAll('br').forEach((lineBreak) => {
        lineBreak.remove();
    });
});
</script>
HTML;
}
