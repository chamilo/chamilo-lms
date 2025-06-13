<?php
/* For licensing terms, see /license.txt */
/**
 *  This script allows platform admins to add users to urls.
 *  It displays a list of users and a list of courses;
 *  you can select multiple users and courses and then click on.
 *
 *  @author Julio Montoya <gugli100@gmail.com>
 */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_global_admin_script();
if (!api_get_multiple_access_url()) {
    header('Location: index.php');
    exit;
}

$userGroup = new UserGroupModel();
$firstLetterUserGroup = null;
$courses = [];
$url_list = [];

$tbl_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
$tool_name = get_lang('Add group to URL');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('Multiple access URL / Branding')];

Display::display_header($tool_name);

echo '<div class="flex gap-2 items-center mb-4 mt-4">';
echo Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to URL list')),
    api_get_path(WEB_CODE_PATH).'admin/access_urls.php'
);
echo Display::url(
    Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add group to URL')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit_usergroup_to_url.php'
);
echo '</div>';

Display::page_subheader2($tool_name);
?>
    <h2 class="text-xl font-semibold text-gray-800 mt-6 mb-2">
        <?php echo $tool_name; ?>
    </h2>
<?php
if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    $userGroups = isset($_POST['user_group_list']) && is_array($_POST['user_group_list']) ? $_POST['user_group_list'] : [];
    $urlList = is_array($_POST['url_list']) ? $_POST['url_list'] : [];
    $firstLetterUserGroup = $_POST['first_letter_user_group'] ?? null;

    if (1 == $form_sent) {
        if (0 == count($userGroups) || 0 == count($urlList)) {
            echo Display::return_message(get_lang('You need to select at least one group and one site'), 'error');
        } else {
            UrlManager::addUserGroupListToUrl($userGroups, $urlList);
            echo Display::return_message(get_lang('The group now belongs to the selected site'), 'confirm');
        }
    }
}

$firstLetterUser = null;
if ($userGroup->getTotalCount() > 1000) {
    //if there are too much num_courses to gracefully handle with the HTML select list,
    // assign a default filter on users names
    $firstLetterUser = 'A';
}

$dbUserGroups = $userGroup->filterByFirstLetter($firstLetterUserGroup);

$sql = "SELECT id, url FROM $tbl_access_url  WHERE active = 1 ORDER BY url";
$result = Database::query($sql);
$db_urls = Database::store_result($result);
?>
    <form method="post" class="space-y-6" onsubmit="return confirmSubmission(event)">
        <input type="hidden" name="form_sent" value="1" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo get_lang('User groups list'); ?></label>
                <input type="text" id="groupFilter" onkeyup="filterSelect('groupFilter', 'groupSelect')" class="mb-2 w-full p-2 border rounded" placeholder="<?php echo get_lang('Search group'); ?>">
                <select name="user_group_list[]" id="groupSelect" multiple size="20" class="w-full h-[400px] p-2 border rounded">
                    <?php foreach ($dbUserGroups as $item): ?>
                        <option value="<?php echo $item['id']; ?>"><?php echo $item['title']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2"><?php echo get_lang('URL list'); ?></label>
                <input type="text" id="urlFilter" onkeyup="filterSelect('urlFilter', 'urlSelect')" class="mb-2 w-full p-2 border rounded" placeholder="<?php echo get_lang('Search URL'); ?>">
                <select name="url_list[]" id="urlSelect" multiple size="20" class="w-full h-[400px] p-2 border rounded">
                    <?php foreach ($db_urls as $url_obj): ?>
                        <option value="<?php echo $url_obj['id']; ?>"><?php echo $url_obj['url']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex justify-center gap-4 mt-6 flex-wrap">
            <button name="add" class="rounded-lg px-6 py-2 shadow btn--info">
                <?php echo get_lang('Add user group to selected URLs'); ?>
            </button>
            <button name="remove" class="rounded-lg px-6 py-2 shadow"
                    style="background: rgb(var(--color-danger-base)); color: rgb(var(--color-danger-button-text));">
                <?php echo get_lang('Remove user group from selected URLs'); ?>
            </button>
        </div>
    </form>
    <script>
        function filterSelect(inputId, selectId) {
            const input = document.getElementById(inputId);
            const filter = input.value.toLowerCase();
            const select = document.getElementById(selectId);
            const options = select.options;

            for (let i = 0; i < options.length; i++) {
                const txt = options[i].text.toLowerCase();
                options[i].style.display = txt.includes(filter) ? '' : 'none';
            }
        }

        function confirmSubmission(event) {
            const form = event.target;
            const addClicked = form.querySelector('[name="add"]').matches(':focus');
            const removeClicked = form.querySelector('[name="remove"]').matches(':focus');

            if (addClicked) {
                return confirm("<?php echo get_lang('Are you sure you want to assign the selected groups to the selected URLs?'); ?>");
            }
            if (removeClicked) {
                return confirm("<?php echo get_lang('Are you sure you want to unassign the selected groups from the selected URLs?'); ?>");
            }
            return true;
        }
    </script>
<?php

Display :: display_footer();
