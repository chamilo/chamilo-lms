<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @desc The dropbox is a personal (peer to peer) file exchange module that allows
 * you to send documents to a certain (group of) users.
 *
 * @version 1.3
 *
 * @author Jan Bols <jan@ivpv.UGent.be>, main programmer, initial version
 * @author René Haentjens <rene.haentjens@UGent.be>, several contributions
 * @author Roan Embrechts, virtual course support
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University (see history version 1.3)
 *
 * @todo complete refactoring. Currently there are about at least 3 sql queries needed for every individual dropbox document.
 *  first we find all the documents that were sent (resp. received) by the user
 *   then for every individual document the user(s)information who received (resp. sent) the document is searched
 *  then for every individual document the feedback is retrieved
 * @todo
 * the implementation of the dropbox categories could (on the database level) have been done more elegantly by storing the category
 * in the dropbox_person table because this table stores the relationship between the files (sent OR received) and the users
 */

/**
HISTORY
Version 1.1
------------
- dropbox_init1.inc.php: changed include statements to require statements.
  This way if a file is not found, it stops the execution of a script instead of continuing with warnings.
- dropbox_init1.inc.php: the include files "claro_init_global.inc.php" & "debug.lib.inc.php" are first checked for
  their existence before including them. If they don't exist, in the .../include dir,
  they get loaded from the .../inc dir. This change is necessary because the UCL changed the include dir to inc.
- dropbox_init1.inc.php: the databasetable name in the variable $dropbox_cnf["introTbl"]
  is changed from "introduction" to "tool_intro"
- install.php: after submit, checks if the database uses accueil or tool_list as a tablename
- index.php: removed the behaviour of only the teachers that are allowed to delete entries
- index.php: added field "lastUploadDate" in table dropbox_file to store information
  about last update when resubmiting a file
- dropbox.inc.php: added $lang["lastUpdated"]
- index.php: entries in received list show when file was last updated if it is updated
- index.php: entries in sent list show when file was last resent if it was resent
- index.php: add POST-variable to the upload form with overwrite data when
  user decides to overwrite the previous sent file with new file
- dropbox_submit.php: add sanity checks on POST['overwrite'] data
- index.php: remove title field in upload form
- dropbox_init1.inc.php: added $dropbox_cnf["version"] variable
- dropbox_class.inc.php: add $this->lastUploadDate to Dropbox_work class
- dropbox.inc.php: added $lang['emptyTable']
- index.php: if the received or sent list is empty, a message is displayed
- dropbox_download.php: the $file var is set equal to the title-field of the filetable.
  So not constructed anymore by substracting the username from the filename
- index.php: add check to see if column lastUploadDate exists in filetable
- index.php: moved javascripts from dropbox_init2.inc.php to index.php
- index.php: when specifying an uploadfile in the form, a checkbox allowing the user to overwrite a
  previously sent file is shown when the specified file has the same name as a previously uploaded file of that user.
- index.php: assign all the metadata (author, description, date, recipient, sender) of an
  entry in a list to the class="dropbox_detail" and add css to html-header
- index.php: assign all dates of entries in list to the class="dropbox_date" and add CSS
- index.php: assign all persons in entries of list to the class="dropbox_person" and add CSS
- dropbox.inc.php: added $lang['dropbox_version'] to indicate the lates version.
  This must be equal to the $dropbox_cnf['version'] variable.
- dropbox_init1.inc.php: if the newest lang file isn't loaded by claro_init_global.inc.php
  from the .../lang dir it will be loaded locally from the .../plugin/dropbox/ dir.
  This way an administrator must not install the dropbox.inc.php in the .../lang/english dir,
  but he can leave it in the local .../plugin/dropbox/ dir.
  However if you want to present multiple language translations of the file you must still
  put the file in the /lang/ dir, because there is no language management system inside the .../plugin/dropbox dir.
- mime.inc.php: created this file. It contains an array $mimetype with all the mimetypes
  that are used by dropbox_download.php to give hinst to the browser during download about content
- dropbox_download.php: remove https specific headers because they're not necessary
- dropbox_download.php: use application/octet-stream as the default mime and inline as the default Content-Disposition
- dropbox.inc.php: add lang vars for "order by" action
- dropbox_class.inc.php: add methods orderSentWork, orderReceivedWork en _cmpWork and
  propery _orderBy to class Dropbox_person to take care of sorting
- index.php: add selectionlist to headers of sent/received lists to select "order by"
  and add code to keep selected value in sessionvar.
- index.php: moved part of a <a> hyperlink to previous line to remove the underlined space between
  symbol and title of a work entry in the sent/received list
- index.php: add filesize info in sent/received lists
- dropbox_submit.php: resubmit prevention only for GET action, because it gives some annoying behaviour in POST
 * situation: white screen in IE6.
- removed all self-built database tables names
 */

/**
 * First initialisation file with initialisation of variables and
 * without outputting anything to browser.
 * 1. Calls global.inc.php and lang file
 * 2. Initialises $dropbox_cnf array with all relevant vars
 * 3. Often used functions.
 *
 * @version 1.31
 *
 * @copyright 2004-2005
 * @author Jan Bols <jan@ivpv.UGent.be>, main programmer
 * @author René Haentjens, severalcontributions <rene.haentjens@UGent.be>
 * @author Roan Embrechts, virtual course support
 * @author Patrick Cool <patrick.cool@UGent.be>
 * Chamilo Config Settings (AWACS)
 * Refactoring
 * tool introduction
 * folders
 * download file / folder (download icon)
 * same action on multiple documents
 * extended feedback
 */
require_once __DIR__.'/../inc/global.inc.php';
$is_allowed_in_course = api_is_allowed_in_course();
$is_courseTutor = api_is_course_tutor();
$is_courseAdmin = api_is_course_admin();

$current_course_tool = TOOL_DROPBOX;

// the dropbox file that contains additional functions
require_once 'dropbox_functions.inc.php';

// protecting the script
api_protect_course_script();

$user_id = api_get_user_id();
$course_code = api_get_course_id();
$course_info = api_get_course_info($course_code);
$session_id = api_get_session_id();

$action = isset($_GET['action']) ? $_GET['action'] : null;
$view = isset($_GET['view']) ? Security::remove_XSS($_GET['view']) : null;
$postAction = isset($_POST['action']) ? $_POST['action'] : null;

if (api_is_excluded_user_type()) {
    api_not_allowed(true);
}

if (empty($session_id)) {
    $is_course_member = CourseManager::is_user_subscribed_in_course(
        $user_id,
        $course_code,
        false
    );
} else {
    $is_course_member = CourseManager::is_user_subscribed_in_course(
        $user_id,
        $course_code,
        true,
        $session_id
    );
}

// we need this here because the javascript to re-upload the file needs an array
// off all the documents that have already been sent.
// @todo consider moving the javascripts in a function that displays the javascripts
// only when it is needed.
if ('add' == $action) {
    $dropbox_person = new Dropbox_Person(
        $user_id,
        $is_courseAdmin,
        $is_courseTutor
    );
}

/*	Create javascript and htmlHeaders */
$javascript = "<script>
function confirmsend()
{
    if (confirm(\"".get_lang('MailingConfirmSend', '')."\")){
        return true;
    } else {
        return false;
    }
    return true;
}

function confirmation (name)
{
    if (confirm(\"".get_lang('ConfirmDelete', '')." : \"+ name )){
        return true;
    } else {
        return false;
    }
    return true;
}

function checkForm (frm)
{
    if (frm.elements['recipients[]'].selectedIndex < 0){
        alert(\"".get_lang('NoUserSelected', '')."\");
        return false;
    } else if (frm.file.value == '') {
        alert(\"".get_lang('NoFileSpecified', '')."\");
        return false;
    } else {
        return true;
    }
}
";

$allowOverwrite = api_get_setting('dropbox_allow_overwrite');
if ($allowOverwrite == 'true') {
    //sentArray keeps list of all files still available in the sent files list
    //of the user.
    //This is used to show or hide the overwrite file-radio button of the upload form
    $javascript .= " var sentArray = new Array(";
    if (isset($dropbox_person)) {
        for ($i = 0; $i < count($dropbox_person->sentWork); $i++) {
            if ($i > 0) {
                $javascript .= ", ";
            }
            $javascript .= "'".$dropbox_person->sentWork[$i]->title."'";
        }
    }
    $javascript .= ");

		function checkfile(str)
		{
			ind = str.lastIndexOf('/'); //unix separator
			if (ind == -1) ind = str.lastIndexOf('\\\');	//windows separator
			filename = str.substring(ind+1, str.length);

			found = 0;
			for (i=0; i<sentArray.length; i++) {
				if (sentArray[i] == filename) found=1;
			}

			//always start with unchecked box
			el = getElement('cb_overwrite');
			el.checked = false;

			//show/hide checkbox
			if (found == 1) {
				displayEl('overwrite');
			} else {
				undisplayEl('overwrite');
			}
		}

		function getElement(id)
		{
			return document.getElementById ? document.getElementById(id) :
			document.all ? document.all(id) : null;
		}

		function displayEl(id)
		{
			var el = getElement(id);
			if (el && el.style) el.style.display = '';
		}

		function undisplayEl(id)
		{
			var el = getElement(id);
			if (el && el.style) el.style.display = 'none';
		}";
}

$javascript .= "
	</script>";
$htmlHeadXtra[] = $javascript;
$htmlHeadXtra[] = "<script>
function confirmation (name)
{
	if (confirm(\" ".get_lang("AreYouSureToDeleteJS")." \"+ name + \" ?\"))
		{return true;}
	else
		{return false;}
}
</script>";

Session::write('javascript', $javascript);

$htmlHeadXtra[] = '<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="expires" content="-1">';
$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);
$htmlHeadXtra[] = "<script>
$(function () {
    $('#recipient_form').on('change', function() {
        $('#multiple_form').show();
    });
});
</script>";
$checked_files = false;
if (!$view || $view == 'received') {
    $part = 'received';
} elseif ($view == 'sent') {
    $part = 'sent';
} else {
    header('location: index.php?'.api_get_cidreq().'&view=received&error=Error');
    exit;
}

if (($postAction == 'download_received' || $postAction == 'download_sent') and !$_POST['store_feedback']) {
    $checked_file_ids = $_POST['id'];
    if (!is_array($checked_file_ids) || count($checked_file_ids) == 0) {
        header('Location: index.php?'.api_get_cidreq().'&view='.$view.'&error=CheckAtLeastOneFile');
    } else {
        handle_multiple_actions();
    }
    exit;
}

/*
 * AUTHORISATION SECTION
 * Prevents access of all users that are not course members
 */
if ((!$is_allowed_in_course || !$is_course_member) &&
    !api_is_allowed_to_edit(null, true)
) {
    if ($origin != 'learnpath') {
        api_not_allowed(true); //print headers/footers
    } else {
        api_not_allowed();
    }
    exit();
}

/*	BREADCRUMBS */
if ($view == 'received') {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'dropbox/index.php?'.api_get_cidreq(),
        'name' => get_lang('Dropbox', ''),
    ];
    $nameTools = get_lang('ReceivedFiles');

    if ($action == 'addreceivedcategory') {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'dropbox/index.php?view=received&'.api_get_cidreq(),
            'name' => get_lang('ReceivedFiles'),
        ];
        $nameTools = get_lang('AddNewCategory');
    }
}

if ($view == 'sent' || empty($view)) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'dropbox/index.php?'.api_get_cidreq(),
        'name' => get_lang('Dropbox'),
    ];
    $nameTools = get_lang('SentFiles');

    if ($action == 'addsentcategory') {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'dropbox/index.php?view=sent&'.api_get_cidreq(),
            'name' => get_lang('SentFiles'),
        ];
        $nameTools = get_lang('AddNewCategory');
    }
    if ($action == 'add') {
        $nameTools = get_lang('UploadNewFile');
    }

    if ($action == 'update') {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'dropbox/index.php?view=sent&'.api_get_cidreq(),
            'name' => get_lang('SentFiles'),
        ];
        $nameTools = get_lang('UpdateFile');
    }
}

/*	HEADER & TITLE */
if (isset($origin) && $origin == 'learnpath') {
    $htmlHeadXtra[] = $javascript;
    Display::display_reduced_header($nameTools, 'Dropbox');
} else {
    Display::display_header($nameTools, 'Dropbox');
}
