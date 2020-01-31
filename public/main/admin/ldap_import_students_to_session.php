<?php
/* For licensing terms, see /license.txt */
/**
 * Edition script for sessions categories.
 */

// resetting the course id
$cidReset = true;
require_once '../inc/global.inc.php';
// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
// Access restrictions
api_protect_admin_script();
 require '../auth/ldap/authldap.php';

$annee_base = date('Y');

$tool_name = get_lang('LDAP Import');
// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

$htmlHeadXtra[] = '<script language="JavaScript" type="text/javascript">
var buttoncheck = 1;
function checkAll() {
	//var boxes = document.form.elements[\'checkboxes[]\'];
	var boxes = document.getElementsByName(\'checkboxes[]\');
	if (buttoncheck == 0) {
		for (i = 0; i < boxes.length; i++) {
			boxes[i].checked = true;
		}
		buttoncheck = 1;
		return "'.get_lang('none').'";
	}
	else {
		for (i = 0; i < boxes.length; i++) {
			boxes[i].checked = false;
		}
		buttoncheck = 0;
		return " '.get_lang('All').' ";
	}
}
</script>';

$annee = $_GET['annee'];
$id_session = $_POST['id_session'];

// form1 annee = 0; composante= 0 etape = 0
//if ($annee == "" && $composante == "" && $etape == "") {
if (empty($annee) && empty($id_session)) {
    Display::display_header($tool_name);
    echo '<div style="align:center">';
    echo Display::return_icon('group.gif', get_lang('Select a filter to find a matching string at the end of the OU attribute')).' '.get_lang('Select a filter to find a matching string at the end of the OU attribute');
    echo '<form method="get" action="'.api_get_self().'"><br />';
    echo '<em>'.get_lang('The OU attribute filter').' :</em> ';
    echo '<input  type="text" name="annee" size="4" maxlength="30" value="'.$annee_base.'"> ';
    echo '<input type="submit" value="'.get_lang('Submit').'">';
    echo '</form>';
    echo '</div>';
} elseif (!empty($annee) && empty($id_session)) {
    Display::display_header($tool_name);
    echo '<div style="align:center">';
    echo Display::return_icon(
            'course.png',
            get_lang('Select the session in which you want to import these users')
        ).' '.get_lang('Select the session in which you want to import these users').'<br />';
    echo '<form method="post" action="'.api_get_self().'?annee='.Security::remove_XSS($annee).'"><br />';
    echo '<select name="id_session">';

    $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
    $sql = "SELECT id,name,nbr_courses,access_start_date,access_end_date ".
        " FROM $tbl_session ".
        " ORDER BY name";
    $result = Database::query($sql);

    $sessions = Database::store_result($result);
    $nbr_results = count($sessions);
    foreach ($sessions as $row) {
        echo '<option value="'.$row['id'].'">'.api_htmlentities($row['name']).' ('.$row['access_start_date'].' - '.$row['access_end_date'].')</option>';
    }
    echo '</select>';
    echo '<input type="submit" value="'.get_lang('Submit').'">';
    echo '</form>';
    echo '</div>';
}
// form4  annee != 0; composante != 0 etape != 0
//elseif ($annee <> "" && $composante <> "" && $etape <> "" && $listeok != 'yes') {
elseif (!empty($annee) && !empty($id_session) && empty($_POST['confirmed'])) {
    Display::display_header($tool_name);
    echo '<div style="align: center;">';
    echo '<br />';
    echo '<br />';
    echo '<h3>'.Display::return_icon('group.gif', get_lang('Select learners')).' '.get_lang('Select learners').'</h3>';
    //echo "Connection ...";
    $ds = ldap_connect($ldap_host, $ldap_port) or die(get_lang('LDAP Connection Error'));
    ldap_set_version($ds);
    if ($ds) {
        $r = false;
        $res = ldap_handle_bind($ds, $r);

        //$sr = @ ldap_search($ds, "ou=people,$LDAPbasedn", "(|(edupersonprimaryorgunitdn=ou=$etape,ou=$annee,ou=diploma,o=Paris1,$LDAPbasedn)(edupersonprimaryorgunitdn=ou=02PEL,ou=$annee,ou=diploma,o=Paris1,$LDAPbasedn))");
        //echo "(ou=*$annee,ou=$composante)";
        $sr = @ldap_search($ds, $ldap_basedn, "(ou=*$annee)");

        $info = ldap_get_entries($ds, $sr);

        for ($key = 0; $key < $info["count"]; $key++) {
            $nom_form[] = $info[$key]["sn"][0];
            $prenom_form[] = $info[$key]["givenname"][0];
            $email_form[] = $info[$key]["mail"][0];
            // Get uid from dn
            //$dn_array=ldap_explode_dn($info[$key]["dn"],1);
            //$username_form[] = $dn_array[0]; // uid is first key
            $username_form[] = $info[$key]['uid'][0];
            $outab[] = $info[$key]["eduPersonPrimaryAffiliation"][0]; // Ici "student"
            //$val = ldap_get_values_len($ds, $entry, "userPassword");
            //$password_form[] = $val[0];
            $password_form[] = $info[$key]['userPassword'][0];
        }
        ldap_unbind($ds);
        asort($nom_form);
        reset($nom_form);
        $statut = 5;
        include 'ldap_form_add_users_group.php';
    } else {
        echo '<h4>'.get_lang('Unable to connect to').' '.$host.'</h4>';
    }
    echo '<br /><br />';
    echo '<a href="ldap_import_students.php?annee=">'.get_lang('Back to start new search').'</a>';
    echo '<br /><br />';
    echo '</div>';
} elseif (!empty($annee) && !empty($id_session) && ('yes' == $_POST['confirmed'])) {
    $id = $_POST['username_form'];
    $UserList = [];
    $userid_match_login = [];
    foreach ($id as $form_index => $user_id) {
        if (is_array($_POST['checkboxes']) && in_array($form_index, array_values($_POST['checkboxes']))) {
            $tmp = ldap_add_user($user_id);
            $UserList[] = $tmp;
            $userid_match_login[$tmp] = $user_id;
        }
    }
    if (!empty($_POST['id_session'])) {
        $num = 0;
        $tbl_session_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        foreach ($UserList as $user_id) {
            $res_user = Database::insert(
                $tbl_session_user,
                [
                    'session_id' => intval($id_session),
                    'user_id' => intval($user_id),
                    'registered_at' => api_get_utc_datetime(),
                ]
            );
            if (false !== $res_user) {
                $num++;
            }
        }

        if ($num > 0) {
            $sql = 'UPDATE '.$tbl_session.' SET nbr_users = (nbr_users + '.$num.') WHERE id = '.intval($id_session);
            $res = Database::query($sql);
        }
        header('Location: resume_session.php?id_session='.Security::remove_XSS($_POST['id_session']));
        exit;
    } else {
        $message = get_lang('No user added');
        Display::addFlash(Display::return_message($message, 'normal', false));
        Display::display_header($tool_name);
    }
    echo '<br /><br />';
    echo '<a href="ldap_import_students.php?annee=&composante=&etape=">'.get_lang('Back to start new search').'</a>';
    echo '<br /><br />';
}
Display::display_footer();
