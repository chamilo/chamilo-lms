<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;

// resetting the course id
$cidReset = true;

// including some necessary files
require_once __DIR__.'/../inc/global.inc.php';

$xajax = new xajax();
$xajax->registerFunction('search_sessions');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'career_dashboard.php', 'name' => get_lang('Careers and promotions')];

// Setting the name of the tool
$tool_name = get_lang('Subscribe sessions to promotions');
$add_type = 'multiple';
if (isset($_REQUEST['add_type']) && '' != $_REQUEST['add_type']) {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<script>
function add_user_to_session (code, content) {

    document.getElementById("user_to_add").value = "";
    document.getElementById("ajax_list_users_single").innerHTML = "";

    destination = document.getElementById("session_in_promotion");

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

$form_sent = 0;
$users = $sessions = [];
$promotion = new Promotion();
$id = (int) ($_GET['id']);
if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    $session_in_promotion_posted = $_POST['session_in_promotion_name'];
    if (!is_array($session_in_promotion_posted)) {
        $session_in_promotion_posted = [$session_in_promotion_posted];
    }
    if (1 == $form_sent) {
        // Added a parameter to send emails when registering a user
        SessionManager::subscribe_sessions_to_promotion($id, $session_in_promotion_posted);
        header('Location: promotions.php');
        exit;
    }
}

$promotion_data = $promotion->get($id);
$session_list = SessionManager::get_sessions_list([], ['name']);
$session_not_in_promotion = $session_in_promotion = [];

if (!empty($session_list)) {
    foreach ($session_list as $session) {
        $promotion_id = $session['promotion_id'];
        if (isset($promotion_id) && !empty($promotion_id)) {
            if ($promotion_id == $id) {
                $session_in_promotion[$session['id']] = $session['name'];
            } else {
                $session_not_in_promotion[$session['id']] = $session['name'];
            }
        } else {
            $session_not_in_promotion[$session['id']] = $session['name'];
        }
    }
}
$ajax_search = 'unique' === $add_type ? true : false;

// Checking for extra field with filter on

$xajax->processRequests();

Display::display_header($tool_name);

if ('multiple' === $add_type) {
    $link_add_type_unique = '<a href="'.api_get_self().'?id='.$id.'&add_type=unique">'.Display::getMdiIcon(ObjectIcon::SINGLE_ELEMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL).get_lang('Single registration').'</a>';
    $link_add_type_multiple = Display::getMdiIcon(ObjectIcon::MULTI_ELEMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL).get_lang('Multiple registration');
} else {
    $link_add_type_unique = Display::getMdiIcon(ObjectIcon::SINGLE_ELEMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL).get_lang('Single registration');
    $link_add_type_multiple = '<a href="'.api_get_self().'?id='.$id.'&add_type=multiple">'.Display::getMdiIcon(ObjectIcon::MULTI_ELEMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL).get_lang('Multiple registration').'</a>';
}

echo Display::toolbarAction(
    'url',
    [
        '<a href="promotions.php">'.Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')).'</a>',
    ]
);
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?id=<?php echo $id; if (!empty($add)) {
    echo '&add=true';
} ?>" style="margin:0px;" <?php if ($ajax_search) {
    echo ' onsubmit="valide();"';
}?>>
<?php echo '<legend>'.$tool_name.' '.$promotion_data['name'].'</legend>';

if ('multiple' == $add_type) {
    $extraField = new \ExtraField('session');
    $extra_field_list = $extraField->get_all_extra_field_by_type(ExtraField::FIELD_TYPE_SELECT);
    $new_field_list = [];
    if (is_array($extra_field_list) && (count($extra_field_list) > 0)) {
        echo '<h3>'.get_lang('Filter sessions').'</h3>';
        foreach ($extra_field_list as $new_field) {
            echo $new_field['name'];
            $varname = 'field_'.$new_field['variable'];
            echo '&nbsp;<select name="'.$varname.'">';
            echo '<option value="0">--'.get_lang('Select').'--</option>';
            foreach ($new_field['data'] as $option) {
                $checked = '';
                if (isset($_POST[$varname])) {
                    if ($_POST[$varname] == $option[1]) {
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
echo Display::input('hidden', 'id', $id);
echo Display::input('hidden', 'form_sent', '1');
echo Display::input('hidden', 'add_type', null);
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
  <td align="center"><b><?php echo get_lang('Sessions not subscribed'); ?> :</b>
  </td>
  <td></td>
  <td align="center"><b><?php echo get_lang('Sessions in this promotion'); ?> :</b></td>
</tr>

<?php if ('multiple' == $add_type) {
    ?>
<tr>
<td align="center">
<?php echo get_lang('First letter of session name'); ?> :
     <select name="firstLetterUser" onchange = "xajax_search_sessions(this.value,'multiple')" >
      <option value = "%">--</option>
      <?php
        echo Display :: get_alphabet_options(); ?>
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
      if (!('multiple' == $add_type)) {
          ?>
        <input type="text" id="user_to_add" onkeyup="xajax_search_users(this.value,'single')" />
        <div id="ajax_list_users_single"></div>
        <?php
      } else {
          ?>
      <div id="ajax_list_multiple">
        <?php echo Display::select('session_not_in_promotion_name', $session_not_in_promotion, '', ['style' => 'width:360px', 'multiple' => 'multiple', 'id' => 'session_not_in_promotion', 'size' => '15px'], false); ?>
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
    <button class="btn btn--plain" type="button" onclick="remove_item(document.getElementById('session_in_promotion'))" >
        <i class="mdi mdi-rewind-outline ch-tool-icon"></i>
    </button>
  <?php
  } else {
      ?>
    <button class="btn btn--plain" type="button" onclick="moveItem(document.getElementById('session_not_in_promotion'), document.getElementById('session_in_promotion'))" onclick="moveItem(document.getElementById('session_not_in_promotion'), document.getElementById('session_in_promotion'))">
        <i class="mdi mdi-fast-forward-outline ch-tool-icon"></i>
    </button>
    <br /><br />
    <button class="btn btn--plain" type="button" onclick="moveItem(document.getElementById('session_in_promotion'), document.getElementById('session_not_in_promotion'))" onclick="moveItem(document.getElementById('session_in_promotion'), document.getElementById('session_not_in_promotion'))">
        <i class="mdi mdi-rewind-outline ch-tool-icon"></i>
    </button>
    <?php
  }
  ?>
    <br /><br /><br /><br /><br /><br />
  </td>
  <td align="center">
<?php
    echo Display::select(
        'session_in_promotion_name[]',
        $session_in_promotion,
        '',
        ['style' => 'width:360px', 'multiple' => 'multiple', 'id' => 'session_in_promotion', 'size' => '15px'],
        false
    );
?>
 </td>
</tr>
<tr>
    <td colspan="3" align="center">
        <br />
        <?php
        echo '<button class="btn btn--primary" type="button" value="" onclick="valide()" >'.get_lang('Subscribe sessions to promotion').'</button>';
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
    if (a.text.toLowerCase() > b.text.toLowerCase()){
        return 1;
    }
    if (a.text.toLowerCase() < b.text.toLowerCase()){
        return -1;
    }
    return 0;
}

function valide(){
    var options = document.getElementById('session_in_promotion').options;
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
    alert("Your browser does not support XMLHTTPRequest...");

    xhr_object.open("POST", "loadUsersInSelect.ajax.php");
    xhr_object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    nosessionUsers = makepost(document.getElementById('session_not_in_promotion'));
    sessionUsers = makepost(document.getElementById('session_in_promotion'));
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

function makepost(select) {
    var options = select.options;
    var ret = "";
    for (i = 0 ; i<options.length ; i++)
        ret = ret + options[i].value +'::'+options[i].text+";;";
    return ret;
}
</script>
<?php
Display::display_footer();

function search_sessions($needle, $type)
{
    global $session_in_promotion;
    $xajax_response = new xajaxResponse();
    $return = '';
    if (!empty($needle) && !empty($type)) {
        $session_list = SessionManager::get_sessions_list(
            ['s.title' => ['operator' => 'LIKE', 'value' => "$needle%"]]
        );
        $return .= '<select id="session_not_in_promotion" name="session_not_in_promotion_name[]" multiple="multiple" size="15" style="width:360px;">';
        foreach ($session_list as $row) {
            if (!in_array($row['id'], array_keys($session_in_promotion))) {
                $return .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
            }
        }
        $return .= '</select>';
        $xajax_response->addAssign('ajax_list_multiple', 'innerHTML', api_utf8_encode($return));
    }

    return $xajax_response;
}
