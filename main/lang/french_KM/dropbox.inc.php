<?php
/**
 * Dropbox module for Dokeos/Claroline
 * language file in English language.
 * To make a version in your own language, you have 2 options:
 * 		- if you want to make use of the multilanguage tool in Claroline (this way you
 * 		can make 2 seperate courses in 2 different languages and Claroline will take
 * 		care of the translations) this file must be placed in the .../claroline/lang/English/
 * 		directory and the copy of this file that contains the translations must be placed in
 * 		the .../claroline/lang/YourLang/ directory. Be sure to give the translated version the same
 * 		name as this one.
 * 		- if you're sure you will only need the dropbox module in 1 language, you can just leave this
 * 		file in the current directory (.../claroline/plugin/dropbox/) and translate each variable into
 * 		the correct language.
 *
 * @version 1.20
 * @copyright 2004
 * @author Jan Bols <jan@ivpv.UGent.be>
 * with contributions by René Haentjens <rene.haentjens@UGent.be> (see RH)
 */
/**
 * +----------------------------------------------------------------------+
 * |   This program is free software; you can redistribute it and/or      |
 * |   modify it under the terms of the GNU General Public License        |
 * |   as published by the Free Software Foundation; either version 2     |
 * |   of the License, or (at your option) any later version.             |
 * |                                                                      |
 * |   This program is distributed in the hope that it will be useful,    |
 * |   but WITHOUT ANY WARRANTY; without even the implied warranty of     |
 * |   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the      |
 * |   GNU General Public License for more details.                       |
 * |                                                                      |
 * |   You should have received a copy of the GNU General Public License  |
 * |   along with this program; if not, write to the Free Software        |
 * |   Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA          |
 * |   02111-1307, USA. The GNU GPL license is also available through     |
 * |   the world-wide-web at http://www.gnu.org/copyleft/gpl.html         |
 * +----------------------------------------------------------------------+
 * |   Authors: Jan Bols          <jan@ivpv.UGent.be>              	      |
 * +----------------------------------------------------------------------+
 */

/*
* General variables
*/
$dropbox_lang["dropbox"] = "Partage";
$dropbox_lang["help"] = "Aide";

//$dropbox_cnf["version"] = "1.2";	//This variable is used to find out if this language file is outdated or not
									//When outdated, it will not be used.
									//The number must be the same as the version number in the dorpbox_init1.inc.php file

/**
 * error variables
 */
$dropbox_lang["aliensNotAllowed"] = "Only course members can use the dropbox. You are not a member of this course.";
$dropbox_lang["queryError"] = "Error in database query. Please contact your system administrator.";
$dropbox_lang["generalError"] = "An error has occured. Please contact your system administrator.";
$dropbox_lang["badFormData"] = "Submit failed: bad form data. Please contact your system administrator.";
$dropbox_lang["noUserSelected"] = "Please select a user to send the file to.";
$dropbox_lang["noFileSpecified"] = "You didn't specify a file to upload.";
$dropbox_lang["tooBig"] = "You didn't choose a file or the file is too big.";
$dropbox_lang["uploadError"] = "Error uploading file. Please contact your system administrator.";
$dropbox_lang["errorCreatingDir"] = "Can't create the dropbox directory. Please contact your system administrator.";
$dropbox_lang["installError"] = "Can't install the necessary tables for the dropbox module. Please contact your system administrator.";

/**
 * upload file variables
 */
$dropbox_lang["uploadFile"] = "Upload document";
// $dropbox_lang["titleWork"] = "Paper Title";	//this var isn't used anymore
$dropbox_lang["authors"] = "Authors";
$dropbox_lang["description"] = "Paper Description";
$dropbox_lang["sendTo"] = "Send to";

/**
 * Sent/Received list variables
 */
$dropbox_lang["receivedTitle"] = "Received Files";
$dropbox_lang["sentTitle"] = "Sent Files";
$dropbox_lang["confirmDelete"] = "This will remove the entry from your list only";
$dropbox_lang["all"] = "all documents";
$dropbox_lang["workDelete"] = "Remove entry from list";
$dropbox_lang["sentBy"] = "Sent by";
$dropbox_lang["sentTo"] = "Sent to";
$dropbox_lang["sentOn"] = "on";
$dropbox_lang["anonymous"] = "unknown";
$dropbox_lang["ok"] = "OK";
$dropbox_lang["lastUpdated"] = "Last updated on";
$dropbox_lang["lastResent"] = "Last resent on";
$dropbox_lang['tableEmpty'] = "The list is empty.";
$dropbox_lang["overwriteFile"] = "Overwrite previously sent file?";
$dropbox_lang['orderBy'] = "Order by ";
$dropbox_lang['lastDate'] = "date last sent";
$dropbox_lang['firstDate'] = "date first sent";
$dropbox_lang['title'] = "title";
$dropbox_lang['size'] = "filesize";
$dropbox_lang['author'] = "author";
$dropbox_lang['sender'] = "sender";
$dropbox_lang['recipient'] = "recipient";

/**
 * Feedback variables
 */
$dropbox_lang["docAdd"] = "Paper has been added succesfully";
$dropbox_lang["fileDeleted"] = "The selected file has been removed from your dropbox.";
$dropbox_lang["backList"] = "Go back to your dropbox";

/**
 * RH: Mailing variables
 */
$dropbox_lang["mailingAsUsername"] = "Mailing ";
$dropbox_lang["mailingInSelect"] = "---Mailing---";
$dropbox_lang["mailingSelectNoOther"] = "Mailing cannot be combined with other recipients";
$dropbox_lang["mailingNonMailingError"] = "Mailing cannot be overwritten by non-mailing and vice-versa";
$dropbox_lang["mailingExamine"] = "Examine mailing zip-file";
$dropbox_lang["mailingNotYetSent"] = "Mailing content files have not yet been sent out...";
$dropbox_lang["mailingSend"] = "Send content files";
$dropbox_lang["mailingConfirmSend"] = "Send content files to individual destinations ?";
$dropbox_lang["mailingBackToDropbox"] = "(back to Dropbox main window)";
$dropbox_lang["mailingWrongZipfile"] = "Mailing must be zipfile with STUDENTID or LOGINNAME";
$dropbox_lang["mailingZipEmptyOrCorrupt"] = "Mailing zipfile is empty or not a valid zipfile";
$dropbox_lang["mailingZipPhp"] = "Mailing zipfile must not contain php files - it will not be sent";
$dropbox_lang["mailingZipDups"] = "Mailing zipfile must not contain duplicate files - it will not be sent";
$dropbox_lang["mailingFileFunny"] = "no name, or extension not 1-4 letters or digits";
$dropbox_lang["mailingFileNoPrefix"] = "name does not start with ";
$dropbox_lang["mailingFileNoPostfix"] = "name does not end with ";
$dropbox_lang["mailingFileNoRecip"] = "name does not contain any recipient-id";
$dropbox_lang["mailingFileRecipNotFound"] = "no such student with ";
$dropbox_lang["mailingFileRecipDup"] = "multiple students have ";
$dropbox_lang["mailingFileIsFor"] = "is for ";
$dropbox_lang["mailingFileSentTo"] = "sent to ";
$dropbox_lang["mailingFileNotRegistered"] = " (not registered for this course)";
$dropbox_lang["mailingNothingFor"] = "Nothing for";

/**
 * RH: Just Upload
 */
$dropbox_lang["justUploadInSelect"] = "---JustUpload---";
$dropbox_lang["justUploadInList"] = "Upload by";
$dropbox_lang["mailingJustUploadNoOther"] = "Just Upload cannot be combined with other recipients";
?>
