<?php
/* For licensing terms, see /license.txt */
/**
 * This script allows platform admins to add users to urls.
 *    It displays a list of users and a list of courses;
 *    you can select multiple users and courses and then click on.
 *
 * @author Julio Montoya <gugli100@gmail.com>
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

$first_letter_user = '';
$url_list = [];
$users = [];

$tbl_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);

$tool_name = get_lang('Add users to an URL');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('Multiple access URL / Branding')];

Display::display_header($tool_name);

echo '<div class="flex gap-2 items-center mb-4 mt-4">';
echo Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to URL list')),
    api_get_path(WEB_CODE_PATH).'admin/access_urls.php'
);
echo Display::url(
    Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit users and URLs')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit_users_to_url.php'
);
echo '</div>';

Display::page_subheader2($tool_name);

if (!empty($_POST['form_sent'])) {
    $form_sent = $_POST['form_sent'];
    $users = isset($_POST['user_list']) && is_array($_POST['user_list']) ? array_map('intval', $_POST['user_list']) : [];
    $url_list = isset($_POST['url_list']) && is_array($_POST['url_list']) ? $_POST['url_list'] : [];
    $first_letter_user = $_POST['first_letter_user'] ?? '';

    if (1 == $form_sent) {
        if (count($users) === 0 || count($url_list) === 0) {
            echo Display::return_message(
                get_lang('You must select at least one user and one URL'),
                'error'
            );
        } elseif (isset($_POST['add'])) {
            UrlManager::add_users_to_urls($users, $url_list);
            echo Display::return_message(get_lang('The user accounts are now attached to the URL'), 'confirm');
        } elseif (isset($_POST['remove'])) {
            UrlManager::remove_users_from_urls($users, $url_list);
            echo Display::return_message(get_lang('The user accounts have been unassigned from the URL'), 'confirm');
        }
    }
}

/*	Display GUI	*/
if (empty($first_letter_user)) {
    $sql = "SELECT count(*) as nb_users FROM $tbl_user WHERE active <> ".USER_SOFT_DELETED;
    $result = Database::query($sql);
    $num_row = Database::fetch_array($result);
    if ($num_row['nb_users'] > 1000) {
        //if there are too much users to gracefully handle with the HTML select list,
        // assign a default filter on users names
        $first_letter_user = 'A';
    }
    unset($result);
}
$first_letter_user_lower = Database::escape_string(api_strtolower($first_letter_user));

$target_name = api_sort_by_first_name() ? 'firstname' : 'lastname';
$target_name = 'lastname';
$sql = "SELECT id, lastname, firstname, username FROM $tbl_user
	    WHERE active <> ".USER_SOFT_DELETED." AND ".$target_name." LIKE '".$first_letter_user_lower."%' OR ".$target_name." LIKE '".$first_letter_user_lower."%'
		ORDER BY ".(count($users) > 0 ? '(id IN('.implode(',', $users).')) DESC,' : '').' '.$target_name;
$result = Database::query($sql);
$db_users = Database::store_result($result);
unset($result);

$sql = "SELECT id, url FROM $tbl_access_url  WHERE active=1 ORDER BY url";
$result = Database::query($sql);
$db_urls = Database::store_result($result);
unset($result);
?>

    <form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" class="space-y-6" onsubmit="return confirmSubmission(event)">
    <input type="hidden" name="form_sent" value="1" />
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <?php echo get_lang('Select').' '.('firstname' == $target_name ? get_lang('First name') : get_lang('Last name')); ?>
                </label>
                <select
                    name="first_letter_user"
                    onchange="javascript:document.formulaire.form_sent.value='2'; document.formulaire.submit();"
                    class="rounded-md border border-gray-300 p-2 shadow-sm focus:border-primary focus:ring-primary"
                >
                    <option value="">--</option>
                    <?php echo Display::get_alphabet_options($first_letter_user); ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2"><?php echo get_lang('User list'); ?></label>
                <input
                    type="text"
                    id="userFilter"
                    onkeyup="filterSelect('userFilter', 'userSelect')"
                    placeholder="<?php echo get_lang('Search user'); ?>"
                    class="mb-2 w-full rounded-md border border-gray-300 p-2 text-sm focus:outline-none"
                />

                <select
                    id="userSelect"
                    name="user_list[]"
                    multiple
                    size="20"
                    class="w-full h-[400px] rounded-md border border-gray-300 p-2 text-sm focus:outline-none"
                >
                    <?php foreach ($db_users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php if (in_array($user['id'], $users)) echo 'selected'; ?>>
                            <?php echo api_get_person_name($user['firstname'], $user['lastname']) . ' (' . $user['username'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2"><?php echo get_lang('URL list'); ?></label>
                <input
                    type="text"
                    id="urlFilter"
                    onkeyup="filterSelect('urlFilter', 'urlSelect')"
                    placeholder="<?php echo get_lang('Search URL'); ?>"
                    class="mb-2 w-full rounded-md border border-gray-300 p-2 text-sm focus:outline-none"
                />
                <select
                    id="urlSelect"
                    name="url_list[]"
                    multiple
                    size="20"
                    class="w-full h-[400px] rounded-md border border-gray-300 p-2 text-sm focus:outline-none"
                >
                    <?php foreach ($db_urls as $url_obj): ?>
                        <option value="<?php echo $url_obj['id']; ?>" <?php if (in_array($url_obj['id'], $url_list)) echo 'selected'; ?>>
                            <?php echo $url_obj['url']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex justify-center gap-4 mt-6 flex-wrap">
            <button
                type="submit"
                name="add"
                class="rounded-lg px-6 py-2 shadow focus:outline-none focus:ring btn--info"
            >
                <?php echo get_lang('Add users to selected URLs'); ?>
            </button>

            <button
                type="submit"
                name="remove"
                class="rounded-lg px-6 py-2 shadow focus:outline-none focus:ring"
                style="background: rgb(var(--color-danger-base)); color: rgb(var(--color-danger-button-text));"
            >
                <?php echo get_lang('Remove users from selected URLs'); ?>
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
            const addClicked = form.querySelector('[name="add"]')?.matches(':focus');
            const removeClicked = form.querySelector('[name="remove"]')?.matches(':focus');

            if (addClicked) {
                return confirm("<?php echo get_lang('Are you sure you want to assign the selected users to the selected URLs?'); ?>");
            }
            if (removeClicked) {
                return confirm("<?php echo get_lang('Are you sure you want to unassign the selected users from the selected URLs?'); ?>");
            }
            return true;
        }
    </script>
<?php

Display :: display_footer();
