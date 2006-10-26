<?php //$id: $
/**
 * Controller script. Prepares the common background variables to give to the scripts corresponding to
 * the requested action
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Initialisations
 */
$debug = 0;
if($debug>0) error_log('New LP -+- Entered lp_controller.php -+-',0);
$langFile[] = "scormdocument";
$langFile[] = "scorm";
$langFile[] = "learnpath";

//include class definitions before session_start() to ensure availability when touching
//session vars containing learning paths
require_once('learnpath.class.php');
if($debug>0) error_log('New LP - Included learnpath',0);
require_once('learnpathItem.class.php');
if($debug>0) error_log('New LP - Included learnpathItem',0);
require_once('scorm.class.php');
if($debug>0) error_log('New LP - Included scorm',0);
require_once('scormItem.class.php');
if($debug>0) error_log('New LP - Included scormItem',0);
require_once('aicc.class.php');
if($debug>0) error_log('New LP - Included aicc',0);
require_once('aiccItem.class.php');
if($debug>0) error_log('New LP - Included aiccItem',0);
require_once('temp.lib.php');
if($debug>0) error_log('New LP - Included temp',0);


require_once('back_compat.inc.php');
if($debug>0) error_log('New LP - Included back_compat',0);
api_protect_course_script();
//TODO @TODO define tool, action and task to give as parameters to:
//$is_allowed_to_edit = api_is_allowedapi_is_allowed_to_edit();

if ($is_allowed_in_course == false){
	Display::display_header('');
	api_not_allowed();
	Display::display_footer();
}

require_once(api_get_path(LIBRARY_PATH) . "/fckeditor.lib.php");

$lpfound = false;

$myrefresh = 0;
$myrefresh_id = 0;
if(!empty($_SESSION['refresh']) && $_SESSION['refresh']==1){
	//check if we should do a refresh of the oLP object (for example after editing the LP)
	//if refresh is set, we regenerate the oLP object from the database (kind of flush)
	api_session_unregister('refresh');
	$myrefresh = 1;
	if($debug>0) error_log('New LP - Refresh asked',0);
}
if($debug>0) error_log('New LP - Passed refresh check',0);

if(!empty($_REQUEST['dialog_box'])){
	$dialog_box = learnpath::escape_string(urldecode($_REQUEST['dialog_box']));
}

$lp_controller_touched = 1;

var_dump($_SESSION['lpobject']);

if(isset($_SESSION['lpobject']))
{
	if($debug>0) error_log('New LP - SESSION[lpobject] is defined',0);
	$oLP = unserialize($_SESSION['lpobject']);
	if(is_object($oLP)){
		if($debug>0) error_log('New LP - oLP is object',0);
		if($myrefresh == 1 OR $oLP->cc != api_get_course_id()){
			if($debug>0) error_log('New LP - Course has changed, discard lp object',0);
			if($myrefresh == 1){$myrefresh_id = $oLP->get_id();}
			$oLP = null;
			api_session_unregister('oLP');
			api_session_unregister('lpobject');
		}else{
			$_SESSION['oLP'] = $oLP;
			$lp_found = true;
		}
	}
}
if($debug>0) error_log('New LP - Passed data remains check',0);

if($lp_found == false 
	|| ($_SESSION['oLP']->get_id() != $_REQUEST['lp_id'])
	)
{
	if($debug>0) error_log('New LP - oLP is not object, has changed or refresh been asked, getting new',0);		
	//regenerate a new lp object? Not always as some pages don't need the object (like upload?)
	if(!empty($_REQUEST['lp_id']) || !empty($myrefresh_id)){
		if($debug>0) error_log('New LP - lp_id is defined',0);
		//select the lp in the database and check which type it is (scorm/dokeos/aicc) to generate the
		//right object
		$lp_table = Database::get_course_table('lp');
		if(!empty($_REQUEST['lp_id'])){
			$lp_id = escape_txt($_REQUEST['lp_id']);
		}else{
			$lp_id = $myrefresh_id;
		}
		$sel = "SELECT * FROM $lp_table WHERE id = $lp_id";
		if($debug>0) error_log('New LP - querying '.$sel,0);
		$res = api_sql_query($sel);
		if(Database::num_rows($res))
		{
			$row = Database::fetch_array($res);
			$type = $row['lp_type'];
			if($debug>0) error_log('New LP - found row - type '.$type. ' - Calling constructor with '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id(),0);			
			switch($type){
				case 1:
			if($debug>0) error_log('New LP - found row - type dokeos - Calling constructor with '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id(),0);			
					$oLP = new learnpath(api_get_course_id(),$lp_id,api_get_user_id());
					if($oLP !== false){ $lp_found = true; }else{eror_log($oLP->error,0);}
					break;
				case 2:
			if($debug>0) error_log('New LP - found row - type scorm - Calling constructor with '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id(),0);			
					$oLP = new scorm(api_get_course_id(),$lp_id,api_get_user_id());
					if($oLP !== false){ $lp_found = true; }else{eror_log($oLP->error,0);}
					break;
				case 3:
			if($debug>0) error_log('New LP - found row - type aicc - Calling constructor with '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id(),0);			
					$oLP = new aicc(api_get_course_id(),$lp_id,api_get_user_id());
					if($oLP !== false){ $lp_found = true; }else{eror_log($oLP->error,0);}
					break;					
				default:
			if($debug>0) error_log('New LP - found row - type other - Calling constructor with '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id(),0);			
					$oLP = new learnpath(api_get_course_id(),$lp_id,api_get_user_id());
					if($oLP !== false){ $lp_found = true; }else{eror_log($oLP->error,0);}
					break;
			}
		}
	}else{
		if($debug>0) error_log('New LP - Request[lp_id] and refresh_id were empty',0);
	}
	if($lp_found)
	{
		$_SESSION['oLP'] = $oLP;
	}
}
if($debug>0) error_log('New LP - Passed oLP creation check',0);

/**
 * Actions switching
 */

$_SESSION['oLP']->update_queue = array(); //reinitialises array used by javascript to update items in the TOC
$_SESSION['oLP']->message = ''; //should use ->clear_message() method but doesn't work

if(api_is_allowed_to_edit())
{	
	switch($_REQUEST['action'])
	{
		case 'add_item':
			
			$htmlHeadXtra[] = $_SESSION['oLP']->create_js();
			
			require('lp_admin_header.php');
			
			if($debug>0) error_log('New LP - add item action triggered',0);
			
			//old->if(!$lp_found){ error_log('New LP - No learnpath given for add item',0); require('lp_list.php'); }
			if(!$_SESSION['oLP']){ error_log('New LP - No learnpath given for add item',0); require('lp_list.php'); }
			else
			{
				if(!empty($_REQUEST['submit_button']) && !empty($_REQUEST['title']))
				{
					$_SESSION['refresh'] = 1;
								
					if(isset($_SESSION['post_time']) && $_SESSION['post_time'] == $_REQUEST['post_time'])
					{
						echo $_SESSION['oLP']->build();
					}
					else
					{
						$_SESSION['post_time'] = $_REQUEST['post_time'];
									
						if($_REQUEST['type'] != 'dokeos_chapter' && $_REQUEST['type'] != 'dokeos_module' && $_REQUEST['type'] != 'dokeos_step')
							$_REQUEST['type'] = 'dokeos_module';
						
						if($_REQUEST['type'] == 'dokeos_step')
						{
							if(isset($_REQUEST['doc']))
								$document_id = $_REQUEST['doc'];
							else
								$document_id = $_SESSION['oLP']->create_document($_course);
										
							$new_item_id = $_SESSION['oLP']->add_item($_REQUEST['parent'], $_REQUEST['previous'], $_REQUEST['type'], $document_id, $_REQUEST['title'], $_REQUEST['description'], $_REQUEST['description']);
						}
						else
						{
							$new_item_id = $_SESSION['oLP']->add_item($_REQUEST['parent'], $_REQUEST['previous'], $_REQUEST['type'], $_REQUEST['path'], $_REQUEST['title'], $_REQUEST['description']);
						}
							
						echo $_SESSION['oLP']->build($new_item_id);
					}
				}
				else
				{
					echo $_SESSION['oLP']->build();
				}
			}
			
			Display::display_footer();
		
			break;
					
		case 'add_lp':
			
			require('lp_admin_header.php');
				
			if($debug>0) error_log('New LP - admin action with add_lp mode triggered', 0);
						
			$_REQUEST['learnpath_name'] = trim($_REQUEST['learnpath_name']);
						
			if(!empty($_REQUEST['learnpath_name']))
			{
				$_SESSION['refresh'] = 1;
							
				if(isset($_SESSION['post_time']) && $_SESSION['post_time'] == $_REQUEST['post_time'])
				{
					echo $_SESSION['oLP']->build();
				}
				else
				{
					$_SESSION['post_time'] = $_REQUEST['post_time'];
								
					//Kevin Van Den Haute: changed $_REQUEST['learnpath_description'] by '' because it's not used
					//old->$new_lp_id = learnpath::add_lp(api_get_course_id(), $_REQUEST['learnpath_name'], $_REQUEST['learnpath_description'], 'dokeos', 'manual', '');
					$new_lp_id = learnpath::add_lp(api_get_course_id(), $_REQUEST['learnpath_name'], '', 'dokeos', 'manual', '');
							
					//Kevin Van Den Haute: only go further if learnpath::add_lp has returned an id
					if(is_numeric($new_lp_id))
					{
						//TODO maybe create a first module directly to avoid bugging the user with useless queries
						$_SESSION['oLP'] = new learnpath(api_get_course_id(),$new_lp_id,api_get_user_id());
								
						//$_SESSION['oLP']->add_item(0,-1,'dokeos_chapter',$_REQUEST['path'],'Default');
									
						echo $_SESSION['oLP']->build();
					}
				}
			}
			else
			{
				echo '<div class="lp_message">';
							
					echo '<p style="margin-top:0"><span style="font-weight:bold;">Welcome</span> to Dokeos Learning path authoring tool.</p>';
					echo 'You will be able to create your learning path step by step. The structure of your learning path will appear in a menu on the left.';
								
				echo '</div>';
							
				echo '<div style="padding-left:40px;">';
							
					echo '<p style="font-weight:bold">To start, give a title to your learning path:</p>';
							
					echo '<form method="post">';
									
						echo '<label for="idTitle" style="margin-right:10px;">Title:</label><input id="idTitle" name="learnpath_name" type="text" />';
						echo '<p><input type="submit" value="OK" /></p>';
						echo '<input name="post_time" type="hidden" value="' . time() . '"/>';
					
					echo '</form>';
						echo '</div>';
			}

			Display::display_footer();
					
			break;
				
		case 'build':
			
			require('lp_admin_header.php');
					
			if($debug>0) error_log('New LP - admin action with build mode triggered', 0);
						
			$_SESSION['refresh'] = 1;
					
			echo $_SESSION['oLP']->build();
			
			Display::display_footer();
					
			break;
				
		case 'delete_item':
			
			require('lp_admin_header.php');
					
			$_SESSION['oLP']->delete_item($_GET['id']);
			
			require('lp_admin_header.php');
				
			if(isset($_GET['lp_id']))
				echo $_SESSION['oLP']->overview();
			else
				echo $_SESSION['oLP']->build();
			
			Display::display_footer();
					
			break;
					
		case 'edit_item':
			
			$htmlHeadXtra[] = $_SESSION['oLP']->create_js();
			
			require('lp_admin_header.php');
					
			if($_REQUEST['submit_button'])
			{
				$_SESSION['oLP']->edit_item($_REQUEST['id'], $_REQUEST['parent'], $_REQUEST['previous'], $_REQUEST['title'], $_REQUEST['description']);
				
				if(isset($_GET['view']) && $_GET['view'] == 'advanced')
				{
					echo $_SESSION['oLP']->build();
				}
				else
				{
					echo $_SESSION['oLP']->overview();
				}
			}
			else
			{
				if($_GET['view'] && $_GET['view'] == 'advanced')
				{
					echo $_SESSION['oLP']->build();
				}
				else
				{	
					echo $_SESSION['oLP']->display_edit_item($_GET['id']);
					echo $_SESSION['oLP']->overview();
				}
			}
			
			Display::display_footer();
			
			break;
					
		case 'edit_step':
			
			$htmlHeadXtra[] = $_SESSION['oLP']->create_js();
			
			require('lp_admin_header.php');
			
			if($_REQUEST['submit_button'])
			{
				$_SESSION['oLP']->edit_item($_REQUEST['id'], $_REQUEST['title'], $_REQUEST['description']);
				$_SESSION['oLP']->edit_document($_course);
				
				$data = $_REQUEST['id'];
			}
				
			echo $_SESSION['oLP']->build($data);
			
			Display::display_footer();
				
			break;
				
		case 'move':
			
			$htmlHeadXtra[] = $_SESSION['oLP']->create_js();
			
			require('lp_admin_header.php');
			
			if($_REQUEST['submit_button'])
			{
				$_SESSION['oLP']->edit_item($_REQUEST['id'], $_REQUEST['parent'], $_REQUEST['previous'], $_REQUEST['title'], $_REQUEST['description'], $_REQUEST['old_previous'], $_REQUEST['old_next']);
				
				echo $_SESSION['oLP']->build();
			}
			else
			{
				//if($_GET['view'] && $_GET['view'] == 'advanced')
				//{
					echo $_SESSION['oLP']->build();
				//}
				//else
				//{	
				//	echo $_SESSION['oLP']->display_edit_item($_GET['id']);
					//echo $_SESSION['oLP']->overview();
				//}
			}
				
			//echo $_SESSION['oLP']->build();
			
			Display::display_footer();
			
			break;
					
		case 'move_item':
			
			require('lp_admin_header.php');
				
			if($debug > 0) error_log('New LP - move_item action triggered', 0);
					
			//old->if(!$lp_found){ error_log('New LP - No learnpath given for move_item', 0); require('lp_list.php'); }
			
			if(!empty($_REQUEST['direction']) && !empty($_REQUEST['id']))
			{
				$_SESSION['refresh'] = 1;
						
				$_SESSION['oLP']->move_item($_REQUEST['id'], $_REQUEST['direction']);
			}
			
			echo $_SESSION['oLP']->overview();
			
			Display::display_footer();
				
			break;
						
		case 'prerequisites':
			
			require('lp_admin_header.php');
				
			echo $_SESSION['oLP']->build();
			
			Display::display_footer();
			
			break;
					
		case 'view_item':
			
			require('lp_admin_header.php');
				
			if($debug>0) error_log('New LP - admin action with view_item mode triggered', 0);
			//old->if(!$lp_found){ error_log('New LP - No learnpath given for view item',0); require('lp_list.php'); }
					
			echo $_SESSION['oLP']->build();
			
			Display::display_footer();
			
			break;
						
		case 'admin_view':
			
			require('lp_admin_header.php');
			
			if($debug>0) error_log('New LP - admin action with admin_view mode triggered', 0);
			
			$_SESSION['refresh'] = 1;
			
			echo $_SESSION['oLP']->overview();
			
			Display::display_footer();
			
			break;
			
			/* not modified by Kevin Van Den Haute */
			
			case 'upload':
				if($debug>0) error_log('New LP - upload action triggered',0);
				$cwdir = getcwd();
				require('lp_upload.php');
				//reinit current working directory as many functions in upload change it
				chdir($cwdir);
				require('lp_list.php');
				break;
			case 'export':
				if($debug>0) error_log('New LP - export action triggered',0);
				if(!$lp_found){ error_log('New LP - No learnpath given for export',0); require('lp_list.php'); }
				else{
					if($_SESSION['oLP']->get_type()==2){
						$_SESSION['oLP']->export_zip();
					}
					//require('lp_list.php'); 
				}
				break;
			case 'delete':
			if($debug>0) error_log('New LP - delete action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for delete',0); require('lp_list.php'); }
			else{
				$_SESSION['refresh'] = 1;
				$_SESSION['oLP']->delete(null,null,'remove');
				api_session_unregister('oLP');
				//require('lp_delete.php');
				require('lp_list.php');
			}
			break;
		
		case 'toggle_visible': //change lp visibility
			if($debug>0) error_log('New LP - publish action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for publish',0); require('lp_list.php'); }
			else{
				learnpath::toggle_visibility($_REQUEST['lp_id'],$_REQUEST['new_status']);
				require('lp_list.php');
			}
			break;
		/* rewritten by Kevin Van Den Haute (see more in the beginning of the document */
		/*
		case 'edit':
			if($debug>0) error_log('New LP - edit action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for edit',0); require('lp_list.php'); }
			else{
				$_SESSION['refresh'] = 1;
				require('lp_edit.php');
				//require('lp_admin_view.php');
			}
			break;
		*/
		case 'update_lp':
			if($debug>0) error_log('New LP - update_lp action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for edit',0); require('lp_list.php'); }
			else{
				$_SESSION['refresh'] = 1;
				$_SESSION['oLP']->set_name($_REQUEST['lp_name']);
				$_SESSION['oLP']->set_encoding($_REQUEST['lp_encoding']);
				$_SESSION['oLP']->set_maker($_REQUEST['lp_maker']);
				$_SESSION['oLP']->set_proximity($_REQUEST['lp_proximity']);
				require('lp_list.php');
			}	
			break;	
			
		case 'add_sub_item': //add an item inside a chapter
			if($debug>0) error_log('New LP - add sub item action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for add sub item',0); require('lp_list.php'); }
			else{
				$_SESSION['refresh'] = 1;
				if(!empty($_REQUEST['parent_item_id'])){
					$_SESSION['from_learnpath']='yes';
					$_SESSION['origintoolurl'] = 'lp_controller.php?action=admin_view&lp_id='.$_REQUEST['lp_id'];
					require('resourcelinker.php');
					//$_SESSION['oLP']->add_sub_item($_REQUEST['parent_item_id'],$_REQUEST['previous'],$_REQUEST['type'],$_REQUEST['path'],$_REQUEST['title'], $_REQUEST['description']);
				}else{
					require('lp_admin_view.php');
				}
			}
			break;
		/*
		case 'deleteitem':
		case 'delete_item':
			if($debug>0) error_log('New LP - delete item action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for delete item',0); require('lp_list.php'); }
			else{
				$_SESSION['refresh'] = 1;
				if(!empty($_REQUEST['id'])){
					$_SESSION['oLP']->delete_item($_REQUEST['id']);
				}
				require('lp_admin_view.php');
			}
			break;
		*/
		/*
		case 'edititem':
		case 'edit_item':
			if($debug>0) error_log('New LP - edit item action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for edit item',0); require('lp_list.php'); }
			else{
				if(!empty($_REQUEST['id']) && !empty($_REQUEST['submit_item'])){
					$_SESSION['refresh'] = 1;
					$_SESSION['oLP']->edit_item($_REQUEST['id'], $_REQUEST['title'], $_REQUEST['description']);
				}
				require('lp_admin_view.php');
			}
			break;
		*/
		case 'edititemprereq':
		case 'edit_item_prereq':
			if($debug>0) error_log('New LP - edit item prereq action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for edit item prereq',0); require('lp_list.php'); }
			else{
				if(!empty($_REQUEST['id']) && !empty($_REQUEST['submit_item'])){
					$_SESSION['refresh'] = 1;
					$_SESSION['oLP']->edit_item_prereq($_REQUEST['id'],$_REQUEST['prereq']);
				}
				require('lp_admin_view.php');
			}
			break;
		case 'restart':
			if($debug>0) error_log('New LP - restart action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for restart',0); require('lp_list.php'); }
			else{
				$_SESSION['oLP']->restart();
				require('lp_view.php');
			}
			break;
		case 'last':
			if($debug>0) error_log('New LP - last action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for last',0); require('lp_list.php'); }
			else{
				$_SESSION['oLP']->last();
				require('lp_view.php');
			}
			break;
		case 'first':
			if($debug>0) error_log('New LP - first action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for first',0); require('lp_list.php'); }
			else{
				$_SESSION['oLP']->first();
				require('lp_view.php');
			}
			break;
		case 'next':
			if($debug>0) error_log('New LP - next action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for next',0); require('lp_list.php'); }
			else{
				$_SESSION['oLP']->next();
				require('lp_view.php');
			}
			break;
		case 'previous':
			if($debug>0) error_log('New LP - previous action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for previous',0); require('lp_list.php'); }
			else{
				$_SESSION['oLP']->previous();
				require('lp_view.php');
			}
			break;
		case 'content':
			if($debug>0) error_log('New LP - content action triggered',0);
			if($debug>0) error_log('New LP - Item id is '.$_GET['item_id'],0);
			if(!$lp_found){ error_log('New LP - No learnpath given for content',0); require('lp_list.php'); }
			else{
				$_SESSION['oLP']->set_current_item($_GET['item_id']); 
				$_SESSION['oLP']->start_current_item();
				require('lp_content.php'); 
			}
			break;	
		case 'view':
			if($debug > 0)
				error_log('New LP - view action triggered', 0);
			if(!$lp_found)
			{
				error_log('New LP - No learnpath given for view', 0);
				require('lp_list.php');
			}
			else
			{
				if($debug > 0){error_log('New LP - Trying to set current item to ' . $_REQUEST['item_id'], 0);}
				$_SESSION['oLP']->set_current_item($_REQUEST['item_id']);
				require('lp_view.php');
			}
			break;
			
		case 'save':
			if($debug>0) error_log('New LP - save action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for save',0); require('lp_list.php'); }
			else{
				$_SESSION['oLP']->save_item();
				require('lp_save.php');
			}
			break;		
		case 'stats':
			if($debug>0) error_log('New LP - stats action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for stats',0); require('lp_list.php'); }
			else{
				$_SESSION['oLP']->save_current();
				$_SESSION['oLP']->save_last();
				require('lp_stats.php');
			}
			break;
		case 'list':
			if($debug>0) error_log('New LP - list action triggered',0);
			if($lp_found){
				$_SESSION['refresh'] = 1;
				$_SESSION['oLP']->save_last();
			}
			require('lp_list.php');
			break;
		case 'mode':
			//switch between fullscreen and embedded mode
			if($debug>0) error_log('New LP - mode change triggered',0);
			$mode = $_REQUEST['mode'];
			if($mode == 'fullscreen'){
				$_SESSION['oLP']->mode = 'fullscreen';
			}else{
				$_SESSION['oLP']->mode = 'embedded';		
			}
			require('lp_view.php');
			break;
		case 'switch_view_mode':
			if($debug>0) error_log('New LP - switch_view_mode action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for switch',0); require('lp_list.php'); }
			$_SESSION['refresh'] = 1;
			$_SESSION['oLP']->update_default_view_mode();		
			require('lp_list.php');
			break;
		case 'switch_force_commit':
			if($debug>0) error_log('New LP - switch_force_commit action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for switch',0); require('lp_list.php'); }
			$_SESSION['refresh'] = 1;
			$_SESSION['oLP']->update_default_scorm_commit();
			require('lp_list.php');
			break;
		case 'switch_reinit':
			if($debug>0) error_log('New LP - switch_reinit action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for switch',0); require('lp_list.php'); }
			$_SESSION['refresh'] = 1;
			$_SESSION['oLP']->update_reinit();
			require('lp_list.php');
			break;		
		case 'switch_scorm_debug':
			if($debug>0) error_log('New LP - switch_scorm_debug action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for switch',0); require('lp_list.php'); }
			$_SESSION['refresh'] = 1;
			$_SESSION['oLP']->update_scorm_debug();
			require('lp_list.php');
			break;		
		case 'intro_cmdAdd':
			if($debug>0) error_log('New LP - intro_cmdAdd action triggered',0);
			//add introduction section page
			break;
			
		case 'js_api_refresh':
			if($debug>0) error_log('New LP - js_api_refresh action triggered',0);
			if(!$lp_found){ error_log('New LP - No learnpath given for js_api_refresh',0); require('lp_message.php'); }
			if(isset($_REQUEST['item_id'])){
				$htmlHeadXtra[] = $_SESSION['oLP']->get_js_info($_REQUEST['item_id']);
			}
			require('lp_message.php');
			break;
			
		default:
			if($debug>0) error_log('New LP - default action triggered',0);
			//$_SESSION['refresh'] = 1;
			require('lp_list.php');
			break;
			/* end of not modified by Kevin Van Den Haute */ 
		}
	}
	else
	{
		Display::display_header();
		api_not_allowed();
		Display::display_footer();	
	}

if(!empty($_SESSION['oLP'])){
	$_SESSION['lpobject'] = serialize($_SESSION['oLP']);
	if($debug>0) error_log('New LP - lpobject is serialized in session',0);
}
?>