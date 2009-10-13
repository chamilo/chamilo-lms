<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */


/*
 * ========================================
 * PREVENT RESUBMITING
 * ========================================
 * This part checks if the $dropbox_unid var has the same ID
 * as the session var $dropbox_uniqueid that was registered as a session
 * var before.
 * The resubmit prevention only works with GET requests, because it gives some annoying
 * behaviours with POST requests.
 */
/*
if (isset($_POST["dropbox_unid"])) {
	$dropbox_unid = $_POST["dropbox_unid"];
} elseif (isset($_GET["dropbox_unid"]))
{
	$dropbox_unid = $_GET["dropbox_unid"];
} else {
	die(dropbox_lang("badFormData")." (code 400)");
}

if (isset($_SESSION["dropbox_uniqueid"]) && isset($_GET["dropbox_unid"]) && $dropbox_unid == $_SESSION["dropbox_uniqueid"]) {
	//resubmit : go to index.php
	// only prevent resending of data for GETS, not POSTS because this gives annoying results

	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]=="on") {
		$mypath = "https";
	} else {
		$mypath = "http";
	}
	$mypath=$mypath."://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/index.php";

echo 'hier';
    header("Location: $mypath");

}

$dropbox_uniqueid = $dropbox_unid;

api_session_register("dropbox_uniqueid");
*/


/**
 * ========================================
 * FORM SUBMIT
 * ========================================
 * - VALIDATE POSTED DATA
 * - UPLOAD NEW FILE
 */
if ( isset( $_POST["submitWork"]))
{
    if (file_exists(api_get_path(INCLUDE_PATH) . "/fileUploadLib.inc.php"))
    {
        require_once(api_get_path(INCLUDE_PATH) . "/fileUploadLib.inc.php");
    }
    else
    {
        require_once(api_get_path(LIBRARY_PATH) . "/fileUpload.lib.php");
	}

    $error = FALSE;
    $errormsg = '';


    /**
     * --------------------------------------
     * FORM SUBMIT : VALIDATE POSTED DATA
     * --------------------------------------
     */
    // the author or description field is empty
    if ( !isset( $_POST['authors']) || !isset( $_POST['description']))
    {
        $error = TRUE;

        $errormsg = dropbox_lang("badFormData");
    }
	elseif ( !isset( $_POST['recipients']) || count( $_POST['recipients']) <= 0)
    {
        $error = TRUE;

        $errormsg = dropbox_lang("noUserSelected");
    }
    else
    {
        $thisIsAMailing = FALSE;  // RH: Mailing selected as destination
        $thisIsJustUpload = FALSE;  // RH

	    foreach( $_POST['recipients'] as $rec)
        {
            if ( $rec == 'mailing')
            {
	            $thisIsAMailing = TRUE;
            }
            elseif ( $rec == 'upload')
            {
	            $thisIsJustUpload = TRUE;
            }
	        elseif (strpos($rec, 'user_') === 0 && !isCourseMember(substr($rec, strlen('user_') ) ))
	        {
	        	echo '401';
	        	die( dropbox_lang("badFormData")." (code 401)");
	        }
	        elseif (strpos($rec, 'group_') !== 0 && strpos($rec, 'user_') !== 0)
	        {
	        	echo '402';
	        	die( dropbox_lang("badFormData")." (code 402)");
	        }
        }

		// we are doing a mailing but an additional recipient is selected
        if ( $thisIsAMailing && ( count($_POST['recipients']) != 1))
        {
            $error = TRUE;

            $errormsg = dropbox_lang("mailingSelectNoOther");
        }
        // we are doing a just upload but an additional recipient is selected.
        elseif ( $thisIsJustUpload && ( count($_POST['recipients']) != 1))
        {
            $error = TRUE;

            $errormsg = get_lang("MailingJustUploadSelectNoOther");
        }
        elseif ( empty( $_FILES['file']['name']))
        {
            $error = TRUE;

            $errormsg = dropbox_lang("noFileSpecified");
        }
    }

	//check if $_POST['cb_overwrite'] is true or false
	$dropbox_overwrite = false;
	if ( isset($_POST['cb_overwrite']) && $_POST['cb_overwrite']==true)
	{
		$dropbox_overwrite = true;
	}

    /**
     * --------------------------------------
     * FORM SUBMIT : UPLOAD NEW FILE
     * --------------------------------------
     */
    if ( !$error)
    {
        $dropbox_filename = $_FILES['file']['name'];

        $dropbox_filesize = $_FILES['file']['size'];

        $dropbox_filetype = $_FILES['file']['type'];

        $dropbox_filetmpname = $_FILES['file']['tmp_name'];

        if ( $dropbox_filesize <= 0 || $dropbox_filesize > dropbox_cnf("maxFilesize"))
        {
            $errormsg = dropbox_lang("tooBig");

            $error = TRUE;
        }elseif ( !is_uploaded_file( $dropbox_filetmpname)) // check user fraud : no clean error msg.
            {
                die ( dropbox_lang("badFormData")." (code 403)");
        }

        if ( !$error)
        {
            // Try to add an extension to the file if it hasn't got one
            $dropbox_filename = add_ext_on_mime( $dropbox_filename,$dropbox_filetype);
            // Replace dangerous characters
            $dropbox_filename = replace_dangerous_char( $dropbox_filename);
            // Transform any .php file in .phps fo security
            $dropbox_filename = php2phps ( $dropbox_filename);
            if(!filter_extension($dropbox_filename))
            {
            	$error = true;
            	$errormsg = get_lang('UplUnableToSaveFileFilteredExtension');
            }
            else
            {
	            // set title
	            $dropbox_title = $dropbox_filename;

	            // set author
	            if ( $_POST['authors'] == '')
	            {
	                $_POST['authors'] = getUserNameFromId( $_user['user_id']);
	            }

				if ( $dropbox_overwrite)  // RH: Mailing: adapted
				{
					$dropbox_person = new Dropbox_Person( $_user['user_id'], $is_courseAdmin, $is_courseTutor);

					foreach($dropbox_person->sentWork as $w)
					{
						if ($w->title == $dropbox_filename)
						{
						    if ( ($w->recipients[0]['id'] > dropbox_cnf("mailingIdBase")) xor $thisIsAMailing)
						    {
								$error = TRUE;
								$errormsg = dropbox_lang("mailingNonMailingError");
							}
							if ( ($w->recipients[0]['id'] == $_user['user_id']) xor $thisIsJustUpload)
							{
								$error = TRUE;
								$errormsg = get_lang("MailingJustUploadSelectNoOther");
							}
							$dropbox_filename = $w->filename; $found = true;
							break;
						}
					}
				}
				else  // rename file to login_filename_uniqueId format
				{
					$dropbox_filename = getLoginFromId( $_user['user_id']) . "_" . $dropbox_filename . "_".uniqid('');
				}

				if ( ( ! is_dir( dropbox_cnf("sysPath"))))
	            {
					//The dropbox subdir doesn't exist yet so make it and create the .htaccess file
	                mkdir( dropbox_cnf("sysPath"), 0700) or die ( dropbox_lang("errorCreatingDir")." (code 404)");
					$fp = fopen( dropbox_cnf("sysPath")."/.htaccess", "w") or die (dropbox_lang("errorCreatingDir")." (code 405)");
					fwrite($fp, "AuthName AllowLocalAccess
	                             AuthType Basic

	                             order deny,allow
	                             deny from all

	                             php_flag zlib.output_compression off") or die (dropbox_lang("errorCreatingDir")." (code 406)");
	            }

				if ( $error) {}
	            elseif ( $thisIsAMailing)  // RH: $newWorkRecipients is integer - see class
				{
				    if ( preg_match( dropbox_cnf("mailingZipRegexp"), $dropbox_title))
					{
			            $newWorkRecipients = dropbox_cnf("mailingIdBase");
					}
					else
					{
				        $error = TRUE;
				        $errormsg = $dropbox_title . ": " . dropbox_lang("mailingWrongZipfile");
					}
				}
				elseif ( $thisIsJustUpload)  // RH: $newWorkRecipients is empty array
				{
		            $newWorkRecipients = array();
	        	}
				else
				{ 	// creating the array that contains all the users who will receive the file
					$newWorkRecipients = array();
		            foreach ($_POST["recipients"] as $rec)
		            {
		            	if (strpos($rec, 'user_') === 0) {
		            		$newWorkRecipients[] = substr($rec, strlen('user_') );
		            	}
		            	elseif (strpos($rec, 'group_') === 0 )
		            	{
		            		$userList = GroupManager::get_subscribed_users(substr($rec, strlen('group_') ));
		            		foreach ($userList as $usr)
		            		{
		            			if (! in_array($usr['user_id'], $newWorkRecipients) && $usr['user_id'] != $_user['user_id'])
		            			{
		            				$newWorkRecipients[] = $usr['user_id'];
		            			}
		            		}
		            	}
		            }
	        	}

				//After uploading the file, create the db entries

	        	if ( !$error)
	        	{
		            @move_uploaded_file( $dropbox_filetmpname, dropbox_cnf("sysPath") . '/' . $dropbox_filename)
		            	or die( dropbox_lang("uploadError")." (code 407)");
		            new Dropbox_SentWork( $_user['user_id'], $dropbox_title, $_POST['description'], strip_tags($_POST['authors']), $dropbox_filename, $dropbox_filesize, $newWorkRecipients);
	        	}
            }
        }
    } //end if(!$error)


    /**
     * ========================================
     * SUBMIT FORM RESULTMESSAGE
     * ========================================
     */
    if ( !$error)
    {
		$return_message=get_lang('FileUploadSucces');
    }

    else
    {
		$return_message=$errormsg;
    }
} // end if ( isset( $_POST["submitWork"]))


/**
 * ========================================
 * // RH: EXAMINE OR SEND MAILING  (NEW)
 * ========================================
 */
if ( isset( $_GET['mailingIndex']))  // examine or send
{
    $dropbox_person = new Dropbox_Person( $_user['user_id'], $is_courseAdmin, $is_courseTutor);
	if ( isset($_SESSION["sentOrder"]))
	{
		$dropbox_person->orderSentWork ($_SESSION["sentOrder"]);
	}
    $i = $_GET['mailingIndex']; $mailing_item = $dropbox_person->sentWork[$i];
    $mailing_title = $mailing_item->title;
    $mailing_file = dropbox_cnf("sysPath") . '/' . $mailing_item->filename;
    $errormsg = '<b>' . $mailing_item->recipients[0]['name'] . ' ('
    	. "<a href='dropbox_download.php?origin=$origin&id=".urlencode($mailing_item->id)."'>"
		. htmlspecialchars($mailing_title,ENT_QUOTES,$charset) . '</a>):</b><br /><br />';

    if ( preg_match( dropbox_cnf("mailingZipRegexp"), $mailing_title, $nameParts))
	{
		$var = api_strtoupper($nameParts[2]);  // the variable part of the name
		$course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
		$sel = "SELECT u.user_id, u.lastname, u.firstname, cu.status
				FROM `".$_configuration['main_database']."`.`user` u
				LEFT JOIN $course_user cu
				ON cu.user_id = u.user_id AND cu.course_code = '".$_course['sysCode']."'";
		$sel .= " WHERE u.".dropbox_cnf("mailingWhere".$var)." = '";

        function getUser($thisRecip)
        {
			// string result = error message, array result = [user_id, lastname, firstname]

	    	global $var, $sel;
            if (isset($students)) {
                unset($students);
            }

	        $result = Database::query($sel . $thisRecip . "'",__FILE__,__LINE__);
	        while ( ($res = Database::fetch_array($result))) {$students[] = $res;}
	        mysql_free_result($result);

	    	if (count($students) == 1)
	    	{
		    	return($students[0]);
	    	}
	    	elseif (count($students) > 1)
	    	{
	        	return ' <'.dropbox_lang("mailingFileRecipDup", "noDLTT").$var."= $thisRecip>";
	    	}
	    	else
	    	{
	        	return ' <'.dropbox_lang("mailingFileRecipNotFound", "noDLTT").$var."= $thisRecip>";
	    	}
        }

		$preFix = $nameParts[1]; $postFix = $nameParts[3];
		$preLen = api_strlen($preFix); $postLen = api_strlen($postFix);

		function findRecipient($thisFile)
		{
			// string result = error message, array result = [user_id, lastname, firstname, status]

			global $nameParts, $preFix, $preLen, $postFix, $postLen;

            if ( preg_match(dropbox_cnf("mailingFileRegexp"), $thisFile, $matches))
            {
	            $thisName = $matches[1];
	            if ( api_substr($thisName, 0, $preLen) == $preFix)
	            {
		            if ( $postLen == 0 || api_substr($thisName, -$postLen) == $postFix)
		            {
			            $thisRecip = api_substr($thisName, $preLen, api_strlen($thisName) - $preLen - $postLen);
			            if ( $thisRecip) return getUser($thisRecip);
			            return ' <'.dropbox_lang("mailingFileNoRecip", "noDLTT").'>';
		            }
		            else
		            {
			            return ' <'.dropbox_lang("mailingFileNoPostfix", "noDLTT").$postFix.'>';
		            }
	            }
	            else
	            {
		            return ' <'.dropbox_lang("mailingFileNoPrefix", "noDLTT").$preFix.'>';
	            }
            }
            else
            {
	            return ' <'.dropbox_lang("mailingFileFunny", "noDLTT").'>';
            }
        }

	    if (file_exists(api_get_path(INCLUDE_PATH) . "/pclzip/pclzip.lib.php"))
	    {
	        require(api_get_path(INCLUDE_PATH) . "/pclzip/pclzip.lib.php");
	    }
	    else
	    {
	        require(api_get_path(LIBRARY_PATH) . "/pclzip/pclzip.lib.php");
		}

		$zipFile = new pclZip($mailing_file);  $goodFiles  = array();
		$zipContent = $zipFile->listContent(); $ucaseFiles = array();

		if ( $zipContent)
		{
			foreach( $zipFile->listContent() as $thisContent)
			{
	            $thisFile = substr(strrchr('/' . $thisContent['filename'], '/'), 1);
	            $thisFileUcase = strtoupper($thisFile);
				if ( preg_match("~.(php.*|phtml)$~i", $thisFile) )
				{
		            $error = TRUE; $errormsg .= $thisFile . ': ' . dropbox_lang("mailingZipPhp");
					break;
				}
				elseif ( !$thisContent['folder'])
				{
		            if ( $ucaseFiles[$thisFileUcase])
		            {
			            $error = TRUE; $errormsg .= $thisFile . ': ' . dropbox_lang("mailingZipDups");
						break;
		            }
		            else
		            {
			            $goodFiles[$thisFile] = findRecipient($thisFile);
			            $ucaseFiles[$thisFileUcase] = "yep";
		            }
				}

			}
		}
		else
		{
            $error = TRUE; $errormsg .= dropbox_lang("mailingZipEmptyOrCorrupt");
        }

		if ( !$error)
		{
			$students = array();  // collect all recipients in this course

			foreach( $goodFiles as $thisFile => $thisRecip)
			{
				$errormsg .= htmlspecialchars($thisFile,ENT_QUOTES,$charset) . ': ';
	            if ( is_string($thisRecip))  // see findRecipient
	            {
					$errormsg .= '<font color="#FF0000">'
						. htmlspecialchars($thisRecip,ENT_QUOTES,$charset) . '</font><br>';
	            }
	            else
	            {
					if ( isset( $_GET['mailingSend']))
					{
			            $errormsg .= dropbox_lang("mailingFileSentTo");
		            }
		            else
		            {
						$errormsg .= dropbox_lang("mailingFileIsFor");
		            }
					$errormsg .= htmlspecialchars(api_get_person_name($thisRecip[2], $thisRecip[1]), ENT_QUOTES, $charset);

					if ( is_null($thisRecip[3]))
					{
						$errormsg .= dropbox_lang("mailingFileNotRegistered");
					}
					else
					{
						$students[] = $thisRecip[0];
					}
					$errormsg .= '<br>';

	            }
			}

			// find student course members not among the recipients

			$course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
			$sql = "SELECT u.lastname, u.firstname
					FROM $course_user cu
					LEFT JOIN  `".$_configuration['main_database']."`.`user` u
					ON cu.user_id = u.user_id AND cu.course_code = '".$_course['sysCode']."'
					WHERE cu.status = 5
					AND u.user_id NOT IN ('" . implode("', '" , $students) . "')";
	        $result = Database::query($sql,__FILE__,__LINE__);

	        if ( mysql_num_rows($result) > 0)
	        {
		        $remainingUsers = '';
		        while ( ($res = Database::fetch_array($result)))
		        {
					$remainingUsers .= ', ' . htmlspecialchars(api_get_person_name($res[1], $res[0]), ENT_QUOTES, $charset);
		        }
		        $errormsg .= '<br />' . dropbox_lang("mailingNothingFor") . api_substr($remainingUsers, 1) . '.<br />';
	        }

			if ( isset( $_GET['mailingSend']))
			{
				chdir(dropbox_cnf("sysPath"));
				$zipFile->extract(PCLZIP_OPT_REMOVE_ALL_PATH);

				$mailingPseudoId = dropbox_cnf("mailingIdBase") + $mailing_item->id;

				foreach( $goodFiles as $thisFile => $thisRecip)
				{
		            if ( is_string($thisRecip))  // remove problem file
		            {
			            @unlink(dropbox_cnf("sysPath") . '/' . $thisFile);
		            }
		            else
		            {
				        $newName = getLoginFromId( $_user['user_id']) . "_" . $thisFile . "_" . uniqid('');
				        if ( rename(dropbox_cnf("sysPath") . '/' . $thisFile, dropbox_cnf("sysPath") . '/' . $newName))
							new Dropbox_SentWork( $mailingPseudoId, $thisFile, $mailing_item->description, $mailing_item->author, $newName, $thisContent['size'], array($thisRecip[0]));
		            }
				}

			    $sendDT = addslashes(date("Y-m-d H:i:s",time()));
			    // set filesize to zero on send, to avoid 2nd send (see index.php)
				$sql = "UPDATE ".dropbox_cnf("tbl_file")."
						SET filesize = '0'
						, upload_date = '".$sendDT."', last_upload_date = '".$sendDT."'
						WHERE id='".addslashes($mailing_item->id)."'";
				$result =Database::query($sql,__FILE__,__LINE__);
			}
			elseif ( $mailing_item->filesize != 0)
			{
		        $errormsg .= '<br>' . dropbox_lang("mailingNotYetSent") . '<br>';
			}
        }
    }
    else
    {
        $error = TRUE; $errormsg .= dropbox_lang("mailingWrongZipfile");
    }


    /**
     * ========================================
     * EXAMINE OR SEND MAILING RESULTMESSAGE
     * ========================================
     */
    if ( $error)
    {
        ?>
		<b><font color="#FF0000"><?php echo $errormsg?></font></b><br><br>
		<a href="index.php<?php echo "?origin=$origin"; ?>"><?php echo dropbox_lang("backList")?></a><br>
		<?php
    }

    else
    {
        ?>
		<?php echo $errormsg?><br><br>
		<a href="index.php<?php echo "?origin=$origin"; ?>"><?php echo dropbox_lang("backList")?></a><br>
		<?php
    }



}


/**
 * =============================================
 * DELETE RECEIVED OR SENT FILES - EDIT FEEDBACK  // RH: Feedback
 * =============================================
 * - DELETE ALL RECEIVED FILES
 * - DELETE 1 RECEIVED FILE
 * - DELETE ALL SENT FILES
 * - DELETE 1 SENT FILE
 * - EDIT FEEDBACK                                // RH: Feedback
 */
if ( isset( $_GET['deleteReceived']) || isset( $_GET['deleteSent'])
         || isset( $_GET['showFeedback']) || isset( $_GET['editFeedback']))  // RH: Feedback
{
	if ( $_GET['mailing'])  // RH: Mailing
	{
		getUserOwningThisMailing($_GET['mailing'], $_user['user_id'], '408');  // RH or die
		$dropbox_person = new Dropbox_Person( $_GET['mailing'], $is_courseAdmin, $is_courseTutor);
	}
	else
	{
	    $dropbox_person = new Dropbox_Person( $_user['user_id'], $is_courseAdmin, $is_courseTutor);
    }

	// RH: these two are needed, I think

	if ( isset($_SESSION["sentOrder"]))
	{
		$dropbox_person->orderSentWork ($_SESSION["sentOrder"]);
	}
	if ( isset($_SESSION["receivedOrder"]))
	{
		$dropbox_person->orderReceivedWork ($_SESSION["receivedOrder"]);
	}

	/*if (! $dropbox_person->isCourseAdmin || ! $dropbox_person->isCourseTutor) {
	    die(dropbox_lang("generalError")." (code 408)");
	}*/

	$tellUser = dropbox_lang("fileDeleted");  // RH: Feedback

    if ( isset( $_GET['deleteReceived']))
    {
        if ( $_GET["deleteReceived"] == "all")
        {
            $dropbox_person->deleteAllReceivedWork( );
        }elseif ( is_numeric( $_GET["deleteReceived"]))
        {
            $dropbox_person->deleteReceivedWork( $_GET['deleteReceived']);
        }
        else
        {
            die(dropbox_lang("generalError")." (code 409)");
        }
    }
    elseif ( isset( $_GET['deleteSent']))  // RH: Feedback
    {
        if ( $_GET["deleteSent"] == "all")
        {
            $dropbox_person->deleteAllSentWork( );
        }elseif ( is_numeric( $_GET["deleteSent"]))
        {
            $dropbox_person->deleteSentWork( $_GET['deleteSent']);
        }
        else
        {
            die(dropbox_lang("generalError")." (code 410)");
        }
    }
    elseif ( isset( $_GET['showFeedback']))  // RH: Feedback
    {
		$w = new Dropbox_SentWork($id = $_GET['showFeedback']);

		if ($w->uploader_id != $_user['user_id'])
		    getUserOwningThisMailing($w->uploader_id, $_user['user_id'], '411');  // RH or die

    	foreach( $w -> recipients as $r) if (($fb = $r["feedback"]))
    	{
            $fbarray [$r["feedback_date"].$r["name"]]=
                $r["name"] . ' ' . dropbox_lang("sentOn", "noDLTT") .
                ' ' . $r["feedback_date"] . ":\n" . $fb;
    	}

    	if ($fbarray)
    	{
        	krsort($fbarray);
            echo '<textarea class="dropbox_feedbacks">',
                    htmlspecialchars(implode("\n\n", $fbarray),ENT_QUOTES,$charset), '</textarea>', "\n";
        }
        else
        {
            echo '<textarea class="dropbox_feedbacks">&nbsp;</textarea>', "\n";
        }

        $tellUser = dropbox_lang("showFeedback");
    }
    else  // if ( isset( $_GET['editFeedback']))  // RH: Feedback
    {
        $id = $_GET['editFeedback']; $found = false;
		foreach($dropbox_person->receivedWork as $w) {
			if ($w->id == $id) {
			   $found = true; break;
			}
		}
		if (! $found) die(dropbox_lang("generalError")." (code 415)");

        echo '<form method="post" action="index.php">', "\n",
            '<input type="hidden" name="feedbackid" value="',
                $id, '"/>', "\n",
            '<textarea name="feedbacktext" class="dropbox_feedbacks">',
                htmlspecialchars($w->feedback,ENT_QUOTES,$charset), '</textarea>', "<br>\n",
            '<input type="submit" name="feedbacksubmit" value="', dropbox_lang("ok", "noDLTT"), '"/>', "\n",
            '</form>', "\n";
        $tellUser = dropbox_lang("giveFeedback");
    }

    /**
     * ==============================================
     * RESULTMESSAGE FOR DELETE FILE OR EDIT FEEDBACK  // RH: Feedback
     * ==============================================
     */
    $return_message=dropbox_lang("backList");
}
?>
