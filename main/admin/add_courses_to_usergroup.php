<?php
/* For licensing terms, see /license.txt */
/**
*   @package chamilo.admin
*/

// name of the language file that needs to be included
$language_file = array('admin','registration');

// resetting the course id
$cidReset = true;

// including some necessary files
require_once '../inc/global.inc.php';
require_once '../inc/lib/xajax/xajax.inc.php';
require_once api_get_path(LIBRARY_PATH).'usergroup.lib.php';

$xajax = new xajax();

//$xajax->debugOn();
$xajax->registerFunction('search');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => 'usergroups.php','name' => get_lang('Classes'));

// Database Table Definitions

// setting the name of the tool
$tool_name=get_lang('SubscribeClassToCourses');

$add_type = 'multiple';
if(isset($_REQUEST['add_type']) && $_REQUEST['add_type']!=''){
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script>
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

function validate_filter() {
        document.formulaire.add_type.value = \''.$add_type.'\';
        document.formulaire.form_sent.value=0;
        document.formulaire.submit();
}
</script>';


$form_sent  = 0;
$errorMsg   = '';
$sessions=array();
$usergroup = new UserGroup();
$id = intval($_GET['id']);
if($_POST['form_sent']) {
    $form_sent              = $_POST['form_sent'];    
    $elements_posted        = $_POST['elements_in_name'];     
    if (!is_array($elements_posted)) {
        $elements_posted=array();
    }
    if ($form_sent == 1) {
        $usergroup->subscribe_courses_to_usergroup($id, $elements_posted);
        header('Location: usergroups.php');
        exit;        
    }
}
$data               = $usergroup->get($id);
$course_list_in     = $usergroup->get_courses_by_usergroup($id);
$course_list        = CourseManager::get_courses_list(0,0,'title');

//api_display_tool_title($tool_name.' ('.$session_info['name'].')');
$elements_not_in = $elements_in= array();

if (!empty($course_list)) {
    foreach($course_list as $item) {        
        if (in_array($item['id'], $course_list_in)) {            
            $elements_in[$item['id']] = $item['title']." (".$item['code'].")";
        } else {
            $elements_not_in[$item['id']] = $item['title']." (".$item['code'].")";
        }
    }
}
$ajax_search = $add_type == 'unique' ? true : false;

//checking for extra field with filter on

function search($needle,$type) {
    global $tbl_user,$elements_in;
    $xajax_response = new XajaxResponse();
    $return = '';
    if (!empty($needle) && !empty($type)) {

        // xajax send utf8 datas... datas in db can be non-utf8 datas
        $charset = api_get_system_encoding();
        $needle  = Database::escape_string($needle);
        $needle  = api_convert_encoding($needle, $charset, 'utf-8');

        if ($type == 'single') {
            // search users where username or firstname or lastname begins likes $needle
          /*  $sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
                    WHERE (username LIKE "'.$needle.'%"
                    OR firstname LIKE "'.$needle.'%"
                OR lastname LIKE "'.$needle.'%") AND user.user_id<>"'.$user_anonymous.'"   AND user.status<>'.DRH.''.
                $order_clause.
                ' LIMIT 11';*/
        } else {
            $list = CourseManager::get_courses_list(0, 0, 2, 'ASC', -1, $needle);
        }     
        $i=0;        
        if ($type=='single') {
            /*
            while ($user = Database :: fetch_array($rs)) {
                $i++;
                if ($i<=10) {
                    $person_name = api_get_person_name($user['firstname'], $user['lastname']);
                    $return .= '<a href="javascript: void(0);" onclick="javascript: add_user_to_session(\''.$user['user_id'].'\',\''.$person_name.' ('.$user['username'].')'.'\')">'.$person_name.' ('.$user['username'].')</a><br />';
                } else {
                    $return .= '...<br />';
                }
            }
            $xajax_response -> addAssign('ajax_list_users_single','innerHTML',api_utf8_encode($return));*/
        } else {
            $return .= '<select id="elements_not_in" name="elements_not_in_name[]" multiple="multiple" size="15" style="width:360px;">';
            
            foreach ($list as $row ) {         
                if (!in_array($row['id'], array_keys($elements_in))) {       
                    $return .= '<option value="'.$row['id'].'">'.$row['title'].' ('.$row['code'].')</option>';
                }
            }
            $return .= '</select>';
            $xajax_response -> addAssign('ajax_list_multiple','innerHTML',api_utf8_encode($return));
        }
    }
    return $xajax_response;
}
$xajax -> processRequests();

Display::display_header($tool_name);

if ($add_type == 'multiple') {
    $link_add_type_unique = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=unique">'.Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'</a>';
    $link_add_type_multiple = Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple');
} else {
    $link_add_type_unique = Display::return_icon('single.gif').get_lang('SessionAddTypeUnique');
    $link_add_type_multiple = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=multiple">'.Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').'</a>';
}

echo '<div class="actions">';
echo '<a href="usergroups.php">'.Display::return_icon('back.png',get_lang('Back'), array(), ICON_SIZE_MEDIUM).'</a>';       
echo '</div>';
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?id=<?php echo $id; if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?>>

<?php echo '<legend>'.$data['name'].': '.$tool_name.'</legend>'; 

if ($add_type=='multiple') {
    if (is_array($extra_field_list)) {
        if (is_array($new_field_list) && count($new_field_list)>0 ) {
            echo '<h3>'.get_lang('FilterUsers').'</h3>';
            foreach ($new_field_list as $new_field) {
                echo $new_field['name'];
                $varname = 'field_'.$new_field['variable'];
                echo '&nbsp;<select name="'.$varname.'">';
                echo '<option value="0">--'.get_lang('Select').'--</option>';
                foreach ($new_field['data'] as $option) {
                    $checked='';
                    if (isset($_POST[$varname])) {
                        if ($_POST[$varname]==$option[1]) {
                            $checked = 'selected="true"';
                        }
                    }
                    echo '<option value="'.$option[1].'" '.$checked.'>'.$option[1].'</option>';
                }
                echo '</select>';
                echo '&nbsp;&nbsp;';
            }
            echo '<input type="button" value="'.get_lang('Filter').'" onclick="validate_filter()" />';
            echo '<br /><br />';
        }
    }
}
echo Display::input('hidden','id',$id);
echo Display::input('hidden','form_sent','1');
echo Display::input('hidden','add_type',null);
if(!empty($errorMsg)) {
    Display::display_normal_message($errorMsg); //main API
}
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
  <td align="center"><b><?php echo get_lang('CoursesInPlatform') ?> :</b>
  </td>
  <td></td>
  <td align="center"><b><?php echo get_lang('CoursesInGroup') ?> :</b></td>
</tr>

<?php if ($add_type=='multiple') { ?>
<tr>
<td align="center">
<?php echo get_lang('FirstLetterCourseTitle'); ?> :
     <select name="firstLetterUser" onchange = "xajax_search(this.value,'multiple')" >
      <option value = "%">--</option>
      <?php
        echo Display :: get_alphabet_options();
      ?>
     </select>
</td>
<td align="center">&nbsp;</td>
</tr>
<?php } ?>
<tr>
  <td align="center">
  <div id="content_source">
      <?php           
      if (!($add_type=='multiple')) {        
        ?>
        <input type="text" id="user_to_add" onkeyup="xajax_search_users(this.value,'single')" />
        <div id="ajax_list_users_single"></div>
        <?php
      } else {               
      ?>
      <div id="ajax_list_multiple">
        <?php echo Display::select('elements_not_in_name',$elements_not_in, '',array('style'=>'width:360px', 'multiple'=>'multiple','id'=>'elements_not_in','size'=>'15px'),false); ?> 
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
    <button class="arrowl" type="button" onclick="remove_item(document.getElementById('elements_in'))" ></button>
  <?php
  } else {
  ?>
    <button class="arrowr" type="button" onclick="moveItem(document.getElementById('elements_not_in'), document.getElementById('elements_in'))" onclick="moveItem(document.getElementById('elements_not_in'), document.getElementById('elements_in'))"></button>
    <br /><br />
    <button class="arrowl" type="button" onclick="moveItem(document.getElementById('elements_in'), document.getElementById('elements_not_in'))" onclick="moveItem(document.getElementById('elements_in'), document.getElementById('elements_not_in'))"></button>
    <?php
  }
  ?>
    <br /><br /><br /><br /><br /><br />
  </td>
  <td align="center">
<?php
    echo Display::select('elements_in_name[]', $elements_in, '', array('style'=>'width:360px', 'multiple'=>'multiple','id'=>'elements_in','size'=>'15px'),false );
    unset($sessionUsersList);
?>
 </td>
</tr>
<tr>
    <td colspan="3" align="center">
        <br />
        <?php
        echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('SubscribeClassToCourses').'</button>';
        ?>
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
<!--
function moveItem(origin , destination){

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


function loadUsersInSelect(select){

    var xhr_object = null;

    if(window.XMLHttpRequest) // Firefox
        xhr_object = new XMLHttpRequest();
    else if(window.ActiveXObject) // Internet Explorer
        xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
    else  // XMLHttpRequest non supporté par le navigateur
    alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");

    //xhr_object.open("GET", "loadUsersInSelect.ajax.php?id_session=<?php echo $id_session ?>&letter="+select.options[select.selectedIndex].text, false);
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
-->
</script>
<?php
Display::display_footer();
