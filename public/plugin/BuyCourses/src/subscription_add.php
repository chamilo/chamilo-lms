<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/*
 * Configuration page for subscriptions for the Buy Courses plugin.
 */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

/**
 * Add classes to an element without removing existing ones.
 */
function addTailwindClassesToElement(DOMElement $element, array $classes): void
{
    $existing = trim((string) $element->getAttribute('class'));
    $currentClasses = '' === $existing ? [] : preg_split('/\s+/', $existing);
    $currentClasses = is_array($currentClasses) ? $currentClasses : [];

    foreach ($classes as $class) {
        if (!in_array($class, $currentClasses, true)) {
            $currentClasses[] = $class;
        }
    }

    $element->setAttribute('class', trim(implode(' ', array_filter($currentClasses))));
}

/**
 * Return the inner HTML of a DOM element.
 */
function getElementInnerHtml(DOMElement $element): string
{
    $html = '';

    foreach ($element->childNodes as $childNode) {
        $html .= $element->ownerDocument->saveHTML($childNode);
    }

    return $html;
}

/**
 * Normalize subscription frequencies for save/render.
 *
 * @return array<int, array{duration:int, price:float}>
 */
function normalizeSubscriptionFrequencies(array $rawFrequencies): array
{
    $normalized = [];

    foreach ($rawFrequencies as $frequency) {
        if (!is_array($frequency)) {
            continue;
        }

        $duration = isset($frequency['duration']) ? (int) $frequency['duration'] : 0;
        $price = isset($frequency['price']) ? (float) $frequency['price'] : 0.0;

        if ($duration <= 0 || $price <= 0) {
            continue;
        }

        $normalized[] = [
            'duration' => $duration,
            'price' => $price,
        ];
    }

    return $normalized;
}

/**
 * Style legacy FormValidator markup with Tailwind utility classes.
 */
function styleBuyCoursesFormHtml(string $html): string
{
    if (!class_exists(DOMDocument::class) || '' === trim($html)) {
        return $html;
    }

    $previousUseInternalErrors = libxml_use_internal_errors(true);

    $document = new DOMDocument('1.0', 'UTF-8');
    $wrappedHtml = '<?xml encoding="utf-8" ?><div id="buycourses-form-root">'.$html.'</div>';

    $loaded = $document->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    if (!$loaded) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $xpath = new DOMXPath($document);
    $root = $document->getElementById('buycourses-form-root');

    if (!$root) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $forms = $xpath->query('.//form', $root);
    if ($forms) {
        foreach ($forms as $form) {
            if (!$form instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($form, ['space-y-6']);
        }
    }

    $formGroups = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " form-group ")]', $root);
    if ($formGroups) {
        foreach ($formGroups as $group) {
            if (!$group instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($group, [
                'rounded-2xl',
                'border',
                'border-gray-25',
                'bg-white',
                'p-5',
                'shadow-sm',
                'space-y-4',
            ]);
        }
    }

    $labels = $xpath->query('.//label', $root);
    if ($labels) {
        foreach ($labels as $label) {
            if (!$label instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($label, [
                'mb-3',
                'block',
                'text-sm',
                'font-semibold',
                'text-gray-90',
            ]);
        }
    }

    $columns = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " col-sm-2 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-3 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-5 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-7 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-8 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-10 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-11 ")]',
        $root
    );

    if ($columns) {
        foreach ($columns as $column) {
            if (!$column instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($column, ['w-full', 'max-w-none']);
        }
    }

    $inputs = $xpath->query('.//input', $root);
    if ($inputs) {
        foreach ($inputs as $input) {
            if (!$input instanceof DOMElement) {
                continue;
            }

            $type = strtolower((string) $input->getAttribute('type'));

            if ('hidden' === $type) {
                continue;
            }

            if (in_array($type, ['checkbox', 'radio'], true)) {
                addTailwindClassesToElement($input, [
                    'h-4',
                    'w-4',
                    'border-gray-25',
                    'text-primary',
                    'focus:ring-primary',
                ]);

                continue;
            }

            if (in_array($type, ['submit', 'button'], true)) {
                addTailwindClassesToElement($input, [
                    'inline-flex',
                    'items-center',
                    'justify-center',
                    'gap-2',
                    'rounded-xl',
                    'bg-success',
                    'px-4',
                    'py-2.5',
                    'text-sm',
                    'font-semibold',
                    'text-white',
                    'shadow-sm',
                    'transition',
                    'hover:opacity-90',
                    'focus:outline-none',
                    'focus:ring-2',
                    'focus:ring-success/30',
                    'focus:ring-offset-2',
                ]);

                continue;
            }

            addTailwindClassesToElement($input, [
                'block',
                'w-full',
                'rounded-xl',
                'border-gray-25',
                'bg-white',
                'text-sm',
                'text-gray-90',
                'shadow-sm',
                'placeholder:text-gray-50',
                'focus:border-primary',
                'focus:ring-primary',
            ]);
        }
    }

    $selects = $xpath->query('.//select', $root);
    if ($selects) {
        foreach ($selects as $select) {
            if (!$select instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($select, [
                'block',
                'w-full',
                'rounded-xl',
                'border-gray-25',
                'bg-white',
                'text-sm',
                'text-gray-90',
                'shadow-sm',
                'focus:border-primary',
                'focus:ring-primary',
            ]);
        }
    }

    $textareas = $xpath->query('.//textarea', $root);
    if ($textareas) {
        foreach ($textareas as $textarea) {
            if (!$textarea instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($textarea, [
                'block',
                'w-full',
                'rounded-xl',
                'border-gray-25',
                'bg-white',
                'text-sm',
                'text-gray-90',
                'shadow-sm',
                'placeholder:text-gray-50',
                'focus:border-primary',
                'focus:ring-primary',
            ]);
        }
    }

    $buttons = $xpath->query('.//button');
    if ($buttons) {
        foreach ($buttons as $button) {
            if (!$button instanceof DOMElement) {
                continue;
            }

            $buttonClasses = [
                'inline-flex',
                'items-center',
                'justify-center',
                'gap-2',
                'rounded-xl',
                'px-4',
                'py-2.5',
                'text-sm',
                'font-semibold',
                'shadow-sm',
                'transition',
                'focus:outline-none',
                'focus:ring-2',
                'focus:ring-offset-2',
            ];

            $buttonClasses = array_merge($buttonClasses, [
                'bg-success',
                'text-white',
                'hover:opacity-90',
                'focus:ring-success/30',
            ]);

            addTailwindClassesToElement($button, $buttonClasses);
        }
    }

    $helpBlocks = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " help-block ")
            or contains(concat(" ", normalize-space(@class), " "), " form-control-feedback ")]',
        $root
    );
    if ($helpBlocks) {
        foreach ($helpBlocks as $helpBlock) {
            if (!$helpBlock instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($helpBlock, [
                'mt-3',
                'block',
                'text-sm',
                'text-gray-50',
            ]);
        }
    }

    $result = getElementInnerHtml($root);

    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    return $result;
}

/**
 * Return a translated plugin label with a safe fallback when the key is missing.
 */
function getPluginLabelWithFallback($plugin, string $key, string $fallback): string
{
    $value = (string) $plugin->get_lang($key);

    if ('' === trim($value) || $value === $key) {
        return $fallback;
    }

    return $value;
}

/**
 * Build the subscription frequency manager script.
 */
function buildSubscriptionFrequencyManagerScript(array $prefilledFrequencies, array $frequencyLabels): string
{
    $frequenciesJson = json_encode(
        array_values($prefilledFrequencies),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    $labelsJson = json_encode(
        $frequencyLabels,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if (false === $frequenciesJson) {
        $frequenciesJson = '[]';
    }

    if (false === $labelsJson) {
        $labelsJson = '{}';
    }

    $script = <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addButton = document.getElementById('subscription-add-frequency');
    const durationSelect = document.getElementById('subscription-frequency-duration');
    const priceInput = document.getElementById('subscription-frequency-price');
    const tableBody = document.getElementById('subscription-frequencies-body');
    const hiddenInputsContainer = document.getElementById('subscription-frequency-hidden-inputs');
    const form = hiddenInputsContainer ? hiddenInputsContainer.closest('form') : null;

    if (!form || !tableBody || !hiddenInputsContainer) {
        return;
    }

    const frequencyLabels = __LABELS_JSON__;
    let frequencies = __FREQUENCIES_JSON__;

    if (!Array.isArray(frequencies)) {
        frequencies = [];
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value);
        return div.innerHTML;
    }

    function renderFrequencies() {
        hiddenInputsContainer.innerHTML = '';

        if (!frequencies.length) {
            tableBody.innerHTML = `
                <tr id="subscription-empty-row">
                    <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-50">
                        No subscription periods added yet.
                    </td>
                </tr>
            `;
            return;
        }

        const rows = [];

        frequencies.forEach((item, index) => {
            const durationLabel = frequencyLabels[item.duration] || item.duration;
            const safeDurationLabel = escapeHtml(durationLabel);
            const safePrice = Number(item.price).toFixed(2);

            rows.push(`
                <tr class="align-middle">
                    <td class="px-4 py-4 text-sm font-medium text-gray-90">${safeDurationLabel}</td>
                    <td class="px-4 py-4 text-sm text-gray-90">${safePrice}</td>
                    <td class="px-4 py-4 text-right">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-danger px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger/30 focus:ring-offset-2"
                            data-remove-index="${index}"
                        >
                            <em class="fa fa-trash fa-fw"></em>
                            Remove
                        </button>
                    </td>
                </tr>
            `);

            const durationInput = document.createElement('input');
            durationInput.type = 'hidden';
            durationInput.name = `frequencies[${index}][duration]`;
            durationInput.value = String(item.duration);
            hiddenInputsContainer.appendChild(durationInput);

            const priceHiddenInput = document.createElement('input');
            priceHiddenInput.type = 'hidden';
            priceHiddenInput.name = `frequencies[${index}][price]`;
            priceHiddenInput.value = String(item.price);
            hiddenInputsContainer.appendChild(priceHiddenInput);
        });

        tableBody.innerHTML = rows.join('');
    }

    function addFrequency() {
        if (!durationSelect || !priceInput) {
            return;
        }

        const duration = parseInt(durationSelect.value, 10);
        const price = parseFloat(priceInput.value);

        if (!duration || Number.isNaN(duration)) {
            window.alert('Please select a subscription period.');
            durationSelect.focus();
            return;
        }

        if (!price || Number.isNaN(price) || price <= 0) {
            window.alert('Please enter a valid price greater than zero.');
            priceInput.focus();
            return;
        }

        const existingIndex = frequencies.findIndex((item) => Number(item.duration) === duration);

        if (existingIndex !== -1) {
            frequencies[existingIndex].price = price;
        } else {
            frequencies.push({
                duration: duration,
                price: price,
            });
        }

        renderFrequencies();

        durationSelect.value = '';
        priceInput.value = '';
        durationSelect.focus();
    }

    if (addButton) {
        addButton.addEventListener('click', function (event) {
            event.preventDefault();
            addFrequency();
        });
    }

    tableBody.addEventListener('click', function (event) {
        const target = event.target instanceof HTMLElement ? event.target.closest('[data-remove-index]') : null;
        if (!target) {
            return;
        }

        const index = parseInt(target.getAttribute('data-remove-index') || '', 10);
        if (Number.isNaN(index)) {
            return;
        }

        frequencies.splice(index, 1);
        renderFrequencies();
    });

    form.addEventListener('submit', function () {
        renderFrequencies();
    });

    renderFrequencies();
});
</script>
HTML;

    return str_replace(
        ['__LABELS_JSON__', '__FREQUENCIES_JSON__'],
        [$labelsJson, $frequenciesJson],
        $script
    );
}

api_protect_admin_script(true);

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$type = isset($_REQUEST['type']) ? (int) $_REQUEST['type'] : 0;

if (empty($id) || empty($type)) {
    api_not_allowed();
}

$queryString = 'id='.(int) $_REQUEST['id'].'&type='.(int) $_REQUEST['type'];

$editingCourse = BuyCoursesPlugin::PRODUCT_TYPE_COURSE === $type;
$editingSession = BuyCoursesPlugin::PRODUCT_TYPE_SESSION === $type;

$plugin = BuyCoursesPlugin::create();

$includeSession = 'true' === $plugin->get('include_sessions');

$entityManager = Database::getManager();
$currency = $plugin->getSelectedCurrency();

$currencyMissingMessage = getPluginLabelWithFallback(
    $plugin,
    'CurrencyIsNotConfigured',
    'Currency is not configured yet. Please configure the currency before creating a subscription.'
);

$frequencyMissingMessage = getPluginLabelWithFallback(
    $plugin,
    'FrequencyIsNotConfigured',
    'Subscription periods are not configured yet. Please configure at least one subscription period before creating a subscription.'
);

$subscriptionSaveErrorMessage = getPluginLabelWithFallback(
    $plugin,
    'SubscriptionSaveError',
    'The subscription could not be saved. Please review the data and try again.'
);

$subscriptionRequiresPeriodsMessage = getPluginLabelWithFallback(
    $plugin,
    'SubscriptionPeriodsRequired',
    'You must add at least one subscription period before saving.'
);

if (empty($currency)) {
    Display::addFlash(
        Display::return_message($currencyMissingMessage, 'error')
    );
}

$currencyIso = null;

$subscriptionsListUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscriptions_courses.php';
if ($editingSession) {
    $subscriptionsListUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscriptions_sessions.php';
}

$frequencyConfigUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/configure_frequency.php';
$backUrl = $subscriptionsListUrl;

if ($editingCourse) {
    $course = $entityManager->find(Course::class, $id);

    if (!$course) {
        api_not_allowed(true);
    }

    $courseItem = $plugin->getCourseForConfiguration($course, $currency);

    $currencyIso = $courseItem['currency'];
    $formDefaults = [
        'id' => $courseItem['course_id'],
        'type' => BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
        'tax_perc' => $courseItem['tax_perc'],
    ];
    $productLabelText = get_lang('Course');
    $productNameText = (string) $courseItem['course_title'];
} elseif ($editingSession) {
    if (!$includeSession) {
        api_not_allowed(true);
    }

    $session = $entityManager->find(Session::class, $id);

    if (!$session) {
        api_not_allowed(true);
    }

    $sessionItem = $plugin->getSessionForConfiguration($session, $currency);

    $currencyIso = $sessionItem['currency'];
    $formDefaults = [
        'id' => $session->getId(),
        'type' => BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
        'tax_perc' => $sessionItem['tax_perc'],
    ];
    $productLabelText = get_lang('Session');
    $productNameText = (string) $sessionItem['session_name'];
} else {
    api_not_allowed(true);
}

$globalSettingsParams = $plugin->getGlobalParameters();
$defaultGlobalTax = (int) ($globalSettingsParams['global_tax_perc'] ?? 0);

$frequencies = $plugin->getFrequencies();
$hasFrequencies = !empty($frequencies);

if (!$hasFrequencies) {
    Display::addFlash(
        Display::return_message($frequencyMissingMessage, 'error')
    );
}

$selectOptions = '<option value="">'.htmlspecialchars(get_lang('Select'), ENT_QUOTES, 'UTF-8').'</option>';
$frequencyLabels = [];

foreach ($frequencies as $key => $frequency) {
    $frequencyLabels[(int) $key] = (string) $frequency;
    $selectOptions .= '<option value="'.(int) $key.'">'.htmlspecialchars((string) $frequency, ENT_QUOTES, 'UTF-8').'</option>';
}

$prefilledFrequencies = [];
if (isset($_POST['frequencies']) && is_array($_POST['frequencies'])) {
    $prefilledFrequencies = normalizeSubscriptionFrequencies($_POST['frequencies']);
}

if (isset($_POST['tax_perc'])) {
    $formDefaults['tax_perc'] = $_POST['tax_perc'];
}

$form = new FormValidator('add_subscription', 'post', api_get_self().'?'.$queryString);

$form->addElement(
    'number',
    'tax_perc',
    [$plugin->get_lang('TaxPerc'), $plugin->get_lang('TaxPercDescription'), '%'],
    [
        'step' => 1,
        'placeholder' => $defaultGlobalTax.'% '.$plugin->get_lang('ByDefault'),
    ]
);

if ($hasFrequencies) {
    $form->addHtml(
        '
        <section class="rounded-3xl border border-gray-25 bg-gray-10 p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-90">'.$plugin->get_lang('FrequencyConfig').'</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-50">
                        Add one or more subscription periods with a price for each duration.
                    </p>
                </div>
                <a
                    href="'.htmlspecialchars($frequencyConfigUrl, ENT_QUOTES, 'UTF-8').'"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-primary/20 bg-white px-4 py-2.5 text-sm font-semibold text-primary transition hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <em class="fa fa-calendar-alt fa-fw"></em>
                    '.$plugin->get_lang('ConfigureSubscriptionsFrequencies').'
                </a>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
                <div class="rounded-2xl border border-gray-25 bg-white p-5 shadow-sm">
                    <div class="space-y-5">
                        <div>
                            <label for="subscription-frequency-duration" class="mb-3 block text-sm font-semibold text-gray-90">
                                '.$plugin->get_lang('Duration').'
                            </label>
                            <select
                                class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
                                id="subscription-frequency-duration"
                            >
                                '.$selectOptions.'
                            </select>
                        </div>

                        <div>
                            <label for="subscription-frequency-price" class="mb-3 block text-sm font-semibold text-gray-90">
                                '.$plugin->get_lang('Price').'
                            </label>
                            <div class="flex items-center gap-3">
                                <input
                                    class="block w-full rounded-xl border-gray-25 bg-white text-sm text-gray-90 shadow-sm placeholder:text-gray-50 focus:border-primary focus:ring-primary"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    id="subscription-frequency-price"
                                    placeholder="0.00"
                                >
                                <span class="shrink-0 text-sm font-semibold text-gray-50">'.htmlspecialchars((string) $currencyIso, ENT_QUOTES, 'UTF-8').'</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-1">
                            <button
                                id="subscription-add-frequency"
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                            >
                                <em class="fa fa-plus fa-fw"></em>
                                '.htmlspecialchars(get_lang('Add'), ENT_QUOTES, 'UTF-8').'
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
                    <div class="border-b border-gray-25 bg-gray-15 px-5 py-4">
                        <h3 class="text-base font-semibold text-gray-90">'.$plugin->get_lang('FrequencyConfig').'</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-25">
                            <thead class="bg-gray-15">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">'.$plugin->get_lang('Duration').'</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-50">'.$plugin->get_lang('Price').'</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-50">'.$plugin->get_lang('Actions').'</th>
                                </tr>
                            </thead>
                            <tbody id="subscription-frequencies-body" class="divide-y divide-gray-25 bg-white">
                                <tr id="subscription-empty-row">
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-50">
                                        No subscription periods added yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="subscription-frequency-hidden-inputs"></div>
        </section>
        '
    );
} else {
    $form->addHtml(
        '
        <section class="rounded-3xl border border-danger/20 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 text-danger">
                        <em class="fa fa-exclamation-triangle text-lg"></em>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-90">Subscription periods are not configured</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-90">
                            '.htmlspecialchars($frequencyMissingMessage, ENT_QUOTES, 'UTF-8').'
                        </p>
                    </div>
                </div>

                <a
                    href="'.htmlspecialchars($frequencyConfigUrl, ENT_QUOTES, 'UTF-8').'"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                >
                    <em class="fa fa-calendar-alt fa-fw"></em>
                    '.$plugin->get_lang('ConfigureSubscriptionsFrequencies').'
                </a>
            </div>
        </section>
        '
    );
}

$form->addHidden('type', (string) $type);
$form->addHidden('id', (string) $id);
$button = $form->addButtonSave(get_lang('Save'));

if (empty($currency) || !$hasFrequencies) {
    $button->setAttribute('disabled');
}

if ($form->validate()) {
    $formValues = $form->getSubmitValues();

    $productId = isset($formValues['id']) ? (int) $formValues['id'] : 0;
    $productType = isset($formValues['type']) ? (int) $formValues['type'] : 0;
    $currencyId = isset($currency['id']) ? (int) $currency['id'] : 0;
    $taxPerc = '' !== (string) ($formValues['tax_perc'] ?? '')
        ? (int) $formValues['tax_perc']
        : null;

    $rawFrequencies = isset($formValues['frequencies']) && is_array($formValues['frequencies'])
        ? $formValues['frequencies']
        : [];

    $normalizedFrequencies = normalizeSubscriptionFrequencies($rawFrequencies);
    $prefilledFrequencies = $normalizedFrequencies;
    $formDefaults['tax_perc'] = $formValues['tax_perc'] ?? $formDefaults['tax_perc'];

    if ($productId <= 0 || $productType <= 0 || $currencyId <= 0) {
        Display::addFlash(
            Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error')
        );
    } elseif (empty($normalizedFrequencies)) {
        Display::addFlash(
            Display::return_message($subscriptionRequiresPeriodsMessage, 'error')
        );
    } else {
        $currentSubscriptions = $plugin->getSubscriptions($productType, $productId);
        $existingDurations = [];

        if (is_array($currentSubscriptions)) {
            foreach ($currentSubscriptions as $currentSubscription) {
                $existingDurations[] = (int) ($currentSubscription['duration'] ?? 0);
            }
        }

        $duplicateDuration = null;

        foreach ($normalizedFrequencies as $frequency) {
            $duration = (int) ($frequency['duration'] ?? 0);

            if (in_array($duration, $existingDurations, true)) {
                $duplicateDuration = $duration;
                break;
            }
        }

        if (null !== $duplicateDuration) {
            Display::addFlash(
                Display::return_message(
                    $plugin->get_lang('SubscriptionAlreadyExists').' ('.$duplicateDuration.')',
                    'error'
                )
            );
        } else {
            $subscription = [
                'product_id' => $productId,
                'product_type' => $productType,
                'currency_id' => $currencyId,
                'tax_perc' => $taxPerc,
                'frequencies' => $normalizedFrequencies,
            ];

            $result = $plugin->addNewSubscription($subscription);

            if ($result) {
                header('Location: '.$subscriptionsListUrl);
                exit;
            }

            Display::addFlash(
                Display::return_message($subscriptionSaveErrorMessage, 'error')
            );
        }
    }
}

$form->setDefaults($formDefaults);

$templateName = $plugin->get_lang('SubscriptionAdd');
$interbreadcrumb[] = [
    'url' => $subscriptionsListUrl,
    'name' => get_lang('Configuration'),
];
$interbreadcrumb[] = [
    'url' => $subscriptionsListUrl,
    'name' => $plugin->get_lang('SubscriptionList'),
];

$formHtml = styleBuyCoursesFormHtml($form->returnForm());
$formHtml .= buildSubscriptionFrequencyManagerScript($prefilledFrequencies, $frequencyLabels);

$productLabel = htmlspecialchars($productLabelText, ENT_QUOTES, 'UTF-8');
$productName = htmlspecialchars($productNameText, ENT_QUOTES, 'UTF-8');
$currencyLabel = htmlspecialchars((string) ($currencyIso ?: get_lang('None')), ENT_QUOTES, 'UTF-8');

$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('page_title', $templateName);
$template->assign('back_url', $backUrl);
$template->assign('frequency_url', $frequencyConfigUrl);
$template->assign('items_form', $formHtml);
$template->assign('currencyIso', $currencyIso);
$template->assign('product_label', $productLabel);
$template->assign('product_name', $productName);
$template->assign('currency_label', $currencyLabel);
$template->assign('has_frequencies', $hasFrequencies);
$template->assign('has_currency', !empty($currency));
$template->assign('frequency_missing_message', $frequencyMissingMessage);

$content = $template->fetch('BuyCourses/view/subscription_add.tpl');
$template->assign('content', $content);

$template->display_one_col_template();
