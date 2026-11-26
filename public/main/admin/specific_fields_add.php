<?php
/* For licensing terms, see /license.txt */

/**
 * Add form.
 */

use Chamilo\CoreBundle\Framework\Container;

// Resetting the course id.
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// section for the tabs
$this_section = SECTION_PLATFORM_ADMIN;

// user permissions
api_protect_admin_script();
$fieldId = filter_var($_POST['field_id'] ?? $_GET['field_id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if (false === $fieldId || (0 === $fieldId && 'edit' === ($_GET['action'] ?? ''))) {
    api_not_allowed(true);
    exit;
}
$router = Container::getRouter();
$interbreadcrumb[] = [
    'url' => $router->generate('admin'),
    'name' => get_lang('Administration'),
];
$interbreadcrumb[] = [
    'url' => $router->generate('chamilo_platform_settings', ['namespace' => 'search']),
    'name' => get_lang('Configuration settings'),
];
$interbreadcrumb[] = [
    'url' => 'specific_fields.php',
    'name' => get_lang('Specific search fields'),
];

$tool_name = get_lang('Add a specific search field');

if ($fieldId) {
    $tool_name = get_lang('Edit specific search field');
}
// Create the form
$action = 'specific_fields_add.php'.($fieldId ? '?'.http_build_query(['action' => 'edit', 'field_id' => $fieldId]) : '');
$form = new FormValidator('specific_fields_add', 'post', $action);
$form->protect();
// Field variable name
$form->addElement('hidden', 'field_id', $fieldId);
$form->addElement('text', 'field_name', get_lang('Field name'));
$form->applyFilter('field_name', 'html_filter');
$form->applyFilter('field_name', 'trim');
$form->addRule('field_name', get_lang('Required field'), 'required');
$form->addRule('field_name', get_lang('Only letters and numbers allowed'), 'regex', '/^[\p{L}\p{N}._-]+$/u');
$form->addRule('field_name', '', 'maxlength', 20);

// Set default values (only not empty when editing)
$defaults = [];
if ($fieldId) {
    $form_information = get_specific_field_list(['id' => $fieldId]);
    if (empty($form_information)) {
        api_not_allowed(true);
        exit;
    }
    $defaults['field_name'] = $form_information[0]['title'];
}
$form->setDefaults($defaults);
// Submit button
if ($fieldId) {
    $form->addButtonUpdate(get_lang('Update'), 'submit');
} else {
    $form->addButtonCreate(get_lang('Add'), 'submit');
}

// Validate form
if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && $form->validate()) {
    $field = $form->exportValues();
    $fieldName = $field['field_name'];
    $saved = $fieldId
        ? edit_specific_field($fieldId, $fieldName)
        : add_specific_field($fieldName);

    if (false !== $saved) {
        $message = $fieldId ? get_lang('Update successful') : get_lang('Field successfully added');
        header('Location: specific_fields.php?'.http_build_query(['message' => $message]));
        exit;
    }
    // FormValidator consumes the CSRF token after successful validation.
    Display::addFlash(Display::return_message(get_lang('An error occurred'), 'error'));
    $query = $fieldId ? '?'.http_build_query(['action' => 'edit', 'field_id' => $fieldId]) : '';
    header('Location: specific_fields_add.php'.$query);
    exit;
}
// Display form
Display::display_header($tool_name);
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div class="w-full max-w-2xl mx-auto space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-gray-90"><?= $escape($tool_name) ?></h1>
        <a href="specific_fields.php" class="btn btn--plain-outline"><?= $escape(get_lang('Back to')) ?> <?= $escape(get_lang('Specific search fields')) ?></a>
    </div>
    <div class="rounded-xl border border-gray-25 bg-white p-6 shadow-sm">
        <?php $form->display(); ?>
    </div>
</div>
<?php
Display::display_footer();
