<?php
/* For licensing terms, see /license.txt */
/**
 * Course Restorer script
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Course.class.php';
require_once 'Event.class.php';
require_once 'Link.class.php';
require_once 'ToolIntro.class.php';
require_once 'LinkCategory.class.php';
require_once 'ForumCategory.class.php';
require_once 'Forum.class.php';
require_once 'ForumTopic.class.php';
require_once 'ForumPost.class.php';
require_once 'CourseDescription.class.php';
require_once 'CourseCopyLearnpath.class.php';
require_once 'Survey.class.php';
require_once 'SurveyQuestion.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/question.class.php';
require_once 'Glossary.class.php';
require_once 'wiki.class.php';
require_once 'Thematic.class.php';

require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';

define('FILE_SKIP',             1);
define('FILE_RENAME',           2);
define('FILE_OVERWRITE',        3);
define('UTF8_CONVERT', 		false); //false by default

/**
 * Class to restore items from a course object to a Chamilo-course
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @author Julio Montoya <gugli100@gmail.com> Several fixes/improvements
 * @package chamilo.backup
 */
class CourseRestorer
{
	/**
	 * The course-object
	 */
	var $course;

	var $destination_course_info;

	/**
	 * What to do with files with same name (FILE_SKIP, FILE_RENAME or
	 * FILE_OVERWRITE)
	 */
	var $file_option;
	var $set_tools_invisible_by_default;
	var $skip_content;
    var $tools_to_restore = array(                            
                            'announcements',
                            'attendance',
                            'course_descriptions',
                            'documents', 
                            'events',
                            'forum_category', 
                            'forums',
                           // 'forum_topics',
                            'glossary',
                            'quizzes',                                     
                            'learnpaths',
                            'links', 
                            'surveys',
                            //'scorm_documents', ??
                            'tool_intro',
                            'thematic',
                            'wiki'
        );

    /** Setting per tool */
    var $tool_copy_settings = array();

    /**
     * If true adds the text "copy" in the title of an item (only for LPs right now)
     *
     **/
    var $add_text_in_items = false;

	/**
	 * Create a new CourseRestorer
	 */
	function __construct($course) {
		$this->course							= $course;
		$course_info 							= api_get_course_info($this->course->code);
		$this->course_origin_id 				= $course_info['real_id'];
		$this->file_option 						= FILE_RENAME;
		$this->set_tools_invisible_by_default 	= false;
		$this->skip_content 					= array();
	}

	/**
	 * Set the file-option
	 * @param constant $options What to do with files with same name (FILE_SKIP,
	 * FILE_RENAME or FILE_OVERWRITE)
	 */
	function set_file_option($option) {
		$this->file_option = $option;
	}
    function set_add_text_in_items($status) {
        $this->add_text_in_items = $status;
    }
    function set_tool_copy_settings($array) {
        $this->tool_copy_settings = $array;
    }

	/**
	 * Restore a course.
	 * @param 	string 	The code of the Chamilo-course in
	 * @param	int		The session id
	 * @param	bool	Course settings are going to be restore?

	 */
	function restore($destination_course_code = '', $session_id = 0, $update_course_settings = false, $respect_base_content = false) {
		if ($destination_course_code == '') {
			$course_info = api_get_course_info();
			$this->destination_course_info = $course_info;
			$this->course->destination_path = $course_info['path'];
		} else {
			$course_info = api_get_course_info($destination_course_code);
			$this->destination_course_info = $course_info;
			$this->course->destination_path = $course_info['path'];
		}
		$this->destination_course_id = $course_info['real_id'];
        
        //Getting first teacher (for the forums)        
        $teacher_list = CourseManager::get_teacher_list_from_course_code($course_info['code']);
        $this->first_teacher_id = api_get_user_id();
        if (!empty($teacher_list)) {
            foreach ($teacher_list  as $teacher) {
                $this->first_teacher_id = $teacher['user_id'];
                break;
            }
        }

        if (empty($this->course)) {
            return false;
        }

		// Source platform encoding - reading/detection
		// The correspondent data field has been added as of version 1.8.6.1

		if (empty($this->course->encoding)) {
			// The archive has been created by a system which is prior to 1.8.6.1 version.
			// In this case we have to detect the encoding.
			$sample_text = $this->course->get_sample_text()."\n";
			// Let us exclude ASCII lines, probably they are English texts.
			$sample_text = explode("\n", $sample_text);
			foreach ($sample_text as $key => &$line) {
				if (api_is_valid_ascii($line)) {
					unset($sample_text[$key]);
				}
			}
			$sample_text = join("\n", $sample_text);
			$this->course->encoding = api_detect_encoding($sample_text, $course_info['language']);
		}

		// Encoding conversion of the course, if it is needed.
		$this->course->to_system_encoding();

        foreach ($this->tools_to_restore as $tool) {
            $function_build = 'restore_'.$tool;
            $this->$function_build($session_id, $respect_base_content, $destination_course_code);
        }

        /*
		$this->restore_links($session_id);
		$this->restore_documents($session_id, $destination_course_code);
		$this->restore_quizzes($session_id, $respect_base_content);
		$this->restore_glossary($session_id);
		$this->restore_learnpaths($session_id, $respect_base_content);
		$this->restore_course_descriptions($session_id);
		$this->restore_wiki($session_id);
		$this->restore_thematic($session_id);
		$this->restore_attendance($session_id);

		if (!empty($session_id)) {

		} else {
			//$this->restore_links();
			$this->restore_tool_intro();
			$this->restore_events();
			$this->restore_announcements();
			//$this->restore_documents();
			$this->restore_scorm_documents();
			//$this->restore_course_descriptions();
			//$this->restore_quizzes(); // after restore_documents! (for correct import of sound/video)
			//$this->restore_learnpaths();
			$this->restore_surveys();
			$this->restore_student_publication();
			//$this->restore_glossary();
			//$this->restore_wiki();
			//$this->restore_thematic();
			//$this->restore_attendance();
		}*/

		if ($update_course_settings) {
		    $this->restore_course_settings($destination_course_code);
		}

		// Restore the item properties
		$table = Database :: get_course_table(TABLE_ITEM_PROPERTY);

		$condition_session = "";

		if (!empty($session_id)) {
			$condition_session = " , id_session='".intval($session_id)."'";
		}

		foreach ($this->course->resources as $type => $resources) {
			if (is_array($resources)) {
				foreach ($resources as $id => $resource) {
					foreach ($resource->item_properties as $property) {
						// First check if there isn't allready a record for this resource
						$sql = "SELECT * FROM $table WHERE c_id = ".$this->destination_course_id." AND tool = '".$property['tool']."' AND ref = '".$resource->destination_id."'";

						$res = Database::query($sql);
						if( Database::num_rows($res) == 0) {
							// The to_group_id and to_user_id are set to default values as users/groups possibly not exist in the target course
							$sql = "INSERT INTO $table SET
									c_id 				= '".$this->destination_course_id."',
									tool 				= '".self::DBUTF8escapestring($property['tool'])."',
									insert_user_id 		= '".self::DBUTF8escapestring($property['insert_user_id'])."',
									insert_date 		= '".self::DBUTF8escapestring($property['insert_date'])."',
									lastedit_date 		= '".self::DBUTF8escapestring($property['lastedit_date'])."',
									ref 				= '".self::DBUTF8escapestring($resource->destination_id)."',
									lastedit_type 		= '".self::DBUTF8escapestring($property['lastedit_type'])."',
									lastedit_user_id 	= '".self::DBUTF8escapestring($property['lastedit_user_id'])."',
									visibility 			= '".self::DBUTF8escapestring($property['visibility'])."',
									start_visible 		= '".self::DBUTF8escapestring($property['start_visible'])."',
									end_visible 		= '".self::DBUTF8escapestring($property['end_visible'])."',
									to_user_id  		= '".self::DBUTF8escapestring($property['to_user_id'])."',
									to_group_id 		= '0' $condition_session" ;
													;
							Database::query($sql);
						}
					}
				}
			}
		}

		/*
		// Restore the linked-resources
		$table = Database :: get_course_table(TABLE_LINKED_RESOURCES);
		foreach ($this->course->resources as $type => $resources) {
			if (is_array($resources))
				foreach ($resources as $id => $resource) {
					$linked_resources = $resource->get_linked_resources();
					foreach ($linked_resources as $to_type => $to_ids) {
						foreach ($to_ids as $index => $to_id) {
							$to_resource = $this->course->resources[$to_type][$to_id];
							$sql = "INSERT INTO ".$table." SET

									source_type = '".$type."',
									source_id = '".$resource->destination_id."',
									resource_type='".$to_type."',
									resource_id='".$to_resource->destination_id."' ";
							Database::query($sql);
						}
					}
				}
		}*/
	}

	/**
	 * Restore only harmless course settings: course_language, visibility, department_name,department_url, subscribe, unsubscribe ,category_code
	 *
	 * @return unknown_type
	 */
	function restore_course_settings($destination_course_code) {
	    $origin_course_info = api_get_course_info($destination_course_code);
	    $course_info = $this->course->info;
	    $params['course_language'] = $course_info['language'];
	    $params['visibility']      = $course_info['visibility'];
	    $params['department_name'] = $course_info['department_name'];
	    $params['department_url']  = $course_info['department_url'];

	    $params['category_code']   = $course_info['categoryCode'];
	    $params['subscribe']       = $course_info['subscribe_allowed'];
	    $params['unsubscribe']     = $course_info['unsubscribe'];
	    CourseManager::update_attributes($origin_course_info['real_id'], $params);
	}

	/**
	 * Restore documents
     * @param   int session id
     *
	 */
	function restore_documents($session_id = 0, $respect_base_content = false, $destination_course_code = '') {
		$perm 			= api_get_permissions_for_new_directories();
        $course_info 	= api_get_course_info($destination_course_code);
        if ($this->course->has_resources(RESOURCE_DOCUMENT)) {

			$table = Database :: get_course_table(TABLE_DOCUMENT);
			$resources = $this->course->resources;
            
			foreach ($resources[RESOURCE_DOCUMENT] as $id => $document) {
				$path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/';
				//$dirs = explode('/', dirname($document->path));

                if (empty($document->item_properties[0]['id_session'])) {
                    $my_session_id = 0;
                } else {
                    $my_session_id = $session_id;
                }
                
		    	if ($document->file_type == FOLDER) {
		    		$visibility = $document->item_properties[0]['visibility'];
		    		if (!empty($document->title))  {
		    		    $title  = $document->title;
		    		} else {
		    		    $title  = basename($document->path);
		    		}
		    		$new = substr($document->path, 8);

		    		if (!is_dir($path.'document'.$new)) {		    			
						$created_dir = create_unexisting_directory($course_info, api_get_user_id(), $my_session_id, 0, 0 ,$path.'document', $new, $title, $visibility);
		    		}
		    	} elseif ($document->file_type == DOCUMENT) {
		    	    /*
                    echo 'option'; var_dump($this->file_option);
                    echo 'file type'; var_dump($document->file_type);
                    echo 'session'; var_dump($session_id);
                    echo 'file _exists';  var_dump($path.$document->path); var_dump(file_exists($path.$document->path));
                    */                   
                    //echo '---->';var_dump($path.dirname($document->path));
                    
                    
                    //Checking if folder exists in the database otherwise we created it 
                    $dir_to_create = dirname($document->path);                    
                    if (!empty($dir_to_create) && $dir_to_create != 'document' && $dir_to_create != '/') {
                        if (is_dir($path.dirname($document->path))) {
                            $sql = "SELECT id FROM ".$table." WHERE c_id = ".$this->destination_course_id." AND path = '/".self::DBUTF8escapestring(substr(dirname($document->path), 9))."'";
                            $res = Database::query($sql);                            
                            if (Database::num_rows($res) == 0) {                         
                                //continue;
                                $visibility = $document->item_properties[0]['visibility'];
                                $new        = '/'.substr(dirname($document->path), 9);
                                $title      = str_replace('/', '', $new);

                                // This code fixes the possibility for a file without a directory entry to be
                                $document_id = add_document($course_info, $new, 'folder', 0, $title, null, null, false);
                                api_item_property_update($course_info, TOOL_DOCUMENT, $document_id, 'FolderCreated', $document->item_properties[0]['insert_user_id'], $document->item_properties[0]['to_group_id'], $document->item_properties[0]['to_user_id'], null, null, $my_session_id);
                            }
                        } else {
                            //$created_dir = create_unexisting_directory($course_info, api_get_user_id(), $my_session_id, 0, 0 , $path.'document', $new, $title, $visibility, true);
                        }
                    }

					if (file_exists($path.$document->path)) {					    
						switch ($this->file_option) {
							case FILE_OVERWRITE :
                                $origin_path = $this->course->backup_path.'/'.$document->path;
								if (file_exists($origin_path)) {
        						    copy($origin_path, $path.$document->path);
                                    $sql = "SELECT id FROM ".$table." WHERE c_id = ".$this->destination_course_id." AND path = '/".self::DBUTF8escapestring(substr($document->path, 9))."'";
        						    $res = Database::query($sql);
                                    $count = Database::num_rows($res);
                                    if ($count == 0) {
                                        $sql = "INSERT INTO $table SET
    												path 		= '/".self::DBUTF8escapestring(substr($document->path, 9))."',
    												c_id 		= ".$this->destination_course_id.",
    												comment 	= '".self::DBUTF8escapestring($document->comment)."',
    												title 		= '".self::DBUTF8escapestring($document->title)."' ,
    												filetype	='".$document->file_type."',
    												size		= '".$document->size."',
    												session_id 	= '$my_session_id'";
                                    	Database::query($sql);
    									$document_id = Database::insert_id();
                                        $this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $document_id;
                                        api_item_property_update($course_info, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $document->item_properties[0]['insert_user_id'], $document->item_properties[0]['to_group_id'], $document->item_properties[0]['to_user_id'], null, null, $my_session_id);
                                    } else {
                                        $obj = Database::fetch_object($res);
                                        $sql = "UPDATE ".$table." SET
                                            path 		= '/".self::DBUTF8escapestring(substr($document->path, 9))."',
											c_id 		= ".$this->destination_course_id.",
											comment 	= '".self::DBUTF8escapestring($document->comment)."',
											title 		= '".self::DBUTF8escapestring($document->title)."' ,
											filetype	='".$document->file_type."',
											size		= '".$document->size."',
											session_id 	= '$my_session_id'
                                            WHERE c_id = ".$this->destination_course_id." AND path = '/".self::DBUTF8escapestring(substr($document->path, 9))."'";
                                        Database::query($sql);
                                        api_item_property_update($course_info, TOOL_DOCUMENT, $obj->id, 'default', $document->item_properties[0]['insert_user_id'], $document->item_properties[0]['to_group_id'], $document->item_properties[0]['to_user_id'], null, null, $my_session_id);
                                    }
								}
                                //Replace old course code with the new destination code

                                $file_info = pathinfo($path.$document->path);
                                
                                if (in_array($file_info['extension'], array('html','htm'))) {
                                    $content    = file_get_contents($path.$document->path);
                                    if (UTF8_CONVERT) $content = utf8_encode($content);
                                    $content    = DocumentManager::replace_urls_inside_content_html_from_copy_course($content ,$this->course->code,$this->course->destination_path);
                                    $result     = file_put_contents($path.$document->path,$content);
                                }

								$sql = "SELECT id FROM ".$table." WHERE c_id = ".$this->destination_course_id." AND path='/".substr($document->path, 9)."'";
								$res = Database::query($sql);
								$obj = Database::fetch_object($res);
								$this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $obj->id;
								$sql = "UPDATE ".$table." SET comment = '".self::DBUTF8escapestring($document->comment)."', title='".self::DBUTF8escapestring($document->title)."', size='".$document->size."'
										WHERE c_id = ".$this->destination_course_id." AND id = '".$obj->id."'";
								Database::query($sql);
								break;
							case FILE_SKIP :
								$sql = "SELECT id FROM ".$table." WHERE c_id = ".$this->destination_course_id." AND path='/".self::DBUTF8escapestring(substr($document->path, 9))."'";
								$res = Database::query($sql);
								$obj = Database::fetch_object($res);
								$this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $obj->id;
								break;
							case FILE_RENAME :
								$i = 1;
								$ext = explode('.', basename($document->path));
								if (count($ext) > 1) {
									$ext = array_pop($ext);
									$file_name_no_ext = substr($document->path, 0, - (strlen($ext) + 1));
									$ext = '.'.$ext;
								} else {
									$ext = '';
									$file_name_no_ext = $document->path;
								}
								$new_file_name = $file_name_no_ext.'_'.$i.$ext;
								$file_exists = file_exists($path.$new_file_name);
								while ($file_exists) {
									$i ++;
									$new_file_name = $file_name_no_ext.'_'.$i.$ext;
									$file_exists = file_exists($path.$new_file_name);
								}

								if (!empty($session_id)) {

									$document_path = explode('/',$document->path,3);
									$course_path = $path;								// "/var/www/wiener/courses/"
									$orig_base_folder = $document_path[1];
									$orig_base_path   = $course_path.$document_path[0].'/'.$document_path[1];
									//echo '$orig_base_path'; var_dump($orig_base_path);

									if (is_dir($orig_base_path)) {

										$new_base_foldername = $orig_base_folder;	// e.g: "carpeta1"
										$new_base_path   = $orig_base_path;			// e.g: "/var/www/wiener/courses/CURSO4/document/carpeta1"

										if ($_SESSION['orig_base_foldername'] != $new_base_foldername) {
											unset($_SESSION['new_base_foldername']);
											unset($_SESSION['orig_base_foldername']);
											unset($_SESSION['new_base_path']);
										}

										$folder_exists = file_exists($new_base_path);
										if ($folder_exists) {
											$_SESSION['orig_base_foldername'] = $new_base_foldername; 		// e.g: carpeta1 in session
											$x = '';
											while ($folder_exists) {
												$x = $x + 1;
												$new_base_foldername = $document_path[1].'_'.$x;
												$new_base_path = $orig_base_path.'_'.$x;
												if ($_SESSION['new_base_foldername'] == $new_base_foldername) break;
												$folder_exists = file_exists($new_base_path);
											}
											$_SESSION['new_base_foldername'] = $new_base_foldername;
											$_SESSION['new_base_path'] = $new_base_path;
										}

										if (isset($_SESSION['new_base_foldername']) && isset($_SESSION['new_base_path'])) {
											$new_base_foldername = $_SESSION['new_base_foldername'];
											$new_base_path = $_SESSION['new_base_path'];
										}

										$dest_document_path = $new_base_path.'/'.$document_path[2];		// e.g: "/var/www/wiener/courses/CURSO4/document/carpeta1_1/subcarpeta1/collaborative.png"
										$basedir_dest_path 	= dirname($dest_document_path);				// e.g: "/var/www/wiener/courses/CURSO4/document/carpeta1_1/subcarpeta1"
										//$dest_filename 		= basename($dest_document_path);  				// e.g: "collaborative.png"
										$base_path_document = $course_path.$document_path[0];			// e.g: "/var/www/wiener/courses/CURSO4/document"

										$path_title = '/'.$new_base_foldername.'/'.$document_path[2];

										copy_folder_course_session($basedir_dest_path, $base_path_document, $session_id, $course_info, $document, $this->course_origin_id);

                                        if (file_exists($course_path.$document->path)) {
                                            copy($course_path.$document->path, $dest_document_path);
                                        }

                                        //Replace old course code with the new destination code see BT#1985
                                        if (file_exists($dest_document_path)) {
                                            $file_info = pathinfo($dest_document_path);
                                            if (in_array($file_info['extension'], array('html','htm'))) {
                                                $content    = file_get_contents($dest_document_path);
                                                if (UTF8_CONVERT) $content = utf8_encode($content);
                                                $content    = DocumentManager::replace_urls_inside_content_html_from_copy_course($content, $this->course->code, $this->course->destination_path);
                                                $result     = file_put_contents($dest_document_path,$content);
                                            }
                                        }

										$sql = "INSERT INTO $table SET
												path 		= '$path_title',
												c_id 		= ".$this->destination_course_id.",
												comment 	= '".self::DBUTF8escapestring($document->comment)."',
												title 		= '".self::DBUTF8escapestring(basename($path_title))."' ,
												filetype	='".$document->file_type."',
												size		= '".$document->size."',
												session_id 	= '$my_session_id'";

										Database::query($sql);
										$document_id = Database::insert_id();
                                        $this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $document_id;
                                        api_item_property_update($course_info, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $document->item_properties[0]['insert_user_id'], $document->item_properties[0]['to_group_id'], $document->item_properties[0]['to_user_id'], null, null, $my_session_id);

									} else {

									    if (file_exists($path.$document->path)) {
										      copy($path.$document->path, $path.$new_file_name);
									    }

                                        //Replace old course code with the new destination code see BT#1985
                                        if (file_exists($path.$new_file_name)) {
                                            $file_info = pathinfo($path.$new_file_name);
                                            if (in_array($file_info['extension'], array('html','htm'))) {
                                                $content    = file_get_contents($path.$new_file_name);
                                                if (UTF8_CONVERT) $content = utf8_encode($content);
                                                $content    = DocumentManager::replace_urls_inside_content_html_from_copy_course($content ,$this->course->code,$this->course->destination_path);
                                                $result     = file_put_contents($path.$new_file_name, $content);
                                            }
                                        }

										$sql = "INSERT INTO ".$table." SET
														c_id 		= ".$this->destination_course_id.",
														path 		= '/".self::DBUTF8escapestring(substr($new_file_name, 9))."',
														comment 	= '".self::DBUTF8escapestring($document->comment)."',
														title 		= '".self::DBUTF8escapestring($document->title)."' ,
														filetype	='".$document->file_type."',
														size		= '".$document->size."',
														session_id 	= '$my_session_id'";
										Database::query($sql);

										$document_id = Database::insert_id();
                                        $this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $document_id;
                                        api_item_property_update($course_info, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $document->item_properties[0]['insert_user_id'], $document->item_properties[0]['to_group_id'], $document->item_properties[0]['to_user_id'], null, null, $my_session_id);

									}

								} else {

									copy($this->course->backup_path.'/'.$document->path, $path.$new_file_name);

                                    //Replace old course code with the new destination code see BT#1985
                                    if (file_exists($path.$new_file_name)) {
                                        $file_info = pathinfo($path.$new_file_name);
                                        if (in_array($file_info['extension'], array('html','htm'))) {
                                            $content    = file_get_contents($path.$new_file_name);
                                            if (UTF8_CONVERT) $content = utf8_encode($content);
                                            $content    = DocumentManager::replace_urls_inside_content_html_from_copy_course($content ,$this->course->code,$this->course->destination_path);
                                            $result     = file_put_contents($path.$new_file_name, $content);
                                        }
                                    }
									$sql = "INSERT INTO ".$table." SET
												c_id 		= ".$this->destination_course_id.",
												path 		= '/".self::DBUTF8escapestring(substr($new_file_name, 9))."',
												comment 	= '".self::DBUTF8escapestring($document->comment)."',
												title 		= '".self::DBUTF8escapestring($document->title)."' ,
												filetype	='".$document->file_type."',
												size		= '".$document->size."',
												session_id 	= '$my_session_id'";
									Database::query($sql);

									$document_id = Database::insert_id();
                                    $this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $document_id;
                                    api_item_property_update($course_info, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $document->item_properties[0]['insert_user_id'], $document->item_properties[0]['to_group_id'], $document->item_properties[0]['to_user_id'], null, null, $my_session_id);

								}
								break;

						} // end switch
					} else { // end if file exists

						//make sure the source file actually exists
						if (is_file($this->course->backup_path.'/'.$document->path) && is_readable($this->course->backup_path.'/'.$document->path) && is_dir(dirname($path.$document->path)) && is_writeable(dirname($path.$document->path))) {
						    //echo 'Copying';
							copy($this->course->backup_path.'/'.$document->path, $path.$document->path);

                            //Replace old course code with the new destination code see BT#1985
                            if (file_exists($path.$document->path)) {
                                $file_info = pathinfo($path.$document->path);
                                if (in_array($file_info['extension'], array('html','htm'))) {
                                    $content    = file_get_contents($path.$document->path);
                                    if (UTF8_CONVERT) $content = utf8_encode($content);
                                    $content    = DocumentManager::replace_urls_inside_content_html_from_copy_course($content ,$this->course->code,$this->course->destination_path);
                                    $result     = file_put_contents($path.$document->path, $content);
                                }
                            }

							$sql = "INSERT INTO ".$table." SET
							                 c_id = ".$this->destination_course_id.",
							                 path = '/".substr($document->path, 9)."',
							                 comment = '".self::DBUTF8escapestring($document->comment)."',
							                 title = '".self::DBUTF8escapestring($document->title)."' ,
							                 filetype='".$document->file_type."',
							                 size= '".$document->size."',
							                 session_id = '$my_session_id'";
							Database::query($sql);
							$document_id = Database::insert_id();
							$this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $document_id;
							api_item_property_update($course_info, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $document->item_properties[0]['insert_user_id'], $document->item_properties[0]['to_group_id'], $document->item_properties[0]['to_user_id'], null, null, $my_session_id);
						} else {
						    //echo 'not Copying';
							if(is_file($this->course->backup_path.'/'.$document->path) && is_readable($this->course->backup_path.'/'.$document->path)) {
								error_log('Course copy generated an ignoreable error while trying to copy '.$this->course->backup_path.'/'.$document->path.': file not found');
							}
							if(!is_dir(dirname($path.$document->path))) {
								error_log('Course copy generated an ignoreable error while trying to copy to '.dirname($path.$document->path).': directory not found');
							}
							if(!is_writeable(dirname($path.$document->path))) {
								error_log('Course copy generated an ignoreable error while trying to copy to '.dirname($path.$document->path).': directory not writeable');
							}
						}
					} // end file doesn't exist
				} else {

					/*$sql = "SELECT id FROM ".$table." WHERE path = '/".self::DBUTF8escapestring(substr($document->path, 9))."'";
					$res = Database::query($sql);
					if( Database::num_rows($res)> 0)
					{
						$obj = Database::fetch_object($res);
						$this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $obj->id;
					}
					else
					{
						$sql = "INSERT INTO ".$table." SET path = '/".self::DBUTF8escapestring(substr($document->path, 9))."', comment = '".self::DBUTF8escapestring($document->comment)."', title = '".self::DBUTF8escapestring($document->title)."' ,filetype='".$document->file_type."', size= '".$document->size."'";
						Database::query($sql);
						$this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = Database::insert_id();
					}*/
				} // end folder
			} // end for each

    		// Delete sessions for the copy the new folder in session
    		unset($_SESSION['new_base_foldername']);
    		unset($_SESSION['orig_base_foldername']);
    		unset($_SESSION['new_base_path']);
		}
	}

	/**
	 * Restore scorm documents
	 * TODO @TODO check that the restore function with renaming doesn't break the scorm structure!
	 */
	function restore_scorm_documents() {
		$perm = api_get_permissions_for_new_directories();

		if ($this->course->has_resources(RESOURCE_SCORM)) {
			$resources = $this->course->resources;

			foreach ($resources[RESOURCE_SCORM] as $id => $document) {
				$path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/';

				@mkdir(dirname($path.$document->path), $perm, true);

				if (file_exists($path.$document->path)) {
					switch ($this->file_option) {
						case FILE_OVERWRITE :
							rmdirr($path.$document->path);
							copyDirTo($this->course->backup_path.'/'.$document->path, $path.dirname($document->path), false);

							break;
						case FILE_SKIP :
							break;
						case FILE_RENAME :
							$i = 1;

							$ext = explode('.', basename($document->path));

							if (count($ext) > 1) {
								$ext = array_pop($ext);
								$file_name_no_ext = substr($document->path, 0, - (strlen($ext) + 1));
								$ext = '.'.$ext;
							} else {
								$ext = '';
								$file_name_no_ext = $document->path;
							}

							$new_file_name = $file_name_no_ext.'_'.$i.$ext;
							$file_exists = file_exists($path.$new_file_name);

							while ($file_exists) {
								$i ++;
								$new_file_name = $file_name_no_ext.'_'.$i.$ext;
								$file_exists = file_exists($path.$new_file_name);
							}

							rename($this->course->backup_path.'/'.$document->path,$this->course->backup_path.'/'.$new_file_name);

							copyDirTo($this->course->backup_path.'/'.$new_file_name, $path.dirname($new_file_name), false);

							rename($this->course->backup_path.'/'.$new_file_name,$this->course->backup_path.'/'.$document->path);

							break;
					} // end switch
				} // end if file exists
				else {
					copyDirTo($this->course->backup_path.'/'.$document->path, $path.dirname($document->path), false);
				}
			} // end for each
		}
	}

	/**
	 * Restore forums
	 */
	function restore_forums() {
		if ($this->course->has_resources(RESOURCE_FORUM)) {
			$table_forum = Database::get_course_table(TABLE_FORUM);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_FORUM] as $id => $forum) {
                $params = (array)$forum->obj;
                if ($this->course->resources[RESOURCE_FORUMCATEGORY][$params['forum_category']]->destination_id == -1) {
                    $cat_id = $this->restore_forum_category($params['forum_category']);
                } else {
                    $cat_id = $this->course->resources[RESOURCE_FORUMCATEGORY][$params['forum_category']]->destination_id;
                }
                
                self::DBUTF8_array($params);
                $params['c_id'] = $this->destination_course_id;
                $params['forum_category'] = $cat_id;    
                unset($params['forum_id']);
                
                $params['forum_comment'] = DocumentManager::replace_urls_inside_content_html_from_copy_course($params['forum_comment'], $this->course->code, $this->course->destination_path);        
                
                if (!empty($params['forum_image'])) {
                    $original_forum_image = $this->course->path.'upload/forum/images/'.$params['forum_image'];                    
                    if (file_exists($original_forum_image)) {
                        $new_forum_image = api_get_path(SYS_COURSE_PATH).$this->destination_course_info['path'].'/upload/forum/images/'.$params['forum_image'];                        
                        @copy($original_forum_image, $new_forum_image);
                    }
                }
                
                $new_id = Database::insert($table_forum, $params);
			
				$this->course->resources[RESOURCE_FORUM][$id]->destination_id = $new_id;
                
				$forum_topics = 0;
				if (is_array($this->course->resources[RESOURCE_FORUMTOPIC])) {                    
					foreach ($this->course->resources[RESOURCE_FORUMTOPIC] as $topic_id => $topic) {                        
						if ($topic->obj->forum_id == $id) {
							$this->restore_topic($topic_id, $new_id);
							$forum_topics ++;
						}
					}
				}
				if ($forum_topics > 0) {					
					$sql = "UPDATE ".$table_forum." SET forum_threads = ".$forum_topics." 
                            WHERE c_id = {$this->destination_course_id} AND forum_id = ".(int)$new_id;
					Database::query($sql);
				}
			}
		}
	}
    
	/**
	 * Restore forum-categories
	 */    
	function restore_forum_category($my_id = null) {
		$forum_cat_table = Database :: get_course_table(TABLE_FORUM_CATEGORY);
		$resources = $this->course->resources;
		//$forum_cat = $resources[RESOURCE_FORUMCATEGORY][$id];
        foreach ($resources[RESOURCE_FORUMCATEGORY] as $id => $forum_cat) {
            if (!empty($my_id)) {
                if ($my_id != $id) {
                    continue;
                }
            }
            if ($forum_cat && !$forum_cat->is_restored()) {
                $title = $forum_cat->obj->cat_title;
                if (!empty($title)) {
                    if (!preg_match('/.*\((.+)\)$/', $title, $matches)) {
                        // This is for avoiding repetitive adding of training code after several backup/restore cycles.
                        if ($matches[1] != $this->course->code) {
                            $title = $title.' ('.$this->course->code.')';
                        }
                    }
                }
                $params = (array) $forum_cat->obj;
                $params['c_id'] = $this->destination_course_id;                
                $params['cat_comment']    = DocumentManager::replace_urls_inside_content_html_from_copy_course($params['cat_comment'], $this->course->code, $this->course->destination_path);
                unset($params['cat_id']);
                self::DBUTF8_array($params);     
                $new_id = Database::insert($forum_cat_table, $params);                
                $this->course->resources[RESOURCE_FORUMCATEGORY][$id]->destination_id = $new_id;                
                if (!empty($my_id)) {
                    return $new_id;
                }
            }            
        }
	}
    
	/**
	 * Restore a forum-topic
	 */
	function restore_topic($thread_id, $forum_id) {
		$table = Database :: get_course_table(TABLE_FORUM_THREAD);        
		$topic = $this->course->resources[RESOURCE_FORUMTOPIC][$thread_id];
		                 
        $params = (array)$topic->obj;
        self::DBUTF8_array($params);        
        $params['c_id']     = $this->destination_course_id;
        $params['forum_id'] = $forum_id;
        $params['thread_poster_id'] = $this->first_teacher_id;
        $params['thread_date'] = api_get_utc_datetime();
        $params['thread_close_date']  = '0000-00-00 00:00:00';
        $params['thread_last_post'] = 0;
        $params['thread_replies'] = 0;
        $params['thread_views'] = 0;
        unset($params['thread_id']);
        
        $new_id = Database::insert($table, $params);        
        api_item_property_update($this->destination_course_info, TOOL_FORUM_THREAD, $new_id, 'ThreadAdded', api_get_user_id(), 0, 0, null, null);
        
		$this->course->resources[RESOURCE_FORUMTOPIC][$thread_id]->destination_id = $new_id;
        
		$topic_replies = -1;        
        
		foreach ($this->course->resources[RESOURCE_FORUMPOST] as $post_id => $post){    
			if ($post->obj->thread_id == $thread_id) {
				$topic_replies++;
				$this->restore_post($post_id, $new_id, $forum_id);
			}
		}       
		return $new_id;
	}
    
	/**
	 * Restore a forum-post
	 * @TODO Restore tree-structure of posts. For example: attachments to posts.
	 */
	function restore_post($id, $topic_id, $forum_id) {
		$table_post = Database :: get_course_table(TABLE_FORUM_POST);
		$post = $this->course->resources[RESOURCE_FORUMPOST][$id];        
        $params = (array) $post->obj;
        $params['c_id'] = $this->destination_course_id;
        $params['forum_id'] = $forum_id;
        $params['thread_id'] = $topic_id;
        $params['poster_id'] = $this->first_teacher_id;
        $params['post_date'] = api_get_utc_datetime();
        unset($params['post_id']);        
        $params['post_text']    = DocumentManager::replace_urls_inside_content_html_from_copy_course($params['post_text'], $this->course->code, $this->course->destination_path);        
        $new_id = Database::insert($table_post, $params);
        api_item_property_update($this->destination_course_info, TOOL_FORUM_POST, $new_id, 'PostAdded', api_get_user_id(), 0, 0, null, null);        
		$this->course->resources[RESOURCE_FORUMPOST][$id]->destination_id = $new_id;
		return $new_id;
	}
    
	/**
	 * Restore links
	 */
	function restore_links($session_id = 0) {
		if ($this->course->has_resources(RESOURCE_LINK)) {
			$link_table = Database :: get_course_table(TABLE_LINK);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_LINK] as $id => $link) {
				$cat_id = $this->restore_link_category($link->category_id,$session_id);
				$sql = "SELECT MAX(display_order) FROM  $link_table WHERE c_id = ".$this->destination_course_id."  AND category_id='" . self::DBUTF8escapestring($cat_id). "'";
				$result = Database::query($sql);
    			list($max_order) = Database::fetch_array($result);

    			$condition_session = "";
    			if (!empty($session_id)) {
    				$condition_session = " , session_id = '$session_id' ";
    			}

				$sql = "INSERT INTO ".$link_table." SET
				            c_id            = ".$this->destination_course_id." ,
				            url             = '".self::DBUTF8escapestring($link->url)."',
				            title           = '".self::DBUTF8escapestring($link->title)."',
				            description     = '".self::DBUTF8escapestring($link->description)."',
				            category_id     = '".$cat_id."',
				            on_homepage     = '".$link->on_homepage."',
				            display_order   = '".($max_order+1)."' $condition_session";

				Database::query($sql);
				$this->course->resources[RESOURCE_LINK][$id]->destination_id = Database::insert_id();
			}
		}
	}
    
	/**
	 * Restore tool intro
	 */
	function restore_tool_intro() {
		if ($this->course->has_resources(RESOURCE_TOOL_INTRO)) {
			$tool_intro_table = Database :: get_course_table(TABLE_TOOL_INTRO);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_TOOL_INTRO] as $id => $tool_intro) {
				$sql = "DELETE FROM ".$tool_intro_table." WHERE c_id = ".$this->destination_course_id."  AND id='".self::DBUTF8escapestring($tool_intro->id)."'";
				Database::query($sql);
                $tool_intro->intro_text = DocumentManager::replace_urls_inside_content_html_from_copy_course($tool_intro->intro_text,$this->course->code,$this->course->destination_path);
				$sql = "INSERT INTO ".$tool_intro_table." SET c_id = ".$this->destination_course_id." , id='".self::DBUTF8escapestring($tool_intro->id)."', intro_text = '".self::DBUTF8escapestring($tool_intro->intro_text)."'";
				Database::query($sql);

				$this->course->resources[RESOURCE_TOOL_INTRO][$id]->destination_id = Database::insert_id();
			}
		}
	}

	/**
	 * Restore a link-category
	 */
	function restore_link_category($id,$session_id = 0) {
		$condition_session = "";
		if (!empty($session_id)) {
			$condition_session = " , session_id = '$session_id' ";
		}

		if ($id == 0)
			return 0;
		$link_cat_table = Database :: get_course_table(TABLE_LINK_CATEGORY);
		$resources = $this->course->resources;
		$link_cat = $resources[RESOURCE_LINKCATEGORY][$id];
		if (is_object($link_cat) && !$link_cat->is_restored()) {
			$sql = "SELECT MAX(display_order) FROM  $link_cat_table WHERE c_id = ".$this->destination_course_id." ";
			$result=Database::query($sql);
			list($orderMax)=Database::fetch_array($result,'NUM');
			$display_order=$orderMax+1;
			$sql = "INSERT INTO ".$link_cat_table." SET c_id = ".$this->destination_course_id." , category_title = '".self::DBUTF8escapestring($link_cat->title)."', description='".self::DBUTF8escapestring($link_cat->description)."', display_order='".$display_order."' $condition_session ";
			Database::query($sql);
			$new_id = Database::insert_id();
			$this->course->resources[RESOURCE_LINKCATEGORY][$id]->destination_id = $new_id;
			return $new_id;
		}
		return $this->course->resources[RESOURCE_LINKCATEGORY][$id]->destination_id;
	}

	/**
	 * Restore events
	 */
	function restore_events() {
		if ($this->course->has_resources(RESOURCE_EVENT)) {
			$table = Database :: get_course_table(TABLE_AGENDA);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_EVENT] as $id => $event) {
				// check resources inside html from fckeditor tool and copy correct urls into recipient course
				$event->content = DocumentManager::replace_urls_inside_content_html_from_copy_course($event->content, $this->course->code, $this->course->destination_path);

				$sql = "INSERT INTO ".$table." SET
        				c_id = ".$this->destination_course_id." ,
        				title = '".self::DBUTF8escapestring($event->title)."',
        				content = '".self::DBUTF8escapestring($event->content)."',
                        all_day = '".$event->all_day."',
        				start_date = '".$event->start_date."',
        				end_date = '".$event->end_date."'";
                
				Database::query($sql);
				$new_event_id = Database::insert_id();
				$this->course->resources[RESOURCE_EVENT][$id]->destination_id = $new_event_id;

				//Copy event attachment

				$origin_path = $this->course->backup_path.'/upload/calendar/';
				$destination_path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/upload/calendar/';

				if (!empty($this->course->orig)) {

					$table_attachment = Database :: get_course_table(TABLE_AGENDA_ATTACHMENT);
					$sql = 'SELECT path, comment, size, filename FROM '.$table_attachment.' WHERE c_id = '.$this->destination_course_id.' AND agenda_id = '.$id;
					$attachment_event = Database::query($sql);
					$attachment_event = Database::fetch_object($attachment_event);

					if (file_exists($origin_path.$attachment_event->path) && !is_dir($origin_path.$attachment_event->path) ) {
						$new_filename = uniqid(''); //ass seen in the add_agenda_attachment_file() function in agenda.inc.php
						$copy_result = copy($origin_path.$attachment_event->path, $destination_path.$new_filename);
						//$copy_result = true;
						if ($copy_result) {
							$table_attachment = Database :: get_course_table(TABLE_AGENDA_ATTACHMENT);
							$sql = "INSERT INTO ".$table_attachment." SET c_id = ".$this->destination_course_id." , path = '".self::DBUTF8escapestring($new_filename)."', comment = '".self::DBUTF8escapestring($attachment_event->comment)."', size = '".$attachment_event->size."', filename = '".$attachment_event->filename."' , agenda_id = '".$new_event_id."' ";
							Database::query($sql);
						}
					}
				} else {
					// get the info of the file
					if(!empty($event->attachment_path) && is_file($origin_path.$event->attachment_path) && is_readable($origin_path.$event->attachment_path)) {
						$new_filename = uniqid(''); //ass seen in the add_agenda_attachment_file() function in agenda.inc.php
						$copy_result = copy($origin_path.$event->attachment_path, $destination_path.$new_filename);
						if ($copy_result) {
							$table_attachment = Database :: get_course_table(TABLE_AGENDA_ATTACHMENT);
							$sql = "INSERT INTO ".$table_attachment." SET c_id = ".$this->destination_course_id." , path = '".self::DBUTF8escapestring($new_filename)."', comment = '".self::DBUTF8escapestring($event->attachment_comment)."', size = '".$event->attachment_size."', filename = '".$event->attachment_filename."' , agenda_id = '".$new_event_id."' ";
							Database::query($sql);
						}
					}
				}
			}
		}
	}
	/**
	 * Restore course-description
	 */
	function restore_course_descriptions($session_id = 0) {
		if ($this->course->has_resources(RESOURCE_COURSEDESCRIPTION)) {
			$table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_COURSEDESCRIPTION] as $id => $cd) {
				if (isset($_POST['destination_course'])) {
					$course_destination=Security::remove_XSS($_POST['destination_course']);
					$course_destination=api_get_course_info($course_destination);
					$course_destination=$course_destination['path'];
				} else {
					$course_destination=$this->course->destination_path;
				}

				// check resources inside html from fckeditor tool and copy correct urls into recipient course
				$description_content = DocumentManager::replace_urls_inside_content_html_from_copy_course($cd->content, $this->course->code, $this->course->destination_path);

				$condition_session = "";
				if (!empty($session_id)) {
					$session_id = intval($session_id);
					$condition_session = " , session_id = '$session_id' ";
				}
				$sql = "INSERT INTO ".$table." SET c_id = ".$this->destination_course_id." , description_type = '".self::DBUTF8escapestring($cd->description_type)."',title = '".self::DBUTF8escapestring($cd->title)."', content = '".self::DBUTF8escapestring($description_content)."' $condition_session";
				Database::query($sql);
				$this->course->resources[RESOURCE_COURSEDESCRIPTION][$id]->destination_id = Database::insert_id();
			}
		}
	}
	/**
	 * Restore announcements
	 */
	function restore_announcements() {
		if ($this->course->has_resources(RESOURCE_ANNOUNCEMENT)) {
			$table = Database :: get_course_table(TABLE_ANNOUNCEMENT);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_ANNOUNCEMENT] as $id => $announcement) {

				// check resources inside html from fckeditor tool and copy correct urls into recipient course
				$announcement->content = DocumentManager::replace_urls_inside_content_html_from_copy_course($announcement->content, $this->course->code, $this->course->destination_path);

				$sql = "INSERT INTO ".$table." " ."SET
							c_id = ".$this->destination_course_id." ,
							title = '".self::DBUTF8escapestring($announcement->title)."'," .
							"content = '".self::DBUTF8escapestring($announcement->content)."', " .
							"end_date = '".$announcement->date."', " .
							"display_order = '".$announcement->display_order."', " .
							"email_sent = '".$announcement->email_sent."'";
				Database::query($sql);
				$new_announcement_id = Database::insert_id();
				$this->course->resources[RESOURCE_ANNOUNCEMENT][$id]->destination_id = $new_announcement_id;


				$origin_path = $this->course->backup_path.'/upload/announcements/';
				$destination_path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/upload/announcements/';

				//Copy announcement attachment file
				if (!empty($this->course->orig)) {

					$table_attachment = Database :: get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);

					$sql = 'SELECT path, comment, size, filename FROM '.$table_attachment.' WHERE c_id = '.$this->destination_course_id.' AND announcement_id = '.$id;
					$attachment_event = Database::query($sql);
					$attachment_event = Database::fetch_object($attachment_event);

					if (file_exists($origin_path.$attachment_event->path) && !is_dir($origin_path.$attachment_event->path) ) {
						$new_filename = uniqid(''); //ass seen in the add_agenda_attachment_file() function in agenda.inc.php
						$copy_result = copy($origin_path.$attachment_event->path, $destination_path.$new_filename);
						//error_log($destination_path.$new_filename); error_log($copy_result);
						//$copy_result = true;
						if ($copy_result) {
							$table_attachment = Database :: get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
							$sql = "INSERT INTO ".$table_attachment." SET c_id = ".$this->destination_course_id." , path = '".self::DBUTF8escapestring($new_filename)."', comment = '".self::DBUTF8escapestring($attachment_event->comment)."', size = '".$attachment_event->size."', filename = '".$attachment_event->filename."' , announcement_id = '".$new_announcement_id."' ";
							Database::query($sql);
						}
					}
				} else {
					// get the info of the file
					if(!empty($announcement->attachment_path) && is_file($origin_path.$announcement->attachment_path) && is_readable($origin_path.$announcement->attachment_path)) {
						$new_filename = uniqid(''); //ass seen in the add_agenda_attachment_file() function in agenda.inc.php
						$copy_result = copy($origin_path.$announcement->attachment_path, $destination_path.$new_filename);

						if ($copy_result) {
							$table_attachment = Database :: get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
							$sql = "INSERT INTO ".$table_attachment." SET c_id = ".$this->destination_course_id." , path = '".self::DBUTF8escapestring($new_filename)."', comment = '".self::DBUTF8escapestring($announcement->attachment_comment)."', size = '".$announcement->attachment_size."', filename = '".$announcement->attachment_filename."' , announcement_id = '".$new_announcement_id."' ";
							Database::query($sql);
						}
					}
				}
			}
		}
	}

	/**
	 * Restore Quiz
	 */
	function restore_quizzes($session_id = 0, $respect_base_content = false) {
		if ($this->course->has_resources(RESOURCE_QUIZ)) {
			$table_qui = Database :: get_course_table(TABLE_QUIZ_TEST);
			$table_rel = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
			$table_doc = Database :: get_course_table(TABLE_DOCUMENT);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_QUIZ] as $id => $quiz) {
				$doc = '';
				if (strlen($quiz->media) > 0) {
					if ($this->course->resources[RESOURCE_DOCUMENT][$quiz->media]->is_restored()) {
						$sql = "SELECT path FROM ".$table_doc." WHERE c_id = ".$this->destination_course_id."  AND id = ".$resources[RESOURCE_DOCUMENT][$quiz->media]->destination_id;
						$doc = Database::query($sql);
						$doc = Database::fetch_object($doc);
						$doc = str_replace('/audio/', '', $doc->path);
					}
				}
				if ($id != -1) {
                    if ($respect_base_content) {
                        $my_session_id = $quiz->session_id;
                        if (!empty($quiz->session_id)) {
                            $my_session_id = $session_id;
                        }
                        $condition_session = " , session_id = '$my_session_id' ";
                    } else {
    					$condition_session = "";
        				if (!empty($session_id)) {
        					$session_id = intval($session_id);
        					$condition_session = " , session_id = '$session_id' ";
    				    }
                    }

					// check resources inside html from fckeditor tool and copy correct urls into recipient course
					$quiz->description = DocumentManager::replace_urls_inside_content_html_from_copy_course($quiz->description, $this->course->code, $this->course->destination_path);

					global $_custom;
					if (isset($_custom['exercises_clean_dates_when_restoring']) && $_custom['exercises_clean_dates_when_restoring']) {
						$quiz->start_time = null;
						$quiz->end_time   = null;
					}


					// Normal tests are stored in the database.
					$sql = "INSERT INTO ".$table_qui." SET
						c_id = ".$this->destination_course_id." ,
						title = '".self::DBUTF8escapestring($quiz->title).
						"', description = '".self::DBUTF8escapestring($quiz->description).
						"', type = '".$quiz->quiz_type.
						"', random = '".$quiz->random.
						"', active = '".$quiz->active.
						"', sound = '".self::DBUTF8escapestring($doc).
						"', max_attempt = ".(int)$quiz->attempts.
						",  results_disabled = ".(int)$quiz->results_disabled.
						",  access_condition = '".$quiz->access_condition.
						"', start_time = '".$quiz->start_time.
						"', end_time = '".$quiz->end_time.
						"', feedback_type = ".(int)$quiz->feedback_type.
						", random_answers = ".(int)$quiz->random_answers.
						", expired_time = ".(int)$quiz->expired_time.
						$condition_session;
					Database::query($sql);
					$new_id = Database::insert_id();
				} else {
					// $id = -1 identifies the fictionary test for collecting orphan questions. We do not store it in the database.
					$new_id = -1;
				}
                
				$this->course->resources[RESOURCE_QUIZ][$id]->destination_id = $new_id;
				$order = 0;                
				foreach ($quiz->question_ids as $index => $question_id) {
					$qid = $this->restore_quiz_question($question_id);
					$question_order = $quiz->question_orders[$index] ? $quiz->question_orders[$index] : ++$order;					
					$sql = "INSERT IGNORE INTO ".$table_rel." SET c_id = ".$this->destination_course_id." , question_id = ".$qid.", exercice_id = ".$new_id.", question_order = ".$question_order;
					Database::query($sql);
				}
			}
		}
	}

	/**
	 * Restore quiz-questions
	 */
	function restore_quiz_question($id) {
		$resources = $this->course->resources;        
		$question = $resources[RESOURCE_QUIZQUESTION][$id];   
                
		$new_id = 0;

		if (is_object($question)) {
			if ($question->is_restored()) {
				return $question->destination_id;
			}
			$table_que 		= Database::get_course_table(TABLE_QUIZ_QUESTION);
			$table_ans 		= Database::get_course_table(TABLE_QUIZ_ANSWER);
            $table_options	= Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);

			// check resources inside html from fckeditor tool and copy correct urls into recipient course
			$question->description = DocumentManager::replace_urls_inside_content_html_from_copy_course($question->description, $this->course->code, $this->course->destination_path);

			$sql = "INSERT INTO ".$table_que." SET
                    c_id = ".$this->destination_course_id." ,
                    question = '".self::DBUTF8escapestring($question->question)."',
                    description = '".self::DBUTF8escapestring($question->description)."',
                    ponderation = '".self::DBUTF8escapestring($question->ponderation)."',
                    position = '".self::DBUTF8escapestring($question->position)."',
                    type='".self::DBUTF8escapestring($question->quiz_type)."',
                    picture='".self::DBUTF8escapestring($question->picture)."',
                    level='".self::DBUTF8escapestring($question->level)."',
                    extra='".self::DBUTF8escapestring($question->extra)."'";
            
			Database::query($sql);
			$new_id = Database::insert_id();        
            
			if ($question->quiz_type == MATCHING) {
                $t = array();
                foreach ($question->answers as $index => $answer) {
                    $t[$answer['position']] = $answer;
                }
                foreach ($t as $index => $answer) {
                    $sql = "INSERT INTO ".$table_ans." SET c_id = ".$this->destination_course_id." , id= '".$index."',question_id = '".$new_id."', answer = '".self::DBUTF8escapestring($answer['answer'])."', correct = '".$answer['correct']."', comment = '".self::DBUTF8escapestring($answer['comment'])."', ponderation='".$answer['ponderation']."', position = '".$answer['position']."', hotspot_coordinates = '".$answer['hotspot_coordinates']."', hotspot_type = '".$answer['hotspot_type']."'";
					Database::query($sql);
				}
			} else {
				foreach ($question->answers as $index => $answer) {

					// check resources inside html from fckeditor tool and copy correct urls into recipient course
					$answer['answer']  = DocumentManager::replace_urls_inside_content_html_from_copy_course($answer['answer'], $this->course->code, $this->course->destination_path);
					$answer['comment'] = DocumentManager::replace_urls_inside_content_html_from_copy_course($answer['comment'], $this->course->code, $this->course->destination_path);

					$sql = "INSERT INTO ".$table_ans." SET c_id = ".$this->destination_course_id." , id= '". ($index +1)."',question_id = '".$new_id."', answer = '".self::DBUTF8escapestring($answer['answer'])."', correct = '".$answer['correct']."', comment = '".self::DBUTF8escapestring($answer['comment'])."', ponderation='".$answer['ponderation']."', position = '".$answer['position']."', hotspot_coordinates = '".$answer['hotspot_coordinates']."', hotspot_type = '".$answer['hotspot_type']."'";
					Database::query($sql);
				}
			}
            //@todo check this
            $course_id = api_get_course_int_id();

            //Moving quiz_question_options
            if ($question->quiz_type == MULTIPLE_ANSWER_TRUE_FALSE) {
                $question_option_list = Question::readQuestionOption($id, $course_id);
                $old_option_ids = array();
                foreach ($question_option_list  as $item) {
                    $old_id = $item['id'];
                    unset($item['id']);
                    $item['question_id'] = $new_id;
                    $item['c_id'] = $this->destination_course_id;
                    $question_option_id = Database::insert($table_options, $item);
                    $old_option_ids[$old_id] = $question_option_id;
                }
                $new_answers = Database::select('id, correct', $table_ans, array('WHERE' => array('question_id = ? AND c_id = ? '=>array($new_id, $this->destination_course_id))));
                foreach ($new_answers as $answer_item) {
                    $params['correct'] = $old_option_ids[$answer_item['correct']];
                    $question_option_id = Database::update($table_ans, $params, array('id = ? AND c_id = ? '=> array($answer_item['id'], $this->destination_course_id)));
                }
            }
			$this->course->resources[RESOURCE_QUIZQUESTION][$id]->destination_id = $new_id;
		}
		return $new_id;
	}
	/**
	 * Restore surveys
	 */
	function restore_surveys() {
		if ($this->course->has_resources(RESOURCE_SURVEY)) {
			$table_sur = Database :: get_course_table(TABLE_SURVEY);
			$table_que = Database :: get_course_table(TABLE_SURVEY_QUESTION);
			$table_ans = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_SURVEY] as $id => $survey) {

				$sql_check = 'SELECT survey_id FROM '.$table_sur.'
							WHERE 	c_id = '.$this->destination_course_id.' AND
									code = "'.self::DBUTF8escapestring($survey->code).'" AND
									lang = "'.self::DBUTF8escapestring($survey->lang).'" ';

				$result_check = Database::query($sql_check);

				// check resources inside html from fckeditor tool and copy correct urls into recipient course
				$survey->title 		  = DocumentManager::replace_urls_inside_content_html_from_copy_course($survey->title, $this->course->code, $this->course->destination_path);
				$survey->subtitle 	  = DocumentManager::replace_urls_inside_content_html_from_copy_course($survey->subtitle, $this->course->code, $this->course->destination_path);
				$survey->intro 		  = DocumentManager::replace_urls_inside_content_html_from_copy_course($survey->intro, $this->course->code, $this->course->destination_path);
				$survey->surveythanks = DocumentManager::replace_urls_inside_content_html_from_copy_course($survey->surveythanks, $this->course->code, $this->course->destination_path);

				$doc = '';
				$sql = "INSERT INTO ".$table_sur." " ."SET
						c_id = ".$this->destination_course_id." ,
						code = '".self::DBUTF8escapestring($survey->code)."', " .
						"title = '".self::DBUTF8escapestring($survey->title)."', " .
						"subtitle = '".self::DBUTF8escapestring($survey->subtitle)."', " .
						"author = '".self::DBUTF8escapestring($survey->author)."', " .
						"lang = '".self::DBUTF8escapestring($survey->lang)."', " .
						"avail_from = '".self::DBUTF8escapestring($survey->avail_from)."', " .
						"avail_till = '".self::DBUTF8escapestring($survey->avail_till)."', " .
						"is_shared = '".self::DBUTF8escapestring($survey->is_shared)."', " .
						"template = '".self::DBUTF8escapestring($survey->template)."', " .
						"intro = '".self::DBUTF8escapestring($survey->intro)."', " .
						"surveythanks = '".self::DBUTF8escapestring($survey->surveythanks)."', " .
						"creation_date = '".self::DBUTF8escapestring($survey->creation_date)."', " .
						"invited = '0', " .
						"answered = '0', " .
						"invite_mail = '".self::DBUTF8escapestring($survey->invite_mail)."', " .
						"reminder_mail = '".self::DBUTF8escapestring($survey->reminder_mail)."'";

				//An existing survey exists with the same code and the same language
				if (Database::num_rows($result_check) == 1) {
					switch ($this->file_option) {
						case FILE_SKIP:
							//Do nothing
							break;
						case FILE_RENAME:
							$survey_code = $survey->code.'_';
							$i=1;
							$temp_survey_code = $survey_code.$i;
							while (!$this->is_survey_code_available($temp_survey_code)) {
								$temp_survey_code = $survey_code.++$i;
							}
							$survey_code = $temp_survey_code;

							$sql = "INSERT INTO ".$table_sur." " ."SET
									c_id = ".$this->destination_course_id." ,
									code = '".self::DBUTF8escapestring($survey_code)."', " .
									"title = '".self::DBUTF8escapestring($survey->title)."', " .
									"subtitle = '".self::DBUTF8escapestring($survey->subtitle)."', " .
									"author = '".self::DBUTF8escapestring($survey->author)."', " .
									"lang = '".self::DBUTF8escapestring($survey->lang)."', " .
									"avail_from = '".self::DBUTF8escapestring($survey->avail_from)."', " .
									"avail_till = '".self::DBUTF8escapestring($survey->avail_till)."', " .
									"is_shared = '".self::DBUTF8escapestring($survey->is_shared)."', " .
									"template = '".self::DBUTF8escapestring($survey->template)."', " .
									"intro = '".self::DBUTF8escapestring($survey->intro)."', " .
									"surveythanks = '".self::DBUTF8escapestring($survey->surveythanks)."', " .
									"creation_date = '".self::DBUTF8escapestring($survey->creation_date)."', " .
									"invited = '0', " .
									"answered = '0', " .
									"invite_mail = '".self::DBUTF8escapestring($survey->invite_mail)."', " .
									"reminder_mail = '".self::DBUTF8escapestring($survey->reminder_mail)."'";

							//Insert the new source survey
							Database::query($sql);

							$new_id = Database::insert_id();
							$this->course->resources[RESOURCE_SURVEY][$id]->destination_id = $new_id;
							foreach ($survey->question_ids as $index => $question_id) {
								$qid = $this->restore_survey_question($question_id, $new_id);
								$sql = "UPDATE ".$table_que." SET survey_id = ".$new_id." WHERE c_id = ".$this->destination_course_id." AND question_id = $qid";
								Database::query($sql);
								$sql = "UPDATE ".$table_ans." SET survey_id = ".$new_id." WHERE  c_id = ".$this->destination_course_id." AND  question_id = $qid";
								Database::query($sql);
							}

							break;

						case FILE_OVERWRITE:
							// Delete the existing survey with the same code and language and import the one of the source course
							// getting the information of the survey (used for when the survey is shared)
							require_once(api_get_path(SYS_CODE_PATH).'survey/survey.lib.php');

							$sql_select_existing_survey = "SELECT * FROM $table_sur WHERE c_id = ".$this->destination_course_id." AND survey_id='".self::DBUTF8escapestring(Database::result($result_check,0,0))."'";
							$result = Database::query($sql_select_existing_survey);
							$survey_data = Database::fetch_array($result,'ASSOC');

							// if the survey is shared => also delete the shared content
							if (is_numeric($survey_data['survey_share'])) {
								survey_manager::delete_survey($survey_data['survey_share'], true,$this->destination_course_id);
							}
							$return = survey_manager :: delete_survey($survey_data['survey_id'],false,$this->destination_course_id);

							//Insert the new source survey
							Database::query($sql);

							$new_id = Database::insert_id();
							$this->course->resources[RESOURCE_SURVEY][$id]->destination_id = $new_id;
							foreach ($survey->question_ids as $index => $question_id) {
								$qid = $this->restore_survey_question($question_id, $new_id);
								$sql = "UPDATE $table_que SET survey_id = $new_id WHERE c_id = ".$this->destination_course_id." AND question_id = $qid";
								Database::query($sql);
								$sql = "UPDATE $table_ans SET survey_id = $new_id WHERE c_id = ".$this->destination_course_id." AND question_id = $qid";
								Database::query($sql);
							}
							break;

						default:
							break;
					}
				}
				//No existing survey with the same language and the same code, we just copy the survey
				else {
					Database::query($sql);
					$new_id = Database::insert_id();
					$this->course->resources[RESOURCE_SURVEY][$id]->destination_id = $new_id;
					foreach ($survey->question_ids as $index => $question_id) {
						$qid = $this->restore_survey_question($question_id, $new_id);
						$sql = "UPDATE $table_que SET survey_id = $new_id WHERE c_id = ".$this->destination_course_id." AND question_id = $qid";
						Database::query($sql);
						$sql = "UPDATE $table_ans SET survey_id = $new_id WHERE c_id = ".$this->destination_course_id." AND question_id = $qid";
						Database::query($sql);
					}
				}

			}
		}
	}

	/**
	 * Check availability of a survey code
	 */
	function is_survey_code_available($survey_code)	{
		$table_sur = Database :: get_course_table(TABLE_SURVEY);
		$sql = "SELECT * FROM $table_sur WHERE c_id = ".$this->destination_course_id." AND code='".self::DBUTF8escapestring($survey_code)."'";
		$result = Database::query($sql);
		if(Database::num_rows($result) > 0) return false; else return true;

	}

	/**
	 * Restore survey-questions
	 */
	function restore_survey_question($id, $survey_id) {
		$resources = $this->course->resources;
		$question = $resources[RESOURCE_SURVEYQUESTION][$id];

		$new_id=0;

		if (is_object($question)) {
			if ($question->is_restored()) {
				return $question->destination_id;
			}
			$table_que = Database :: get_course_table(TABLE_SURVEY_QUESTION);
			$table_ans = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);

			// check resources inside html from fckeditor tool and copy correct urls into recipient course
			$question->survey_question = DocumentManager::replace_urls_inside_content_html_from_copy_course($question->survey_question, $this->course->code, $this->course->destination_path);

			$sql = "INSERT INTO ".$table_que." SET
					c_id = ".$this->destination_course_id." ,
					survey_id = 		'".self::DBUTF8escapestring($survey_id)."', " .
					"survey_question = 		'".self::DBUTF8escapestring($question->survey_question)."', " .
					"survey_question_comment = '".self::DBUTF8escapestring($question->survey_question_comment)."', " .
					"type = 				'".self::DBUTF8escapestring($question->survey_question_type)."', " .
					"display = 				'".self::DBUTF8escapestring($question->display)."', " .
					"sort = 				'".self::DBUTF8escapestring($question->sort)."', " .
					"shared_question_id = 	'".self::DBUTF8escapestring($question->shared_question_id)."', " .
					"max_value = 			'".self::DBUTF8escapestring($question->max_value)."' ";

			Database::query($sql);

			$new_id = Database::insert_id();
			foreach ($question->answers as $index => $answer) {

				// check resources inside html from fckeditor tool and copy correct urls into recipient course
				$answer['option_text'] = DocumentManager::replace_urls_inside_content_html_from_copy_course($answer['option_text'], $this->course->code, $this->course->destination_path);

				$sql = "INSERT INTO ".$table_ans." SET
						c_id = ".$this->destination_course_id." ,
						question_id = '".self::DBUTF8escapestring($new_id)."', " .
						"option_text = '".self::DBUTF8escapestring($answer['option_text'])."', " .
						"sort 		 = '".self::DBUTF8escapestring($answer['sort'])."', " .
						"survey_id 	 = '".self::DBUTF8escapestring($survey_id)."'";

				Database::query($sql);
			}
			$this->course->resources[RESOURCE_SURVEYQUESTION][$id]->destination_id = $new_id;
		}

		return $new_id;
	}

	/**
	 * Restore learnpaths
	 */
	function restore_learnpaths($session_id = 0, $respect_base_content = false) {
		if ($this->course->has_resources(RESOURCE_LEARNPATH)) {
			$table_main 	= Database::get_course_table(TABLE_LP_MAIN);
			$table_item 	= Database::get_course_table(TABLE_LP_ITEM);
			$table_tool 	= Database::get_course_table(TABLE_TOOL_LIST);

			$resources = $this->course->resources;

			$origin_path = $this->course->backup_path.'/upload/learning_path/images/';
			$destination_path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/upload/learning_path/images/';

			foreach ($resources[RESOURCE_LEARNPATH] as $id => $lp) {

				$condition_session = "";
				if (!empty($session_id)) {
                    if ($respect_base_content) {
                        $my_session_id = $lp->session_id;
                        if (!empty($lp->session_id)) {
                            $my_session_id = $session_id;
                        }
                        $condition_session = " , session_id = '$my_session_id' ";
                    } else {
                        $session_id = intval($session_id);
                        $condition_session = " , session_id = '$session_id' ";
                    }
				}

				//Adding the author's image
				if (!empty($lp->preview_image)) {
					$new_filename = uniqid('').$new_filename.substr($lp->preview_image,strlen($lp->preview_image)-7, strlen($lp->preview_image));
					if (file_exists($origin_path.$lp->preview_image) && !is_dir($origin_path.$lp->preview_image)) {
						$copy_result = copy($origin_path.$lp->preview_image, $destination_path.$new_filename);
						//$copy_result = true;
						if ($copy_result) {
							$lp->preview_image = $new_filename;
						} else {
							$lp->preview_image ='';
						}
					}
				}

                if ($this->add_text_in_items) {
                    $lp->name = $lp->name.' '.get_lang('Copy');
                }

                if (isset($this->tool_copy_settings['learnpaths'])) {
                    if (isset($this->tool_copy_settings['learnpaths']['reset_dates']) && $this->tool_copy_settings['learnpaths']['reset_dates']) {
                        $lp->created_on     = api_get_utc_datetime();
                        $lp->modified_on    = api_get_utc_datetime();
                        $lp->publicated_on  = null;
                    }
                }

				$sql = "INSERT INTO ".$table_main." SET
						c_id 				= ".$this->destination_course_id." ,
						lp_type             = '".$lp->lp_type."', " .
						"name               = '".self::DBUTF8escapestring($lp->name)."', " .
						"path               = '".self::DBUTF8escapestring($lp->path)."', " .
						"ref                = '".$lp->ref."', " .
						"description        = '".self::DBUTF8escapestring($lp->description)."', " .
						"content_local      = '".self::DBUTF8escapestring($lp->content_local)."', " .
						"default_encoding   = '".self::DBUTF8escapestring($lp->default_encoding)."', " .
						"default_view_mod   = '".self::DBUTF8escapestring($lp->default_view_mod)."', " .
						"prevent_reinit     = '".self::DBUTF8escapestring($lp->prevent_reinit)."', " .
						"force_commit       = '".self::DBUTF8escapestring($lp->force_commit)."', " .
						"content_maker      = '".self::DBUTF8escapestring($lp->content_maker)."', " .
						"display_order      = '".self::DBUTF8escapestring($lp->display_order)."', " .
						"js_lib             = '".self::DBUTF8escapestring($lp->js_lib)."', " .
						"content_license    = '".self::DBUTF8escapestring($lp->content_license)."', " .
						"author             = '".self::DBUTF8escapestring($lp->author)."', " .
						"preview_image      = '".self::DBUTF8escapestring($lp->preview_image)."', " .
        				"use_max_score      = '".self::DBUTF8escapestring($lp->use_max_score)."', " .
        				"autolunch          = '".self::DBUTF8escapestring($lp->autolunch)."', " .
        				"created_on         = '".self::DBUTF8escapestring($lp->created_on)."', " .
        				"modified_on        = '".self::DBUTF8escapestring($lp->modified_on)."', " .
        				"publicated_on      = '".self::DBUTF8escapestring($lp->publicated_on)."', " .
				        "expired_on         = '".self::DBUTF8escapestring($lp->expired_on)."', " .
						"debug              = '".self::DBUTF8escapestring($lp->debug)."' $condition_session ";

				Database::query($sql);
				$new_lp_id = Database::insert_id();
				if ($lp->visibility) {
					$sql = "INSERT INTO $table_tool SET c_id = ".$this->destination_course_id." , name='".self::DBUTF8escapestring($lp->name)."', link='newscorm/lp_controller.php?action=view&lp_id=$new_lp_id', image='scormbuilder.gif', visibility='1', admin='0', address='squaregrey.gif'";
					Database::query($sql);
				}

				if ($new_lp_id) {
					api_item_property_update($this->destination_course_info, TOOL_LEARNPATH, $new_lp_id, 'LearnpathAdded', api_get_user_id(), 0, 0, 0, 0, $session_id);
                    //Set the new LP to visible
                    api_item_property_update($this->destination_course_info, TOOL_LEARNPATH, $new_lp_id, 'invisible', api_get_user_id(), 0, 0, 0, 0, $session_id);
				}

				$new_item_ids 		= array();
				$parent_item_ids 	= array();
				$previous_item_ids 	= array();
				$next_item_ids 		= array();
				$old_prerequisite 	= array();
				$old_refs 			= array();
				$prerequisite_ids 	= array();
                
				foreach ($lp->get_items() as $index => $item) {                    
					// we set the ref code here and then we update in a for loop
					$ref = $item['ref'];

					//Dealing with path the same way as ref as some data has been put into path when it's a local resource
					//Only fix the path for no scos
                    if ($item['item_type'] == 'sco') {
                        $path = self::DBUTF8escapestring($item['path']);                        
                    } else {
                        $path = self::DBUTF8escapestring($item['path']);
                        $path = $this->get_new_id($item['item_type'], $path);
                    }                

					$sql = "INSERT INTO ".$table_item." SET
							c_id = ".$this->destination_course_id." ,
							lp_id = '".			self::DBUTF8escapestring($new_lp_id)."', " .
							"item_type='".			self::DBUTF8escapestring($item['item_type'])."', " .
							"ref = '".				self::DBUTF8escapestring($ref)."', " .
							"title = '".			self::DBUTF8escapestring($item['title'])."', " .
							"description ='".		self::DBUTF8escapestring($item['description'])."', " .
							"path = '".				self::DBUTF8escapestring($path)."', " .
							"min_score = '".		self::DBUTF8escapestring($item['min_score'])."', " .
							"max_score = '".		self::DBUTF8escapestring($item['max_score'])."', " .
							"mastery_score = '".	self::DBUTF8escapestring($item['mastery_score'])."', " .
							"parent_item_id = '".	self::DBUTF8escapestring($item['parent_item_id'])."', " .
							"previous_item_id = '".	self::DBUTF8escapestring($item['previous_item_id'])."', " .
							"next_item_id = '".		self::DBUTF8escapestring($item['next_item_id'])."', " .
							"display_order = '".	self::DBUTF8escapestring($item['display_order'])."', " .
							"prerequisite = '".		self::DBUTF8escapestring($item['prerequisite'])."', " .
							"parameters='".			self::DBUTF8escapestring($item['parameters'])."', " .
							"audio='".				self::DBUTF8escapestring($item['audio'])."', " .
							"launch_data = '".		self::DBUTF8escapestring($item['launch_dataprereq_type'])."'";

					Database::query($sql);

					$new_item_id = Database::insert_id();
					//save a link between old and new item IDs
					$new_item_ids[$item['id']] = $new_item_id;
					//save a reference of items that need a parent_item_id refresh
					$parent_item_ids[$new_item_id] = $item['parent_item_id'];
					//save a reference of items that need a previous_item_id refresh
					$previous_item_ids[$new_item_id] = $item['previous_item_id'];
					//save a reference of items that need a next_item_id refresh
					$next_item_ids[$new_item_id] = $item['next_item_id'];

					if (!empty($item['prerequisite'])) {
						if ($lp->lp_type =='2') {
							// if is an sco
							$old_prerequisite[$new_item_id]= $item['prerequisite'];
						} else {
							$old_prerequisite[$new_item_id]= $new_item_ids[$item['prerequisite']];
						}
					}

					if (!empty($ref)) {
						if ($lp->lp_type =='2') {
							// if is an sco
							$old_refs[$new_item_id]= $ref;
						} else {
                            $old_refs[$new_item_id]= $new_item_ids[$ref];
						}
					}

					$prerequisite_ids[$new_item_id] = $item['prerequisite'];
				}

				// updating prerequisites
				foreach ($old_prerequisite  as $key=>$my_old_prerequisite) {
					if($my_old_prerequisite != ''){
						$sql = "UPDATE ".$table_item." SET prerequisite = '".$my_old_prerequisite."' WHERE c_id = ".$this->destination_course_id." AND id = '".$key."'  ";
						Database::query($sql);
					}
				}

				//updating refs
				foreach ($old_refs  as $key=>$my_old_ref) {
					if ($my_old_ref != '') {
						$sql = "UPDATE ".$table_item." SET ref = '".$my_old_ref."' WHERE c_id = ".$this->destination_course_id." AND id = '".$key."'  ";
						Database::query($sql);
					}
				}

				foreach ($parent_item_ids as $new_item_id => $parent_item_old_id) {
					$parent_new_id = 0;
					if($parent_item_old_id != 0){
						$parent_new_id = $new_item_ids[$parent_item_old_id];
					}
					$sql = "UPDATE ".$table_item." SET parent_item_id = '".$parent_new_id."' WHERE c_id = ".$this->destination_course_id." AND id = '".$new_item_id."'";
					Database::query($sql);
				}
				foreach ($previous_item_ids as $new_item_id => $previous_item_old_id) {
					$previous_new_id = 0;
					if($previous_item_old_id != 0){
						$previous_new_id = $new_item_ids[$previous_item_old_id];
					}
					$sql = "UPDATE ".$table_item." SET previous_item_id = '".$previous_new_id."' WHERE  c_id = ".$this->destination_course_id." AND id = '".$new_item_id."'";
					Database::query($sql);
				}

				foreach ($next_item_ids as $new_item_id => $next_item_old_id) {
					$next_new_id = 0;
					if($next_item_old_id != 0){
						$next_new_id = $new_item_ids[$next_item_old_id];
					}
					$sql = "UPDATE ".$table_item." SET next_item_id = '".$next_new_id."' WHERE c_id = ".$this->destination_course_id." AND id = '".$new_item_id."'";
					Database::query($sql);
				}

				foreach ($prerequisite_ids as $new_item_id => $prerequisite_old_id) {
					$prerequisite_new_id = 0;
					if($prerequisite_old_id != 0){
						$prerequisite_new_id = $new_item_ids[$prerequisite_old_id];
					}
					$sql = "UPDATE ".$table_item." SET prerequisite = '".$prerequisite_new_id."' WHERE c_id = ".$this->destination_course_id." AND id = '".$new_item_id."'";
					Database::query($sql);
				}
				$this->course->resources[RESOURCE_LEARNPATH][$id]->destination_id = $new_lp_id;
			}

		}
	}
	/**
	 * restore works
	 * @todo I fixed this crappy function jmontoya
	 */
	function restore_student_publication() {
		$work_assignment_table  = Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
		$work_table    			= Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
		$item_property_table  	= Database :: get_course_table(TABLE_ITEM_PROPERTY);

		//query in student publication

	/*	$query_sql_fin_sp='INSERT IGNORE INTO '.$my_tbl_db_destination.' (c_id, id,url,title,description,author,active,accepted,post_group_id,sent_date,' .
		'filetype,has_properties,view_properties,qualification,date_of_qualification,' .
		'parent_id,qualificator_id,session_id) ';
*/
		$sql = 'SELECT c_id, id, url, title,description,author,active,accepted,post_group_id, sent_date,filetype,has_properties,view_properties,qualification,date_of_qualification,qualificator_id,session_id
		      FROM '.$work_table.'
		      WHERE c_id = '.$this->course_origin_id.' AND filetype="folder" AND active IN (0, 1) ';

		$result = Database::query($sql);
		$folders = Database::store_result($result, 'ASSOC');

		foreach ($folders  as $folder) {
		    $old_id = $folder['id'];

            unset($folder['id']);
			$folder['c_id'] = $this->destination_course_id;
            $folder['parent_id'] = 0;
			$new_id = Database::insert($work_table, $folder);

            if ($new_id) {
                 //query in item property
                $sql = 'SELECT tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type, lastedit_user_id,to_group_id,to_user_id,visibility,start_visible, end_visible
                           FROM '.$item_property_table.' ip INNER JOIN '.$work_table.' sp ON ip.ref=sp.id
                           WHERE  sp.c_id = '.$this->course_origin_id.' AND
                                  ip.c_id = '.$this->course_origin_id.' AND
                                  tool="work" AND sp.id = '.$old_id.'';

                $result = Database::query($sql);
                $sub_folders = Database::store_result($result, 'ASSOC');
                foreach ($sub_folders  as $sub_folder) {
                    $sub_folder['c_id'] = $this->destination_course_id;
                    $sub_folder['ref'] = $new_id;
                    $new_item_id = Database::insert($item_property_table, $sub_folder);
                }

                $sql = 'SELECT sa.id, sa.expires_on,sa.ends_on,sa.add_to_calendar, sa.enable_qualification, sa.publication_id
                          FROM '.$work_assignment_table.' sa INNER JOIN '.$work_table.' sp ON sa.publication_id=sp.id
                          WHERE   sp.c_id = '.$this->course_origin_id.' AND
                                  sa.c_id = '.$this->course_origin_id.' AND
                                  filetype="folder" AND sp.id = '.$old_id.'';

                $result = Database::query($sql);
                $assing_list = Database::store_result($result, 'ASSOC');
                foreach($assing_list  as $assign) {
                    $assign['c_id'] = $this->destination_course_id;
                    $assign['id'] = $new_id;
                    Database::insert($work_assignment_table, $assign);
                }
            }
		}

		$destination='../../courses/'.$this->course->destination_path.'/work/';
		$origin='../../courses/'.$this->course->info['path'].'/work/';
		self::allow_create_all_directory($origin,$destination,false);
	}

/**
 * copy all directory and sub directory
 * @param string The path origin
 * @param string The path destination
 * @param boolean Option Overwrite
 * @return void()
 */
	function allow_create_all_directory($source, $dest, $overwrite = false) {
   		if(!is_dir($dest)) {
   			mkdir($dest, api_get_permissions_for_new_directories());
   		}
	    if ($handle = opendir($source)) {        // if the folder exploration is sucsessful, continue
	        while (false !== ($file = readdir($handle))) { // as long as storing the next file to $file is successful, continue
	            if ($file != '.' && $file != '..') {
	                $path = $source . '/' . $file;
	                if (is_file($path)) {
	                   /* if (!is_file($dest . '/' . $file) || $overwrite)
	                    if (!@copy($path, $dest . '/' . $file)) {
	                        echo '<font color="red">File ('.$path.') '.get_lang('NotHavePermission').'</font>';
	                    }*/
	                } elseif(is_dir($path)) {
	                    if (!is_dir($dest . '/' . $file))
	                    mkdir($dest . '/' . $file);
	                   self:: allow_create_all_directory($path, $dest . '/' . $file, $overwrite);
	                }
	            }
	        }
	        closedir($handle);
	    }
	}

	/**
	 * Gets the new ID of one specific tool item from the tool name and the old ID
	 * @param	string	Tool name
	 * @param	integer	Old ID
	 * @return	integer	New ID
	 */
	function get_new_id($tool, $ref) {
		//transform $tool into one backup/restore constant

        //just in case you copy the tool in the same course
        //error_log($this->course_origin_id .' - '.$this->destination_course_id);
        if ($this->course_origin_id == $this->destination_course_id) {
            return $ref;
        }

		if ($tool == 'hotpotatoes') { $tool = 'document'; }
		if (!empty($this->course->resources[$tool][$ref]->destination_id)) {
			return $this->course->resources[$tool][$ref]->destination_id;
		}
		return '';
	}
	/**
	 * Restore glossary
	 */
	function restore_glossary($session_id = 0) {
		if ($this->course->has_resources(RESOURCE_GLOSSARY)) {
			$table_glossary = Database :: get_course_table(TABLE_GLOSSARY);
			$t_item_propery = Database :: get_course_table(TABLE_ITEM_PROPERTY);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_GLOSSARY] as $id => $glossary) {

				$condition_session = "";
    			if (!empty($session_id)) {
    				$session_id = intval($session_id);
    				$condition_session = " , session_id = '$session_id' ";
    			}

				// check resources inside html from fckeditor tool and copy correct urls into recipient course
				$glossary->description = DocumentManager::replace_urls_inside_content_html_from_copy_course($glossary->description, $this->course->code, $this->course->destination_path);

				$sql = "INSERT INTO ".$table_glossary." SET  c_id = ".$this->destination_course_id." , name = '".self::DBUTF8escapestring($glossary->name)."', description = '".self::DBUTF8escapestring($glossary->description)."', display_order='".self::DBUTF8escapestring($glossary->display_order)."' $condition_session ";
				Database::query($sql);
				$my_id = Database::get_last_insert_id();
				api_item_property_update($this->destination_course_info, TOOL_GLOSSARY, $my_id,"GlossaryAdded", api_get_user_id());
				$this->course->resources[RESOURCE_GLOSSARY][$id]->destination_id = Database::insert_id();

			}
		}
	}

	function restore_wiki($session_id = 0) {
		if ($this->course->has_resources(RESOURCE_WIKI)) {
			// wiki table of the target course
			$table_wiki 		= Database :: get_course_table('wiki');
			$table_wiki_conf 	= Database :: get_course_table('wiki_conf');

			// storing all the resources that have to be copied in an array
			$resources = $this->course->resources;

			foreach ($resources[RESOURCE_WIKI] as $id => $wiki) {
				//$wiki = new Wiki($obj->page_id, $obj->reflink, $obj->title, $obj->content, $obj->user_id, $obj->group_id, $obj->dtime);
				// the sql statement to insert the groups from the old course to the new course

				// check resources inside html from fckeditor tool and copy correct urls into recipient course
				$wiki->content = DocumentManager::replace_urls_inside_content_html_from_copy_course($wiki->content, $this->course->code, $this->course->destination_path);

				$sql = "INSERT INTO $table_wiki (c_id, page_id, reflink, title, content, user_id, group_id, dtime, progress, version, session_id) VALUES (
							".$this->destination_course_id." ,
							'".self::DBUTF8escapestring($wiki->page_id)."',
							'".self::DBUTF8escapestring($wiki->reflink)."',
							'".self::DBUTF8escapestring($wiki->title)."',
							'".self::DBUTF8escapestring($wiki->content)."',
							'".intval($wiki->user_id)."',
							'".intval($wiki->group_id)."',
							'".self::DBUTF8escapestring($wiki->dtime)."',
							'".self::DBUTF8escapestring($wiki->progress)."',
							'".intval($wiki->version)."',
							'".(!empty($session_id)?intval($session_id):0)."')";
				$rs2 = Database::query($sql);
				$new_id = Database::insert_id();
				$this->course->resources[RESOURCE_WIKI][$id]->destination_id = $new_id;
				$sql = "UPDATE $table_wiki set page_id = '$new_id' WHERE c_id = ".$this->destination_course_id." AND id = '$new_id'";
				Database::query($sql);

				// we also add an entry in wiki_conf
				$sql = "INSERT INTO $table_wiki_conf (c_id, page_id, task, feedback1, feedback2, feedback3, fprogress1, fprogress2, fprogress3, max_size, max_text, max_version, startdate_assig, enddate_assig, delayedsubmit) VALUES
						(".$this->destination_course_id." , '".intval($new_id)."', '', '', '', '', '', '', '', NULL, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0)";
				$rs1 = Database::query($sql);
			}
		}
	}

	/**
	* Restore Thematics
	*/
	function restore_thematic($session_id = 0) {
		if ($this->course->has_resources(RESOURCE_THEMATIC)) {
			$table_thematic 		= Database :: get_course_table(TABLE_THEMATIC);
			$table_thematic_advance = Database :: get_course_table(TABLE_THEMATIC_ADVANCE);
			$table_thematic_plan    = Database :: get_course_table(TABLE_THEMATIC_PLAN);

			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_THEMATIC] as $id => $thematic) {

				// check resources inside html from fckeditor tool and copy correct urls into recipient course
				$thematic->content 	  = DocumentManager::replace_urls_inside_content_html_from_copy_course($thematic->content, $this->course->code, $this->course->destination_path);
				$thematic->params['c_id']  = $this->destination_course_id;
				$doc = '';
				$thematic->params['id'] = null;
				$last_id = Database::insert($table_thematic, $thematic->params, false);

				if (is_numeric($last_id)) {
					api_item_property_update($this->destination_course_info, 'thematic', $last_id, "ThematicAdded", api_get_user_id());

					foreach($thematic->thematic_advance_list as $thematic_advance) {
						unset($thematic_advance['id']);
						$thematic_advance['attendance_id'] = 0;
						$thematic_advance['thematic_id'] = $last_id;
						$thematic_advance['c_id']  = $this->destination_course_id;
						$my_id = Database::insert($table_thematic_advance, $thematic_advance, false);

						if (is_numeric($my_id)) {
							api_item_property_update($this->destination_course_info, 'thematic_advance', $my_id,"ThematicAdvanceAdded", api_get_user_id());
						}
					}

					foreach($thematic->thematic_plan_list as $thematic_plan) {
						unset($thematic_plan['id']);
						$thematic_plan['thematic_id'] 	= $last_id;
						$thematic_plan['c_id']  		= $this->destination_course_id;
						$my_id = Database::insert($table_thematic_plan, $thematic_plan, false);
						if (is_numeric($my_id)) {
							api_item_property_update($this->destination_course_info, 'thematic_plan', $my_id, "ThematicPlanAdded", api_get_user_id());
						}
					}

				}
			}
		}
	}

	/**
	* Restore Attendance
	*/

	function restore_attendance($session_id = 0) {
		if ($this->course->has_resources(RESOURCE_ATTENDANCE)) {
			$table_attendance 		   = Database :: get_course_table(TABLE_ATTENDANCE);
			$table_attendance_calendar = Database :: get_course_table(TABLE_ATTENDANCE_CALENDAR);

			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_ATTENDANCE] as $id => $obj) {

				// check resources inside html from fckeditor tool and copy correct urls into recipient course
				$obj->params['description'] = DocumentManager::replace_urls_inside_content_html_from_copy_course($obj->params['description'], $this->course->code, $this->course->destination_path);
				$doc = '';
				$obj->params['id'] = null;
				$obj->params['c_id'] = $this->destination_course_id;

				$last_id = Database::insert($table_attendance, $obj->params);

				if (is_numeric($last_id)) {
					api_item_property_update($this->destination_course_info, TOOL_ATTENDANCE, $last_id,"AttendanceAdded", api_get_user_id());

					foreach($obj->attendance_calendar as $attendance_calendar) {
						unset($attendance_calendar['id']);
						$attendance_calendar['attendance_id'] = $last_id;
						$attendance_calendar['c_id'] = $this->destination_course_id;
						$my_id = Database::insert($table_attendance_calendar, $attendance_calendar);
	/*
						if (is_numeric($my_id)) {
							api_item_property_update($this->destination_course_info, 'thematic_advance', $my_id,"ThematicAdvanceAdded", api_get_user_id());
						}*/
					}
				}
			}
		}
	}

	function DBUTF8escapestring($str) {
		if (UTF8_CONVERT) $str = utf8_encode($str);
		return Database::escape_string($str);
	}
    
    function DBUTF8_array($array) {        
        if (UTF8_CONVERT) {
            
            foreach ($array as &$item)  {
                $item = utf8_encode($item);
            }
            return $array;
        } else {
            return $array;
        }
    }
}