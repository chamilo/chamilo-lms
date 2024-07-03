<?php
/* For licensing terms, see /license.txt */
/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
// resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$xajax = new xajax();
$xajax->registerFunction(['search_users', 'AccessUrlEditUsersToUrl', 'search_users']);

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_global_admin_script();

if (!api_get_multiple_access_url()) {
    header('Location: index.php');
    exit;
}

// Database Table Definitions
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
// setting breadcrumbs
$tool_name = get_lang('EditUsersToURL');
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
function add_user_to_url(code, content) {
	document.getElementById("user_to_add").value = "";
	document.getElementById("ajax_list_users").innerHTML = "";
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

$errorMsg = '';
$message = '';
$userGroup = new UserGroup();
$allUserGroup = $userGroup->get_all_group_tags();
$checkList = [];
$join = '';
$where = '';

if (isset($_POST['form_sent'])) {
    $form_sent = $_POST['form_sent'];
    $UserList = ($_POST['sessionUsersList'] ?? []);

    if (!is_array($UserList)) {
        $UserList = [];
    }
    if ($form_sent == 0) {
        $tblUsergroupRelUser = Database::get_main_table(TABLE_USERGROUP_REL_USER);
        if (isset($_POST['no_any_class'])) {
            $where = "AND u.id NOT IN(SELECT g.user_id FROM $tblUsergroupRelUser g)";
        } else {
            foreach ($allUserGroup as $userGroup) {
                if (isset($_POST[$userGroup['id']])) {
                    $checkList[] = $userGroup['id'];
                    $where .= $userGroup['id'].',';
                }
            }
            if (count($checkList) > 0) {
                $join = "INNER JOIN $tblUsergroupRelUser g ON u.user_id = g.user_id";
                $where = trim($where, ',');
                $where = "AND g.usergroup_id IN($where)";
            }
        }
    }
    if ($form_sent == 1) {
        if ($access_url_id == 0) {
            Display::addFlash(Display::return_message(get_lang('SelectURL')));
            header('Location: access_url_edit_users_to_url.php');
            exit;
        } elseif (is_array($UserList)) {
            $result = UrlManager::update_urls_rel_user($UserList, $access_url_id);
            $url_info = UrlManager::get_url_data_from_id($access_url_id);
            if (!empty($result)) {
                $message .= 'URL: '.$url_info['url'].'<br />';
            }

            if (!empty($result['users_added'])) {
                $message .= '<h4>'.get_lang('UsersAdded').':</h4>';
                $i = 1;
                $user_added_list = [];
                foreach ($result['users_added'] as $user) {
                    $user_info = api_get_user_info($user);
                    if (!empty($user_info)) {
                        $user_added_list[] = $i.'. '.api_get_person_name($user_info['firstname'], $user_info['lastname'], null, null, null, $user_info['username']);
                        $i++;
                    }
                }
                if (!empty($user_added_list)) {
                    $message .= implode(', ', $user_added_list);
                }
            }

            if (!empty($result['users_deleted'])) {
                $message .= '<br /><h4>'.get_lang('UsersDeleted').': </h4>';
                $user_deleted_list = [];
                $i = 1;
                foreach ($result['users_deleted'] as $user) {
                    $user_info = api_get_user_info($user);
                    if (!empty($user_info)) {
                        $user_deleted_list[] = $i.'. '.api_get_person_name($user_info['firstname'], $user_info['lastname']);
                        $i++;
                    }
                }
                if (!empty($user_deleted_list)) {
                    $message .= implode(', ', $user_deleted_list);
                }
            }
        }
    }
}

Display::display_header($tool_name);

if (!empty($message)) {
    echo Display::return_message($message, 'normal', false);
}

echo '<div class="actions">';
echo Display::url(
    Display::return_icon('view_more_stats.gif', get_lang('AddUserToURL'), ''),
    api_get_path(WEB_CODE_PATH).'admin/access_url_add_users_to_url.php'
);
echo '</div>';

api_display_tool_title($tool_name);

$nosessionUsersList = $sessionUsersList = [];
$ajax_search = $add_type == 'unique' ? true : false;

$showAndOrderByOfficialCode = api_get_configuration_value('multiple_access_url_user_management_show_and_order_by_official_code');

if ($ajax_search) {
    $Users = UrlManager::get_url_rel_user_data($access_url_id, null, $join, $where);
    foreach ($Users as $user) {
        $sessionUsersList[$user['user_id']] = $user;
    }
} else {
    if ($showAndOrderByOfficialCode) {
        $order_clause = 'ORDER BY official_code, username';
    } else {
        $order_clause = api_sort_by_first_name() ? ' ORDER BY username, firstname, lastname' : ' ORDER BY username, lastname, firstname';
    }

    $Users = UrlManager::get_url_rel_user_data(null, $order_clause);
    foreach ($Users as $user) {
        if ($user['access_url_id'] == $access_url_id) {
            $sessionUsersList[$user['user_id']] = $user;
        }
    }

    $sql = "SELECT u.user_id, lastname, firstname, username, official_code
	  	  	FROM $tbl_user u
            $join
            WHERE u.status <> ".ANONYMOUS."
            $where
            $order_clause";
    $result = Database::query($sql);
    $Users = Database::store_result($result);
    $user_list_leys = array_keys($sessionUsersList);
    foreach ($Users as $user) {
        if (!in_array($user['user_id'], $user_list_leys)) {
            $nosessionUsersList[$user['user_id']] = $user;
        }
    }
}

if ($add_type == 'multiple') {
    $link_add_type_unique = '<a href="'.api_get_self().'?add_type=unique&access_url_id='.$access_url_id.'">'.get_lang('SessionAddTypeUnique').'</a>';
    $link_add_type_multiple = get_lang('SessionAddTypeMultiple');
} else {
    $link_add_type_unique = get_lang('SessionAddTypeUnique');
    $link_add_type_multiple = '<a href="'.api_get_self().'?add_type=multiple&access_url_id='.$access_url_id.'">'.get_lang('SessionAddTypeMultiple').'</a>';
}
$url_list = UrlManager::get_url_data();
?>

<div style="text-align: left;">
<?php echo $link_add_type_unique; ?>&nbsp;|&nbsp;<?php echo $link_add_type_multiple; ?>
</div>
<br /><br />
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;" <?php if ($ajax_search) {
    echo ' onsubmit="valide();"';
} ?> >
<?php echo get_lang('SelectUrl').' : '; ?>
<select name="access_url_id" onchange="javascript:send();">
<option value="0"> <?php echo get_lang('SelectUrl'); ?></option>
        <?php
        $url_selected = '';
        foreach ($url_list as $url_obj) {
            $checked = '';
            if (!empty($access_url_id)) {
                if ($url_obj['id'] == $access_url_id) {
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

<?php

if (count($allUserGroup) > 0) {
    echo get_lang('FilterByClass').' : ';
    echo '<ul>';
    foreach ($allUserGroup as $userGroup) {
        $checked = in_array($userGroup['id'], $checkList) ? 'checked' : '';
        echo '<li>';
        echo '<input type="checkbox" name="'.$userGroup['id'].'" value="'.$userGroup['id'].'" onclick="javascript:send();" '.$checked.'>';
        echo ' '.$userGroup['name'];
        echo '</li>';
    }
    echo '</ul>';

    $checked = isset($_POST['no_any_class']) ? 'checked' : '';
    echo '<input type="checkbox" name="no_any_class" onclick="javascript:send();" '.$checked.'> ';
    echo get_lang('NotInAnyClass');
}
?>
<br /><br />

<input type="hidden" name="form_sent" value="1" />
<input type="hidden" name="add_type" value = "<?php echo $add_type; ?>" />

<?php
if (!empty($errorMsg)) {
    echo Display::return_message($errorMsg, 'normal'); //main API
}
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
    <td>
    <h3>
    <?php
        $total_users = count($nosessionUsersList) + count($sessionUsersList);
        echo get_lang('TotalAvailableUsers').' '.$total_users;
    ?>
    </h3>
    </td>
</tr>
<tr>
  <td align="center"><b><?php echo get_lang('UserListInPlatform'); ?> : <?php echo count($nosessionUsersList); ?></b>
  </td>
  <td></td>
  <td align="center"><b><?php echo get_lang('UserListIn').' '.$url_selected; ?> : <?php echo count($sessionUsersList); ?></b></td>
</tr>
<tr>
  <td align="center">
  <div id="content_source">
    <?php if ($ajax_search) {
        ?>
    <input type="text" id="user_to_add" onkeyup="xajax_search_users(this.value,document.formulaire.access_url_id.options[document.formulaire.access_url_id.selectedIndex].value)" />
    <div id="ajax_list_users"></div>
    <?php
    } else {
        ?>
    <select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:380px;">
    <?php
        $userOfficialCode = '';
        foreach ($nosessionUsersList as $enreg) {
            if ($showAndOrderByOfficialCode) {
                $userOfficialCode = $enreg['official_code'].' - ';
            } ?>
    <option value="<?php echo $enreg['user_id']; ?>"><?php echo $userOfficialCode.$enreg['username'].' - '.api_get_person_name($enreg['firstname'], $enreg['lastname']); ?></option>
    <?php
        }
        unset($nosessionUsersList); ?>
    </select>
        <?php
    }
    ?>
  </div>
  </td>
  <td width="10%" valign="middle" align="center">
    <?php if ($ajax_search) {
        ?>
        <button class="btn btn-default" type="button" onclick="remove_item(document.getElementById('destination_users'))">
            <em class="fa fa-arrow-left"></em>
        </button>
    <?php
    } else {
        ?>
        <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))" >
            <em class="fa fa-arrow-right"></em>
        </button>
        <br /><br />
        <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))" >
            <em class="fa fa-arrow-left"></em>

        </button>
    <?php
    } ?>
	<br /><br /><br /><br /><br /><br />
  </td>
  <td align="center">
  <select id="destination_users" name="sessionUsersList[]" multiple="multiple" size="15" style="width:380px;">
    <?php
        foreach ($sessionUsersList as $enreg) {
            if ($showAndOrderByOfficialCode) {
                $userOfficialCode = $enreg['official_code'].' - ';
            } ?>
        <option value="<?php echo $enreg['user_id']; ?>">
            <?php echo $userOfficialCode.$enreg['username'].' - '.api_get_person_name($enreg['firstname'], $enreg['lastname']); ?>
        </option>
    <?php
        }
    unset($sessionUsersList);
    ?>
  </select></td>
</tr>
<tr>
	<td colspan="3" align="center">
		<br />
        <?php
        if (isset($_GET['add'])) {
            echo '<button class="save" type="button" onclick="valide()" >'.get_lang('AddUsersToURL').'</button>';
        } else {
            echo '<button class="save" type="button" onclick="valide()" >'.get_lang('EditUsersToURL').'</button>';
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
	if(a.text.toLowerCase() > b.text.toLowerCase()){
		return 1;
	}
	if(a.text.toLowerCase() < b.text.toLowerCase()){
		return -1;
	}
	return 0;
}

function valide() {
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
		if (xhr_object.readyState == 4) {
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
