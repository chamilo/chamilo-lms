<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;

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

if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $course_list = $_POST['course_list'] ?? [];
    if (!is_array($course_list)) {
        $course_list = [];
    }

    if ((int) $_POST['form_sent'] === 1) {
        if ((int) $access_url_id === 0) {
            Display::addFlash(Display::return_message(get_lang('Select a URL')));
            header('Location: access_url_edit_users_to_url.php');
        } else {
            UrlManager::update_urls_rel_usergroup($course_list, (int) $access_url_id);
            Display::addFlash(Display::return_message(get_lang('Update successful')));
            header('Location: access_urls.php');
        }
        exit;
    }
}

Display::display_header($tool_name);

echo '<div class="flex gap-2 items-center mb-4 mt-4">';
echo Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to URL list')),
    api_get_path(WEB_CODE_PATH).'admin/access_urls.php'
);
echo Display::url(
    Display::getMdiIcon(ActionIcon::VIEW_DETAILS, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add group to URL')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_add_usergroup_to_url.php'
);
echo '</div>';

Display::page_subheader2($tool_name);
?>
<h2 class="text-xl font-semibold text-gray-800 mt-6 mb-2">
    <?php echo $tool_name; ?>
</h2>
<?php
$userGroups = UrlManager::get_url_rel_usergroup_data();
$userGroupList = [];
$noUserGroupList = [];

foreach ($userGroups as $item) {
    if ($item['access_url_id'] == $access_url_id) {
        $userGroupList[$item['id']] = $item;
    }
}

$noUserGroupList = $userGroup->getUserGroupNotInList(array_keys($userGroupList));
$url_list = UrlManager::get_url_data();
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" class="space-y-6">
    <input type="hidden" name="form_sent" value="1"/>
    <input type="hidden" name="add_type" value="<?php echo $add_type; ?>"/>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="access_url_id" class="block text-sm font-bold text-gray-90 mb-2">
                <?php echo get_lang('Select URL'); ?>
            </label>
            <select name="access_url_id" id="access_url_id"
                    onchange="this.form.submit();"
                    class="w-full rounded-md border border-gray-25 p-2 text-sm focus:outline-none">
                <option value="0">-- <?php echo get_lang('Select URL'); ?> --</option>
                <?php foreach ($url_list as $url_obj): ?>
                    <option value="<?php echo $url_obj['id']; ?>" <?php if ($url_obj['id'] == $access_url_id) echo 'selected'; ?>>
                        <?php echo $url_obj['title']; ?>
                    </option>

                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-bold text-gray-90 mb-2"><?php echo get_lang('Platform groups list'); ?></label>
            <select id="origin_users" name="no_course_list[]" multiple size="15"
                    class="w-full h-[400px] rounded-md border border-gray-25 p-2 text-sm focus:outline-none">
                <?php foreach ($noUserGroupList as $noItem): ?>
                    <option value="<?php echo $noItem['id']; ?>"><?php echo $noItem['title']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-90 mb-2">
                <?php echo get_lang('Groups assigned to URL'); ?>
            </label>
            <select id="destination_users" name="course_list[]" multiple size="15"
                    class="w-full h-[400px] rounded-md border border-gray-25 p-2 text-sm focus:outline-none">
                <?php foreach ($userGroupList as $item): ?>
                    <option value="<?php echo $item['id']; ?>"><?php echo $item['title']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="flex flex-col items-center justify-center gap-4 my-6">
        <button type="button" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))"
                class="btn btn--plain">
            <i class="mdi mdi-fast-forward-outline ch-tool-icon"></i>
        </button>
        <button type="button" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))"
                class="btn btn--plain">
            <i class="mdi mdi-rewind-outline ch-tool-icon"></i>
        </button>
    </div>

    <div class="text-center mt-6">
        <button type="submit" class="rounded-lg px-6 py-2 shadow focus:outline-none focus:ring btn--primary">
            <i class="mdi mdi-content-save ch-tool-icon mr-2"></i>
            <?php echo get_lang('Save'); ?>
        </button>
    </div>
</form>

<script>
    function moveItem(origin, destination) {
        for (let i = 0; i < origin.options.length; i++) {
            if (origin.options[i].selected) {
                destination.options[destination.length] = new Option(origin.options[i].text, origin.options[i].value);
                origin.options[i] = null;
                i = i - 1;
            }
        }
        destination.selectedIndex = -1;
        sortOptions(destination.options);
    }

    function sortOptions(options) {
        let newOptions = Array.from(options);
        newOptions.sort((a, b) => a.text.toLowerCase().localeCompare(b.text.toLowerCase()));
        options.length = 0;
        for (let opt of newOptions) {
            options.add(opt);
        }
    }
</script>

<?php Display::display_footer(); ?>
