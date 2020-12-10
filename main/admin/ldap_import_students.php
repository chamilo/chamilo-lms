<?php
/* For licensing terms, see /license.txt */

/**
 * Script to import students from LDAP.
 *
 * @package chamilo.admin
 * Copyright (c) 2007 Mustapha Alouani (supervised by Michel Moreau-Belliard)
 */
// resetting the course id
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script();
require '../auth/ldap/authldap.php';

$annee_base = date('Y');

$tool_name = get_lang('LDAPImport');
// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$htmlHeadXtra[] = '<script>
var buttoncheck = 1;
function checkAll() {
    //var boxes = document.form.elements[\'checkboxes[]\'];
    var boxes = document.getElementsByName(\'checkboxes[]\');
    if (buttoncheck == 0) {
        for (i = 0; i < boxes.length; i++) {
            boxes[i].checked = true;
        }
        buttoncheck = 1;
        return "'.get_lang('None').'";
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
$composante = $_GET['composante'];
$etape = $_GET['etape'];
$course = $_POST['course'];
// form1 annee = 0; composante= 0 etape = 0
//if ($annee == "" && $composante == "" && $etape == "") {
if (empty($annee) && empty($course)) {
    Display::display_header($tool_name);
    echo '<div style="align:center">';
    Display::display_icon('group.gif', get_lang('LDAPSelectFilterOnUsersOU'));
    echo get_lang('LDAPSelectFilterOnUsersOU');
    //echo '<em>'.get_lang('ToDoThisYouMustEnterYearComponentAndComponentStep').'</em><br />';
    ///echo get_lang('FollowEachOfTheseStepsStepByStep').'<br />';

    echo '<form method="get" action="'.api_get_self().'"><br />';
    echo '<em>'.get_lang('LDAPOUAttributeFilter').' :</em> ';
    echo '<input  type="text" name="annee" size="4" maxlength="30" value="'.$annee_base.'"><br />';
    echo '<input type="submit" value="'.get_lang('Submit').'">';
    echo '</form>';
    echo '</div>';
} elseif (!empty($annee) && empty($course)) {
    Display::display_header($tool_name);
    echo '<div style="align:center">';
    echo Display::return_icon('course.png', get_lang('SelectCourseToImportUsersTo')).' '.get_lang('SelectCourseToImportUsersTo').'<br />';
    echo '<form method="post" action="'.api_get_self().'?annee='.Security::remove_XSS($annee).'"><br />';
    echo '<select name="course">';
    $courses = CourseManager::get_courses_list();
    foreach ($courses as $row) {
        echo '<option value="'.$row['code'].'">'.api_htmlentities($row['title']).'</option>';
    }
    echo '</select>';
    echo '<input type="submit" value="'.get_lang('Submit').'">';
    echo '</form>';
    echo '</div>';
} elseif (!empty($annee) && !empty($course) && empty($_POST['confirmed'])) {
    // form4  annee != 0; composante != 0 etape != 0
    //elseif ($annee <> "" && $composante <> "" && $etape <> "" && $listeok != 'yes') {
    Display::display_header($tool_name);
    echo '<div style="align: center;">';
    echo '<br />';
    echo '<br />';
    echo '<h3>'.Display::return_icon('group.gif', get_lang('SelectStudents')).' '.get_lang('SelectStudents').'</h3>';
    //echo "Connection ...";
    $ds = ldap_connect($ldap_host, $ldap_port) or exit(get_lang('LDAPConnectionError'));
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
        echo '<h4>'.get_lang('UnableToConnectTo').' '.$host.'</h4>';
    }
    echo '<br /><br />';
    echo '<a href="ldap_import_students.php?annee=&composante=&etape=">'.get_lang('BackToNewSearch').'</a>';
    echo '<br /><br />';
    echo '</div>';
} elseif (!empty($annee) && !empty($course) && ($_POST['confirmed'] == 'yes')) {
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
    if (!empty($_POST['course'])) {
        foreach ($UserList as $user_id) {
            CourseManager::subscribeUser($user_id, $_POST['course']);
        }
        header('Location: course_information.php?code='.Security::remove_XSS($_POST['course']));
        exit;
    } else {
        $message = get_lang('NoUserAdded');
        Display::addFlash(Display::return_message($message, 'normal', false));
        Display::display_header($tool_name);
    }
    echo '<br /><br />';
    echo '<a href="ldap_import_students.php?annee=&composante=&etape=">'.get_lang('BackToNewSearch').'</a>';
    echo '<br /><br />';
}
Display::display_footer();
