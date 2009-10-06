<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */

/**
 * Dropbox module for Dokeos
 * Classes for the dropbox module.
 *
 * 3 classes are defined:
 * - Dropbox_Work:
 * 		. id
 * 		. uploader_id	=> who sent it		// RH: Mailing: or mailing pseudo_id
 * 		. uploaderName
 * 		. filename		=> name of file stored on the server
 * 		. filesize							// RH: Mailing: zero for sent zip
 * 		. title			=> name of file returned to user. This is the original name of the file
 * 							except when the original name contained spaces. In that case the spaces
 * 							will be replaced by _
 * 		. description
 * 		. author
 * 		. upload_date	=> date when file was first sent
 * 		. last_upload_date=> date when file was last sent
 *  	. isOldWork 	=> has the work already been uploaded before
 *
 *      . feedback_date  => date of most recent feedback     // RH: Feedback
 *      . feedback      => feedback text (or HTML?)         // RH: Feedback
 *
 * - Dropbox_SentWork extends Dropbox_Work
 * 		. recipients	=> array of ["id"]["name"] lists the recipients of the work
 * 											// RH: Mailing: or mailing pseudo_id
 *                          ["feedback_date"]["feedback"]    // RH: Feedback
 * - Dropbox_Person:
 * 		. userId
 * 		. receivedWork 	=> array of Dropbox_Work objects
 * 		. sentWork 		=> array of Dropbox_SentWork objects
 * 		. isCourseTutor
 * 		. isCourseAdmin
 * 		. _orderBy		=>private property used for determining the field by which the works have to be ordered
 *
 * @version 1.30
 * @copyright 2004
 * @author Jan Bols <jan@ivpv.UGent.be>
 * with contributions by Ren� Haentjens <rene.haentjens@UGent.be> (see RH)
  *	@package dokeos.dropbox
 **/
class Dropbox_Work {
	public $id;
	public $uploader_id;
	public $uploaderName;
	public $filename;
	public $filesize;
	public $title;
	public $description;
	public $author;
	public $upload_date;
	public $last_upload_date;
	public $isOldWork;
	public $feedback_date, $feedback;  // RH: Feedback

	/**
		* Constructor calls private functions to create a new work or retreive an existing work from DB
		* depending on the number of parameters
	 *
	 * @param unknown_type $arg1
	 * @param unknown_type $arg2
	 * @param unknown_type $arg3
	 * @param unknown_type $arg4
	 * @param unknown_type $arg5
	 * @param unknown_type $arg6
	 * @return Dropbox_Work
		*/
	function Dropbox_Work ($arg1, $arg2=null, $arg3=null, $arg4=null, $arg5=null, $arg6=null) {
		if (func_num_args()>1)  {
		    $this->_createNewWork($arg1, $arg2, $arg3, $arg4, $arg5, $arg6);
		}  else  {
			$this->_createExistingWork($arg1);
		}
	}

	/**
		* private function creating a new work object
	 *
	 * @param unknown_type $uploader_id
	 * @param unknown_type $title
	 * @param unknown_type $description
	 * @param unknown_type $author
	 * @param unknown_type $filename
	 * @param unknown_type $filesize
	 *
	 * @todo 	$author was originally a field but this has now been replaced by the first and lastname of the uploader (to prevent anonymous uploads)
	 * 			As a consequence this parameter can be removed
		*/
	function _createNewWork ($uploader_id, $title, $description, $author, $filename, $filesize) {
		global $_user;

		// Do some sanity checks
		settype($uploader_id, 'integer') or die(dropbox_lang("generalError")." (code 201)"); //set $uploader_id to correct type
		//if (! isCourseMember($uploader_id)) die(); //uploader must be coursemember to be able to upload
			//-->this check is done when submitting data so it isn't checked here

		// Fill in the properties
		$this->uploader_id = $uploader_id;
		$this->uploaderName = getUserNameFromId($this->uploader_id);
		$this->filename = $filename;
		$this->filesize = $filesize;
		$this->title = $title;
		$this->description = $description;
		$this->author = api_get_person_name($_user['firstName'], $_user['lastName']);
		$this->last_upload_date = date("Y-m-d H:i:s",time());

		// Check if object exists already. If it does, the old object is used
		// with updated information (authors, descriptio, upload_date)
		$this->isOldWork = FALSE;
		$sql="SELECT id, upload_date
				FROM ".dropbox_cnf("tbl_file")."
				WHERE filename = '".addslashes($this->filename)."'";
        $result = Database::query($sql,__FILE__,__LINE__);
		$res = mysql_fetch_array($result);
		if ($res != FALSE) {
			$this->isOldWork = TRUE;
		}
		// insert or update the dropbox_file table and set the id property
		if ($this->isOldWork) {
			$this->id = $res["id"];
			$this->upload_date = $res["upload_date"];
		    $sql = "UPDATE ".dropbox_cnf("tbl_file")."
					SET filesize = '".addslashes($this->filesize)."'
					, title = '".addslashes($this->title)."'
					, description = '".addslashes($this->description)."'
					, author = '".addslashes($this->author)."'
					, last_upload_date = '".addslashes($this->last_upload_date)."'
					WHERE id='".addslashes($this->id)."'";
			$result = Database::query($sql,__FILE__,__LINE__);
		} else {
			$this->upload_date = $this->last_upload_date;
			$sql="INSERT INTO ".dropbox_cnf("tbl_file")."
				(uploader_id, filename, filesize, title, description, author, upload_date, last_upload_date, session_id)
				VALUES ('".addslashes($this->uploader_id)."'
						, '".addslashes($this->filename)."'
						, '".addslashes($this->filesize)."'
						, '".addslashes($this->title)."'
						, '".addslashes($this->description)."'
						, '".addslashes($this->author)."'
						, '".addslashes($this->upload_date)."'
						, '".addslashes($this->last_upload_date)."'
						, ".intval($_SESSION['id_session'])."
						)";

        	$result = Database::query($sql,__FILE__,__LINE__);
			$this->id = mysql_insert_id(); //get automatically inserted id
		}

		// insert entries into person table
		$sql="INSERT INTO ".dropbox_cnf("tbl_person")."
				(file_id, user_id)
				VALUES ('".addslashes($this->id)."'
						, '".addslashes($this->uploader_id)."'
						)";
        $result = Database::query($sql);	//if work already exists no error is generated
	}

	/**
		* private function creating existing object by retreiving info from db
	 *
	 * @param unknown_type $id
		*/
	function _createExistingWork ($id) {
		global $_user;  // RH: Feedback

		// Do some sanity checks
		settype($id, 'integer') or die(dropbox_lang("generalError")." (code 205)"); //set $id to correct type

		// get the data from DB
		$sql="SELECT uploader_id, filename, filesize, title, description, author, upload_date, last_upload_date, cat_id
				FROM ".dropbox_cnf("tbl_file")."
				WHERE id='".addslashes($id)."'";
        $result = Database::query($sql,__FILE__,__LINE__);
		$res = mysql_fetch_array($result,MYSQL_ASSOC);

		// Check if uploader is still in Dokeos system
		$uploader_id = stripslashes($res["uploader_id"]);
		$uploaderName = getUserNameFromId($uploader_id);
		if ($uploaderName == FALSE) {
			//deleted user
			$this->uploader_id = -1;
			$this->uploaderName = dropbox_lang("anonymous", "noDLTT");
		} else {
			$this->uploader_id = $uploader_id;
			$this->uploaderName = $uploaderName;
		}

		// Fill in properties
		$this->id = $id;
		$this->filename = stripslashes($res["filename"]);
		$this->filesize = stripslashes($res["filesize"]);
		$this->title = stripslashes($res["title"]);
		$this->description = stripslashes($res["description"]);
		$this->author = stripslashes($res["author"]);
		$this->upload_date = stripslashes($res["upload_date"]);
		$this->last_upload_date = stripslashes($res["last_upload_date"]);
		$this->category = $res['cat_id'];


		// Getting the feedback on the work.
		if ($_GET['action']=='viewfeedback' AND $this->id==$_GET['id']) {
			$feedback2=array();
			$sql_feedback = "SELECT * FROM ".dropbox_cnf("tbl_feedback")." WHERE file_id='".$id."' ORDER BY feedback_id ASC";
			$result = Database::query($sql_feedback, __FILE__, __LINE__);
			while ($row_feedback=Database::fetch_array($result)) {
				$feedback2[]=$row_feedback;
			}
			$this->feedback2=$feedback2;
		}
		/*
		// RH: Feedback
		$result = Database::query("SELECT feedback_date, feedback, cat_id FROM ".
		    dropbox_cnf("tbl_post")." WHERE dest_user_id='".$_user['user_id'].
		    "' AND file_id='".$id."'",__FILE__,__LINE__);
		if ($res = mysql_fetch_array($result))
		{
    		$this->feedback_date = $res["feedback_date"];
    		$this->feedback = $res["feedback"];
    		$this->category = $res['cat_id'];
		}  // do not fail if there is no recipient = current user...*/
	}
}

class Dropbox_SentWork extends Dropbox_Work
{
	public $recipients;	//array of ["id"]["name"] arrays

	/**
		* Constructor calls private functions to create a new work or retreive an existing work from DB
		* depending on the number of parameters
	 *
	 * @param unknown_type $arg1
	 * @param unknown_type $arg2
	 * @param unknown_type $arg3
	 * @param unknown_type $arg4
	 * @param unknown_type $arg5
	 * @param unknown_type $arg6
	 * @param unknown_type $arg7
	 * @return Dropbox_SentWork
		*/
	function Dropbox_SentWork ($arg1, $arg2=null, $arg3=null, $arg4=null, $arg5=null, $arg6=null, $arg7=null) {
		if (func_num_args()>1) {
		    $this->_createNewSentWork ($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7);
		} else {
			$this->_createExistingSentWork ($arg1);
		}
	}

	/**
		* private function creating a new SentWork object
		*
	 * @param unknown_type $uploader_id
	 * @param unknown_type $title
	 * @param unknown_type $description
	 * @param unknown_type $author
	 * @param unknown_type $filename
	 * @param unknown_type $filesize
	 * @param unknown_type $recipient_ids
		*/
	function _createNewSentWork ($uploader_id, $title, $description, $author, $filename, $filesize, $recipient_ids) {
		// Call constructor of Dropbox_Work object
		$this->Dropbox_Work($uploader_id, $title, $description, $author, $filename, $filesize);

		// Do sanity checks on recipient_ids array & property fillin
		// The sanity check for ex-coursemembers is already done in base constructor
		settype($uploader_id, 'integer') or die(dropbox_lang("generalError")." (code 208)"); //set $uploader_id to correct type

		$justSubmit = FALSE;  // RH: mailing zip-file or just upload
		if ( is_int($recipient_ids)) {
			$justSubmit = TRUE; $recipient_ids = array($recipient_ids + $this->id);
		} elseif ( count($recipient_ids) == 0) {  // RH: Just Upload
			$justSubmit = TRUE; $recipient_ids = array($uploader_id);
		}
		if (! is_array($recipient_ids) || count($recipient_ids) == 0) {
			die(dropbox_lang("generalError")." (code 209)");
		}
		foreach ($recipient_ids as $rec) {
			if (empty($rec)) die(dropbox_lang("generalError")." (code 210)");
			//if (!isCourseMember($rec)) die(); //cannot sent document to someone outside of course
				//this check is done when validating submitted data
			$this->recipients[] = array("id"=>$rec, "name"=>getUserNameFromId($rec));
		}

		// insert data in dropbox_post and dropbox_person table for each recipient
		foreach ($this->recipients as $rec) {
			$sql="INSERT INTO ".dropbox_cnf("tbl_post")."
				(file_id, dest_user_id, session_id)
				VALUES ('".addslashes($this->id)."', '".addslashes($rec["id"])."', ".intval($_SESSION['id_session']).")";
	        $result = Database::query($sql);	//if work already exists no error is generated

			//insert entries into person table
			$sql="INSERT INTO ".dropbox_cnf("tbl_person")."
				(file_id, user_id)
				VALUES ('".addslashes($this->id)."'
						, '".addslashes($rec["id"])."'
						)";
        	// RH: do not add recipient in person table if mailing zip or just upload
			if (!$justSubmit) {
				$result = Database::query($sql);	//if work already exists no error is generated
			}
			//update item_property (previously last_tooledit) table for each recipient

			global $_course, $dropbox_cnf;

			if (($ownerid = $this->uploader_id) > $dropbox_cnf["mailingIdBase"]) {
			    $ownerid = getUserOwningThisMailing($ownerid);
			}
			if (($recipid = $rec["id"]) > $dropbox_cnf["mailingIdBase"]) {
			    $recipid = $ownerid;  // mailing file recipient = mailing id, not a person
			}
			api_item_property_update($_course, TOOL_DROPBOX, $this->id, "DropboxFileAdded", $ownerid, NULL, $recipid) ;
		}
	}

	/**
		* private function creating existing object by retreiving info from db
	 *
	 * @param unknown_type $id
		*/
	function _createExistingSentWork  ($id) {
		// Call constructor of Dropbox_Work object
		$this->Dropbox_Work($id);

		// Do sanity check. The sanity check for ex-coursemembers is already done in base constructor
		settype($id, 'integer') or die(dropbox_lang("generalError")." (code 211)"); //set $id to correct type

		//Fill in recipients array/
		$this->recipients = array();  // RH: Feedback: added to SELECT
		$sql="SELECT dest_user_id, feedback_date, feedback
				FROM ".dropbox_cnf("tbl_post")."
				WHERE file_id='".addslashes($id)."'";
        $result = Database::query($sql,__FILE__,__LINE__);
		while ($res = Database::fetch_array($result)) {
			// check for deleted users
			$dest_user_id = $res["dest_user_id"];
			$recipientName = getUserNameFromId($dest_user_id);
			//$this->category=$res['cat_id'];
			if ($recipientName == FALSE) {
				$this->recipients[] = array("id"=>-1, "name"=> dropbox_lang("anonymous", "noDLTT"));
			} else {
				$this->recipients[] = array("id"=>$dest_user_id, "name"=>$recipientName, "user_id"=>$dest_user_id,
				    "feedback_date"=>$res["feedback_date"], "feedback"=>$res["feedback"]);  // RH: Feedback
			}
		}
	}
}

class Dropbox_Person
{
	public $receivedWork;	//array of Dropbox_Work objects
	public $sentWork;		//array of Dropbox_SentWork objects
	public $userId = 0;
	public $isCourseAdmin = FALSE;
	public $isCourseTutor = FALSE;
	public $_orderBy = '';	//private property that determines by which field
						//the receivedWork and the sentWork arrays are sorted

	/**
	 * Constructor for recreating the Dropbox_Person object
	 *
	 * @param unknown_type $userId
	 * @param unknown_type $isCourseAdmin
	 * @param unknown_type $isCourseTutor
	 * @return Dropbox_Person
	 */
	function Dropbox_Person ($userId, $isCourseAdmin, $isCourseTutor) {
		// Fill in properties
		$this->userId = $userId;
		$this->isCourseAdmin = $isCourseAdmin;
		$this->isCourseTutor = $isCourseTutor;
		$this->receivedWork = array();
		$this->sentWork = array();

		//Note: perhaps include an ex coursemember check to delete old files

		$post_tbl = Database::get_course_table(TABLE_DROPBOX_POST);
		$person_tbl = Database::get_course_table(TABLE_DROPBOX_PERSON);
		$file_tbl = Database::get_course_table(TABLE_DROPBOX_FILE);
		// find all entries where this person is the recipient
		$sql = "SELECT r.file_id, r.cat_id
				FROM $post_tbl r, $person_tbl p
				WHERE r.dest_user_id = '".addslashes($this->userId)."'
					AND r.dest_user_id = p.user_id
					AND r.file_id = p.file_id";

		if (intval($_SESSION['id_session']>0)) {
			$sql .= " AND r.session_id = ".intval($_SESSION['id_session']);
		}
        $result = Database::query($sql,__FILE__,__LINE__);
		while ($res = Database::fetch_array($result)) {
			$temp = new Dropbox_Work($res["file_id"]);
			$temp -> category = $res['cat_id'];
			$this->receivedWork[] = $temp;
		}

		// find all entries where this person is the sender/uploader
		$sql = "SELECT f.id
				FROM $file_tbl f, $person_tbl p
				WHERE f.uploader_id = '".addslashes($this->userId)."'
				AND f.uploader_id = p.user_id
				AND f.id = p.file_id";

		if(intval($_SESSION['id_session']>0)) {
			$sql .= " AND f.session_id = ".intval($_SESSION['id_session']);
		}
        $result =Database::query($sql,__FILE__,__LINE__);
		while ($res = Database::fetch_array($result)) {
			$this->sentWork[] = new Dropbox_SentWork($res["id"]);
		}
	}

	/**
		* This private method is used by the usort function in  the
		* orderSentWork and orderReceivedWork methods.
		* It compares 2 work-objects by 1 of the properties of that object, dictated by the
	 * private property _orderBy
	 *
	 * @param unknown_type $a
	 * @param unknown_type $b
	 * @return -1, 0 or 1 dependent of the result of the comparison.
		*/
	function _cmpWork ($a, $b) {
		$sort = $this->_orderBy;
		$aval = $a->$sort;
		$bval = $b->$sort;
		if ($sort == 'recipients') {	//the recipients property is an array so we do the comparison based on the first item of the recipients array
		    $aval = $aval[0]['name'];
			$bval = $bval[0]['name'];
		}
		if ($sort == 'filesize') {	//filesize is not a string, so we use other comparison technique
			return $aval<$bval ? -1 : 1;
		} elseif ($sort == 'title') { // natural order for sorting titles is more "human-friendly"
			return api_strnatcmp($aval, $bval);
		} else {
		    return api_strcasecmp($aval, $bval);
		}
	}

	/**
		* method that sorts the objects in the sentWork array, dependent on the $sort parameter.
		* $sort can be lastDate, firstDate, title, size, ...
	 *
	 * @param unknown_type $sort
		*/
	function orderSentWork($sort) {
		/*

		*/
		switch($sort) {
			case 'lastDate':
				$this->_orderBy = 'last_upload_date';
				break;
			case 'firstDate':
				$this->_orderBy = 'upload_date';
				break;
			case 'title':
				$this->_orderBy = 'title';
				break;
			case 'size':
				$this->_orderBy = 'filesize';
				break;
			case 'author':
				$this->_orderBy = 'author';
				break;
			case 'recipient':
				$this->_orderBy = 'recipients';
				break;
			default:
				$this->_orderBy = 'last_upload_date';
		} // switch

		usort($this->sentWork, array($this,"_cmpWork"));	//this calls the _cmpWork method
	}

	/**
		* method that sorts the objects in the receivedWork array, dependent on the $sort parameter.
		* $sort can be lastDate, firstDate, title, size, ...
	 * @param unknown_type $sort
		*/
	function orderReceivedWork($sort) {
		switch($sort) {
			case 'lastDate':
				$this->_orderBy = 'last_upload_date';
				break;
			case 'firstDate':
				$this->_orderBy = 'upload_date';
				break;
			case 'title':
				$this->_orderBy = 'title';
				break;
			case 'size':
				$this->_orderBy = 'filesize';
				break;
			case 'author':
				$this->_orderBy = 'author';
				break;
			case 'sender':
				$this->_orderBy = 'uploaderName';
				break;
			default:
				$this->_orderBy = 'last_upload_date';
		} // switch

		usort($this->receivedWork, array($this,"_cmpWork"));	//this calls the _cmpWork method
	}

	/**
		* Deletes all the received work of this person
	 *
		*/
	function deleteAllReceivedWork () {
		//delete entries in person table concerning received works
		foreach ($this->receivedWork as $w) {
			Database::query("DELETE FROM ".dropbox_cnf("tbl_person")." WHERE user_id='".$this->userId."' AND file_id='".$w->id."'",__FILE__,__LINE__);
		}
		removeUnusedFiles();	//check for unused files

	}

   /**
    * Deletes all the received categories and work of this person
	  */
	function deleteReceivedWorkFolder($id) {
		$sql = "DELETE FROM ".dropbox_cnf("tbl_file")." where cat_id = '".$id."' ";
		if(!Database::query($sql))		return false;
		$sql = "DELETE FROM ".dropbox_cnf("tbl_category")." where cat_id = '".$id."' ";
		if(!Database::query($sql))		return false;
		$sql = "DELETE FROM ".dropbox_cnf("tbl_post")." where cat_id = '".$id."' ";
		if(!Database::query($sql))		return false;
		return true;
	}
	/**
	 * Deletes a received dropbox file of this person with id=$id
	 *
	 * @param integer $id
		*/
	function deleteReceivedWork ($id) {
		//id check
		$found = false;
		foreach($this->receivedWork as $w) {
			if ($w->id == $id) {
			   $found = true;
			   break;
			}
		}
		if (! $found) {
			if(!$this->deleteReceivedWorkFolder($id)) {
				die(dropbox_lang("generalError")." (code 216)");
			}
		}
		//delete entries in person table concerning received works
		Database::query("DELETE FROM ".dropbox_cnf("tbl_person")." WHERE user_id='".$this->userId."' AND file_id='".$id."'",__FILE__,__LINE__);
		removeUnusedFiles();	//check for unused files
	}

	/**
	 * Deletes all the sent dropbox files of this person
		*/
	function deleteAllSentWork () {
		//delete entries in person table concerning sent works
		foreach ($this->sentWork as $w) {
			Database::query("DELETE FROM ".dropbox_cnf("tbl_person")." WHERE user_id='".$this->userId."' AND file_id='".$w->id."'",__FILE__,__LINE__);
			removeMoreIfMailing($w->id);  // RH: Mailing: see init1
		}
		removeUnusedFiles();	//check for unused files
	}

	/**
	 * Deletes a sent dropbox file of this person with id=$id
	 *
	 * @param unknown_type $id
		*/
	function deleteSentWork ($id) {
		//index check
		$found = false;
		foreach($this->sentWork as $w) {
			if ($w->id == $id) {
			   $found = true;
			   break;
			}
		}
		if (!$found) {
			if(!$this->deleteReceivedWorkFolder($id)) {
				die(dropbox_lang("generalError")." (code 219)");
			}
		}
		//$file_id = $this->sentWork[$index]->id;  // RH: Mailing
		//delete entries in person table concerning sent works
		Database::query("DELETE FROM ".dropbox_cnf("tbl_person")." WHERE user_id='".$this->userId."' AND file_id='".$id."'",__FILE__,__LINE__);
		removeMoreIfMailing($id);  // RH: Mailing: see init1
		removeUnusedFiles();	//check for unused files
	}

	/**
	 * Updates feedback for received work of this person with id=$id
	 *
	 * @param unknown_type $id
	 * @param unknown_type $text
	 */
	function updateFeedback($id, $text) {
		global $_course, $dropbox_cnf;

		//id check
		$found = false; $wi = -1;
		foreach($this->receivedWork as $w) {
			$wi++;
			if ($w->id == $id){
			   $found = true;
			   break;
			}  // foreach (... as $wi -> $w) gives error 221! (no idea why...)
		}
		if (! $found) {
			die(dropbox_lang("generalError")." (code 221)");
		}

		$feedback_date = date("Y-m-d H:i:s",time());
		$this->receivedWork[$wi]->feedback_date = $feedback_date;
		$this->receivedWork[$wi]->feedback = $text;

		Database::query("UPDATE ".dropbox_cnf("tbl_post")." SET feedback_date='".
		    addslashes($feedback_date)."', feedback='".addslashes($text).
		    "' WHERE dest_user_id='".$this->userId."' AND file_id='".$id."'",__FILE__,__LINE__);

		//update item_property (previously last_tooledit) table

		if (($ownerid = $this->receivedWork[$wi]->uploader_id) > $dropbox_cnf["mailingIdBase"]) {
		    $ownerid = getUserOwningThisMailing($ownerid);
		}
		api_item_property_update($_course, TOOL_DROPBOX, $this->receivedWork[$wi]->id, "DropboxFileUpdated", $this->userId, NULL, $ownerid) ;

	}
	/**
	 * Filter the received work
	 * @param string $type
	 * @param string $value
	 */
	function filter_received_work($type,$value) {
		global $dropbox_cnf;

    	$new_received_work = array();
		foreach ($this->receivedWork as $index => $work) {
			switch ($type) {
				case 'uploader_id':
					if ($work->uploader_id == $value ||
					    ($work->uploader_id > $dropbox_cnf["mailingIdBase"] &&
					     getUserOwningThisMailing($work->uploader_id) == $value)) {
						$new_received_work[] = $work;
					}
					break;
				default:
					$new_received_work[] = $work;
			}
		}
		$this->receivedWork = $new_received_work;
	}
}
