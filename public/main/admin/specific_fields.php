<?php
/* For licensing terms, see /license.txt */
/**
 * Manage specific fields.
 */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

// Resetting the course id.
$cidReset = true;

// Including some necessary chamilo files
require_once __DIR__.'/../inc/global.inc.php';

// User permissions
api_protect_admin_script();

// Use the current Symfony settings page instead of the deprecated legacy screen.
$this_section = SECTION_PLATFORM_ADMIN;
$router = Container::getRouter();
$searchSettingsUrl = $router->generate('chamilo_platform_settings', ['namespace' => 'search']);
$interbreadcrumb[] = ['url' => $router->generate('admin'), 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => $searchSettingsUrl, 'name' => get_lang('Configuration settings')];

if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && 'delete' === ($_POST['action'] ?? '')) {
    if (!Security::check_token('post', null, 'specific_fields')) {
        api_not_allowed(true);
        exit;
    }
    $fieldId = filter_var($_POST['field_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (false === $fieldId || $fieldId < 1) {
        api_not_allowed(true);
        exit;
    }
    delete_specific_field($fieldId);
    header('Location: specific_fields.php?'.http_build_query(['message' => get_lang('Field removed')]));
    exit;
}

// Only allow the two visible data columns to be sorted.
$sort = 'code' === ($_GET['sort'] ?? '') ? 'code' : 'title';
$direction = 'desc' === ($_GET['direction'] ?? '') ? 'DESC' : 'ASC';
$fields = get_specific_field_list([], [$sort.' '.$direction, 'id ASC']);
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$token = Security::get_existing_token('specific_fields');
$confirmation = $escape(
    'return confirm('.json_encode(get_lang('Please confirm your choice'), JSON_HEX_APOS | JSON_HEX_QUOT).');'
);

Display::display_header(get_lang('Specific search fields'));
?>
<div class="w-full space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h1 class="text-lg font-semibold text-gray-90"><?= $escape(get_lang('Specific search fields')) ?></h1>
        <div class="flex flex-wrap gap-2">
            <a class="btn btn--plain-outline" href="<?= $escape($searchSettingsUrl) ?>">
                <?= $escape(get_lang('Configuration settings')) ?>
            </a>
            <a class="btn btn--primary" href="specific_fields_add.php">
                <?= Display::getMdiIcon(ActionIcon::ADD, '', null, ICON_SIZE_SMALL) ?>
                <?= $escape(get_lang('Add a specific search field')) ?>
            </a>
        </div>
    </div>
    <p class="text-body-2 text-gray-70"><?= $escape(get_lang('Here you can define the fields you want to use for indexing content. When you are indexing one element you should add one or many terms on each field comma-separated.')) ?></p>
    <?php if (is_string($_GET['message'] ?? null) && '' !== $_GET['message']) { ?>
        <?= Display::return_message($_GET['message'], 'confirm') ?>
    <?php } ?>
    <div class="overflow-x-auto rounded-xl border border-gray-25 bg-white shadow-sm">
        <table class="w-full">
            <thead class="bg-support-2">
                <tr class="border-b border-gray-25">
                    <?php foreach (['code' => get_lang('Code'), 'title' => get_lang('Name')] as $column => $label) {
                        $next = $sort === $column && 'ASC' === $direction ? 'desc' : 'asc';
                        $sortUrl = 'specific_fields.php?'.http_build_query(['sort' => $column, 'direction' => $next]);
                        $ariaSort = $sort === $column ? ('ASC' === $direction ? 'ascending' : 'descending') : 'none';
                        ?>
                        <th scope="col" aria-sort="<?= $ariaSort ?>" class="px-4 py-3 text-start text-sm font-semibold text-gray-90<?= 'code' === $column ? ' w-24' : '' ?>">
                            <a href="<?= $escape($sortUrl) ?>" class="text-gray-90 hover:text-primary">
                                <?= $escape($label) ?><?= $sort === $column ? ('ASC' === $direction ? ' &#8593;' : ' &#8595;') : '' ?>
                            </a>
                        </th>
                    <?php } ?>
                    <th scope="col" class="w-32 px-4 py-3 text-end text-sm font-semibold text-gray-90"><?= $escape(get_lang('Actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fields as $field) {
                    $fieldId = (int) $field['id'];
                    ?>
                    <tr class="border-b border-gray-25 hover:bg-gray-15">
                        <td class="px-4 py-3 text-body-2 font-mono text-gray-90"><?= $escape($field['code']) ?></td>
                        <td class="px-4 py-3 text-body-2 text-gray-90"><?= $escape($field['title']) ?></td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end gap-2">
                                <a href="specific_fields_add.php?action=edit&amp;field_id=<?= $fieldId ?>"
                                   class="btn btn--plain-outline btn--sm" title="<?= $escape(get_lang('Edit')) ?>" aria-label="<?= $escape(get_lang('Edit')) ?>">
                                    <?= Display::getMdiIcon(ActionIcon::EDIT, '', null, ICON_SIZE_SMALL) ?>
                                </a>
                                <form method="post" action="specific_fields.php" class="inline" onsubmit="<?= $confirmation ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="field_id" value="<?= $fieldId ?>">
                                    <input type="hidden" name="specific_fields_sec_token" value="<?= $escape($token) ?>">
                                    <button type="submit" class="btn btn--plain-outline btn--sm" title="<?= $escape(get_lang('Delete')) ?>" aria-label="<?= $escape(get_lang('Delete')) ?>">
                                        <?= Display::getMdiIcon(ActionIcon::DELETE, '', null, ICON_SIZE_SMALL) ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (!$fields) { ?>
                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-50"><?= $escape(get_lang('No results available')) ?></td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php
Display::display_footer();
