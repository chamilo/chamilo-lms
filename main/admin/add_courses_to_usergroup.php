<?php
/* For licensing terms, see /license.txt */

// Resetting the course id.
$cidReset = true;

// Including some necessary files.
require_once __DIR__.'/../inc/global.inc.php';

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$usergroup = new UserGroup();
$data = $usergroup->get($id);
$usergroup->protectScript($data);

$xajax = new xajax();
$xajax->registerFunction('search');

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

// Setting breadcrumbs.
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'usergroups.php', 'name' => get_lang('Classes')];

// Setting the name of the tool.
$tool_name = get_lang('SubscribeClassToCourses');

$add_type = 'multiple';
if (isset($_REQUEST['add_type']) && $_REQUEST['add_type'] != '') {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$add = isset($_GET['add']) ? Security::remove_XSS($_GET['add']) : null;
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<script>
function remove_item(origin) {
    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            origin.options[i]=null;
            i = i-1;
        }
    }
}

</script>';

$errorMsg = '';
if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    $elements_posted = $_POST['elements_in_name'];
    if (!is_array($elements_posted)) {
        $elements_posted = [];
    }
    if ($form_sent == 1) {
        $usergroup->subscribe_courses_to_usergroup($id, $elements_posted);
        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location: usergroups.php');
        exit;
    }
}

// Filters
$filters = [
    ['type' => 'text', 'name' => 'code', 'label' => get_lang('CourseCode')],
    ['type' => 'text', 'name' => 'title', 'label' => get_lang('Title')],
];

$searchForm = new FormValidator('search', 'get', api_get_self().'?id='.$id);
$searchForm->addHeader(get_lang('AdvancedSearch'));
$renderer = &$searchForm->defaultRenderer();
$searchForm->addElement('hidden', 'id', $id);
foreach ($filters as $param) {
    $searchForm->addElement($param['type'], $param['name'], $param['label']);
}
$searchForm->addButtonSearch();

$filterData = [];
if ($searchForm->validate()) {
    $filterData = $searchForm->getSubmitValues();
}

$conditions = [];
if (!empty($filters) && !empty($filterData)) {
    foreach ($filters as $filter) {
        if (isset($filter['name']) && isset($filterData[$filter['name']])) {
            $value = $filterData[$filter['name']];
            if (!empty($value)) {
                $conditions[$filter['name']] = $value;
            }
        }
    }
}

$course_list_in = $usergroup->get_courses_by_usergroup($id, true);

$onlyThisCourseList = [];
if ($usergroup->allowTeachers()) {
    $userId = api_get_user_id();
    $courseList = CourseManager::getCoursesFollowedByUser($userId, COURSEMANAGER);
    if (!empty($courseList)) {
        $onlyThisCourseList = array_column($courseList, 'id');
    }
}

$course_list = CourseManager::get_courses_list(
    0,
    0,
    'title',
    'asc',
    -1,
    null,
    api_get_current_access_url_id(),
    false,
    $conditions,
    $onlyThisCourseList
);

$elements_not_in = $elements_in = [];
foreach ($course_list_in as $course) {
    $elements_in[$course['id']] = $course['title']." (".$course['visual_code'].")";
}

if (!empty($course_list)) {
    foreach ($course_list as $item) {
        if (isset($elements_in[$item['id']])) {
            continue;
        }
        $elements_not_in[$item['id']] = $item['title']." (".$item['visual_code'].")";
    }
}

$ajax_search = $add_type == 'unique' ? true : false;

// checking for extra field with filter on
function search($needle, $type)
{
    global $elements_in;
    $xajax_response = new xajaxResponse();
    $return = '';
    if (!empty($needle) && !empty($type)) {
        if ($type != 'single') {
            $list = CourseManager::get_courses_list(
                0,
                0,
                'title',
                'ASC',
                -1,
                $needle
            );
        }
        if ($type != 'single') {
            $return .= '<select id="elements_not_in" name="elements_not_in_name[]" multiple="multiple" size="15" style="width:360px;">';

            foreach ($list as $row) {
                if (!in_array($row['id'], array_keys($elements_in))) {
                    $return .= '<option value="'.$row['id'].'">'.$row['title'].' ('.$row['visual_code'].')</option>';
                }
            }
            $return .= '</select>';
            $xajax_response->addAssign('ajax_list_multiple', 'innerHTML', api_utf8_encode($return));
        }
    }

    return $xajax_response;
}

$xajax->processRequests();
Display::display_header($tool_name);

if ($add_type == 'multiple') {
    $link_add_type_unique = '<a href="'.api_get_self().'?add='.$add.'&add_type=unique">'.
        Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'</a>';
    $link_add_type_multiple = Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple');
} else {
    $link_add_type_unique = Display::return_icon('single.gif').get_lang('SessionAddTypeUnique');
    $link_add_type_multiple = '<a href="'.api_get_self().'?add='.$add.'&add_type=multiple">'.
        Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').'</a>';
}

echo '<div class="actions">';
echo '<a href="usergroups.php">';
echo Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM).'</a>';
echo Display::url(get_lang('AdvancedSearch'), '#', ['class' => 'advanced_options', 'id' => 'advanced_search']);
echo '</div>';

echo '<div id="advanced_search_options" style="display:none">';
$searchForm->display();
echo '</div>';
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?id=<?php echo $id; if (!empty($_GET['add'])) {
    echo '&add=true';
} ?>" style="margin:0px;" <?php if ($ajax_search) {
    echo ' onsubmit="valide();"';
}?>>
<?php echo '<legend>'.$data['name'].': '.$tool_name.'</legend>';
echo Display::input('hidden', 'id', $id);
echo Display::input('hidden', 'form_sent', '1');
echo Display::input('hidden', 'add_type', null);
if (!empty($errorMsg)) {
    echo Display::return_message($errorMsg, 'normal'); //main API
}
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
  <td align="center"><b><?php echo get_lang('CoursesInPlatform'); ?> :</b>
  </td>
  <td></td>
  <td align="center"><b><?php echo get_lang('CoursesInGroup'); ?> :</b></td>
</tr>

<?php if ($add_type == 'multiple') {
    ?>
<tr>
<td align="center">
<?php echo get_lang('FirstLetterCourseTitle'); ?> :
    <select name="firstLetterUser" onchange = "xajax_search(this.value,'multiple')" >
    <option value = "%">--</option>
    <?php
    echo Display::get_alphabet_options(); ?>
    </select>
</td>
<td align="center">&nbsp;</td>
</tr>
<?php
} ?>
<tr>
  <td align="center">
  <div id="content_source">
      <?php
      if (!($add_type == 'multiple')) {
          ?>
        <input type="text" id="user_to_add" onkeyup="xajax_search_users(this.value,'single')" />
        <div id="ajax_list_users_single"></div>
        <?php
      } else {
          ?>
      <div id="ajax_list_multiple">
        <?php
        echo Display::select(
            'elements_not_in_name',
            $elements_not_in,
            '',
            ['style' => 'width:360px', 'multiple' => 'multiple', 'id' => 'elements_not_in', 'size' => '15px'],
            false
        ); ?>
      </div>
    <?php
      }
     ?>
  </div>
  </td>
  <td width="10%" valign="middle" align="center">
  <?php
  if ($ajax_search) {
      ?>
    <button class="btn bt-default" type="button" onclick="remove_item(document.getElementById('elements_in'))" >
        <em class="fa fa-arrow-left"></em>
    </button>
  <?php
  } else {
      ?>
    <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('elements_not_in'), document.getElementById('elements_in'))" onclick="moveItem(document.getElementById('elements_not_in'), document.getElementById('elements_in'))">
        <em class="fa fa-arrow-right"></em>
    </button>
    <br /><br />
    <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('elements_in'), document.getElementById('elements_not_in'))" onclick="moveItem(document.getElementById('elements_in'), document.getElementById('elements_not_in'))">
        <em class="fa fa-arrow-left"></em>
    </button>
    <?php
  }
  ?>
    <br /><br /><br /><br /><br /><br />
  </td>
  <td align="center">
<?php
echo Display::select(
    'elements_in_name[]',
    $elements_in,
    '',
    ['style' => 'width:360px', 'multiple' => 'multiple', 'id' => 'elements_in', 'size' => '15px'],
    false
);
unset($sessionUsersList);
?>
 </td>
</tr>
<tr>
    <td colspan="3" align="center">
        <br />
        <?php
        echo '<button class="btn btn-primary" type="button" value="" onclick="valide()" >'.get_lang('SubscribeClassToCourses').'</button>';
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
    if(a.text.toLowerCase() > b.text.toLowerCase()){
        return 1;
    }
    if(a.text.toLowerCase() < b.text.toLowerCase()){
        return -1;
    }
    return 0;
}

function valide() {
    var options = document.getElementById('elements_in').options;
    for (i = 0 ; i<options.length ; i++)
        options[i].selected = true;
    document.forms.formulaire.submit();
}

</script>
<?php
Display::display_footer();
