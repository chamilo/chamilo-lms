<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * Create or edit a purchase-attempt reminder.
 */
$cidReset = true;

require_once '../config.php';
require_once __DIR__.'/admin_token.lib.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();
$helper = $plugin->getPurchaseReminderHelper();
$productsOnSale = $helper->getProductsOnSale();

$reminderId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$reminder = $reminderId > 0 ? $helper->getReminder($reminderId) : null;
if ($reminderId > 0 && null === $reminder) {
    api_not_allowed(true);
}

$editorConfig = [
    'ToolbarSet' => 'TestQuestionDescription',
];
if ('true' === api_get_setting('editor.translate_html')) {
    $editorConfig['extraPlugins'] = 'translatehtml';
    $editorConfig['extended_valid_elements'] = 'span[lang|class|style],div[lang|class|style]';
    $editorConfig['toolbar'] = 'undo redo | translatehtml | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | fontfamily fontsize | forecolor backcolor removeformat | link image media table | emoticons preview print code fullscreen | ltr rtl';
}

$selectedProducts = [];
if (\is_array($reminder['products'] ?? null)) {
    foreach ($reminder['products'] as $product) {
        $selectedProducts[(int) $product['product_type'].'-'.(int) $product['product_id']] = true;
    }
}

$form = new FormValidator(
    'purchase_reminder',
    'post',
    api_get_self().($reminderId > 0 ? '?id='.$reminderId : '')
);
buycoursesAdminTokenField($form);
if ($reminderId > 0) {
    $form->addHidden('id', (string) $reminderId);
}

$form->addNumeric(
    'days_after',
    [$plugin->get_lang('PurchaseReminderDaysAfter'), $plugin->get_lang('PurchaseReminderDaysAfterHelp')],
    ['min' => 0, 'max' => 3650, 'step' => 1]
);
$form->addHtmlEditor(
    'subject',
    [$plugin->get_lang('PurchaseReminderSubject'), $plugin->get_lang('PurchaseReminderPlaceholderHelp')],
    true,
    false,
    $editorConfig
);
$form->addHtmlEditor(
    'body',
    [$plugin->get_lang('PurchaseReminderBody'), $plugin->get_lang('PurchaseReminderPlaceholderHelp')],
    true,
    false,
    $editorConfig
);

$discountTypes = [0 => $plugin->get_lang('PurchaseReminderNoDiscount')] + $plugin->getCouponDiscountTypes();
$form->addSelect('discount_type', $plugin->get_lang('PurchaseReminderDiscountType'), $discountTypes);
$form->addText('discount_amount', $plugin->get_lang('PurchaseReminderDiscountAmount'), false);
$form->addNumeric(
    'discount_valid_days',
    [$plugin->get_lang('PurchaseReminderDiscountValidDays'), $plugin->get_lang('PurchaseReminderDiscountValidDaysHelp')],
    ['min' => 0, 'step' => 1]
);
$form->addCheckBox('active', '', $plugin->get_lang('PurchaseReminderActive'));

$form->addHtml('<fieldset class="mt-4"><legend>'.$plugin->get_lang('PurchaseReminderProducts').'</legend>');
$form->addHtml('<p class="text-sm text-gray-50">'.$plugin->get_lang('PurchaseReminderProductsHelp').'</p>');

$addProductCheckboxes = static function (FormValidator $form, string $field, array $items, array $selected, int $type, string $label): void {
    if ([] === $items) {
        return;
    }
    $form->addHtml('<h3 class="mt-4 text-base font-semibold">'.$label.'</h3>');
    foreach ($items as $item) {
        $value = $type.'-'.(int) $item['id'];
        $form->addCheckBox(
            $field.'['.(int) $item['id'].']',
            '',
            Security::remove_XSS((string) $item['title'])
        );
        if (isset($selected[$value])) {
            $form->setDefaults([$field.'['.(int) $item['id'].']' => '1']);
        }
    }
};

$addProductCheckboxes(
    $form,
    'courses',
    $productsOnSale['courses'],
    $selectedProducts,
    BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
    get_lang('Courses')
);
$addProductCheckboxes(
    $form,
    'sessions',
    $productsOnSale['sessions'],
    $selectedProducts,
    BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
    get_lang('Sessions')
);
$addProductCheckboxes(
    $form,
    'services',
    $productsOnSale['services'],
    $selectedProducts,
    BuyCoursesPlugin::PRODUCT_TYPE_SERVICE,
    $plugin->get_lang('Services')
);
$form->addHtml('</fieldset>');

$form->addButtonSave(get_lang('Save'));

$form->setDefaults([
    'days_after' => $reminder['days_after'] ?? 0,
    'subject' => $reminder['subject'] ?? '',
    'body' => $reminder['body'] ?? '',
    'discount_type' => $reminder['discount_type'] ?? 0,
    'discount_amount' => $reminder['discount_amount'] ?? '',
    'discount_valid_days' => $reminder['discount_valid_days'] ?? '',
    'active' => isset($reminder['active']) ? (int) $reminder['active'] : 1,
]);

buycoursesRequireAdminTokenOnPost(api_get_self().($reminderId > 0 ? '?id='.$reminderId : ''));

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $products = [];
    $collect = static function (array $submitted, int $type) use (&$products): void {
        foreach ($submitted as $id => $checked) {
            if (empty($checked)) {
                continue;
            }
            $products[] = ['product_type' => $type, 'product_id' => (int) $id];
        }
    };
    $collect($values['courses'] ?? [], BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
    $collect($values['sessions'] ?? [], BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
    $collect($values['services'] ?? [], BuyCoursesPlugin::PRODUCT_TYPE_SERVICE);

    try {
        $helper->saveReminder(
            [
                'days_after' => (int) ($values['days_after'] ?? 0),
                'subject' => (string) ($values['subject'] ?? ''),
                'body' => (string) ($values['body'] ?? ''),
                'discount_type' => (int) ($values['discount_type'] ?? 0),
                'discount_amount' => $values['discount_amount'] ?? null,
                'discount_valid_days' => $values['discount_valid_days'] ?? null,
                'active' => !empty($values['active']),
                'products' => $products,
            ],
            $reminderId > 0 ? $reminderId : null
        );
        Display::addFlash(Display::return_message($plugin->get_lang('PurchaseReminderSaved'), 'success', false));
        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/reminders.php');
        exit;
    } catch (InvalidArgumentException $exception) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('PurchaseReminderCouldNotBeSaved'), 'error', false)
        );
    }
}

$interbreadcrumb[] = [
    'url' => '../index.php',
    'name' => $plugin->get_lang('plugin_title'),
];
$interbreadcrumb[] = [
    'url' => 'reminders.php',
    'name' => $plugin->get_lang('PurchaseReminders'),
];

$templateName = $reminderId > 0
    ? $plugin->get_lang('PurchaseReminderEdit')
    : $plugin->get_lang('PurchaseReminderAdd');
$template = new Template($templateName);
$template->assign('content', $form->returnForm());
$template->assign('header', $templateName);
$template->display_one_col_template();
