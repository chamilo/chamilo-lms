<?php
/* For licensing terms, see /license.txt */

/**
 * This script allows platform admins to add courses to multiple URLs.
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

$first_letter_course = '';
$courses = [];
$url_list = [];

$tbl_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

$tool_name = get_lang('Add courses to URLs');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('Multiple access URL / Branding')];

Display::display_header($tool_name);

echo '<div class="flex gap-2 items-center mb-4 mt-4">';
echo Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to URL list')),
    api_get_path(WEB_CODE_PATH).'admin/access_urls.php'
);
echo Display::url(
    Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit courses of an URL')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit_courses_to_url.php'
);
echo '</div>';
Display::page_subheader2($tool_name);
?>
<h2 class="text-xl font-semibold text-gray-800 mt-6 mb-2">
    <?php echo $tool_name; ?>
</h2>
<?php
echo Display::return_message(get_lang("This page allows you to massively assign or remove the assignment of courses to/from many URLs in one single action. Assigning and removing courses to/from URLs can have a high impact on access for users and data stored about users' progress. Please use carefully."), 'warning');
if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $courses = is_array($_POST['course_list']) ? $_POST['course_list'] : [];
    $url_list = is_array($_POST['url_list']) ? $_POST['url_list'] : [];
    $first_letter_course = $_POST['first_letter_course'] ?? '';

    $sql = "SELECT id, url FROM $tbl_access_url WHERE active = 1 ORDER BY url";
    $db_urls = Database::store_result(Database::query($sql));

    if (in_array('__all__', $url_list)) {
        $url_list = array_column($db_urls, 'id');
    }

    if (isset($_POST['add'])) {
        UrlManager::add_courses_to_urls($courses, $url_list);
        echo Display::return_message(get_lang('Course registered to the URL'), 'confirm');
    } elseif (isset($_POST['remove'])) {
        UrlManager::remove_courses_from_urls($courses, $url_list);
        echo Display::return_message(get_lang('Course unregistered from the URL'), 'confirm');
    }
} else {
    $sql = "SELECT id, url FROM $tbl_access_url WHERE active = 1 ORDER BY url";
    $db_urls = Database::store_result(Database::query($sql));
}

$first_letter_course_lower = Database::escape_string(api_strtolower($first_letter_course));
$sql = "SELECT id, code, title FROM $tbl_course
        WHERE title LIKE '".$first_letter_course_lower."%'
        ORDER BY title, code DESC";
$db_courses = Database::store_result(Database::query($sql));
?>

<form
    name="formulaire"
    method="post"
    action="<?php echo api_get_self(); ?>"
    class="space-y-6"
    onsubmit="return confirmSubmission(event)"
>
    <input type="hidden" name="form_sent" value="1"/>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-bold text-gray-90 mb-2"><?php echo get_lang('Course list'); ?></label>
            <input type="text" id="courseFilter" onkeyup="filterSelect('courseFilter', 'courseSelect')" placeholder="<?php echo get_lang('Search course'); ?>"
                   class="mb-2 w-full rounded-md border border-gray-25 p-2 text-sm focus:outline-none">
            <select name="course_list[]" id="courseSelect" multiple size="20"
                    class="w-full h-[400px] rounded-md border border-gray-25 p-2 text-sm focus:outline-none">
                <?php foreach ($db_courses as $course): ?>
                    <option value="<?php echo $course['code']; ?>" <?php if (in_array($course['code'], $courses)) echo 'selected'; ?>>
                        <?php echo $course['title'].' ('.$course['code'].')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-90 mb-2"><?php echo get_lang('URL list'); ?></label>
            <input type="text" id="urlFilter" onkeyup="filterSelect('urlFilter', 'urlSelect')" placeholder="<?php echo get_lang('Search URL'); ?>"
                   class="mb-2 w-full rounded-md border border-gray-25 p-2 text-sm focus:outline-none">
            <select name="url_list[]" id="urlSelect" multiple size="20"
                    class="w-full h-[400px] rounded-md border border-gray-25 p-2 text-sm focus:outline-none">
                <option value="__all__"><?php echo get_lang('Select all URLs'); ?></option>
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
            <?php echo get_lang('Add courses to selected URLs'); ?>
        </button>

        <button
            type="submit"
            name="remove"
            class="rounded-lg px-6 py-2 shadow focus:outline-none focus:ring"
            style="background: rgb(var(--color-danger-base)); color: rgb(var(--color-danger-button-text));"
        >
            <?php echo get_lang('Remove courses from selected URLs'); ?>
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
            return confirm("<?php echo get_lang('Are you sure you want to add the selected courses to the selected URLs?'); ?>");
        }
        if (removeClicked) {
            return confirm("<?php echo get_lang('Are you sure you want to remove the selected courses from the selected URLs?'); ?>");
        }
        return true;
    }
</script>

<?php Display::display_footer(); ?>
