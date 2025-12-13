<?php
/* For licensing terms, see /license.txt */

/**
 * Script to import students from LDAP.
 */

use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\LdapAuthenticatorHelper;
use Chamilo\CoreBundle\Security\Authenticator\Ldap\LdapAuthenticator;

// resetting the course id
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script();

$httpRequest = Container::getRequest();

$annee_base = date('Y');

$tool_name = get_lang('LDAP Import');
// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

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

$annee = $httpRequest->query->get('annee');
$composante = $httpRequest->query->get('composante');
$etape = $httpRequest->query->get('etape');
$course = $httpRequest->request->get('course');
// form1 annee = 0; composante= 0 etape = 0
//if ($annee == "" && $composante == "" && $etape == "") {
if (empty($annee) && empty($course)) {
    Display::display_header($tool_name);
    echo '<div class="space-y-4">';
    echo '<p>';
    echo Display::getMdiIcon(ObjectIcon::GROUP, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Select a filter to find a matching string at the end of the OU attribute'));
    echo get_lang('Select a filter to find a matching string at the end of the OU attribute');
    echo '</p>';
    //echo '<em>'.get_lang('In order to do this, you must enter the year, the component and the component's step').'</em><br />';
    ///echo get_lang('Follow each of these steps, step by step').'<br />';

    $form = new FormValidator('ldap_import_students', 'get', api_get_self());
    $form->addNumeric('annee', get_lang('The OU attribute filter'), ['step' => 1]);
    $form->addButtonFilter(get_lang('Submit'));
    $form->setDefaults(['annee' => $annee_base]);
    $form->display();
    echo '</div>';
} elseif (!empty($annee) && empty($course)) {
    Display::display_header($tool_name);
    echo '<div class="space-y-4">';
    echo '<p>';
    echo Display::getMdiIcon(ObjectIcon::COURSE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Select a course in which you would like to register the users you are going to select next')).' '.get_lang('Select a course in which you would like to register the users you are going to select next').'<br />';
    echo '</p>';

    $form = new FormValidator('ldap_import_students', 'post', api_get_self().'?annee='.Security::remove_XSS($annee));
    $slctCourse = $form->addSelect('course', get_lang('Course'));

    $courses = CourseManager::get_courses_list();
    foreach ($courses as $row) {
        $slctCourse->addOption(api_htmlentities($row['title']), $row['code']);
    }

    $form->addButtonFilter(get_lang('Submit'));
    $form->display();
    echo '</div>';
} elseif (!empty($annee) && !empty($course) && empty($_POST['confirmed'])) {
    // form4  annee != 0; composante != 0 etape != 0
    //elseif ($annee <> "" && $composante <> "" && $etape <> "" && $listeok != 'yes') {
    Display::display_header($tool_name);
    echo '<div class="space-y-4">';
    echo '<br />';
    echo '<br />';
    echo '<h3>'.Display::getMdiIcon(ObjectIcon::GROUP, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Select learners')).' '.get_lang('Select learners').'</h3>';
    //echo "Connection ...";

    /** @var LdapAuthenticatorHelper $ldapHelper */
    $ldapHelper = Container::$container->get(LdapAuthenticatorHelper::class);

    $info = $ldapHelper->getUsersByOu($annee);

    $nom_form = [];
    $prenom_form = [];
    $email_form = [];
    $username_form = [];
    $password_form = [];

    foreach ($info as $item) {
        $nom_form[] = $item['lastname'];
        $prenom_form[] = $item['firstname'];
        $email_form[] = $item['email'];
        $username_form[] = $item['username'];
        $password_form[] = $item['password'];
        //$outab[] = $info[$key]["eduPersonPrimaryAffiliation"][0]; // Ici "student"
    }

    asort($nom_form);
    reset($nom_form);

    $statut = 5;
    include 'ldap_form_add_users_group.php';

    echo '<br /><br />';
    echo '<a href="ldap_import_students.php?annee=&composante=&etape=">'.get_lang('Back to start new search').'</a>';
    echo '<br /><br />';
    echo '</div>';
} elseif (!empty($annee) && !empty($course) && ('yes' == $_POST['confirmed'])) {
    $id = $_POST['username_form'];
    $UserList = [];
    $userid_match_login = [];
    foreach ($id as $form_index => $user_id) {
        if (is_array($_POST['checkboxes']) && in_array($form_index, array_values($_POST['checkboxes']))) {
            /** @var LdapAuthenticator $userAuthenticator */
            $userAuthenticator = Container::$container->get(LdapAuthenticator::class);
            $ldapUser = $userAuthenticator->getUserProvider()->loadUserByIdentifier($user_id);
            $user = $userAuthenticator->createUser($ldapUser);

            $tmp = $user->getId();
            $UserList[] = $tmp;
            $userid_match_login[$tmp] = $user_id;
        }
    }
    if (!empty($_POST['course'])) {
        $courseInfo = api_get_course_info($_POST['course']);
        foreach ($UserList as $user_id) {
            CourseManager::subscribeUser($user_id, $courseInfo['real_id']);
        }
        header('Location: course_information.php?id='.$courseInfo['real_id']);
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
