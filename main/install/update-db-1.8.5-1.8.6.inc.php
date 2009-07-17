<?php // $Id: update-db-1.8.5-1.8.6.inc.php 22197 2009-07-17 17:49:44Z ivantcholakov $
/* See license terms in /dokeos_license.txt */
/**
==============================================================================
* Update the Dokeos database from an older version
* Notice : This script has to be included by index.php or update_courses.php
*
* @package dokeos.install
* @todo
* - conditional changing of tables. Currently we execute for example
* ALTER TABLE `$dbNameForm`.`cours` instructions without checking wether this is necessary.
* - reorganise code into functions
* @todo use database library
==============================================================================
*/


//load helper functions
require_once("install_upgrade.lib.php");
require_once('../inc/lib/image.lib.php');
$old_file_version = '1.8.5';
$new_file_version = '1.8.6';

//remove memory and time limits as much as possible as this might be a long process...
if(function_exists('ini_set'))
{
	ini_set('memory_limit',-1);
	ini_set('max_execution_time',0);
}else{
	error_log('Update-db script: could not change memory and time limits',0);
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

//check if we come from index.php or update_courses.php - otherwise display error msg
if (defined('DOKEOS_INSTALL') || defined('DOKEOS_COURSE_UPDATE'))
{
	//check if the current Dokeos install is elligible for update
	if (!file_exists('../inc/conf/configuration.php'))
	{
		echo '<b>'.get_lang('Error').' !</b> Dokeos '.implode('|', $updateFromVersion).' '.get_lang('HasNotBeenFound').'.<br><br>
								'.get_lang('PleasGoBackToStep1').'.
							    <p><button type="submit" class="back" name="step1" value="&lt; '.get_lang('Back').'">'.get_lang('Back').'</button></p>
							    </td></tr></table></form></body></html>';

		exit ();
	}
	
	//get_config_param() comes from install_functions.inc.php and 
	//actually gets the param from 
	$_configuration['db_glue'] = get_config_param('dbGlu');

	if ($singleDbForm)
	{
		$_configuration['table_prefix'] = get_config_param('courseTablePrefix');
		$_configuration['main_database'] = get_config_param('mainDbName');
		$_configuration['db_prefix'] = get_config_param('dbNamePrefix');
	}

	$dbScormForm = eregi_replace('[^a-z0-9_-]', '', $dbScormForm);

	if (!empty ($dbPrefixForm) && !ereg('^'.$dbPrefixForm, $dbScormForm))
	{
		$dbScormForm = $dbPrefixForm.$dbScormForm;
	}

	if (empty ($dbScormForm) || $dbScormForm == 'mysql' || $dbScormForm == $dbPrefixForm)
	{
		$dbScormForm = $dbPrefixForm.'scorm';
	}
	$res = @mysql_connect($dbHostForm, $dbUsernameForm, $dbPassForm);

	//if error on connection to the database, show error and exit
	if ($res === false)
	{
		//$no = mysql_errno();
		//$msg = mysql_error();

		//echo '<hr>['.$no.'] - '.$msg.'<hr>';
		echo					get_lang('DBServerDoesntWorkOrLoginPassIsWrong').'.<br /><br />' .
				'				'.get_lang('PleaseCheckTheseValues').' :<br /><br />
							    <b>'.get_lang('DBHost').'</b> : '.$dbHostForm.'<br />
								<b>'.get_lang('DBLogin').'</b> : '.$dbUsernameForm.'<br />
								<b>'.get_lang('DBPassword').'</b> : '.$dbPassForm.'<br /><br />
								'.get_lang('PleaseGoBackToStep').' '. (defined('DOKEOS_INSTALL') ? '3' : '1').'.
							    <p><button type="submit" class="back" name="step'. (defined('DOKEOS_INSTALL') ? '3' : '1').'" value="&lt; '.get_lang('Back').'">'.get_lang('Back').'</button></p>
							    </td></tr></table></form></body></html>';

		exit ();
	}

	// The Dokeos system has not been designed to use special SQL modes that were introduced since MySQL 5
	@mysql_query("set session sql_mode='';");

	$dblistres = mysql_list_dbs();
	$dblist = array();
	while ($row = mysql_fetch_object($dblistres)) {
    	$dblist[] = $row->Database;
	}
	/*
	-----------------------------------------------------------
		Normal upgrade procedure:
		start by updating main, statistic, user databases
	-----------------------------------------------------------
	*/
	//if this script has been included by index.php, not update_courses.php, so
	// that we want to change the main databases as well...
	$only_test = false;
	$log = 0;
	if (defined('DOKEOS_INSTALL')) 
	{
		if ($singleDbForm)
		{
			$dbStatsForm = $dbNameForm;
			$dbScormForm = $dbNameForm;
			$dbUserForm = $dbNameForm;
		}
		/**
		 * Update the databases "pre" migration
		 */
		include ("../lang/english/create_course.inc.php");

		if ($languageForm != 'english')
		{
			//languageForm has been escaped in index.php
			include ("../lang/$languageForm/create_course.inc.php");
		}

		//get the main queries list (m_q_list)
		$m_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','main');
		if(count($m_q_list)>0)
		{
			//now use the $m_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if(strlen($dbNameForm)>40){
				error_log('Database name '.$dbNameForm.' is too long, skipping',0);
			}elseif(!in_array($dbNameForm,$dblist)){
				error_log('Database '.$dbNameForm.' was not found, skipping',0);				
			}else{
				mysql_select_db($dbNameForm);
				foreach($m_q_list as $query){
					if($only_test){
						error_log("mysql_query($dbNameForm,$query)",0);
					}else{
						$res = mysql_query($query);
						if($log)
						{
							error_log("In $dbNameForm, executed: $query",0);
						}
					}
				}
			}
		}
		
		// Filling the access_url_rel_user table with access_url_id by default = 1			
		$query = "SELECT user_id FROM $dbNameForm.user";
		
		$result_users = mysql_query($query);		
		while ($row= mysql_fetch_array($result_users,MYSQL_NUM)) {		
			$user_id = $row[0];	
			$sql="INSERT INTO $dbNameForm.access_url_rel_user SET user_id=$user_id, access_url_id=1";					
			$res = mysql_query($sql);
			//Updating user image
			$query = "SELECT picture_uri FROM $dbNameForm.user WHERE user_id=$user_id";
			$res = mysql_query($query);		
			$picture_uri = mysql_fetch_array($res,MYSQL_NUM);
			$file =  $picture_uri[0];
			$dir = api_get_path(SYS_CODE_PATH).'upload/users/';
			$image_repository = file_exists($dir.$file)? $dir.$file:$dir.$user_id.'/'.$file;
			
			if (!is_dir($dir.$user_id)) {
					$perm = octdec(!empty($perm)?$perm:'0777');							
					@mkdir($dir.$user_id, $perm);					
			}						
						
			if (file_exists($image_repository)) {												
				chmod($dir.$user_id, 0777);
				if (is_dir($dir.$user_id)) {
					$picture_location = $dir.$user_id.'/'.$file;
					$big_picture_location = $dir.$user_id.'/big_'.$file;
					
					$temp = new image($image_repository);						
					
					$picture_infos=getimagesize($image_repository);

					$thumbwidth = 150;
					if (empty($thumbwidth) or $thumbwidth==0) {
						$thumbwidth=150;
					}

					$new_height = ($picture_infos[0] > 0)?round(($thumbwidth/$picture_infos[0])*$picture_infos[1]) : 0;
		
					$temp->resize($thumbwidth,$new_height,0);

					$type=$picture_infos[2];
					
					// original picture
					$big_temp = new image($image_repository);
		
					    switch (!empty($type)) {
						    case 2 : $temp->send_image('JPG',$picture_location);
						    		 $big_temp->send_image('JPG',$big_picture_location);
						    		 break;
						    case 3 : $temp->send_image('PNG',$picture_location);
						    		 $big_temp->send_image('JPG',$big_picture_location);
						    		 break;
						    case 1 : $temp->send_image('GIF',$picture_location);
						    		 $big_temp->send_image('JPG',$big_picture_location);
						    		 break;
					    }	
					if ($image_repository == $dir.$file) {				
					   @unlink($image_repository);	
					}
				} 				
			} 						
		}
		// Filling the access_url_rel_session table with access_url_id by default = 1
		$query = "SELECT id FROM $dbNameForm.session";
		$result = mysql_query($query);
		while ($row= mysql_fetch_array($result,MYSQL_NUM)) {			
			$sql="INSERT INTO $dbNameForm.access_url_rel_session SET session_id=".$row[0].", access_url_id=1";			
			$res = mysql_query($sql);
		}
		
		//Since the parser of the migration DB  does not work for this kind of inserts (HTML) we move it here	
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleCourseTitle\', \'TemplateTitleCourseTitleDescription\', \'coursetitle.gif\', \'
		<head>
		            	{CSS}
		            	<style type="text/css">
		            	.gris_title         	{
		            		color: silver;
		            	}            	
		            	h1
		            	{
		            		text-align: right;
		            	}
						</style>
		  
		            </head>
		            <body>
					<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
					<tbody>
					<tr>			
					<td style="vertical-align: middle; width: 50%;" colspan="1" rowspan="1">
						<h1>TITULUS 1<br>
						<span class="gris_title">TITULUS 2</span><br>
						</h1>
					</td>			
					<td style="width: 50%;">
						<img style="width: 100px; height: 100px;" alt="dokeos logo" src="{COURSE_DIR}images/logo_dokeos.png"></td>
					</tr>
					</tbody>
					</table>
					<p><br>
					<br>
					</p>
					</body>
		\');';
		$res = mysql_query($sql);
		
		/*
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleCheckList\', \'TemplateTitleCheckListDescription\', \'checklist.gif\', \'
		      <head>
			               {CSS}	              
			            </head>
			            <body>
						<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
						<tbody>
						<tr>
						<td style="vertical-align: top; width: 66%;">						
						<h3>Lorem ipsum dolor sit amet</h3>
						<ul>
							<li>consectetur adipisicing elit</li>
							<li>sed do eiusmod tempor incididunt</li>
							<li>ut labore et dolore magna aliqua</li>
						</ul>
						
						<h3>Ut enim ad minim veniam</h3>							
						<ul>
							<li>quis nostrud exercitation ullamco</li>
							<li>laboris nisi ut aliquip ex ea commodo consequat</li>
							<li>Excepteur sint occaecat cupidatat non proident</li>
						</ul>
						
						<h3>Sed ut perspiciatis unde omnis</h3>				
						<ul>
							<li>iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam</li>
							<li>eaque ipsa quae ab illo inventore veritatis</li>
							<li>et quasi architecto beatae vitae dicta sunt explicabo.&nbsp;</li>
						</ul>
						
						</td>
						<td style="background: transparent url({IMG_DIR}postit.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; width: 33%; text-align: center; vertical-align: bottom;">
						<h3>Ut enim ad minima</h3>
						Veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur.<br>
						<h3>
						<img style="width: 180px; height: 144px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_smile.png "><br></h3>
						</td>
						</tr>
						</tbody>
						</table>
						<p><br>
						<br>
						</p>
						</body>
		\');';
		
		$res = mysql_query($sql);
		*/
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleTeacher\', \'TemplateTitleTeacherDescription\', \'yourinstructor.gif\', \'
		<head>
		                   {CSS}
		                   <style type="text/css">	            
			            	.text
			            	{	            	
			            		font-weight: normal;
			            	}
							</style>
		                </head>                    
		                <body>
							<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
							<tbody>
							<tr>
							<td></td>
							<td style="height: 33%;"></td>
							<td></td>
							</tr>
							<tr>
							<td style="width: 25%;"></td>
							<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right; font-weight: bold;" colspan="1" rowspan="1">
							<span class="text">
							<br>
							Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque.</span>
							</td>
							<td style="width: 25%; font-weight: bold;">
							<img style="width: 180px; height: 241px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_case.png "></td>
							</tr>
							</tbody>
							</table>
							<p><br>
							<br>
							</p>
						</body>	
		\');
		';
		$res = mysql_query($sql);
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleLeftList\', \'TemplateTitleListLeftListDescription\', \'leftlist.gif\', \'
		<head>
			           {CSS}
			       </head>		    
				    <body>
						<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
						<tbody>
						<tr>
						<td style="width: 66%;"></td>
						<td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="4">&nbsp;<img style="width: 180px; height: 248px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_reads.png "><br>
						</td>
						</tr>
						<tr align="right">
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">Lorem
						ipsum dolor sit amet.
						</td>
						</tr>
						<tr align="right">
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
						Vivamus
						a quam.&nbsp;<br>
						</td>
						</tr>
						<tr align="right">
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
						Proin
						a est stibulum ante ipsum.</td>
						</tr>
						</tbody>
						</table>
					<p><br>
					<br>
					</p>
					</body> 
		\');';
		$res = mysql_query($sql);
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleLeftRightList\', \'TemplateTitleLeftRightListDescription\', \'leftrightlist.gif\', \'
		
		<head>
			           {CSS}
				    </head>
					<body>
						<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; height: 400px; width: 720px;" border="0" cellpadding="15" cellspacing="6">
						<tbody>
						<tr>
						<td></td>
						<td style="vertical-align: top;" colspan="1" rowspan="4">&nbsp;<img style="width: 180px; height: 294px;" alt="Trainer" src="{COURSE_DIR}images/trainer/trainer_join_hands.png "><br>
						</td>
						<td></td>
						</tr>
						<tr>
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">Lorem
						ipsum dolor sit amet.
						</td>
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
						Convallis
						ut.&nbsp;Cras dui magna.</td>
						</tr>
						<tr>
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
						Vivamus
						a quam.&nbsp;<br>
						</td>
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
						Etiam
						lacinia stibulum ante.<br>
						</td>
						</tr>
						<tr>
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
						Proin
						a est stibulum ante ipsum.</td>
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
						Consectetuer
						adipiscing elit. <br>
						</td>
						</tr>
						</tbody>
						</table>
					<p><br>
					<br>
					</p>
					</body> 
		
		\');';
		$res = mysql_query($sql);
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleRightList\', \'TemplateTitleRightListDescription\', \'rightlist.gif\', \'
			<head>
			           {CSS}
				    </head>
				    <body style="direction: ltr;">
						<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
						<tbody>
						<tr>
						<td style="vertical-align: bottom; width: 50%;" colspan="1" rowspan="4"><img style="width: 300px; height: 199px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_points_right.png"><br>
						</td>
						<td style="width: 50%;"></td>
						</tr>
						<tr>
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
						Convallis
						ut.&nbsp;Cras dui magna.</td>
						</tr>
						<tr>
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
						Etiam
						lacinia.<br>
						</td>
						</tr>
						<tr>
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
						Consectetuer
						adipiscing elit. <br>
						</td>
						</tr>
						</tbody>
						</table>
					<p><br>
					<br>
					</p>
					</body>  
		\');';
		$res = mysql_query($sql);
		
		/*
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleComparison\', \'TemplateTitleComparisonDescription\', \'compare.gif\', \'
		<head>
		            {CSS}        
		            </head>
		            
		            <body>
		            	<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">				
						<tr>
							<td style="height: 10%; width: 33%;"></td> 
							<td style="vertical-align: top; width: 33%;" colspan="1" rowspan="2">&nbsp;<img style="width: 180px; height: 271px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_standing.png "><br>
							</td>
							<td style="height: 10%; width: 33%;"></td>
						</tr>
					<tr>
					<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
					Lorem ipsum dolor sit amet.
					</td>
					<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 33%;">
					Convallis
					ut.&nbsp;Cras dui magna.</td>
					</tr>			
					</body>
		\');';
		$res = mysql_query($sql);
		*/
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleDiagram\', \'TemplateTitleDiagramDescription\', \'diagram.gif\', \'
			<head>
			                   {CSS}
						    </head>
						    
							<body>
							<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
							<tbody>
							<tr>
							<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; height: 33%; width: 33%;">
							<br>
							Etiam
							lacinia stibulum ante.
							Convallis
							ut.&nbsp;Cras dui magna.</td>
							<td colspan="1" rowspan="3">
								<img style="width: 350px; height: 267px;" alt="Alaska chart" src="{COURSE_DIR}images/diagrams/alaska_chart.png "></td>
							</tr>
							<tr>
							<td colspan="1" rowspan="1">
							<img style="width: 300px; height: 199px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_points_right.png "></td>
							</tr>
							<tr>
							</tr>
							</tbody>
							</table>
							<p><br>
							<br>
							</p>
							</body>				    
		\');
		';
		$res = mysql_query($sql);
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleDesc\', \'TemplateTitleCheckListDescription\', \'description.gif\', \'
		<head>
			                   {CSS}
						    </head>
							<body>
								<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
								<tbody>
								<tr>
								<td style="width: 50%; vertical-align: top;">
									<img style="width: 48px; height: 49px; float: left;" alt="01" src="{COURSE_DIR}images/small/01.png " hspace="5"><br>Lorem ipsum dolor sit amet<br><br><br>
									<img style="width: 48px; height: 49px; float: left;" alt="02" src="{COURSE_DIR}images/small/02.png " hspace="5">
									<br>Ut enim ad minim veniam<br><br><br>
									<img style="width: 48px; height: 49px; float: left;" alt="03" src="{COURSE_DIR}images/small/03.png " hspace="5">Duis aute irure dolor in reprehenderit<br><br><br>
									<img style="width: 48px; height: 49px; float: left;" alt="04" src="{COURSE_DIR}images/small/04.png " hspace="5">Neque porro quisquam est</td>
									
								<td style="vertical-align: top; width: 50%; text-align: right;" colspan="1" rowspan="1">
									<img style="width: 300px; height: 291px;" alt="Gearbox" src="{COURSE_DIR}images/diagrams/gearbox.jpg "><br></td>
								</tr><tr></tr>
								</tbody>
								</table>
								<p><br>
								<br>
								</p>
							</body>	
		\');
		';
		$res = mysql_query($sql);
		
		/*
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleObjectives\', \'TemplateTitleObjectivesDescription\', \'courseobjectives.gif\', \'
		<head>
			               {CSS}                    
					    </head>	
					    
					    <body>
							<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
							<tbody>
							<tr>
							<td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="2">
							<img style="width: 180px; height: 271px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_chair.png "><br>
							</td>
							<td style="height: 10%; width: 66%;"></td>
							</tr>
							<tr>
							<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 66%;">
							<h3>Lorem ipsum dolor sit amet</h3>
							<ul>
							<li>consectetur adipisicing elit</li>
							<li>sed do eiusmod tempor incididunt</li>
							<li>ut labore et dolore magna aliqua</li>
							</ul>
							<h3>Ut enim ad minim veniam</h3>
							<ul>
							<li>quis nostrud exercitation ullamco</li>
							<li>laboris nisi ut aliquip ex ea commodo consequat</li>
							<li>Excepteur sint occaecat cupidatat non proident</li>
							</ul>
							</td>
							</tr>
							</tbody>
							</table>
						<p><br>
						<br>
						</p>
						</body>		
		\');';
		$res = mysql_query($sql);
		*/
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleCycle\', \'TemplateTitleCycleDescription\', \'cyclechart.gif\', \'
		<head>
			               {CSS}
			               <style>
			               .title
			               {
			               	color: white; font-weight: bold;
			               }
			               </style>                    
					    </head>
					    	
					    	    
					    <body>
						<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="8" cellspacing="6">
						<tbody>
						<tr>
							<td style="text-align: center; vertical-align: bottom; height: 10%;" colspan="3" rowspan="1">
								<img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/top_arrow.png ">
							</td>				
						</tr>			
						<tr>
							<td style="height: 5%; width: 45%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
								<span class="title">Lorem ipsum</span>
							</td>
								
							<td style="height: 5%; width: 10%;"></td>					
							<td style="height: 5%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
								<span class="title">Sed ut perspiciatis</span>
							</td>
						</tr>
							<tr>
								<td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
									<ul>
										<li>dolor sit amet</li>
										<li>consectetur adipisicing elit</li>
										<li>sed do eiusmod tempor&nbsp;</li>
										<li>adipisci velit, sed quia non numquam</li>
										<li>eius modi tempora incidunt ut labore et dolore magnam</li>
									</ul>
						</td>			
						<td style="width: 10%;"></td>
						<td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
							<ul>
							<li>ut enim ad minim veniam</li>
							<li>quis nostrud exercitation</li><li>ullamco laboris nisi ut</li>
							<li> Quis autem vel eum iure reprehenderit qui in ea</li>
							<li>voluptate velit esse quam nihil molestiae consequatur,</li>
							</ul>
							</td>
							</tr>
							<tr align="center">
							<td style="height: 10%; vertical-align: top;" colspan="3" rowspan="1">
							<img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/bottom_arrow.png ">&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;
						</td>
						</tr>			
						</tbody>
						</table>
						<p><br>
						<br>
						</p>
						</body>	
		\');';
		$res = mysql_query($sql);
		
		/*
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleLearnerWonder\', \'TemplateTitleLearnerWonderDescription\', \'learnerwonder.gif\', \'
		<head>
		               {CSS}                    
				    </head>
				    
				    <body>
						<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
						<tbody>
						<tr>
						<td style="width: 33%;" colspan="1" rowspan="4">
							<img style="width: 120px; height: 348px;" alt="learner wonders" src="{COURSE_DIR}images/silhouette.png "><br>
						</td>
						<td style="width: 66%;"></td>
						</tr>
						<tr align="center">
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
						Convallis
						ut.&nbsp;Cras dui magna.</td>
						</tr>
						<tr align="center">
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
						Etiam
						lacinia stibulum ante.<br>
						</td>
						</tr>
						<tr align="center">
						<td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
						Consectetuer
						adipiscing elit. <br>
						</td>
						</tr>
						</tbody>
						</table>
					<p><br>
					<br>
					</p>
					</body>
		\');
		';
		$res = mysql_query($sql);
		*/
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleTimeline\', \'TemplateTitleTimelineDescription\', \'phasetimeline.gif\', \'
		<head>
		               {CSS} 
						<style>
						.title
						{				
							font-weight: bold; text-align: center; 	
						}			
						</style>                
				    </head>	
				    
				    <body>
						<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="8" cellspacing="5">
						<tbody>
						<tr class="title">				
							<td style="vertical-align: top; height: 3%; background-color: rgb(224, 224, 224);">Lorem ipsum</td>
							<td style="height: 3%;"></td>
							<td style="vertical-align: top; height: 3%; background-color: rgb(237, 237, 237);">Perspiciatis</td>
							<td style="height: 3%;"></td>
							<td style="vertical-align: top; height: 3%; background-color: rgb(245, 245, 245);">Nemo enim</td>
						</tr>
						
						<tr>
							<td style="vertical-align: top; width: 30%; background-color: rgb(224, 224, 224);">
								<ul>
								<li>dolor sit amet</li>
								<li>consectetur</li>
								<li>adipisicing elit</li>
							</ul>
							<br>
							</td>
							<td>
								<img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
							</td>
							
							<td style="vertical-align: top; width: 30%; background-color: rgb(237, 237, 237);">
								<ul>
									<li>ut labore</li>
									<li>et dolore</li>
									<li>magni dolores</li>
								</ul>
							</td>
							<td>
								<img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
							</td>
							
							<td style="vertical-align: top; background-color: rgb(245, 245, 245); width: 30%;">
								<ul>
									<li>neque porro</li>
									<li>quisquam est</li>
									<li>qui dolorem&nbsp;&nbsp;</li>
								</ul>
								<br><br>
							</td>
						</tr>
						</tbody>
						</table>
					<p><br>
					<br>
					</p>
					</body>
		\');
		';
		$res = mysql_query($sql);
		
		/*
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleStopAndThink\', \'TemplateTitleStopAndThinkDescription\', \'stopthink.gif\', \'
		<head>
		               {CSS}                    
				    </head>
				    <body>
						<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
						<tbody>
						<tr>
						<td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="2">
							<img style="width: 180px; height: 169px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_staring.png ">
						<br>
						</td>
						<td style="height: 10%; width: 66%;"></td>
						</tr>
						<tr>
						<td style="background: transparent url({IMG_DIR}postit.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; width: 66%; vertical-align: middle; text-align: center;">
							<h3>Attentio sectetur adipisicing elit</h3>
							<ul>
								<li>sed do eiusmod tempor incididunt</li>
								<li>ut labore et dolore magna aliqua</li>
								<li>quis nostrud exercitation ullamco</li>
							</ul><br></td>
						</tr>
						</tbody>
						</table>
					<p><br>
					<br>
					</p>
					</body>
		\');';
		$res = mysql_query($sql);
		*/
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleTable\', \'TemplateTitleCheckListDescription\', \'table.gif\', \'
		<head>
		                   {CSS}
		                   <style type="text/css">
						.title
						{
							font-weight: bold; text-align: center;
						}
						
						.items
						{
							text-align: right;
						}	
		  				
		
							</style>
		  
					    </head>
					    <body>
					    <br />
					   <h2>A table</h2>
						<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px;" border="1" cellpadding="5" cellspacing="0">
						<tbody>
						<tr class="title">
							<td>City</td>
							<td>2005</td>
							<td>2006</td>
							<td>2007</td>
							<td>2008</td>
						</tr>
						<tr class="items">
							<td>Lima</td>
							<td>10,40</td>
							<td>8,95</td>
							<td>9,19</td>
							<td>9,76</td>
						</tr>
						<tr class="items">
						<td>New York</td>
							<td>18,39</td>
							<td>17,52</td>
							<td>16,57</td>
							<td>16,60</td>
						</tr>
						<tr class="items">
						<td>Barcelona</td>
							<td>0,10</td>
							<td>0,10</td>
							<td>0,05</td>
							<td>0,05</td>
						</tr>
						<tr class="items">
						<td>Paris</td>
							<td>3,38</td>
							<td >3,63</td>
							<td>3,63</td>
							<td>3,54</td>
						</tr>
						</tbody>
						</table>
						<br>
						</body>
		\');';
		$res = mysql_query($sql);
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleAudio\', \'TemplateTitleAudioDescription\', \'audiocomment.gif\', \'
		<head>
		               {CSS}                    
				    </head>
		                   <body>
							<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
							<tbody>
							<tr>
							<td>					
							<div align="center">
							<span style="text-align: center;">
								<embed  type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" width="300" height="20" bgcolor="#FFFFFF" src="{REL_PATH}main/inc/lib/mediaplayer/player.swf" allowfullscreen="false" allowscriptaccess="always" flashvars="file={COURSE_DIR}audio/ListeningComprehension.mp3&amp;autostart=true"></embed>
		                    </span></div>     
							
							<br>
							</td>
							<td colspan="1" rowspan="3"><br>
								<img style="width: 300px; height: 341px; float: right;" alt="image" src="{COURSE_DIR}images/diagrams/head_olfactory_nerve.png "><br></td>
							</tr>
							<tr>
							<td colspan="1" rowspan="1">
								<img style="width: 180px; height: 271px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_glasses.png"><br></td>
							</tr>
							<tr>
							</tr>
							</tbody>
							</table>
							<p><br>
							<br>
							</p>
							</body>	
		\');';
		$res = mysql_query($sql);
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleVideo\', \'TemplateTitleVideoDescription\', \'video.gif\', \'
		<head>
		            	{CSS}
					</head>
					
					<body>
					<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
					<tbody>
					<tr>
					<td style="width: 50%; vertical-align: top;">

					<div style="text-align: center;" id="player810625-parent">
					<div style="border-style: none; overflow: hidden; width: 320px; height: 240px; background-color: rgb(220, 220, 220);">

						<div id="player810625">
							<div id="player810625-config" style="overflow: hidden; display: none; visibility: hidden; width: 0px; height: 0px;">url={REL_PATH}main/default_course_document/video/flv/example.flv width=320 height=240 loop=false play=false downloadable=false fullscreen=true displayNavigation=true displayDigits=true align=left dispPlaylist=none playlistThumbs=false</div>
						</div>

						<embed
							type="application/x-shockwave-flash"
							src="{REL_PATH}main/inc/lib/mediaplayer/player.swf"
							width="320"
							height="240"
							id="single"
							name="single"
							quality="high"
							allowfullscreen="true"
							flashvars="width=320&height=240&autostart=false&file={REL_PATH}main/default_course_document/video/flv/example.flv&repeat=false&image=&showdownload=false&link={REL_PATH}main/default_course_document/video/flv/example.flv&showdigits=true&shownavigation=true&logo="
						/>

					</div>
					</div>

					</td>
					<td style="background: transparent url({IMG_DIR}faded_grey.png) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 50%;">
					<h3><br>
					</h3>
					<h3>Lorem ipsum dolor sit amet</h3>
						<ul>
						<li>consectetur adipisicing elit</li>
						<li>sed do eiusmod tempor incididunt</li>
						<li>ut labore et dolore magna aliqua</li>
						</ul>
					<h3>Ut enim ad minim veniam</h3>
						<ul>
						<li>quis nostrud exercitation ullamco</li>
						<li>laboris nisi ut aliquip ex ea commodo consequat</li>
						<li>Excepteur sint occaecat cupidatat non proident</li>
						</ul>
					</td>
					</tr>
					</tbody>
					</table>
					<p><br>
					<br>
					</p>
					 <style type="text/css">body{}</style><!-- to fix a strange bug appearing with firefox when editing this template -->
					</body>
		\'); ';
		$res = mysql_query($sql);
		
		$sql = 'INSERT INTO '.$dbNameForm.'.system_template (title, comment, image, content) VALUES
		(\'TemplateTitleFlash\', \'TemplateTitleFlashDescription\', \'flash.gif\', \'
		<head>
		               {CSS}                    
				    </head>				    
				    <body>
				    <center>
						<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 100%; height: 400px;" border="0" cellpadding="15" cellspacing="6">
						<tbody>
							<tr>
							<td align="center">
							<embed width="700" height="300" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="{COURSE_DIR}flash/SpinEchoSequence.swf" play="true" loop="true" menu="true"></embed></span><br /> 				          													
							</td>
							</tr>
						</tbody>
						</table>
						<p><br>
						<br>
						</p>
					</center>
					</body>
		\'); ';
		$res = mysql_query($sql);
		
        // Check if course_module exists, as it was not installed in Dokeos 1.8.5 because of a broken query, and $sql = 'INSERT it if necessary
        $query = "SELECT * FROM $dbNameForm.course_module";
        $result = mysql_query($query);
        if ($result === false) {
        	//the course_module table doesn't exist, create it
            $sql = "CREATE TABLE $dbNameForm.course_module (
                      id int unsigned NOT NULL auto_increment,
                      name varchar(100) NOT NULL,
                      link varchar(255) NOT NULL,
                      image varchar(100) default NULL,
                      `row` int unsigned NOT NULL default '0',
                      `column` int unsigned NOT NULL default '0',
                      position varchar(20) NOT NULL default 'basic',
                      PRIMARY KEY  (id)
                    )
                    ";
            $result = mysql_query($sql);
            if ($result !== false) {
            	$sql = "INSERT INTO $dbNameForm.course_module (name, link, image, `row`,`column`, position) VALUES
                    ('calendar_event','calendar/agenda.php','agenda.gif',1,1,'basic'),
                    ('link','link/link.php','links.gif',4,1,'basic'),
                    ('document','document/document.php','documents.gif',3,1,'basic'),
                    ('student_publication','work/work.php','works.gif',3,2,'basic'),
                    ('announcement','announcements/announcements.php','valves.gif',2,1,'basic'),
                    ('user','user/user.php','members.gif',2,3,'basic'),
                    ('forum','forum/index.php','forum.gif',1,2,'basic'),
                    ('quiz','exercice/exercice.php','quiz.gif',2,2,'basic'),
                    ('group','group/group.php','group.gif',3,3,'basic'),
                    ('course_description','course_description/','info.gif',1,3,'basic'),
                    ('chat','chat/chat.php','chat.gif',0,0,'external'),
                    ('dropbox','dropbox/index.php','dropbox.gif',4,2,'basic'),
                    ('tracking','tracking/courseLog.php','statistics.gif',1,3,'courseadmin'),
                    ('homepage_link','link/link.php?action=addlink','npage.gif',1,1,'courseadmin'),
                    ('course_setting','course_info/infocours.php','reference.gif',1,1,'courseadmin'),
                    ('External','','external.gif',0,0,'external'),
                    ('AddedLearnpath','','scormbuilder.gif',0,0,'external'),
                    ('conference','conference/index.php?type=conference','conf.gif',0,0,'external'),
                    ('conference','conference/index.php?type=classroom','conf.gif',0,0,'external'),
                    ('learnpath','newscorm/lp_controller.php','scorm.gif',5,1,'basic'),
                    ('blog','blog/blog.php','blog.gif',1,2,'basic'),
                    ('blog_management','blog/blog_admin.php','blog_admin.gif',1,2,'courseadmin'),
                    ('course_maintenance','course_info/maintenance.php','backup.gif',2,3,'courseadmin'),
                    ('survey','survey/survey_list.php','survey.gif',2,1,'basic'),
                    ('wiki','wiki/index.php','wiki.gif',2,3,'basic'),
                    ('gradebook','gradebook/index.php','gradebook.gif',2,2,'basic'),
                    ('glossary','glossary/index.php','glossary.gif',2,1,'basic'),
                    ('notebook','notebook/index.php','notebook.gif',2,1,'basic')";
                $res = mysql_query($sql);
            }
        }

		
		//get the stats queries list (s_q_list)
		$s_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','stats');
	
		if(count($s_q_list)>0)
		{
			//now use the $s_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if(strlen($dbStatsForm)>40){
				error_log('Database name '.$dbStatsForm.' is too long, skipping',0);
			}elseif(!in_array($dbStatsForm,$dblist)){
				error_log('Database '.$dbStatsForm.' was not found, skipping',0);				
			}else{
				mysql_select_db($dbStatsForm);
				foreach($s_q_list as $query){
					if($only_test){
						error_log("mysql_query($dbStatsForm,$query)",0);
					}else{
						$res = mysql_query($query);
						if($log)
						{
							error_log("In $dbStatsForm, executed: $query",0);
						}
					}
				}
			}
		}
		//get the user queries list (u_q_list)
		$u_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','user');
		if(count($u_q_list)>0)
		{
			//now use the $u_q_list
			/**
			 * We connect to the right DB first to make sure we can use the queries
			 * without a database name
			 */
			if(strlen($dbUserForm)>40){
				error_log('Database name '.$dbUserForm.' is too long, skipping',0);
			}elseif(!in_array($dbUserForm,$dblist)){
				error_log('Database '.$dbUserForm.' was not found, skipping',0);				
			}else{
				mysql_select_db($dbUserForm);
				foreach($u_q_list as $query){
					if($only_test){
						error_log("mysql_query($dbUserForm,$query)",0);
						error_log("In $dbUserForm, executed: $query",0);
					}else{
						$res = mysql_query($query);
					}
				}
			}
		}
		//the SCORM database doesn't need a change in the pre-migrate part - ignore
	}
	

	/*
	-----------------------------------------------------------
		Update the Dokeos course databases
		this part can be accessed in two ways:
		- from the normal upgrade process
		- from the script update_courses.php,
		which is used to upgrade more than MAX_COURSE_TRANSFER courses

		Every time this script is accessed, only
		MAX_COURSE_TRANSFER courses are upgraded.
	-----------------------------------------------------------
	*/

	$prefix = ''; 
	if ($singleDbForm)
	{
		$prefix =  get_config_param ('table_prefix');			
	}
	
	//get the courses databases queries list (c_q_list)
	$c_q_list = get_sql_file_contents('migrate-db-'.$old_file_version.'-'.$new_file_version.'-pre.sql','course');

	if(count($c_q_list)>0)
	{
		//get the courses list
		if(strlen($dbNameForm)>40)
		{
			error_log('Database name '.$dbNameForm.' is too long, skipping',0);
		}
		elseif(!in_array($dbNameForm,$dblist))
		{
			error_log('Database '.$dbNameForm.' was not found, skipping',0);				
		}
		else
		{
			mysql_select_db($dbNameForm);
			$res = mysql_query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL ORDER BY code");

			if($res===false){die('Error while querying the courses list in update_db.inc.php');}

			if(mysql_num_rows($res)>0)
			{
				$i=0;
                $list = array();
				//while( ($i < MAX_COURSE_TRANSFER) && ($row = mysql_fetch_array($res)))
				while($row = mysql_fetch_array($res))
				{
					$list[] = $row;
					$i++;
				}
				foreach($list as $row_course)
				{
					//now use the $c_q_list
					/**
					 * We connect to the right DB first to make sure we can use the queries
					 * without a database name
					 */
					if (!$singleDbForm) //otherwise just use the main one
					{									
						mysql_select_db($row_course['db_name']);
					}
                    
					foreach($c_q_list as $query)
					{
						if ($singleDbForm) //otherwise just use the main one
						{
							$query = preg_replace('/^(UPDATE|ALTER TABLE|CREATE TABLE|DROP TABLE|INSERT INTO|DELETE FROM)\s+(\w*)(.*)$/',"$1 $prefix{$row_course['db_name']}_$2$3",$query);												
						}
						
						if($only_test)
						{
							error_log("mysql_query(".$row_course['db_name'].",$query)",0);
						}
						else
						{
							$res = mysql_query($query);						
							if($log)
							{
								error_log("In ".$row_course['db_name'].", executed: $query",0);
							}
						}
					}
                    
                    $t_d = $row_course['db_name'].".document";
                    $t_ip = $row_course['db_name'].".item_property";
                    
                    if($singleDbForm)
                    {
                        $t_d = "$prefix{$row_course['db_name']}_document";
                        $t_ip = "$prefix{$row_course['db_name']}_item_property";
                    }
                    // shared documents folder   
                    $query = "INSERT INTO $t_d (path,title,filetype,size) VALUES ('/shared_folder','".get_lang('SharedDocumentsDirectory')."','folder','0')";
                    $myres = mysql_query($query);
                    if ($myres !== false) {
                    	$doc_id = mysql_insert_id();
                        $query = "INSERT INTO $t_ip (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$doc_id,'FolderAdded',1,0,NULL,1)";
                        $myres = mysql_query($query);
                    }
				}
			}
		}
	}
}
else
{
	echo 'You are not allowed here !';
}
?>