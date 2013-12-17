<?php
/* For licensing terms, see /license.txt */

/*
 * PREVENT RESUBMITING
 * This part checks if the $dropbox_unid var has the same ID
 * as the session var $dropbox_uniqueid that was registered as a session
 * var before.
 * The resubmit prevention only works with GET requests, because it gives some annoying
 * behaviours with POST requests.
 */

/**
 * FORM SUBMIT
 * - VALIDATE POSTED DATA
 * - UPLOAD NEW FILE
 */
if (isset($_POST['submitWork'])) {
    require_once api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php';
    $error = false;
    $errormsg = '';
    /**
     * FORM SUBMIT : VALIDATE POSTED DATA
     */

    // the author or description field is empty
    if (!isset($_POST['authors']) || !isset( $_POST['description'])) {
        $error = true;
        $errormsg = get_lang('BadFormData');
    } elseif (!isset( $_POST['recipients']) || count( $_POST['recipients']) <= 0) {
        $error = true;
        $errormsg = get_lang('NoUserSelected');
    } else {
        $thisIsAMailing = false;
        $thisIsJustUpload = false;

	    foreach ($_POST['recipients'] as $rec) {
            if ($rec == 'mailing') {
	            $thisIsAMailing = true;
            } elseif ($rec == 'upload') {
	            $thisIsJustUpload = true;
            } elseif (strpos($rec, 'user_') === 0 && !isCourseMember(substr($rec, strlen('user_')))) {
	        	echo '401';
	        	die(get_lang('BadFormData').' (code 401)');
	        } elseif (strpos($rec, 'group_') !== 0 && strpos($rec, 'user_') !== 0) {
	        	echo '402';
	        	die(get_lang('BadFormData').' (code 402)');
	        }
        }

		// we are doing a mailing but an additional recipient is selected
        if ($thisIsAMailing && ( count($_POST['recipients']) != 1)) {
            $error = true;
            $errormsg = get_lang('MailingSelectNoOther');
        }
        // we are doing a just upload but an additional recipient is selected.
        elseif ( $thisIsJustUpload && ( count($_POST['recipients']) != 1)) {
            $error = true;
            $errormsg = get_lang('MailingJustUploadSelectNoOther');
        } elseif (empty($_FILES['file']['name'])) {
            $error = true;
            $errormsg = get_lang('NoFileSpecified');
        }
    }

	//check if $_POST['cb_overwrite'] is true or false
	$dropbox_overwrite = false;
	if (isset($_POST['cb_overwrite']) && $_POST['cb_overwrite']) {
		$dropbox_overwrite = true;
	}

    /**
     * FORM SUBMIT : UPLOAD NEW FILE
     */

    if (!$error) {

        $dropbox_filename = $_FILES['file']['name'];
        $dropbox_filesize = $_FILES['file']['size'];
        $dropbox_filetype = $_FILES['file']['type'];
        $dropbox_filetmpname = $_FILES['file']['tmp_name'];

        if ($dropbox_filesize <= 0 || $dropbox_filesize > dropbox_cnf('maxFilesize')) {
            $errormsg = get_lang('TooBig'); // TODO: The "too big" message does not fit in the case of uploading zero-sized file.
            $error = true;
        } elseif (!is_uploaded_file($dropbox_filetmpname)) { // check user fraud : no clean error msg.
            die(get_lang('BadFormData').' (code 403)');
        }

        if (!$error) {
            // Try to add an extension to the file if it hasn't got one
            $dropbox_filename = add_ext_on_mime($dropbox_filename, $dropbox_filetype);
            // Replace dangerous characters
            $dropbox_filename = replace_dangerous_char($dropbox_filename);
            // Transform any .php file in .phps fo security
            $dropbox_filename = php2phps($dropbox_filename);
            if (!filter_extension($dropbox_filename)) {
                $error = true;
                $errormsg = get_lang('UplUnableToSaveFileFilteredExtension');
            } else {
                // set title
                $dropbox_title = $dropbox_filename;

                // set author
                if ($_POST['authors'] == '') {
                    $_POST['authors'] = getUserNameFromId($_user['user_id']);
                }

                if ($dropbox_overwrite) {
                    $dropbox_person = new Dropbox_Person($_user['user_id'], $is_courseAdmin, $is_courseTutor);

                    foreach ($dropbox_person->sentWork as $w) {
                        if ($w->title == $dropbox_filename) {
                            if (($w->recipients[0]['id'] > dropbox_cnf('mailingIdBase')) xor $thisIsAMailing) {
                                $error = true;
                                $errormsg = get_lang('MailingNonMailingError');
                            }
                            if (($w->recipients[0]['id'] == $_user['user_id']) xor $thisIsJustUpload) {
                                $error = true;
                                $errormsg = get_lang('MailingJustUploadSelectNoOther');
                            }
                            $dropbox_filename = $w->filename;
                            $found = true;
                            break;
                        }
                    }
                } else {
                    // rename file to login_filename_uniqueId format
                    $dropbox_filename = getLoginFromId( $_user['user_id']) . '_' . $dropbox_filename . '_'.uniqid('');
                }

                if (!is_dir(dropbox_cnf('sysPath'))) {
                    //The dropbox subdir doesn't exist yet so make it and create the .htaccess file
                    mkdir(dropbox_cnf('sysPath'), api_get_permissions_for_new_directories()) or die(get_lang('ErrorCreatingDir').' (code 404)');
                    $fp = fopen(dropbox_cnf('sysPath').'/.htaccess', 'w') or die(get_lang('ErrorCreatingDir').' (code 405)');
                    fwrite($fp, "AuthName AllowLocalAccess
                                 AuthType Basic

                                 order deny,allow
                                 deny from all

                                 php_flag zlib.output_compression off") or die(get_lang('ErrorCreatingDir').' (code 406)');
                }

				if ($error) {
                } elseif ($thisIsAMailing) {
				    if (preg_match(dropbox_cnf('mailingZipRegexp'), $dropbox_title)) {
			            $newWorkRecipients = dropbox_cnf('mailingIdBase');
					} else {
				        $error = true;
				        $errormsg = $dropbox_title . ': ' . get_lang('MailingWrongZipfile');
					}
				} elseif ($thisIsJustUpload) {
		            $newWorkRecipients = array();
	        	} else {
				 	// Creating the array that contains all the users who will receive the file
					$newWorkRecipients = array();
		            foreach ($_POST['recipients'] as $rec) {
		            	if (strpos($rec, 'user_') === 0) {
		            		$newWorkRecipients[] = substr($rec, strlen('user_'));
		            	} elseif (strpos($rec, 'group_') === 0) {
		            		$userList = GroupManager::get_subscribed_users(substr($rec, strlen('group_')));
		            		foreach ($userList as $usr) {
		            			if (!in_array($usr['user_id'], $newWorkRecipients) && $usr['user_id'] != $_user['user_id']) {
		            				$newWorkRecipients[] = $usr['user_id'];
		            			}
		            		}
		            	}
		            }
	        	}

				// After uploading the file, create the db entries

	        	if (!$error) {
		            @move_uploaded_file($dropbox_filetmpname, dropbox_cnf('sysPath') . '/' . $dropbox_filename)
		            	or die(get_lang('UploadError').' (code 407)');
		            new Dropbox_SentWork($_user['user_id'], $dropbox_title, $_POST['description'], strip_tags($_POST['authors']), $dropbox_filename, $dropbox_filesize, $newWorkRecipients);
	        	}
            }
        }
    } //end if(!$error)

    /**
     * SUBMIT FORM RESULTMESSAGE
     */
    if (!$error) {
		$return_message = get_lang('FileUploadSucces');
    } else {
		$return_message = $errormsg;
    }
}

/**
 * DELETE RECEIVED OR SENT FILES - EDIT FEEDBACK
 * - DELETE ALL RECEIVED FILES
 * - DELETE 1 RECEIVED FILE
 * - DELETE ALL SENT FILES
 * - DELETE 1 SENT FILE
 * - EDIT FEEDBACK
 */
if (isset($_GET['deleteReceived']) || isset($_GET['deleteSent'])
         || isset( $_GET['showFeedback']) || isset( $_GET['editFeedback'])) {
	if ($_GET['mailing']) {
		getUserOwningThisMailing($_GET['mailing'], $_user['user_id'], '408');
		$dropbox_person = new Dropbox_Person($_GET['mailing'], $is_courseAdmin, $is_courseTutor);
	} else {
	    $dropbox_person = new Dropbox_Person($_user['user_id'], $is_courseAdmin, $is_courseTutor);
    }

	if (isset($_SESSION['sentOrder'])) {
		$dropbox_person->orderSentWork($_SESSION['sentOrder']);
	}
	if (isset($_SESSION['receivedOrder'])) {
		$dropbox_person->orderReceivedWork($_SESSION['receivedOrder']);
	}

	/*if (!$dropbox_person->isCourseAdmin || ! $dropbox_person->isCourseTutor) {
	    die(get_lang('GeneralError').' (code 408)');
	}*/

	$tellUser = get_lang('FileDeleted');

    if (isset($_GET['deleteReceived'])) {
        if ($_GET['deleteReceived'] == 'all') {
            $dropbox_person->deleteAllReceivedWork();
        } elseif (is_numeric($_GET['deleteReceived'])) {
            $dropbox_person->deleteReceivedWork( $_GET['deleteReceived']);
        } else {
            die(get_lang('GeneralError').' (code 409)');
        }
    } elseif (isset( $_GET['deleteSent'])) {
        if ($_GET['deleteSent'] == 'all') {
            $dropbox_person->deleteAllSentWork( );
        } elseif (is_numeric($_GET['deleteSent'])) {
            $dropbox_person->deleteSentWork($_GET['deleteSent']);
        } else {
            die(get_lang('GeneralError').' (code 410)');
        }
    } elseif (isset($_GET['showFeedback'])) {
		$w = new Dropbox_SentWork($id = $_GET['showFeedback']);

		if ($w->uploader_id != $_user['user_id']) {
		    getUserOwningThisMailing($w->uploader_id, $_user['user_id'], '411');
		}

    	foreach ($w -> recipients as $r) {
    		if (($fb = $r['feedback'])) {
    			$fbarray[$r['feedback_date'].$r['name']] = $r['name'].' '.get_lang('SentOn', '').' '.$r['feedback_date'].":\n".$fb;
    		}
    	}

    	if ($fbarray) {
        	krsort($fbarray);
            echo '<textarea class="dropbox_feedbacks">',
                    htmlspecialchars(implode("\n\n", $fbarray), ENT_QUOTES, api_get_system_encoding()), '</textarea>', "\n";
        } else {
            echo '<textarea class="dropbox_feedbacks">&nbsp;</textarea>', "\n";
        }

        $tellUser = get_lang('ShowFeedback');

    } else { // if ( isset( $_GET['editFeedback'])) {
        $id = $_GET['editFeedback'];
        $found = false;
		foreach ($dropbox_person->receivedWork as $w) {
			if ($w->id == $id) {
			   $found = true;
			   break;
			}
		}
		if (!$found) die(get_lang('GeneralError').' (code 415)');

        echo '<form method="post" action="index.php">', "\n",
            '<input type="hidden" name="feedbackid" value="',
                $id, '"/>', "\n",
            '<textarea name="feedbacktext" class="dropbox_feedbacks">',
                htmlspecialchars($w->feedback, ENT_QUOTES, api_get_system_encoding()), '</textarea>', "<br />\n",
            '<input type="submit" name="feedbacksubmit" value="', get_lang('Ok', ''), '"/>', "\n",
            '</form>', "\n";
        $tellUser = get_lang('GiveFeedback');
    }

    /**
     * RESULTMESSAGE FOR DELETE FILE OR EDIT FEEDBACK
     */
    $return_message = get_lang('BackList');
}
