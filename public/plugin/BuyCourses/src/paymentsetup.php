<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * Configuration page for payment methods for the Buy Courses plugin.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

/**
 * Add classes to an element without removing the existing ones.
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

    $hrs = $xpath->query('.//hr', $root);
    if ($hrs) {
        foreach ($hrs as $hr) {
            if (!$hr instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($hr, ['my-6', 'border-0', 'border-t', 'border-gray-25']);
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
                'space-y-3',
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
                'mb-2',
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
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-7 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-8 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-10 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-offset-2 ")]',
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
                    'rounded',
                    'border-gray-25',
                    'text-primary',
                    'focus:ring-primary',
                ]);

                continue;
            }

            if (in_array($type, ['submit', 'button'], true)) {
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

                $isPrimaryAction = false !== stripos((string) $input->getAttribute('class'), 'save')
                    || false !== stripos((string) $input->getAttribute('value'), 'save')
                    || false !== stripos((string) $input->getAttribute('value'), 'add')
                    || false !== stripos((string) $input->getAttribute('value'), 'create');

                if ($isPrimaryAction) {
                    $buttonClasses = array_merge($buttonClasses, [
                        'bg-primary',
                        'text-white',
                        'hover:opacity-90',
                        'focus:ring-primary/30',
                    ]);
                } else {
                    $buttonClasses = array_merge($buttonClasses, [
                        'bg-secondary',
                        'text-white',
                        'hover:opacity-90',
                        'focus:ring-secondary/30',
                    ]);
                }

                addTailwindClassesToElement($input, $buttonClasses);

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

    $buttons = $xpath->query('.//button', $root);
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

            $isPrimaryAction = 'submit' === strtolower((string) $button->getAttribute('type'))
                || false !== stripos((string) $button->getAttribute('class'), 'save')
                || false !== stripos((string) $button->textContent, 'save')
                || false !== stripos((string) $button->textContent, 'add')
                || false !== stripos((string) $button->textContent, 'create');

            if ($isPrimaryAction) {
                $buttonClasses = array_merge($buttonClasses, [
                    'bg-primary',
                    'text-white',
                    'hover:opacity-90',
                    'focus:ring-primary/30',
                ]);
            } else {
                $buttonClasses = array_merge($buttonClasses, [
                    'bg-secondary',
                    'text-white',
                    'hover:opacity-90',
                    'focus:ring-secondary/30',
                ]);
            }

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
                'mt-2',
                'block',
                'text-sm',
                'text-gray-50',
            ]);
        }
    }

    $alerts = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " alert ")]', $root);
    if ($alerts) {
        foreach ($alerts as $alert) {
            if (!$alert instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($alert, [
                'rounded-2xl',
                'border',
                'px-4',
                'py-3',
                'text-sm',
            ]);
        }
    }

    $checkboxContainers = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " checkbox ")]',
        $root
    );
    if ($checkboxContainers) {
        foreach ($checkboxContainers as $checkboxContainer) {
            if (!$checkboxContainer instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($checkboxContainer, [
                'flex',
                'items-center',
                'gap-3',
            ]);
        }
    }

    $result = getElementInnerHtml($root);

    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    return $result;
}

api_protect_admin_script(true);

$plugin = BuyCoursesPlugin::create();

$paypalEnable = 'true' === $plugin->get('paypal_enable');
$transferEnable = 'true' === $plugin->get('transfer_enable');
$tpvRedsysEnable = 'true' === $plugin->get('tpv_redsys_enable');
$commissionsEnable = 'true' === $plugin->get('commissions_enable');
$culqiEnable = 'true' === $plugin->get('culqi_enable');
$stripeEnable = 'true' === $plugin->get('stripe_enable');
$cecabankEnable = 'true' === $plugin->get('cecabank_enable');
$taxEnable = 'true' === $plugin->get('tax_enable');
$invoicingEnable = 'true' === $plugin->get('invoicing_enable');

if (isset($_GET['action'], $_GET['id']) && 'delete_taccount' === $_GET['action']) {
    $transferAccountId = is_scalar($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($transferAccountId <= 0) {
        Display::addFlash(
            Display::return_message(get_lang('InvalidId'), 'error')
        );

        header('Location: '.api_get_self());
        exit;
    }

    try {
        $plugin->deleteTransferAccount($transferAccountId);

        Display::addFlash(
            Display::return_message(get_lang('Deleted'), 'success')
        );
    } catch (Exception $e) {
        Display::addFlash(
            Display::return_message($e->getMessage(), 'error')
        );
    }

    header('Location: '.api_get_self());
    exit;
}

$globalSettingForm = new FormValidator('currency');

if ($globalSettingForm->validate()) {
    $globalSettingFormValues = $globalSettingForm->getSubmitValues();

    try {
        $plugin->saveCurrency((int) $globalSettingFormValues['currency']);

        unset($globalSettingFormValues['currency']);
        $plugin->saveGlobalParameters($globalSettingFormValues);

        Display::addFlash(
            Display::return_message(get_lang('Saved'), 'success')
        );
    } catch (Exception $e) {
        Display::addFlash(
            Display::return_message(get_lang($e->getMessage()), 'error')
        );
    }

    header('Location:'.api_get_self());

    exit;
}

$currencies = $plugin->getCurrencies();
$selectedCurrencyLabel = get_lang('None');

$currencySelect = $globalSettingForm->addSelect(
    'currency',
    [
        $plugin->get_lang('CurrencyType'),
        $plugin->get_lang('InfoCurrency'),
    ],
    [get_lang('Select')]
);

foreach ($currencies as $currency) {
    $currencyText = implode(
        ' => ',
        [
            $currency['country_name'],
            $currency['iso_code'],
        ]
    );
    $currencyValue = $currency['id'];

    $currencySelect->addOption($currencyText, $currencyValue);

    if (!empty($currency['status'])) {
        $currencySelect->setSelected($currencyValue);
        $selectedCurrencyLabel = $currencyText;
    }
}

$globalSettingForm->addHtmlEditor(
    'terms_and_conditions',
    [
        $plugin->get_lang('TermsAndConditions'),
        $plugin->get_lang('WriteHereTheTermsAndConditionsOfYourECommerce'),
    ],
    false,
    false,
    ['ToolbarSet' => 'Minimal']
);

$globalSettingForm->addElement(
    'text',
    'sale_email',
    $plugin->get_lang('SaleEmail')
);

if ($taxEnable) {
    $globalSettingForm->addHtml('<hr/>');

    $globalSettingForm->addElement(
        'number',
        'global_tax_perc',
        [$plugin->get_lang('GlobalTaxPerc'), $plugin->get_lang('GlobalTaxPercDescription'), '%'],
        ['step' => 1]
    );

    $taxAppliesTo = $plugin->getTaxAppliesTo();

    $taxTypeSelect = $globalSettingForm->addSelect(
        'tax_applies_to',
        $plugin->get_lang('TaxAppliesTo'),
        [get_lang('Select')]
    );

    foreach ($taxAppliesTo as $key => $value) {
        $taxTypeSelect->addOption($value, $key);
    }

    $globalSettingForm->addElement(
        'text',
        'tax_name',
        $plugin->get_lang('TaxNameCustom'),
        ['placeholder' => $plugin->get_lang('TaxNameExamples')]
    );
}

if ($invoicingEnable) {
    $globalSettingForm->addHtml('<hr/>');

    $globalSettingForm->addElement(
        'text',
        'seller_name',
        $plugin->get_lang('SellerName')
    );

    $globalSettingForm->addElement(
        'text',
        'seller_id',
        $plugin->get_lang('SellerId')
    );

    $globalSettingForm->addElement(
        'text',
        'seller_address',
        $plugin->get_lang('SellerAddress')
    );

    $globalSettingForm->addElement(
        'text',
        'seller_email',
        $plugin->get_lang('SellerEmail')
    );

    $globalSettingForm->addElement(
        'number',
        'next_number_invoice',
        [$plugin->get_lang('NextNumberInvoice'), $plugin->get_lang('NextNumberInvoiceDescription')],
        ['step' => 1]
    );

    $globalSettingForm->addElement(
        'text',
        'invoice_series',
        [$plugin->get_lang('InvoiceSeries'), $plugin->get_lang('InvoiceSeriesDescription')]
    );
}

$globalSettingForm->addButtonSave(get_lang('Save'));
$globalSettingForm->setDefaults($plugin->getGlobalParameters());

$termsAndConditionsForm = new FormValidator('termsconditions');

$paypalForm = new FormValidator('paypal');

if ($paypalForm->validate()) {
    $paypalFormValues = $paypalForm->getSubmitValues();

    try {
        $plugin->savePaypalParams($paypalFormValues);

        $message = Display::return_message(get_lang('Saved'), 'success');
    } catch (Exception $e) {
        $message = Display::return_message($e->getMessage(), 'error');
    }

    Display::addFlash($message);

    header('Location:'.api_get_self());

    exit;
}

$paypalForm->addText(
    'username',
    $plugin->get_lang('ApiUsername'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$paypalForm->addText(
    'password',
    $plugin->get_lang('ApiPassword'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$paypalForm->addText(
    'signature',
    $plugin->get_lang('ApiSignature'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$paypalForm->addCheckBox('sandbox', null, $plugin->get_lang('Sandbox'));
$paypalForm->addButtonSave(get_lang('Save'));
$paypalForm->setDefaults($plugin->getPaypalParams());

$htmlTpvRedsys = Display::return_message($plugin->get_lang('NotFindRedsysFile'), 'warning', false);

if (file_exists(api_get_path(SYS_PLUGIN_PATH).'BuyCourses/resources/apiRedsys.php')) {
    $tpvRedsysForm = new FormValidator('tpv_redsys');
    $tpvRedsysForm->addHtml(
        Display::return_message($plugin->get_lang('InfoTpvRedsysApiCredentials'), 'info', false)
    );

    if ($tpvRedsysForm->validate()) {
        $tpvRedsysFormValues = $tpvRedsysForm->getSubmitValues();

        try {
            $plugin->saveTpvRedsysParams($tpvRedsysFormValues);

            $message = Display::return_message(get_lang('Saved'), 'success');
        } catch (Exception $e) {
            $message = Display::return_message($e->getMessage(), 'error');
        }

        Display::addFlash($message);

        header('Location:'.api_get_self());

        exit;
    }

    $tpvRedsysForm->addText(
        'merchantcode',
        [$plugin->get_lang('DS_MERCHANT_MERCHANTCODE'), 'DS_MERCHANT_MERCHANTCODE'],
        false,
        ['cols-size' => [3, 8, 1]]
    );
    $tpvRedsysForm->addText(
        'terminal',
        [$plugin->get_lang('DS_MERCHANT_TERMINAL'), 'DS_MERCHANT_TERMINAL'],
        false,
        ['cols-size' => [3, 8, 1]]
    );
    $tpvRedsysForm->addText(
        'currency',
        [$plugin->get_lang('DS_MERCHANT_CURRENCY'), 'DS_MERCHANT_CURRENCY'],
        false,
        ['cols-size' => [3, 8, 1]]
    );
    $tpvRedsysForm->addText(
        'kc',
        $plugin->get_lang('kc'),
        false,
        ['cols-size' => [3, 8, 1]]
    );
    $tpvRedsysForm->addText(
        'url_redsys',
        $plugin->get_lang('url_redsys'),
        false,
        ['cols-size' => [3, 8, 1]]
    );
    $tpvRedsysForm->addText(
        'url_redsys_sandbox',
        $plugin->get_lang('url_redsys_sandbox'),
        false,
        ['cols-size' => [3, 8, 1]]
    );
    $tpvRedsysForm->addCheckBox('sandbox', null, $plugin->get_lang('Sandbox'));
    $tpvRedsysForm->addButtonSave(get_lang('Save'));
    $tpvRedsysForm->setDefaults($plugin->getTpvRedsysParams());

    $htmlTpvRedsys = $tpvRedsysForm->returnForm();
}

$commissionForm = new FormValidator('commissions');

if ($commissionForm->validate()) {
    $commissionFormValues = $commissionForm->getSubmitValues();

    $plugin->updateCommission($commissionFormValues);

    Display::addFlash(
        Display::return_message(get_lang('Saved'), 'success')
    );

    header('Location:'.api_get_self());

    exit;
}

$commissionForm->addElement(
    'number',
    'commission',
    [$plugin->get_lang('Commission'), null, '%'],
    [
        'step' => 1,
        'min' => 0,
        'max' => 100,
        'placeholder' => '0',
        'inputmode' => 'numeric',
        'style' => 'max-width: 6rem;',
        'class' => 'js-buycourses-commission-input',
        'cols-size' => [3, 3, 1],
    ]
);
$commissionForm->addButtonSave(get_lang('Save'));
$commissionForm->setDefaults($plugin->getPlatformCommission());

$transferForm = new FormValidator('transfer_account');

if ($transferForm->validate()) {
    $transferFormValues = $transferForm->getSubmitValues();

    try {
        $plugin->saveTransferAccount($transferFormValues);

        $message = Display::return_message(get_lang('Saved'), 'success');
    } catch (\Doctrine\DBAL\Exception $e) {
        $message = Display::return_message($e->getMessage(), 'error');
    }

    Display::addFlash($message);

    header('Location:'.api_get_self());

    exit;
}

$transferForm->addText(
    'tname',
    get_lang('Name'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$transferForm->addText(
    'taccount',
    $plugin->get_lang('BankAccount'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$transferForm->addText(
    'tswift',
    [$plugin->get_lang('SWIFT'), $plugin->get_lang('SWIFT_help')],
    false,
    ['cols-size' => [3, 8, 1]]
);
$transferForm->addButtonCreate(get_lang('Add'));

$transferAccounts = $plugin->getTransferAccounts();

$transferInfoForm = new FormValidator('transfer_info');

if ($transferInfoForm->validate()) {
    $transferInfoFormValues = $transferInfoForm->getSubmitValues();

    try {
        $plugin->saveTransferInfoEmail($transferInfoFormValues);

        $message = Display::return_message(get_lang('Saved'), 'success');
    } catch (Exception $e) {
        $message = Display::return_message($e->getMessage(), 'error');
    }

    Display::addFlash($message);

    header('Location:'.api_get_self());

    exit;
}

$transferInfoForm->addHtmlEditor(
    'tinfo_email_extra',
    $plugin->get_lang('InfoEmailExtra'),
    false,
    false,
    ['ToolbarSet' => 'Minimal']
);
$transferInfoForm->addButtonSave(get_lang('Save'));
$transferInfoForm->setDefaults($plugin->getTransferInfoExtra());

$culqiForm = new FormValidator('culqi_config');

if ($culqiForm->validate()) {
    $culqiFormValues = $culqiForm->getSubmitValues();

    $plugin->saveCulqiParameters($culqiFormValues);

    Display::addFlash(
        Display::return_message(get_lang('Saved'), 'success')
    );

    header('Location:'.api_get_self());

    exit;
}

$culqiForm->addText(
    'commerce_code',
    $plugin->get_lang('CommerceCode'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$culqiForm->addText(
    'api_key',
    $plugin->get_lang('ApiPassword'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$culqiForm->addCheckBox('integration', null, $plugin->get_lang('Sandbox'));
$culqiForm->addButtonSave(get_lang('Save'));
$culqiForm->setDefaults($plugin->getCulqiParams());

$stripeForm = new FormValidator('stripe_config');

if ($stripeForm->validate()) {
    $stripeFormValues = $stripeForm->getSubmitValues();

    try {
        $plugin->saveStripeParameters($stripeFormValues);

        $message = Display::return_message(get_lang('Saved'), 'success');
    } catch (Exception $e) {
        $message = Display::return_message($e->getMessage(), 'error');
    }

    Display::addFlash($message);

    header('Location:'.api_get_self());

    exit;
}

$stripeForm->addText(
    'account_id',
    $plugin->get_lang('StripeAccountId'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$stripeForm->addText(
    'secret_key',
    $plugin->get_lang('StripeSecret'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$stripeForm->addText(
    'endpoint_secret',
    $plugin->get_lang('StripeEndpointSecret'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$stripeForm->addButtonSave(get_lang('Save'));
$stripeForm->setDefaults($plugin->getStripeParams());

$cecabankForm = new FormValidator('cecabank_config');

if ($cecabankForm->validate()) {
    $cecabankFormValues = $cecabankForm->getSubmitValues();

    $plugin->saveCecabankParameters($cecabankFormValues);

    Display::addFlash(
        Display::return_message(get_lang('Saved'), 'success')
    );

    header('Location:'.api_get_self());

    exit;
}

$cecabankForm->addText(
    'crypto_key',
    $plugin->get_lang('CecaSecret'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$cecabankForm->addText(
    'url',
    $plugin->get_lang('CecaUrl'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$cecabankForm->addText(
    'merchant_id',
    $plugin->get_lang('CecaMerchanId'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$cecabankForm->addText(
    'acquirer_bin',
    $plugin->get_lang('CecaAcquirerId'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$cecabankForm->addText(
    'terminal_id',
    $plugin->get_lang('CecaTerminalId'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$cecabankForm->addText(
    'cypher',
    $plugin->get_lang('CecaCypher'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$cecabankForm->addText(
    'exponent',
    $plugin->get_lang('CecaExponent'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$cecabankForm->addText(
    'supported_payment',
    $plugin->get_lang('CecaSupportedPayment'),
    false,
    ['cols-size' => [3, 8, 1]]
);
$cecabankForm->addButtonSave(get_lang('Save'));
$cecabankForm->setDefaults($plugin->getCecabankParams());

$enabledPaymentMethods = array_filter([
    'PayPal' => $paypalEnable,
    'Bank transfer' => $transferEnable,
    'TPV Redsys' => $tpvRedsysEnable,
    'Culqi' => $culqiEnable,
    'Stripe' => $stripeEnable,
    'Cecabank' => $cecabankEnable,
]);

$enabledPaymentMethodLabels = array_keys($enabledPaymentMethods);

$pluginIndexUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$pluginSettingsUrl = api_get_path(WEB_CODE_PATH).'admin/configure_plugin.php?plugin=BuyCourses';
$adminPluginsUrl = api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins';
$deleteTransferBaseUrl = api_get_self();

$interbreadcrumb[] = [
    'name' => get_lang('Administration'),
    'url' => api_get_path(WEB_PATH).'admin',
];
$interbreadcrumb[] = [
    'name' => get_lang('Plugins'),
    'url' => $adminPluginsUrl,
];
$interbreadcrumb[] = [
    'url' => $pluginIndexUrl,
    'name' => $plugin->get_lang('plugin_title'),
];

$templateName = $plugin->get_lang('PaymentsConfiguration');
$tpl = new Template($templateName);
$tpl->assign('header', $templateName);

$tpl->assign('page_title', $templateName);
$tpl->assign('plugin_title', $plugin->get_lang('plugin_title'));
$tpl->assign('plugin_index_url', $pluginIndexUrl);
$tpl->assign('plugin_settings_url', $pluginSettingsUrl);
$tpl->assign('delete_transfer_base_url', $deleteTransferBaseUrl);

$tpl->assign('selected_currency_label', $selectedCurrencyLabel);
$tpl->assign('tax_enable', $taxEnable);
$tpl->assign('invoicing_enable', $invoicingEnable);
$tpl->assign('enabled_payment_method_labels', $enabledPaymentMethodLabels);
$tpl->assign('enabled_payment_method_count', count($enabledPaymentMethodLabels));

$tpl->assign('global_config_form', $globalSettingForm->returnForm());
$tpl->assign('paypal_form', styleBuyCoursesFormHtml($paypalForm->returnForm()));
$tpl->assign('commission_form', styleBuyCoursesFormHtml($commissionForm->returnForm()));
$tpl->assign('transfer_form', styleBuyCoursesFormHtml($transferForm->returnForm()));
$tpl->assign('transfer_info_form', $transferInfoForm->returnForm());
$tpl->assign('culqi_form', styleBuyCoursesFormHtml($culqiForm->returnForm()));
$tpl->assign('tpv_redsys_form', styleBuyCoursesFormHtml($htmlTpvRedsys));
$tpl->assign('stripe_form', styleBuyCoursesFormHtml($stripeForm->returnForm()));
$tpl->assign('cecabank_form', styleBuyCoursesFormHtml($cecabankForm->returnForm()));

$tpl->assign('transfer_accounts', $transferAccounts);

$tpl->assign('paypal_enable', $paypalEnable);
$tpl->assign('commissions_enable', $commissionsEnable);
$tpl->assign('transfer_enable', $transferEnable);
$tpl->assign('culqi_enable', $culqiEnable);
$tpl->assign('tpv_redsys_enable', $tpvRedsysEnable);
$tpl->assign('stripe_enable', $stripeEnable);
$tpl->assign('cecabank_enable', $cecabankEnable);

$content = $tpl->fetch('BuyCourses/view/paymentsetup.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
