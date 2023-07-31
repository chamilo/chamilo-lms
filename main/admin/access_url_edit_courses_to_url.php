<?php
/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
// resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$xajax = new xajax();
$xajax->registerFunction(
    ['search_courses', 'Accessurleditcoursestourl', 'search_courses']
);

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_global_admin_script();
if (!api_get_multiple_access_url()) {
    header('Location: index.php');
    exit;
}

// setting breadcrumbs
$tool_name = get_lang('EditCoursesToURL');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('MultipleAccessURLs')];

$add_type = 'multiple';
if (isset($_REQUEST['add_type']) && $_REQUEST['add_type'] != '') {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$access_url_id = 1;
if (isset($_REQUEST['access_url_id']) && $_REQUEST['access_url_id'] != '') {
    $access_url_id = Security::remove_XSS($_REQUEST['access_url_id']);
}

$xajax->processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<script>
function add_course_to_url(code, content) {
	document.getElementById("course_to_add").value = "";
	document.getElementById("ajax_list_courses").innerHTML = "";
	destination = document.getElementById("destination_courses");
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

    if ($form_sent == 1) {
        if ($access_url_id == 0) {
            Display::addFlash(Display::return_message(get_lang('SelectURL')));
            header('Location: access_url_edit_courses_to_url.php?');
        } elseif (is_array($course_list)) {
            UrlManager::update_urls_rel_course($course_list, $access_url_id);
            Display::addFlash(Display::return_message(get_lang('CoursesWereEdited')));
            header('Location: access_urls.php?');
        }
        exit;
    }
}

Display::display_header($tool_name);

echo '<div class="actions">';
echo Display::url(
    Display::return_icon('view_more_stats.gif', get_lang('AddCoursesToURL')),
    api_get_path(WEB_CODE_PATH).'admin/access_url_add_courses_to_url.php'
);
echo '</div>';

api_display_tool_title($tool_name);

$no_course_list = $course_list = [];
$ajax_search = $add_type == 'unique' ? true : false;

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

if ($add_type == 'multiple') {
    $link_add_type_unique = '<a href="'.api_get_self().'?add_type=unique&access_url_id='.$access_url_id.'">'.
        get_lang('SessionAddTypeUnique').'</a>';
    $link_add_type_multiple = get_lang('SessionAddTypeMultiple');
} else {
    $link_add_type_unique = get_lang('SessionAddTypeUnique');
    $link_add_type_multiple = '<a href="'.api_get_self().'?add_type=multiple&access_url_id='.$access_url_id.'">'.
        get_lang('SessionAddTypeMultiple').'</a>';
}
$url_list = UrlManager::get_url_data();
?>
<div style="text-align: left;">
    <?php echo $link_add_type_unique; ?>&nbsp;|&nbsp;<?php echo $link_add_type_multiple; ?>
</div>
<br /><br />
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;" <?php if ($ajax_search) {
    echo ' onsubmit="valide();"';
}?> >
    <?php echo get_lang('SelectUrl').' : '; ?>
    <select name="access_url_id" onchange="javascript:send();">
        <option value="0">-- <?php echo get_lang('SelectUrl'); ?> -- </option>
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
            if ($url_obj['active'] == 1) {
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
    <table border="0" cellpadding="5" cellspacing="0" width="100%">
        <!-- Users -->
        <tr>
            <td align="center"><b><?php echo get_lang('CourseListInPlatform'); ?> :</b>
            </td>
            <td></td>
            <td align="center"><b><?php echo get_lang('CourseListIn').' '.$url_selected; ?></b></td>
        </tr>
        <tr>
            <td align="center">
                <div id="content_source">
                    <?php
                    if ($ajax_search) {
                        ?>
                        <input type="text" id="course_to_add" onkeyup="xajax_search_courses(this.value,document.formulaire.access_url_id.options[document.formulaire.access_url_id.selectedIndex].value)" />
                        <div id="ajax_list_courses"></div>
                    <?php
                    } else {
                        ?>
                        <select id="origin_courses" name="no_course_list[]" multiple="multiple" size="15" style="width:380px;">
                            <?php
                            foreach ($no_course_list as $no_course) {
                                ?>
                                <option value="<?php echo $no_course['id']; ?>" title="<?php echo htmlentities($no_course['title'], ENT_QUOTES).' ('.$no_course['code'].')'; ?>"><?php echo $no_course['title'].' ('.$no_course['code'].')'; ?></option>
                            <?php
                            }
                        unset($no_course_list); ?>
                        </select>
                    <?php
                    }
                    ?>
                </div>
            </td>
            <td width="10%" valign="middle" align="center">
                <?php
                if ($ajax_search) {
                    ?>
                    <button class="btn btn-default" type="button" onclick="remove_item(document.getElementById('destination_courses'))" >
                        <em class="fa fa-arrow-left"></em>
                    </button>
                <?php
                } else {
                    ?>
                    <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('origin_courses'), document.getElementById('destination_courses'))" >
                        <em class="fa fa-arrow-right"></em>
                    </button>
                    <br /><br />
                    <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('destination_courses'), document.getElementById('origin_courses'))" >
                        <em class="fa fa-arrow-left"></em>
                    </button>
                <?php
                }
                ?>
                <br /><br /><br /><br /><br /><br />
            </td>
            <td align="center">
                <select id="destination_courses" name="course_list[]" multiple="multiple" size="15" style="width:380px;">
                    <?php
                    foreach ($course_list as $course) {
                        $courseInfo = api_get_course_info_by_id($course['id']); ?>
                        <option value="<?php echo $course['id']; ?>" title="<?php echo htmlentities($course['title'], ENT_QUOTES).' ('.$courseInfo['code'].')'; ?>">
                            <?php echo $course['title'].' ('.$courseInfo['code'].')'; ?>
                        </option>
                    <?php
                    }
                    unset($course_list);
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan="3" align="center">
                <br />
                <?php
                if (isset($_GET['add'])) {
                    echo '<button class="btn btn-default" onclick="valide()" >'.get_lang('AddCoursesToURL').'</button>';
                } else {
                    echo '<button class="btn btn-default" onclick="valide()" >'.get_lang('EditCoursesToURL').'</button>';
                }
                ?>
            </td>
        </tr>
    </table>
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
        var options = document.getElementById('destination_courses').options;
        for (i = 0 ; i<options.length ; i++)
            options[i].selected = true;
        document.forms.formulaire.submit();
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
