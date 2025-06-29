<?php
/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_global_admin_script();

if (!api_get_multiple_access_url()) {
    header('Location: index.php');
    exit;
}

require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

$tool_name = get_lang('Edit groups for one URL');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('Multiple access URL / Branding')];

$add_type = $_REQUEST['add_type'] ?? 'multiple';
$access_url_id = $_REQUEST['access_url_id'] ?? 1;

$userGroup = new UserGroupModel();

if (!empty($_POST['form_sent'])) {
    $course_list = $_POST['course_list'] ?? [];
    if (!is_array($course_list)) {
        $course_list = [];
    }

    if ((int) $_POST['form_sent'] === 1) {
        if ((int) $access_url_id === 0) {
            Display::addFlash(Display::return_message(get_lang('Select a URL')));
            header('Location: access_url_edit_usergroup_to_url.php');
            exit;
        } else {
            UrlManager::update_urls_rel_usergroup($course_list, (int) $access_url_id);
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        }
    }
}

$userGroups      = UrlManager::get_url_rel_usergroup_data();
$userGroupList   = [];
$noUserGroupList = [];

foreach ($userGroups as $item) {
    if ($item['access_url_id'] == $access_url_id) {
        $userGroupList[$item['id']] = $item;
    }
}

$noUserGroupList = $userGroup->getUserGroupNotInList(array_keys($userGroupList), $access_url_id);
$url_list        = UrlManager::get_url_data();

$totalGroups         = count($userGroupList) + count($noUserGroupList);
$totalAssignedGroups = count($userGroupList);
$totalAvailable      = count($noUserGroupList);

Display::display_header($tool_name);

echo '<div class="flex gap-2 items-center mb-4 mt-4">';
echo Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to URL list')),
    api_get_path(WEB_CODE_PATH).'admin/access_urls.php'
);
echo Display::url(
    Display::getMdiIcon(ActionIcon::MULTI_COURSE_URL_ASSIGN, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add group to URL')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_add_usergroup_to_url.php'
);
echo '</div>';

Display::page_subheader2($tool_name);
?>
<h2 class="text-xl font-semibold text-gray-800 mt-4 mb-2">
    <?php echo $tool_name; ?>
</h2>

<p class="text-sm text-gray-600 mb-4">
    <?php
    echo get_lang('Total groups').': '.$totalGroups.' · ';
    echo get_lang('Groups assigned to URL').': '.$totalAssignedGroups.' · ';
    echo get_lang('Available groups').': '.$totalAvailable;
    ?>
</p>

<form
    name="formulaire"
    method="post"
    action="<?php echo api_get_self(); ?>"
    class="space-y-6"
>
    <input type="hidden" name="form_sent" value="1" />
    <input type="hidden" name="add_type"  value="<?php echo $add_type; ?>" />

    <div class="flex items-center space-x-4">
        <label class="text-sm font-medium text-gray-700">
            <?php echo get_lang('Select URL'); ?>
        </label>
        <select
            name="access_url_id"
            id="access_url_id"
            onchange="this.form.submit();"
            class="w-1/2 rounded-md border border-gray-300 bg-white p-2 shadow-sm focus:border-primary focus:ring-primary"
        >
            <option value="0"><?php echo get_lang('Select URL'); ?></option>
            <?php foreach ($url_list as $url_obj): ?>
                <?php if ($url_obj['active'] == 1): ?>
                    <option value="<?php echo $url_obj['id']; ?>" <?php if ($url_obj['id'] == $access_url_id) echo 'selected'; ?>>
                        <?php echo $url_obj['url']; ?>
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4 items-start">

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
                <?php echo get_lang('Platform groups list'); ?>
            </label>
            <input
                type="text"
                id="originFilter"
                onkeyup="filterSelect('originFilter', 'origin_users')"
                placeholder="<?php echo get_lang('Search group'); ?>"
                class="mb-2 w-full rounded-md border border-gray-300 p-2 text-sm focus:outline-none"
            />
            <select
                id="origin_users"
                name="no_course_list[]"
                multiple
                size="15"
                class="w-full h-[400px] rounded-md border border-gray-300 p-2 text-sm focus:outline-none"
            >
                <?php foreach ($noUserGroupList as $noItem): ?>
                    <option value="<?php echo $noItem['id']; ?>">
                        <?php echo $noItem['title']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex flex-col items-center justify-center self-center gap-4">
            <button
                type="button"
                onclick="moveItem('origin_users','destination_users')"
                class="rounded-full bg-primary p-2 hover:bg-primary/80 focus:outline-none focus:ring"
            >
                <i class="mdi mdi-fast-forward-outline text-white text-2xl"></i>
            </button>
            <button
                type="button"
                onclick="moveItem('destination_users','origin_users')"
                class="rounded-full bg-secondary p-2 hover:bg-secondary/80 focus:outline-none focus:ring"
            >
                <i class="mdi mdi-rewind-outline text-white text-2xl"></i>
            </button>
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
                <?php echo get_lang('Groups assigned to URL'); ?>
            </label>
            <input
                type="text"
                id="destFilter"
                onkeyup="filterSelect('destFilter', 'destination_users')"
                placeholder="<?php echo get_lang('Search group'); ?>"
                class="mb-2 w-full rounded-md border border-gray-300 p-2 text-sm focus:outline-none"
            />
            <select
                id="destination_users"
                name="course_list[]"
                multiple
                size="15"
                class="w-full h-[400px] rounded-md border border-gray-300 p-2 text-sm focus:outline-none"
            >
                <?php foreach ($userGroupList as $item): ?>
                    <option value="<?php echo $item['id']; ?>">
                        <?php echo $item['title']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="text-center mt-6">
        <button
            type="button"
            onclick="submitWithAllDestinationSelected()"
            class="inline-flex items-center justify-center rounded-lg bg-primary px-6 py-2 text-white shadow hover:bg-primary/90"
        >
            <i class="mdi mdi-content-save ch-tool-icon mr-2"></i>
            <?php echo get_lang('Save'); ?>
        </button>
    </div>
</form>

<script>
    function filterSelect(inputId, selectId) {
        const filter = document.getElementById(inputId).value.toLowerCase();
        const options = document.getElementById(selectId).options;
        for (let i = 0; i < options.length; i++) {
            const txt = options[i].text.toLowerCase();
            options[i].style.display = txt.includes(filter) ? '' : 'none';
        }
    }

    function moveItem(originId, destId) {
        const origin = document.getElementById(originId);
        const dest   = document.getElementById(destId);
        const moved  = [];
        Array.from(origin.options).forEach(opt => {
            if (opt.selected) {
                moved.push(new Option(opt.text, opt.value));
                opt.remove();
            }
        });
        moved.forEach(opt => dest.add(opt));
        sortSelectOptions(dest);
        dest.selectedIndex = -1;
    }

    function sortSelectOptions(selectElement) {
        const sorted = Array.from(selectElement.options)
            .sort((a,b) => a.text.toLowerCase().localeCompare(b.text.toLowerCase()));
        selectElement.innerHTML = '';
        sorted.forEach(opt => selectElement.add(opt));
    }

    function submitWithAllDestinationSelected() {
        const dest = document.getElementById('destination_users');
        Array.from(dest.options).forEach(o => o.selected = true);
        document.forms['formulaire'].submit();
    }
</script>

<?php Display::display_footer(); ?>
