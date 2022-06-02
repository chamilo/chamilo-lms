<?php
/* For licensing terms, see /license.txt */

// resetting the course id
$cidReset = true;

// including some necessary files
require_once __DIR__.'/../inc/global.inc.php';

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$usergroup = new UserGroup();
$data = $usergroup->get($id);
$usergroup->protectScript($data);

$xajax = new xajax();
$xajax->registerFunction('search_usergroup_sessions');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'usergroups.php', 'name' => get_lang('Classes')];

// setting the name of the tool
$tool_name = get_lang('SubscribeClassToSessions');

$add_type = 'multiple';
if (isset($_REQUEST['add_type']) && $_REQUEST['add_type'] != '') {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<script>
function add_user_to_session (code, content) {
    document.getElementById("user_to_add").value = "";
    document.getElementById("ajax_list_users_single").innerHTML = "";
    destination = document.getElementById("elements_in");
    for (i=0;i<destination.length;i++) {
        if(destination.options[i].text == content) {
                return false;
        }
    }
    destination.options[destination.length] = new Option(content,code);
    destination.selectedIndex = -1;
    sortOptions(destination.options);
}
function remove_item(origin) {
    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            origin.options[i]=null;
            i = i-1;
        }
    }
}

function display_advanced_search () {
    if ($("#advancedSearch").css("display") == "none") {
        $("#advancedSearch").css("display","block");
        $("#img_plus_and_minus").html(\'&nbsp;'.Display::return_icon('div_hide.gif', get_lang('Hide'), ['style' => 'vertical-align:middle']).'&nbsp;'.get_lang('AdvancedSearch').'\');
    } else {
        $("#advancedSearch").css("display","none");
        $("#img_plus_and_minus").html(\'&nbsp;'.Display::return_icon('div_show.gif', get_lang('Show'), ['style' => 'vertical-align:middle']).'&nbsp;'.get_lang('AdvancedSearch').'\');
    }
}

function validate_filter() {
    document.formulaire.add_type.value = \''.$add_type.'\';
    document.formulaire.form_sent.value=0;
    document.formulaire.submit();
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
        //added a parameter to send emails when registering a user
        $usergroup->subscribe_sessions_to_usergroup($id, $elements_posted);
        header('Location: usergroups.php');
        exit;
    }
}
$session_list_in = $usergroup->get_sessions_by_usergroup($id);

$onlyThisSessionList = [];
if ($usergroup->allowTeachers()) {
    $userId = api_get_user_id();
    $sessionList = SessionManager::getSessionsFollowedByUser($userId, COURSEMANAGER);
    if (!empty($sessionList)) {
        $onlyThisSessionList = array_column($sessionList, 'id');
    }
}
$session_list = SessionManager::get_sessions_list([], ['name'], null, null, 0, $onlyThisSessionList);
$elements_not_in = $elements_in = [];

if (!empty($session_list)) {
    foreach ($session_list as $session) {
        if (in_array($session['id'], $session_list_in)) {
            $elements_in[$session['id']] = $session['name'];
        } else {
            $elements_not_in[$session['id']] = $session['name'];
        }
    }
}

$ajax_search = $add_type === 'unique' ? true : false;

// checking for extra field with filter on
function search_usergroup_sessions($needle, $type)
{
    global $elements_in;
    $xajax_response = new xajaxResponse();
    $return = '';
    if (!empty($needle) && !empty($type)) {
        if ($type == 'searchbox') {
            $session_list = SessionManager::get_sessions_list(
                ['s.name' => ['operator' => 'LIKE', 'value' => "%$needle%"]]
            );
        } elseif ($type != 'single') {
            $session_list = SessionManager::get_sessions_list(
                ['s.name' => ['operator' => 'LIKE', 'value' => "$needle%"]]
            );
        }
        if ($type != 'single') {
            $return .= '<select id="elements_not_in" name="elements_not_in_name[]" multiple="multiple" size="15" style="width:360px;">';
            foreach ($session_list as $row) {
                if (!in_array($row['id'], array_keys($elements_in))) {
                    $return .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
                }
            }
            $return .= '</select>';
            $xajax_response->addAssign(
                'ajax_list_multiple',
                'innerHTML',
                api_utf8_encode($return)
            );
        }
    }

    return $xajax_response;
}
$xajax->processRequests();

Display::display_header($tool_name);

$add = (empty($_GET['add']) ? '' : Security::remove_XSS($_GET['add']));
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
echo '<a href="usergroups.php">'.
    Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
echo '<a href="javascript://" class="advanced_parameters" style="margin-top: 8px" onclick="display_advanced_search();"><span id="img_plus_and_minus">&nbsp;'.
    Display::return_icon('div_show.gif', get_lang('Show'), ['style' => 'vertical-align:middle']).' '.get_lang('AdvancedSearch').'</span></a>';
echo '</div>';
echo '<div id="advancedSearch" style="display: none">'.get_lang('SearchSessions'); ?> :
     <input name="SearchSession" onchange = "xajax_search_usergroup_sessions(this.value,'searchbox')" onkeyup="this.onchange()">
     </div>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?id=<?php echo $id; if (!empty($_GET['add'])) {
    echo '&add=true';
} ?>" style="margin:0px;" <?php if ($ajax_search) {
    echo ' onsubmit="valide();"';
}?>>
<?php
echo '<legend>'.$data['name'].': '.$tool_name.'</legend>';
echo Display::input('hidden', 'id', $id);
echo Display::input('hidden', 'form_sent', '1');
echo Display::input('hidden', 'add_type', null);
if (!empty($errorMsg)) {
    echo Display::return_message($errorMsg, 'normal'); //main API
}
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
  <td align="center"><b><?php echo get_lang('SessionsInPlatform'); ?> :</b>
  </td>
  <td></td>
  <td align="center"><b><?php echo get_lang('SessionsInGroup'); ?> :</b></td>
</tr>

<?php if ($add_type == 'multiple') {
    ?>
<tr>
<td align="center">
<?php echo get_lang('FirstLetterSessions'); ?> :
     <select name="firstLetterUser" onchange = "xajax_search_usergroup_sessions(this.value,'multiple')" >
      <option value = "%">--</option>
      <?php
        echo Display::get_alphabet_options(); ?>
     </select>
<?php echo '<br />'; ?>
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
          <?php echo Display::select(
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
    <button class="btn btn-default" type="button" onclick="remove_item(document.getElementById('elements_in'))" >
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
        echo '<button class="btn btn-primary" type="button" value="" onclick="valide()" >'.get_lang('SubscribeClassToSessions').'</button>';
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

function mysort(a, b){
    if(a.text.toLowerCase() > b.text.toLowerCase()){
        return 1;
    }
    if(a.text.toLowerCase() < b.text.toLowerCase()){
        return -1;
    }
    return 0;
}

function valide(){
    var options = document.getElementById('elements_in').options;
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
    nosessionUsers = makepost(document.getElementById('elements_not_in'));
    sessionUsers = makepost(document.getElementById('elements_in'));
    nosessionClasses = makepost(document.getElementById('origin_classes'));
    sessionClasses = makepost(document.getElementById('destination_classes'));
    xhr_object.send("nosessionusers="+nosessionUsers+"&sessionusers="+sessionUsers+"&nosessionclasses="+nosessionClasses+"&sessionclasses="+sessionClasses);

    xhr_object.onreadystatechange = function() {
        if(xhr_object.readyState == 4) {
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
</script>
<?php
Display::display_footer();
