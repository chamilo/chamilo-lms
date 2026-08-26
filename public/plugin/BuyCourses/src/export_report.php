<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

// Initialization.
$cidReset = true;

require_once '../config.php';

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
                    'rounded',
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
                    'bg-primary',
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
                    'focus:ring-primary/30',
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

    $buttons = $xpath->query('.//button');
    if ($buttons) {
        foreach ($buttons as $button) {
            if (!$button instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($button, [
                'inline-flex',
                'items-center',
                'justify-center',
                'gap-2',
                'rounded-xl',
                'bg-primary',
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
                'focus:ring-primary/30',
                'focus:ring-offset-2',
            ]);
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

    $result = getElementInnerHtml($root);

    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    return $result;
}

api_protect_admin_script();

function normalizeBuyCoursesExportDate(string $value, bool $endOfDay): string
{
    $value = trim($value);
    $timestamp = '' === $value ? false : strtotime($value);

    if (false === $timestamp) {
        $timestamp = $endOfDay ? time() : strtotime('-90 days');
    }

    $hasTime = (bool) preg_match('/\d{1,2}:\d{2}/', $value);
    if (!$hasTime) {
        return date($endOfDay ? 'Y-m-d 23:59:59' : 'Y-m-d 00:00:00', $timestamp);
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function getBuyCoursesReportStatusLabel(array $statuses, int $status): string
{
    return (string) ($statuses[$status] ?? $status);
}

function getBuyCoursesReportProductLabel(array $productTypes, int $productType): string
{
    return (string) ($productTypes[$productType] ?? $productType);
}

function getBuyCoursesReportPaymentLabel(array $paymentTypes, int $paymentType): string
{
    return (string) ($paymentTypes[$paymentType] ?? $paymentType);
}

function appendBuyCoursesSearchCondition(array &$conditions, string $search, array $columns): void
{
    if ('' === $search) {
        return;
    }

    $escapedSearch = Database::escape_string($search);
    $like = "'%$escapedSearch%'";
    $searchConditions = [];

    foreach ($columns as $column) {
        $searchConditions[] = "$column LIKE $like";
    }

    $conditions[] = '('.implode(' OR ', $searchConditions).')';
}

function getBuyCoursesExportRows(
    BuyCoursesPlugin $plugin,
    string $source,
    string $dateStart,
    string $dateEnd,
    string $selectedStatus,
    string $search
): array {
    $rows = [];
    $paymentTypes = $plugin->getPaymentTypes();
    $productTypes = $plugin->getProductTypes();
    $saleStatuses = $plugin->getSaleStatuses();
    $serviceStatuses = $plugin->getServiceSaleStatuses();
    $serviceTypes = $plugin->getServiceTypes();

    if ('all' === $source || 'course_session' === $source) {
        $saleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SALE);
        $currencyTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $couponSaleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_COUPON_SALE);
        $couponTable = Database::get_main_table(BuyCoursesPlugin::TABLE_COUPON);

        $conditions = [
            "s.date BETWEEN '".Database::escape_string($dateStart)."' AND '".Database::escape_string($dateEnd)."'",
        ];

        if ('all' !== $selectedStatus) {
            $conditions[] = 's.status = '.(int) $selectedStatus;
        }

        appendBuyCoursesSearchCondition($conditions, $search, [
            's.reference',
            's.product_name',
            'u.username',
            'u.firstname',
            'u.lastname',
            'u.email',
            'coupon.code',
        ]);

        $sql = "
            SELECT
                s.id,
                s.reference,
                s.date AS order_date,
                s.status,
                s.price,
                s.tax_amount,
                s.discount_amount,
                s.payment_type,
                s.product_type,
                s.product_name,
                c.iso_code,
                u.firstname,
                u.lastname,
                u.username,
                u.email,
                coupon.code AS coupon_code,
                NULL AS gateway_subscription_id
            FROM $saleTable s
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
            LEFT JOIN $couponSaleTable coupon_sale ON coupon_sale.sale_id = s.id
            LEFT JOIN $couponTable coupon ON coupon.id = coupon_sale.coupon_id
            WHERE ".implode(' AND ', $conditions)."
        ";

        $result = Database::query($sql);
        while ($item = Database::fetch_assoc($result)) {
            $rows[] = [
                'source' => 'course_session',
                'source_label' => $plugin->get_lang('CourseSessionSales'),
                'id' => (int) $item['id'],
                'reference' => (string) $item['reference'],
                'status' => (int) $item['status'],
                'status_label' => getBuyCoursesReportStatusLabel($saleStatuses, (int) $item['status']),
                'order_date' => (string) $item['order_date'],
                'order_date_label' => api_get_local_time((string) $item['order_date']),
                'price' => (float) $item['price'],
                'price_label' => $plugin->getPriceWithCurrencyFromIsoCode((float) $item['price'], (string) $item['iso_code']),
                'tax_amount' => (float) ($item['tax_amount'] ?? 0),
                'tax_amount_label' => $plugin->getPriceWithCurrencyFromIsoCode((float) ($item['tax_amount'] ?? 0), (string) $item['iso_code']),
                'discount_amount' => (float) ($item['discount_amount'] ?? 0),
                'discount_amount_label' => $plugin->getPriceWithCurrencyFromIsoCode((float) ($item['discount_amount'] ?? 0), (string) $item['iso_code']),
                'payment_type_label' => getBuyCoursesReportPaymentLabel($paymentTypes, (int) $item['payment_type']),
                'product_type_label' => getBuyCoursesReportProductLabel($productTypes, (int) $item['product_type']),
                'product_name' => (string) $item['product_name'],
                'complete_user_name' => api_get_person_name((string) $item['firstname'], (string) $item['lastname']),
                'username' => (string) $item['username'],
                'email' => (string) $item['email'],
                'coupon_code' => (string) ($item['coupon_code'] ?? ''),
                'gateway_subscription_id' => '',
            ];
        }
    }

    if ('all' === $source || 'service' === $source) {
        $serviceSaleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SERVICES_SALE);
        $serviceTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SERVICES);
        $currencyTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $couponServiceSaleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_COUPON_SERVICE_SALE);
        $couponTable = Database::get_main_table(BuyCoursesPlugin::TABLE_COUPON);

        $conditions = [
            "ss.buy_date BETWEEN '".Database::escape_string($dateStart)."' AND '".Database::escape_string($dateEnd)."'",
        ];

        if ('all' !== $selectedStatus) {
            $conditions[] = 'ss.status = '.(int) $selectedStatus;
        }

        appendBuyCoursesSearchCondition($conditions, $search, [
            'ss.reference',
            'service.name',
            'u.username',
            'u.firstname',
            'u.lastname',
            'u.email',
            'coupon.code',
            'ss.gateway_subscription_id',
        ]);

        $sql = "
            SELECT
                ss.id,
                ss.reference,
                ss.buy_date AS order_date,
                ss.status,
                ss.price,
                ss.tax_amount,
                ss.discount_amount,
                ss.payment_type,
                ss.gateway_subscription_id,
                service.applies_to AS product_type,
                service.name AS product_name,
                c.iso_code,
                u.firstname,
                u.lastname,
                u.username,
                u.email,
                coupon.code AS coupon_code
            FROM $serviceSaleTable ss
            INNER JOIN $serviceTable service ON ss.service_id = service.id
            INNER JOIN $currencyTable c ON ss.currency_id = c.id
            INNER JOIN $userTable u ON ss.buyer_id = u.id
            LEFT JOIN $couponServiceSaleTable coupon_sale ON coupon_sale.service_sale_id = ss.id
            LEFT JOIN $couponTable coupon ON coupon.id = coupon_sale.coupon_id
            WHERE ".implode(' AND ', $conditions)."
        ";

        $result = Database::query($sql);
        while ($item = Database::fetch_assoc($result)) {
            $rows[] = [
                'source' => 'service',
                'source_label' => $plugin->get_lang('ServiceSales'),
                'id' => (int) $item['id'],
                'reference' => (string) $item['reference'],
                'status' => (int) $item['status'],
                'status_label' => getBuyCoursesReportStatusLabel($serviceStatuses, (int) $item['status']),
                'order_date' => (string) $item['order_date'],
                'order_date_label' => api_get_local_time((string) $item['order_date']),
                'price' => (float) $item['price'],
                'price_label' => $plugin->getPriceWithCurrencyFromIsoCode((float) $item['price'], (string) $item['iso_code']),
                'tax_amount' => (float) ($item['tax_amount'] ?? 0),
                'tax_amount_label' => $plugin->getPriceWithCurrencyFromIsoCode((float) ($item['tax_amount'] ?? 0), (string) $item['iso_code']),
                'discount_amount' => (float) ($item['discount_amount'] ?? 0),
                'discount_amount_label' => $plugin->getPriceWithCurrencyFromIsoCode((float) ($item['discount_amount'] ?? 0), (string) $item['iso_code']),
                'payment_type_label' => getBuyCoursesReportPaymentLabel($paymentTypes, (int) $item['payment_type']),
                'product_type_label' => (string) ($serviceTypes[(int) $item['product_type']] ?? $item['product_type']),
                'product_name' => (string) $item['product_name'],
                'complete_user_name' => api_get_person_name((string) $item['firstname'], (string) $item['lastname']),
                'username' => (string) $item['username'],
                'email' => (string) $item['email'],
                'coupon_code' => (string) ($item['coupon_code'] ?? ''),
                'gateway_subscription_id' => (string) ($item['gateway_subscription_id'] ?? ''),
            ];
        }
    }

    if ('all' === $source || 'subscription' === $source) {
        $subscriptionSaleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SUBSCRIPTION_SALE);
        $currencyTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $couponSubscriptionSaleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_COUPON_SUBSCRIPTION_SALE);
        $couponTable = Database::get_main_table(BuyCoursesPlugin::TABLE_COUPON);

        $conditions = [
            "s.date BETWEEN '".Database::escape_string($dateStart)."' AND '".Database::escape_string($dateEnd)."'",
        ];

        if ('all' !== $selectedStatus) {
            $conditions[] = 's.status = '.(int) $selectedStatus;
        }

        appendBuyCoursesSearchCondition($conditions, $search, [
            's.reference',
            's.product_name',
            'u.username',
            'u.firstname',
            'u.lastname',
            'u.email',
            'coupon.code',
        ]);

        $sql = "
            SELECT
                s.id,
                s.reference,
                s.date AS order_date,
                s.status,
                s.price,
                s.tax_amount,
                s.discount_amount,
                s.payment_type,
                s.product_type,
                s.product_name,
                c.iso_code,
                u.firstname,
                u.lastname,
                u.username,
                u.email,
                coupon.code AS coupon_code,
                NULL AS gateway_subscription_id
            FROM $subscriptionSaleTable s
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
            LEFT JOIN $couponSubscriptionSaleTable coupon_sale ON coupon_sale.sale_id = s.id
            LEFT JOIN $couponTable coupon ON coupon.id = coupon_sale.coupon_id
            WHERE ".implode(' AND ', $conditions)."
        ";

        $result = Database::query($sql);
        while ($item = Database::fetch_assoc($result)) {
            $rows[] = [
                'source' => 'subscription',
                'source_label' => $plugin->get_lang('SubscriptionSales'),
                'id' => (int) $item['id'],
                'reference' => (string) $item['reference'],
                'status' => (int) $item['status'],
                'status_label' => getBuyCoursesReportStatusLabel($saleStatuses, (int) $item['status']),
                'order_date' => (string) $item['order_date'],
                'order_date_label' => api_get_local_time((string) $item['order_date']),
                'price' => (float) $item['price'],
                'price_label' => $plugin->getPriceWithCurrencyFromIsoCode((float) $item['price'], (string) $item['iso_code']),
                'tax_amount' => (float) ($item['tax_amount'] ?? 0),
                'tax_amount_label' => $plugin->getPriceWithCurrencyFromIsoCode((float) ($item['tax_amount'] ?? 0), (string) $item['iso_code']),
                'discount_amount' => (float) ($item['discount_amount'] ?? 0),
                'discount_amount_label' => $plugin->getPriceWithCurrencyFromIsoCode((float) ($item['discount_amount'] ?? 0), (string) $item['iso_code']),
                'payment_type_label' => getBuyCoursesReportPaymentLabel($paymentTypes, (int) $item['payment_type']),
                'product_type_label' => getBuyCoursesReportProductLabel($productTypes, (int) $item['product_type']),
                'product_name' => (string) $item['product_name'],
                'complete_user_name' => api_get_person_name((string) $item['firstname'], (string) $item['lastname']),
                'username' => (string) $item['username'],
                'email' => (string) $item['email'],
                'coupon_code' => (string) ($item['coupon_code'] ?? ''),
                'gateway_subscription_id' => '',
            ];
        }
    }

    usort($rows, static function (array $first, array $second): int {
        return strcmp($second['order_date'], $first['order_date']);
    });

    return $rows;
}

function buildBuyCoursesXlsRows(BuyCoursesPlugin $plugin, array $rows): array
{
    $exportRows = [[
        get_lang('Number'),
        $plugin->get_lang('SaleSource'),
        $plugin->get_lang('OrderReference'),
        $plugin->get_lang('OrderStatus'),
        $plugin->get_lang('OrderDate'),
        $plugin->get_lang('PaymentMethod'),
        $plugin->get_lang('SalePrice'),
        $plugin->get_lang('VAT'),
        $plugin->get_lang('CouponDiscount'),
        $plugin->get_lang('Coupon'),
        $plugin->get_lang('ProductType'),
        $plugin->get_lang('ProductName'),
        get_lang('Name'),
        get_lang('UserName'),
        get_lang('Email'),
        $plugin->get_lang('GatewaySubscription'),
    ]];

    foreach ($rows as $row) {
        $exportRows[] = [
            $row['id'],
            $row['source_label'],
            $row['reference'],
            $row['status_label'],
            $row['order_date_label'],
            $row['payment_type_label'],
            $row['price_label'],
            $row['tax_amount_label'],
            $row['discount_amount_label'],
            $row['coupon_code'],
            $row['product_type_label'],
            $row['product_name'],
            $row['complete_user_name'],
            $row['username'],
            $row['email'],
            $row['gateway_subscription_id'],
        ];
    }

    return $exportRows;
}

$plugin = BuyCoursesPlugin::create();
$httpRequest = Container::getRequest();

$sourceOptions = [
    'all' => $plugin->get_lang('AllSales'),
    'course_session' => $plugin->get_lang('CourseSessionSales'),
    'service' => $plugin->get_lang('ServiceSales'),
    'subscription' => $plugin->get_lang('SubscriptionSales'),
];
$statusOptions = ['all' => get_lang('All')] + $plugin->getSaleStatuses();

$source = (string) $httpRequest->query->get('source', 'all');
if (!isset($sourceOptions[$source])) {
    $source = 'all';
}

$selectedStatus = (string) $httpRequest->query->get('status', 'all');
if ('all' !== $selectedStatus && !isset($statusOptions[(int) $selectedStatus])) {
    $selectedStatus = 'all';
}

$dateStart = (string) $httpRequest->query->get('date_start', date('Y-m-d', strtotime('-90 days')));
$dateEnd = (string) $httpRequest->query->get('date_end', date('Y-m-d'));
$search = trim((string) $httpRequest->query->get('search', ''));

$normalizedDateStart = normalizeBuyCoursesExportDate($dateStart, false);
$normalizedDateEnd = normalizeBuyCoursesExportDate($dateEnd, true);
$hasInvalidDateRange = strtotime($normalizedDateStart) > strtotime($normalizedDateEnd);

$form = new FormValidator('export_validate', 'get');
$form->addSelect('source', $plugin->get_lang('SaleSource'), $sourceOptions);
$form->addSelect('status', $plugin->get_lang('OrderStatus'), $statusOptions);
$form->addText('search', get_lang('Search'), false);
$form->addDatePicker('date_start', $plugin->get_lang('DateStart'), false);
$form->addDatePicker('date_end', $plugin->get_lang('DateEnd'), false);
$form->addButtonSearch(get_lang('Search'), 'preview_sales');
$form->addButton('export_sales', $plugin->get_lang('ExportExcel'), 'check', 'primary');
$form->setDefaults([
    'source' => $source,
    'status' => $selectedStatus,
    'search' => $search,
    'date_start' => date('Y-m-d', strtotime($normalizedDateStart)),
    'date_end' => date('Y-m-d', strtotime($normalizedDateEnd)),
]);

$reportRows = [];
$exportRequested = $httpRequest->query->has('export_sales');

if ($hasInvalidDateRange) {
    Display::addFlash(
        Display::return_message(get_lang('EndDateCannotBeBeforeTheStartDate'), 'error', false)
    );
} else {
    $reportRows = getBuyCoursesExportRows(
        $plugin,
        $source,
        $normalizedDateStart,
        $normalizedDateEnd,
        $selectedStatus,
        $search
    );
}

if ($exportRequested) {
    if (empty($reportRows)) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NoSalesFoundForExport'), 'warning', false)
        );
    } else {
        $archiveFile = 'buycourses_sales_report_'.date('Ymd_His');
        Export::arrayToXls(buildBuyCoursesXlsRows($plugin, $reportRows), $archiveFile);
        exit;
    }
}

$previewRows = array_slice($reportRows, 0, 100);
$totalAmount = 0.0;
foreach ($reportRows as $row) {
    $totalAmount += (float) ($row['price'] ?? 0);
}

$interbreadcrumb[] = [
    'url' => '../index.php',
    'name' => $plugin->get_lang('plugin_title'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/sales_report.php',
    'name' => $plugin->get_lang('SalesReport'),
];

$defaultBackUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/sales_report.php';
$backUrl = $defaultBackUrl;

$templateName = $plugin->get_lang('ExportReport');
$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('page_title', $templateName);
$template->assign('plugin_title', $plugin->get_lang('plugin_title'));
$template->assign('back_url', $backUrl);
$template->assign('sales_report_url', api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/sales_report.php');
$template->assign('service_sales_report_url', api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_sales_report.php');
$template->assign('subscription_sales_report_url', api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscription_sales_report.php');
$template->assign('form', styleBuyCoursesFormHtml($form->returnForm()));
$template->assign('selected_source_label', $sourceOptions[$source]);
$template->assign('selected_status_label', $statusOptions['all' === $selectedStatus ? 'all' : (int) $selectedStatus] ?? get_lang('All'));
$template->assign('date_start', date('Y-m-d', strtotime($normalizedDateStart)));
$template->assign('date_end', date('Y-m-d', strtotime($normalizedDateEnd)));
$template->assign('search', $search);
$template->assign('sale_list', $previewRows);
$template->assign('sales_count', count($reportRows));
$template->assign('preview_limit', 100);
$template->assign('total_amount', number_format($totalAmount, 2, '.', ''));

$content = $template->fetch('BuyCourses/view/export_report.tpl');
$template->assign('content', $content);
$template->display_one_col_template();
