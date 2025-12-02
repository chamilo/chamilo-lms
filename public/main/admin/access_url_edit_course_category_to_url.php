<?php
/* For licensing terms, see /license.txt */

/**
 * Edit course categories (user groups) assigned to an Access URL.
 */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$xajax = new xajax();
$xajax->registerFunction(
    ['searchCourseCategoryAjax', 'UrlManager', 'searchCourseCategoryAjax']
);

// Setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_global_admin_script();
if (!api_get_multiple_access_url()) {
    header('Location: index.php');
    exit;
}

// Setting breadcrumbs
$tool_name = get_lang('Edit groups for one URL');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('Multiple access URL / Branding')];

$add_type = 'multiple';
if (isset($_REQUEST['add_type']) && '' != $_REQUEST['add_type']) {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$access_url_id = 1;
if (isset($_REQUEST['access_url_id']) && '' != $_REQUEST['access_url_id']) {
    $access_url_id = (int) $_REQUEST['access_url_id'];
}

$xajax->processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script>
function add_user_to_url(code, content) {
	document.getElementById("course_to_add").value = "";
	document.getElementById("ajax_list_courses").innerHTML = "";
	destination = document.getElementById("destination_users");
	destination.options[destination.length] = new Option(content,code);
	destination.selectedIndex = -1;
	sortOptions(destination.options);
}

function send() {
	if (document.formulaire.access_url_id.value!=0) {
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

$form_sent = 0;
$UserList = $SessionList = [];
$users = $sessions = [];

if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    $list = $_POST['course_list'];

    if (!is_array($list)) {
        $list = [];
    }

    if (1 == $form_sent) {
        if (0 == $access_url_id) {
            Display::addFlash(Display::return_message(get_lang('Select a URL')));
            header('Location: access_url_edit_users_to_url.php?');
            exit;
        } elseif (is_array($list)) {
            UrlManager::updateUrlRelCourseCategory($list, $access_url_id);
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        }
    }
}

Display::display_header($tool_name);

echo '<div class="flex gap-2 items-center mb-4 mt-4">';
echo Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
    api_get_path(WEB_CODE_PATH).'admin/access_urls.php'
);
echo '</div>';

Display::page_subheader2($tool_name);
?>
    <h2 class="text-xl font-semibold text-gray-800 mt-6 mb-2">
        <?php echo $tool_name; ?>
    </h2>
<?php
$noUserGroupList = $userGroupList = [];
$ajax_search = 'unique' == $add_type ? true : false;

if ($ajax_search) {
    $userGroups = UrlManager::getUrlRelCourseCategory($access_url_id);
    foreach ($userGroups as $item) {
        $userGroupList[$item['id']] = $item;
    }
} else {
    $userGroups = UrlManager::getUrlRelCourseCategory();

    foreach ($userGroups as $item) {
        if ($item['access_url_id'] == $access_url_id) {
            $userGroupList[$item['id']] = $item;
        }
    }
    $noUserGroupList = CourseCategory::getCourseCategoryNotInList(array_keys($userGroupList));
}

if ('multiple' == $add_type) {
    $link_add_type_unique = '<a href="'.api_get_self().'?add_type=unique&access_url_id='.$access_url_id.'">'.
        get_lang('Single registration').'</a>';
    $link_add_type_multiple = get_lang('Multiple registration');
} else {
    $link_add_type_unique = get_lang('Single registration');
    $link_add_type_multiple = '<a href="'.api_get_self().'?add_type=multiple&access_url_id='.$access_url_id.'">'.
        get_lang('Multiple registration').'</a>';
}

$urlList = Container::getAccessUrlRepository()->findAll();
$url_selected = '';

foreach ($urlList as $url) {
  if ($url->getId() == $access_url_id) {
    $url_selected = $url->getUrl();
    break;
  }
}
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
    <form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" onsubmit="valide()">
        <input type="hidden" name="form_sent" value="1" />
        <input type="hidden" name="add_type" value="<?php echo $add_type; ?>" />

        <div class="mb-4">
            <label for="access_url_id" class="block text-sm font-bold text-gray-800 mb-1"><?php echo get_lang('Select URL'); ?></label>
            <select name="access_url_id" id="access_url_id" onchange="send()" class="w-full p-2 border border-gray-300 rounded-md">
                <option value="0">-- <?php echo get_lang('Select URL'); ?> -- </option>
                <?php foreach ($urlList as $url): ?>
                    <?php if (1 == $url->getActive()): ?>
                        <option value="<?php echo $url->getId(); ?>" <?php if ($url->getId() == $access_url_id) echo 'selected'; ?>>
                            <?php echo $url->getUrl(); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <?php echo get_lang('Course categories available'); ?>
                </label>
                <?php if ($ajax_search): ?>
                    <input type="text" id="course_to_add"
                           class="w-full mb-2 p-2 border border-gray-300 rounded-md"
                           onkeyup="xajax_searchCourseCategoryAjax(this.value,document.formulaire.access_url_id.options[document.formulaire.access_url_id.selectedIndex].value)" />
                    <div id="ajax_list_courses"></div>
                <?php else: ?>
                    <select id="origin_users" name="no_course_list[]" multiple size="15"
                            class="w-full h-[400px] p-2 border border-gray-300 rounded-md">
                        <?php foreach ($noUserGroupList as $noItem): ?>
                            <option value="<?php echo $noItem['id']; ?>">
                                <?php echo $noItem['title']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="flex flex-col items-center justify-center gap-4 my-6">
                <?php if (!$ajax_search): ?>
                    <button type="button"
                            onclick="moveItem(
              document.getElementById('origin_users'),
              document.getElementById('destination_users'))"
                            class="rounded-full bg-primary p-2 hover:bg-primary/80 focus:outline-none focus:ring">
                        <i class="mdi mdi-fast-forward-outline text-white text-2xl"></i>
                    </button>

                    <button type="button"
                            onclick="moveItem(
              document.getElementById('destination_users'),
              document.getElementById('origin_users'))"
                            class="rounded-full bg-secondary p-2 hover:bg-secondary/80 focus:outline-none focus:ring">
                        <i class="mdi mdi-rewind-outline text-white text-2xl"></i>
                    </button>
                <?php else: ?>
                    <button type="button"
                            onclick="remove_item(document.getElementById('destination_users'))"
                            class="rounded-full bg-danger p-2 hover:bg-danger/80 focus:outline-none focus:ring"
                            title="<?php echo get_lang('Remove from selection') ?>">
                        <i class="mdi mdi-close text-white text-2xl"></i>
                    </button>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <?php printf(get_lang('Course categories in %s site:'), $url_selected); ?>
                </label>
                <select id="destination_users" name="course_list[]" multiple size="15"
                        class="w-full h-[400px] p-2 border border-gray-300 rounded-md">
                    <?php foreach ($userGroupList as $item): ?>
                        <option value="<?php echo $item['id']; ?>">
                            <?php echo $item['title']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mt-6 text-center">
            <button type="submit" class="rounded-lg px-6 py-2 shadow focus:outline-none focus:ring btn--primary">
                <?php echo get_lang('Save'); ?>
            </button>
        </div>
    </form>

<script>

function moveItem(origin, destination) {
    if (!origin || !destination) return;
    for (let i = 0; i < origin.options.length; i++) {
        if (origin.options[i].selected) {
            destination.options[destination.length] =
                new Option(origin.options[i].text, origin.options[i].value);
            origin.options[i] = null;
            i--;
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
	if(a.text.toLowerCase() > b.text.toLowerCase()){
		return 1;
	}
	if(a.text.toLowerCase() < b.text.toLowerCase()){
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
		if(xhr_object.readyState === 4) {
			document.getElementById('content_source').innerHTML = result = xhr_object.responseText;
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

function remove_item(origin) {
    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            origin.options[i]=null;
            i = i-1;
        }
    }
}
</script>
<?php
Display::display_footer();
