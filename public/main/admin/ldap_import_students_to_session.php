<?php
/* For licensing terms, see /license.txt */
/**
 * Edition script for sessions categories.
 */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\LdapAuthenticatorHelper;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Security\Authenticator\Ldap\LdapAuthenticator;
use Symfony\Component\HttpFoundation\Request;

// resetting the course id
$cidReset = true;
require_once '../inc/global.inc.php';
// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
// Access restrictions
api_protect_admin_script();

$httpRequest = Request::createFromGlobals();

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

$annee = $httpRequest->query->get('annee');
$id_session = $httpRequest->request->get('id_session');

// form1 annee = 0; composante= 0 etape = 0
//if ($annee == "" && $composante == "" && $etape == "") {
if (empty($annee) && empty($id_session)) {
    Display::display_header($tool_name);
    echo '<div class="space-y-4">';
    echo '<p>';
    echo Display::getMdiIcon(ObjectIcon::GROUP, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Select a filter to find a matching string at the end of the OU attribute')).' '.get_lang('Select a filter to find a matching string at the end of the OU attribute');
    echo '</p>';

    $form = new FormValidator('students_to_session', 'get', api_get_self());
    $form->addNumeric('annee', get_lang('The OU attribute filter'), ['step' => 1]);
    $form->addButtonFilter(get_lang('Submit'));
    $form->setDefaults(['annee' => $annee_base]);
    $form->display();
    echo '</div>';
} elseif (!empty($annee) && empty($id_session)) {
    Display::display_header($tool_name);
    echo '<div class="space-y-4">';
    echo '<p>';
    echo Display::getMdiIcon(
        ObjectIcon::COURSE,
        'ch-tool-icon',
        null,
        ICON_SIZE_SMALL,
        get_lang('Select the session in which you want to import these users')
    ).' '.get_lang('Select the session in which you want to import these users').'<br />';
    echo '</p>';

    $form = new FormValidator('students_to_session', 'post', api_get_self().'?annee='.Security::remove_XSS($annee));
    $form->addSelectAjax(
        'id_session',
        get_lang('Session'),
        [],
        [
            'tags' => false,
            'url' => api_get_path(WEB_AJAX_PATH).'session.ajax.php?'.http_build_query(['a' => 'search_session']),
        ]
    );
    $form->addButtonFilter(get_lang('Submit'));
    $form->display();
    echo '</div>';
}
// form4  annee != 0; composante != 0 etape != 0
//elseif ($annee <> "" && $composante <> "" && $etape <> "" && $listeok != 'yes') {
elseif (!empty($annee) && !empty($id_session) && empty($_POST['confirmed'])) {
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
    echo '<a href="ldap_import_students.php?annee=">'.get_lang('Back to start new search').'</a>';
    echo '<br /><br />';
    echo '</div>';
} elseif (!empty($annee) && !empty($id_session) && ('yes' == $_POST['confirmed'])) {
    $id = $_POST['username_form'];
    /** @var array<int, User> $UserList */
    $UserList = [];
    $userid_match_login = [];
    foreach ($id as $form_index => $user_id) {
        if (is_array($_POST['checkboxes']) && in_array($form_index, array_values($_POST['checkboxes']))) {
            /** @var LdapAuthenticator $userAuthenticator */
            $userAuthenticator = Container::$container->get(LdapAuthenticator::class);
            $ldapUser = $userAuthenticator->getUserProvider()->loadUserByIdentifier($user_id);
            $user = $userAuthenticator->createUser($ldapUser);

            $tmp = $user->getId();
            $UserList[] = $user;
            $userid_match_login[$tmp] = $user_id;
        }
    }
    if (!empty($_POST['id_session'])) {
        /** @var Session $session */
        $session = Container::getSessionRepository()->find($id_session);

        foreach ($UserList as $user) {
            $session->addUserInSession(Session::STUDENT, $user);
        }

        Container::getEntityManager()->flush();

        header('Location: ../session/resume_session.php?id_session='.Security::remove_XSS($_POST['id_session']));
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
