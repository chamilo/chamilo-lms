<?php
/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */

use Chamilo\CoreBundle\Enums\ActionIcon;

// resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$xajax = new xajax();
function search_courses($needle, $id)
{
    static $lastNeedle = '';
    $response = new xajaxResponse();

    if (trim($needle) === '' || $needle === $lastNeedle) {
        return $response;
    }

    $lastNeedle = $needle;
    $results = \UrlManager::searchCoursesByTitleOrCode($needle);
    $output = '';
    $i = 0;

    foreach ($results as $course) {
        $i++;
        if ($i <= 10) {
            $label = htmlspecialchars($course['title']) . ' (' . htmlspecialchars($course['code']) . ')';
            $output .= '
                <div class="hover:bg-gray-100 p-2 rounded cursor-pointer transition"
                     onclick="add_user_to_url(' . $course['id'] . ', \'' . addslashes($label) . '\')">
                    <div class="font-medium text-sm text-gray-800">' . $course['title'] . '</div>
                    <div class="text-xs text-gray-500">' . $course['code'] . '</div>
                </div>
            ';
        } else {
            $output .= '<div class="text-xs text-gray-400 italic mt-1">...</div>';
            break;
        }
    }

    $response->addAssign('ajax_list_courses', 'innerHTML', api_utf8_encode($output));
    return $response;
}
$xajax->registerFunction('search_courses');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_global_admin_script();
if (!api_get_multiple_access_url()) {
    header('Location: index.php');
    exit;
}

// setting breadcrumbs
$tool_name = get_lang('Edit courses of an URL');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('Multiple access URL / Branding')];

$add_type = 'multiple';
if (isset($_REQUEST['add_type']) && '' != $_REQUEST['add_type']) {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$access_url_id = 1;
if (isset($_REQUEST['access_url_id']) && '' != $_REQUEST['access_url_id']) {
    $access_url_id = Security::remove_XSS($_REQUEST['access_url_id']);
}

$xajax->processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<script>
function add_user_to_url(code, content) {
	document.getElementById("course_to_add").value = "";
	document.getElementById("ajax_list_courses").innerHTML = "";
	destination = document.getElementById("destination_users");
	destination.options[destination.length] = new Option(content,code);
	destination.selectedIndex = -1;
	sortOptions(destination.options);
}

function send() {
	if (document.formulaire.access_url_id.value != 0) {
		document.formulaire.form_sent.value=0;
		document.formulaire.add_type.value=\''.$add_type.'\';
		document.formulaire.submit();
	}
}

function remove_item(origin) {
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
</script>';

if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    $course_list = $_POST['course_list'];

    if (!is_array($course_list)) {
        $course_list = [];
    }

    if (1 == $form_sent) {
        if (0 == $access_url_id) {
            Display::addFlash(Display::return_message(get_lang('Select a URL')));
            header('Location: access_url_edit_courses_to_url.php?');
            exit;
        } elseif (is_array($course_list)) {
            UrlManager::update_urls_rel_course($course_list, $access_url_id);
            Display::addFlash(Display::return_message(get_lang('Courses updated successfully')));
        }
    }
}

Display::display_header($tool_name);

echo '<div class="flex gap-2 items-center mb-4 mt-4">';
echo Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to URL list')),
    api_get_path(WEB_CODE_PATH).'admin/access_urls.php'
);
echo Display::url(
    Display::getMdiIcon(ActionIcon::MULTI_COURSE_URL_ASSIGN, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add course to this URL')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_add_courses_to_url.php'
);
echo '</div>';

Display::page_subheader2($tool_name);
?>
    <h2 class="text-xl font-semibold text-gray-800 mt-6 mb-2">
        <?php echo $tool_name; ?>
    </h2>
<?php
$no_course_list = $course_list = [];
$ajax_search = 'unique' == $add_type ? true : false;

if ($ajax_search) {
    $courses = UrlManager::get_url_rel_course_data($access_url_id);
    foreach ($courses as $course) {
        $course_list[$course['c_id']] = $course;
    }
} else {
    $courses = UrlManager::get_url_rel_course_data();

    foreach ($courses as $course) {
        if ($course['access_url_id'] == $access_url_id) {
            $course_list[$course['c_id']] = $course;
        }
    }

    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $sql = "SELECT id, code, title
            FROM $tbl_course u
            ORDER BY title, code";
    $result = Database::query($sql);
    $courses = Database::store_result($result);
    $course_list_leys = array_keys($course_list);
    foreach ($courses as $course) {
        if (!in_array($course['id'], $course_list_leys)) {
            $no_course_list[$course['id']] = $course;
        }
    }
}
$url_list = UrlManager::get_url_data();
?>
    <div class="flex space-x-2 border-gray-300 pb-2 mb-4">
        <a href="<?php echo api_get_self(); ?>?add_type=unique&access_url_id=<?php echo $access_url_id; ?>"
           class="text-sm px-4 py-2 transition <?php echo $add_type === 'unique'
               ? 'border-b-2 border-primary text-primary font-semibold'
               : 'text-gray-500 hover:text-primary'; ?>">
            <?php echo get_lang('Single registration'); ?>
        </a>

        <a href="<?php echo api_get_self(); ?>?add_type=multiple&access_url_id=<?php echo $access_url_id; ?>"
           class="text-sm px-4 py-2 transition <?php echo $add_type === 'multiple'
               ? 'border-b-2 border-primary text-primary font-semibold'
               : 'text-gray-500 hover:text-primary'; ?>">
            <?php echo get_lang('Multiple registration'); ?>
        </a>
    </div>
<br /><br />
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;" <?php if ($ajax_search) {
    echo ' onsubmit="valide();"';
}?> >
    <?php echo get_lang('Select URL').' : '; ?>
    <select name="access_url_id" onchange="javascript:send();">
        <option value="0">-- <?php echo get_lang('Select URL'); ?> -- </option>
        <?php
        $url_selected = '';
        foreach ($url_list as $url_obj) {
            $checked = '';
            if (!empty($access_url_id)) {
                if ($url_obj[0] == $access_url_id) {
                    $checked = 'selected=true';
                    $url_selected = $url_obj[1];
                }
            }
            if (1 == $url_obj['active']) {
                ?>
                <option <?php echo $checked; ?> value="<?php echo $url_obj[0]; ?>"> <?php echo $url_obj[1]; ?></option>
            <?php
            }
        }
        ?>
    </select>
    <br /><br />
    <input type="hidden" name="form_sent" value="1" />
    <input type="hidden" name="add_type" value = "<?php echo $add_type; ?>" />
    <div class="flex flex-col gap-4">
        <div class="flex justify-between gap-4 items-center text-sm text-gray-700 font-semibold">
            <div class="w-1/2 text-center">
                <?php echo get_lang('Courses list'); ?>:
            </div>
            <div class="w-10"></div>
            <div class="w-1/2 text-center">
                <?php echo get_lang('Courses of').' '.$url_selected; ?>
            </div>
        </div>

        <div class="flex justify-between gap-4">
            <div id="content_source" class="w-1/2">
                <?php if ($ajax_search): ?>
                    <input
                        type="text"
                        id="course_to_add"
                        onkeyup="xajax_search_courses(this.value,document.formulaire.access_url_id.options[document.formulaire.access_url_id.selectedIndex].value)"
                        class="w-full border rounded px-2 py-1 mb-2"
                        placeholder="<?php echo get_lang('Search course'); ?>"
                    />
                    <div id="ajax_list_courses" class="text-sm text-gray-600"></div>
                <?php else: ?>
                    <select id="origin_users" name="no_course_list[]" multiple size="15"
                            class="w-full min-h-[360px] border rounded">
                        <?php foreach ($no_course_list as $no_course): ?>
                            <option value="<?php echo $no_course['id']; ?>">
                                <?php echo $no_course['title'].' ('.$no_course['code'].')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="flex flex-col justify-center items-center gap-2">
                <?php if ($ajax_search): ?>
                    <button type="button" onclick="remove_item(document.getElementById('destination_users'))"
                            class="rounded-full bg-danger p-2 hover:bg-danger/80 focus:outline-none focus:ring">
                        <i class="mdi mdi-close text-white text-2xl"></i>
                    </button>
                <?php else: ?>
                    <button type="button" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))"
                            class="rounded-full bg-primary p-2 hover:bg-primary/80 focus:outline-none focus:ring">
                        <i class="mdi mdi-fast-forward-outline text-white text-2xl"></i>
                    </button>
                    <button type="button" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))"
                            class="rounded-full bg-secondary p-2 hover:bg-secondary/80 focus:outline-none focus:ring">
                        <i class="mdi mdi-rewind-outline text-white text-2xl"></i>
                    </button>
                <?php endif; ?>
            </div>

            <div class="w-1/2">
                <select id="destination_users" name="course_list[]" multiple size="15"
                        class="w-full min-h-[360px] border rounded">
                    <?php foreach ($course_list as $course):
                        $courseInfo = api_get_course_info_by_id($course['id']); ?>
                        <option value="<?php echo $course['id']; ?>">
                            <?php echo $course['title'].' ('.$courseInfo['code'].')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mt-4 text-center">
            <?php
            $label = get_lang('Save');
            echo '<button class="btn btn--primary" onclick="valide()">'.$label.'</button>';
            ?>
        </div>
    </div>

</form>

<script>
    function moveItem(origin , destination) {
        for(var i = 0 ; i<origin.options.length ; i++) {
            if(origin.options[i].selected) {
                destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
                origin.options[i]=null;
                i = i-1;
            }
        }
        destination.selectedIndex = -1;
        sortOptions(destination.options);
    }

    function sortOptions(options) {
        newOptions = new Array();
        for (i = 0 ; i<options.length ; i++)
            newOptions[i] = options[i];

        newOptions = newOptions.sort(mysort);
        options.length = 0;
        for(i = 0 ; i < newOptions.length ; i++)
            options[i] = newOptions[i];
    }

    function mysort(a, b) {
        if (a.text.toLowerCase() > b.text.toLowerCase()){
            return 1;
        }
        if (a.text.toLowerCase() < b.text.toLowerCase()){
            return -1;
        }
        return 0;
    }

    function valide(){
        var options = document.getElementById('destination_users').options;
        for (i = 0 ; i<options.length ; i++)
            options[i].selected = true;
        document.forms.formulaire.submit();
    }

    function loadUsersInSelect(select) {
        var xhr_object = null;
        if(window.XMLHttpRequest) // Firefox
            xhr_object = new XMLHttpRequest();
        else if(window.ActiveXObject) // Internet Explorer
            xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
        else  // XMLHttpRequest non supporté par le navigateur
            alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");

        xhr_object.open("POST", "loadUsersInSelect.ajax.php");
        xhr_object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        nosessionUsers = makepost(document.getElementById('origin_users'));
        sessionUsers = makepost(document.getElementById('destination_users'));
        nosessionClasses = makepost(document.getElementById('origin_classes'));
        sessionClasses = makepost(document.getElementById('destination_classes'));
        xhr_object.send("nosessionusers="+nosessionUsers+"&sessionusers="+sessionUsers+"&nosessionclasses="+nosessionClasses+"&sessionclasses="+sessionClasses);
        xhr_object.onreadystatechange = function() {
            if(xhr_object.readyState == 4) {
                document.getElementById('content_source').innerHTML = result = xhr_object.responseText;
                //alert(xhr_object.responseText);
            }
        }
    }

    function makepost(select){
        var options = select.options;
        var ret = "";
        for (i = 0 ; i<options.length ; i++)
            ret = ret + options[i].value +'::'+options[i].text+";;";

        return ret;
    }
</script>
<?php
Display::display_footer();
