<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	@author Thomas Depraetere
*	@author Hugues Peeters
*	@author Christophe Gesche
*	@author Sebastien Piraux
*	@author Toon Keppens (Vi-Host.net)
*
*	@package dokeos.tracking
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$pathopen = isset($_REQUEST['pathopen']) ? $_REQUEST['pathopen'] : null;
// name of the language file that needs to be included 

$language_file[] = 'tracking';
$language_file[] = 'scorm';

include('../inc/global.inc.php');
//includes for SCORM and LP
require_once('../newscorm/learnpath.class.php');
require_once('../newscorm/learnpathItem.class.php');
require_once('../newscorm/scorm.class.php');
require_once('../newscorm/scormItem.class.php');

// charset determination
if ($_GET['scormcontopen'])
{
	$tbl_lp = Database::get_course_table('lp');
	$contopen = (int) $_GET['scormcontopen'];
	$sql = "SELECT default_encoding FROM $tbl_lp WHERE id = ".$contopen;
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$row = Database::fetch_array($res);
	$lp_charset = $row['default_encoding'];
	//header('Content-Type: text/html; charset='. $row['default_encoding']);
}

$htmlHeadXtra[] = "<style type='text/css'>
/*<![CDATA[*/
.secLine {background-color : #E6E6E6;}
.content {padding-left : 15px;padding-right : 15px; }
.specialLink{color : #0000FF;}
/*]]>*/
</style>
<style media='print' type='text/css'>

</style>";


/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
// regroup table names for maintenance purpose
$TABLETRACK_ACCESS      = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
$TABLETRACK_LINKS       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
$TABLETRACK_DOWNLOADS   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
$TABLETRACK_ACCESS_2    = Database::get_statistic_table("track_e_access");
$TABLECOURSUSER	        = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE	        = Database::get_main_table(TABLE_MAIN_COURSE);
$TABLECOURSE_LINKS      = Database::get_course_table(TABLE_LINK);
$table_user = Database::get_main_table(TABLE_MAIN_USER);

//$table_scormdata = Database::get_scorm_table(TABLE_SCORM_SCO_DATA);
//$table_scormmain = Database::get_scorm_table(TABLE_SCORM_MAIN);
//$tbl_learnpath_main = Database::get_course_table(TABLE_LEARNPATH_MAIN);
//$tbl_learnpath_item = Database::get_course_table(TABLE_LEARNPATH_ITEM);
//$tbl_learnpath_chapter = Database::get_course_table(TABLE_LEARNPATH_CHAPTER);

$tbl_learnpath_main = Database::get_course_table('lp');
$tbl_learnpath_item = Database::get_course_table('lp_item');
$tbl_learnpath_view = Database::get_course_table('lp_view');
$tbl_learnpath_item_view = Database::get_course_table('lp_item_view');

$view = $_REQUEST['view'];

if($view=="0000001") $nameTools=get_lang('SynthesisView');
if($view=="1000000") $nameTools=get_lang('CourseStats');
if($view=="0100000") $nameTools=get_lang('CourseAccess');
if($view=="0010000") $nameTools=get_lang('ToolsAccess');
if($view=="0001000") $nameTools=get_lang('LinksAccess');
if($view=="0000100") $nameTools=get_lang('DocumentsAccess');
if($view=="00000010") $nameTools=get_lang('ScormAccess');

$interbreadcrumb[] = array ("url" => $_SERVER['PHP_SELF']."?view=0000000", "name" => get_lang('ToolName'));

Display::display_header($nameTools, "Tracking");
include(api_get_path(LIBRARY_PATH)."statsUtils.lib.inc.php");
include("../resourcelinker/resourcelinker.inc.php");

$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin;

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
?>
<br>
<h3><?php echo get_lang('StatsOfCourse')." : ".$_course['official_code']; ?></h3>
<p><?php echo get_lang('SeeIndividualTracking'); ?></p>

<?php
// check if uid is prof of this group

if($is_allowedToTrack && $_configuration['tracking_enabled'])
{
    // show all : view must be equal to the sum of all view values (1024+512+...+64)
    // show none : less than the tiniest value
    /*echo "<div>
            [<a href='".$_SERVER['PHP_SELF']."?view=1111111'>".get_lang('ShowAll')."</a>]
            [<a href='".$_SERVER['PHP_SELF']."?view=0000000'>".get_lang('ShowNone')."</a>]
        </div><br>
    ";*/

    if(!isset($view)) $view ="0000000";
	
	
	if($view =="0000000"){
		
		//Synthesis view
		echo "<div class='admin_section'>
			<h4>
				<img src='../img/synthese_view.gif' align='absbottom'>&nbsp;<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".$_SERVER['PHP_SELF']."?view=0000001' class='specialLink'>".get_lang('SynthesisView')."</a>
			</h4>
		 </div>";
		 
		 //Course Stats
		 echo "<div class='admin_section'>
			<h4>
				<img src='../img/stats_access.gif' align='absbottom'>&nbsp;<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".$_SERVER['PHP_SELF']."?view=1000000' class='specialLink'>".get_lang('CourseStats')."</a>
			</h4>
		 </div>";
		 
		 //Access to this course
		 echo "<div class='admin_section'>
			<h4>
				<img src='../img/course.gif' align='absbottom'>&nbsp;<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".$_SERVER['PHP_SELF']."?view=0100000' class='specialLink'>".get_lang('CourseAccess')."</a>
			</h4>
		 </div>";
		 
		 //Access to tools
		 echo "<div class='admin_section'>
			<h4>
				<img src='../img/acces_tool.gif' align='absbottom'>&nbsp;<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".$_SERVER['PHP_SELF']."?view=0010000' class='specialLink'>".get_lang('ToolsAccess')."</a>
			</h4>
		 </div>";
		 
		 //Links
		 echo "<div class='admin_section'>
			<h4>
				<img src='../img/file_html.gif' align='absbottom'>&nbsp;<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".$_SERVER['PHP_SELF']."?view=0001000' class='specialLink'>".get_lang('LinksAccess')."</a>
			</h4>
		 </div>";
		 
		 //Documents
		 echo "<div class='admin_section'>
			<h4>
				<img src='../img/documents.gif' align='absbottom'>&nbsp;<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".$_SERVER['PHP_SELF']."?view=0000100' class='specialLink'>".get_lang('DocumentsAccess')."</a>
			</h4>
		 </div>";
		 
		 //Learning path - Scorm format courses
		 echo "<div class='admin_section'>
			<h4>
				<img src='../img/scormbuilder.gif' align='absbottom'>&nbsp;<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".$_SERVER['PHP_SELF']."?view=0000010' class='specialLink'>".get_lang('ScormAccess')."</a>
			</h4>
		 </div>";
		 
		
	}
	
/***************************************************************************
 *
 *		Reporting
 *
 ***************************************************************************/
	
	$tempView = $view;
    if($view[6] == '1'){
    	
    	$tempView[6] = '0';
    	
        //--------------------------------BEGIN users in this course
        $sql = "SELECT $TABLECOURSUSER.`user_id`, $table_user.`lastname`, $table_user.`firstname`
                    FROM $TABLECOURSUSER, $table_user
                    WHERE $TABLECOURSUSER.course_code = '".$_cid."' AND $TABLECOURSUSER.`user_id` = $table_user.`user_id`
                    ORDER BY $table_user.`lastname`";
        $results = getManyResults3Col($sql);

        //BUGFIX: get visual code instead of real course code. Scormpaths use the visual code... (should be fixed in future versions)
        $sql = "SELECT visual_code FROM $TABLECOURSE WHERE code = '".$_cid."'";
        $_course['visual_code'] = getOneResult($sql);

        echo "<a href='courseLogCSV.php?".api_get_cidreq()."&uInfo=".$_GET['uInfo']."&view=0000001'><img src=\"../img/excel.gif\" align=\"absmiddle\">&nbsp;".get_lang('ExportAsCSV')."</a>";
        if (is_array($results))
        {
        	
        	echo '<table class="data_table">';
			echo "<tr>
					<td class='secLine'>".get_lang('Name')."</td>
					<td class='secLine'>".get_lang('FirstAccess')."</td>
					<td class='secLine'>".get_lang('LastAccess')."</td>
					<td class='secLine'>%&nbsp;".get_lang('Visited')."</td>
	        	  </tr>";
        	
            for($j = 0 ; $j < count($results) ; $j++)
            {


            	//--------------------------------BEGIN % visited
            	// sum of all items (= multiple learningpaths + SCORM imported paths)
            	$sql = "SELECT COUNT(DISTINCT(iv.lp_item_id)) " .
            			"FROM $tbl_learnpath_item_view iv " .
            			"INNER JOIN $tbl_learnpath_view v ON iv.lp_view_id = v.id " .
            			"WHERE v.user_id = ".$results[$j][0];
            	$total_lpath_items = getOneResult($sql);

            	// sum of all completed items (= multiple learningpaths + SCORM imported paths)
            	$sql = "SELECT COUNT(DISTINCT(iv.lp_item_id)) " .
            			"FROM $tbl_learnpath_item_view iv " .
            			"INNER JOIN $tbl_learnpath_view v ON iv.lp_view_id = v.id " .
            			"WHERE v.user_id = ".$results[$j][0]." " .
            				"AND (status = 'completed' OR status='passed')";
            	$total_lpath_items_completed = getOneResult($sql);

            	// calculation & bgcolor setting
            	$lpath_pct_completed = empty($total_lpath_items) ? "-" : round(($total_lpath_items_completed / $total_lpath_items) * 100);
               	//--------------------------------END % visited



            	//--------------------------------BEGIN first/last access
            	// first access
            	$sql = "SELECT access_date FROM $TABLETRACK_ACCESS_2 WHERE `access_user_id` = '".$results[$j][0]."' AND `access_cours_code` = '".$_course['official_code']."' AND `access_tool` = 'learnpath' ORDER BY access_id ASC LIMIT 1";
            	$first_access = getOneResult($sql);
            	$first_access = empty($first_access) ? "-" : date('d.m.y',strtotime($first_access));

            	// last access
            	$sql = "SELECT access_date FROM $TABLETRACK_ACCESS WHERE `access_user_id` = '".$results[$j][0]."' AND `access_cours_code` = '".$_course['official_code']."' AND `access_tool` = 'learnpath'";
            	$last_access = getOneResult($sql);
            	$last_access = empty($last_access) ? "-" : date('d.m.y',strtotime($last_access));
            	//--------------------------------END first/last access



            	//--------------------------------BEGIN presentation of data
				echo "		<tr>";
				echo "			<td>".$results[$j][1]." ".$results[$j][2]."</td>";
				echo "			<td>".$first_access."</td>";
				echo "			<td>".$last_access."</td>";
				echo "			<td align='center'>".$lpath_pct_completed."</td>";
				echo "		</tr>";
				//--------------------------------END presentation of data
								
            }
            echo "</table>";

        }
        else
        {
            echo "<div class='secLine' align='center'>".get_lang('NoResult')."</div>";
        }
		 
    }
    
    
    
/***************************************************************************
 *
 *		Main
 *
 ***************************************************************************/

    $tempView = $view;
    if($view[0] == '1')
    {
        $tempView[0] = '0';
        
        $sql = "SELECT count(*)
                    FROM $TABLECOURSUSER
                    WHERE course_code = '".$_cid."'";
        $count = getOneResult($sql);
        
        echo "<a href='courseLogCSV.php?".api_get_cidreq()."&uInfo=".$_GET['uInfo']."&view=1000000'>".get_lang('ExportAsCSV')."</a>";        
        echo '<table class="data_table">';
        
        echo "<tr><td class='secLine'>".get_lang('CountUsers')." : ".$count."</td></tr>";
        
        echo '</table>';
        
        
    }   
    

/***************************************************************************
*
*		Access to this course
*
***************************************************************************/
    $tempView = $view;
    if($view[1] == '1'){
    	
        $tempView[1] = '0';
                
        echo "<a href='courseLogCSV.php?".api_get_cidreq()."&uInfo=".$_GET['uInfo']."&view=0100000'>".get_lang('ExportAsCSV')."</a>";                
        echo '<table class="data_table">';
        
        echo "<tr><td class='secLine'>".get_lang('ConnectionsToThisCourse')."</td></tr>";
        
        //Total
        $sql = "SELECT count(*)
                    FROM $TABLETRACK_ACCESS
                    WHERE access_cours_code = '".$_cid."'
                        AND access_tool IS NULL";
        $count = getOneResult($sql);
        
        echo "
            <tr>
                <td valign='top'>"
                .get_lang('CountToolAccess')." : ".$count."
                </td>
            </tr>
        ";
        
        // last 31 days
        $sql = "SELECT count(*)
                    FROM $TABLETRACK_ACCESS
                    WHERE `access_cours_code` = '$_cid'
                        AND (access_date > DATE_ADD(CURDATE(), INTERVAL -31 DAY))
                        AND access_tool IS NULL";
        $count = getOneResult($sql);
        
        echo "
            <tr>
                <td valign='top'>
                ".get_lang('Last31days')." : ".$count."
                </td>
            </tr>
        ";
        
        // last 7 days
        $sql = "SELECT count(*)
                    FROM $TABLETRACK_ACCESS
                    WHERE `access_cours_code` = '$_cid'
                        AND (access_date > DATE_ADD(CURDATE(), INTERVAL -7 DAY))
                        AND access_tool IS NULL";
        $count = getOneResult($sql);
        
        echo "
            <tr>
                <td valign='top'>
                ".get_lang('Last7days')." : ".$count."
                </td>
            </tr>
        ";
        // today
        $sql = "SELECT count(*)
                    FROM $TABLETRACK_ACCESS
                    WHERE `access_cours_code` = '$_cid'
                        AND ( access_date > CURDATE() )
                        AND access_tool IS NULL";
        $count = getOneResult($sql);
        echo "
            <tr>
                <td valign='top'>
                ".get_lang('Thisday')." : ".$count."
                </td>
            </tr>
        ";
        
        //-- view details of traffic
        echo "
            <tr>
                <td valign='top'>
                <a href='course_access_details.php'>".get_lang('TrafficDetails')."</a>
                </td>
            </tr>
        ";
        
		echo '</table>';		
		
    }
    
    
    
/***************************************************************************
 *
 *		Tools
 *
 ***************************************************************************/
	$tempView = $view;
	if($view[2] == '1'){
		
	    $tempView[2] = '0';
	    echo "<a href='courseLogCSV.php?".api_get_cidreq()."&uInfo=".$_GET['uInfo']."&view=0010000'>".get_lang('ExportAsCSV')."</a>";
	    echo '<table class="data_table">';
	    
	    echo "<tr>
                <td class='secLine'>".get_lang('ToolTitleToolnameColumn')."</td>
                <td class='secLine'>".get_lang('ToolTitleUsersColumn')."                </td>
                <td class='secLine'>".get_lang('ToolTitleCountColumn')."                </td>
              </tr>";
              
		$sql = "SELECT `access_tool`, COUNT(DISTINCT `access_user_id`),count( `access_tool` )
                FROM $TABLETRACK_ACCESS
                WHERE `access_tool` IS NOT NULL
                    AND `access_cours_code` = '$_cid'
                GROUP BY `access_tool`";
                
        $results = getManyResults3Col($sql);
        
        if (is_array($results))
        {
            for($j = 0 ; $j < count($results) ; $j++)
            {
                echo "<tr>";
                echo "<td class='content'><a href='toolaccess_details.php?tool=".$results[$j][0]."'>".get_lang(ucfirst($results[$j][0]))."</a></td>";
                echo "<td align='left' class='content'>".$results[$j][1]."</td>";
                echo "<td align='left' class='content'>".$results[$j][2]."</td>";
                echo"</tr>";
            }

        }
        else
        {
            echo "<tr>";
            echo "<td colspan='3'><center>".get_lang('NoResult')."</center></td>";
            echo"</tr>";
        }
	    
	    echo '</table>';
	    
	}
    
    
/***************************************************************************
*
*		Links
*
***************************************************************************/

    $tempView = $view;
    if($view[3] == '1'){
    	
        $tempView[3] = '0';
        
        $sql = "SELECT `cl`.`title`, `cl`.`url`,count(DISTINCT `sl`.`links_user_id`), count(`cl`.`title`)
                    FROM $TABLETRACK_LINKS AS sl, $TABLECOURSE_LINKS AS cl
                    WHERE `sl`.`links_link_id` = `cl`.`id`
                        AND `sl`.`links_cours_id` = '$_cid'
                    GROUP BY `cl`.`title`, `cl`.`url`";
                    
		$results = getManyResultsXCol($sql,4);
		
	    echo "<a href='courseLogCSV.php?".api_get_cidreq()."&uInfo=".$_GET['uInfo']."&view=0001000'>".get_lang('ExportAsCSV')."</a>";		
		echo '<table class="data_table">';
		
		echo "<tr>
                <td class='secLine'>".get_lang('LinksTitleLinkColumn')."</td>
                <td class='secLine'>".get_lang('LinksTitleUsersColumn')."</td>
                <td class='secLine'>".get_lang('LinksTitleCountColumn')."</td>
            </tr>";
        
        if (is_array($results))
        {
            for($j = 0 ; $j < count($results) ; $j++)
            {
                    echo "<tr>";
                    echo "<td class='content'><a href='".$results[$j][1]."'>".$results[$j][0]."</a></td>";
                    echo "<td align='left' class='content'>".$results[$j][2]."</td>";
                    echo "<td align='left' class='content'>".$results[$j][3]."</td>";
                    echo"</tr>";
            }

        }
        else
        {
            echo "<tr>";
            echo "<td colspan='3'><center>".get_lang('NoResult')."</center></td>";
            echo"</tr>";
        }
        
        echo '</table>';
        
    }


/***************************************************************************
*
*		Documents
*
***************************************************************************/

    $tempView = $view;
    if($view[4] == '1'){
    	
        $tempView[4] = '0';
        
        $sql = "SELECT `down_doc_path`, COUNT(DISTINCT `down_user_id`), COUNT(`down_doc_path`)
                    FROM $TABLETRACK_DOWNLOADS
                    WHERE `down_cours_id` = '$_cid'
                    GROUP BY `down_doc_path`";
        
        $results = getManyResults3Col($sql);
        
	    echo "<a href='courseLogCSV.php?".api_get_cidreq()."&uInfo=".$_GET['uInfo']."&view=0000100'>".get_lang('ExportAsCSV')."</a>";
        echo '<table class="data_table">';
        
        echo "<tr>
                <td class='secLine'>".get_lang('DocumentsTitleDocumentColumn')."</td>
                <td class='secLine'>".get_lang('DocumentsTitleUsersColumn')."</td>
                <td class='secLine'>".get_lang('DocumentsTitleCountColumn')."</td>
            </tr>";
        if (is_array($results))
        {
            for($j = 0 ; $j < count($results) ; $j++)
            {
                    echo "<tr>";
                    echo "<td class='content'>".$results[$j][0]."</td>";
                    echo "<td align='left' class='content'>".$results[$j][1]."</td>";
                    echo "<td align='left' class='content'>".$results[$j][2]."</td>";
                    echo"</tr>";
            }

        }
        else
        {
            echo "<tr>";
            echo "<td colspan='3'><center>".get_lang('NoResult')."</center></td>";
            echo"</tr>";
        }
        
        echo '</table>';
        
        
    }


/***************************************************************************
*
*		Scorm contents and Learning Path
*
***************************************************************************/
    $tempView = $view;
    if($view[5] == '1'){
    	
        $tempView[5] = '0';
        
        $sql = "SELECT id, name 
					FROM $tbl_learnpath_main";
                    //WHERE dokeosCourse='$_cid'"; we are using a table inside the course now, so no need for course id
		$result=api_sql_query($sql,__FILE__,__LINE__);
		
	    $ar=Database::fetch_array($result);
	    
	    echo "<a href='courseLogCSV.php?".api_get_cidreq()."&uInfo=".$_GET['uInfo']."&view=0000010'>".get_lang('ExportAsCSV')."</a>";
	    echo '<table class="data_table">';
	    
	    echo "<tr>
	            <td class='secLine'>".get_lang('ScormContentColumn')."</td>
			</tr>";
			
		$scormcontopen=$_REQUEST["scormcontopen"];
		$scormstudentopen=$_REQUEST["scormstudentopen"];
	    
	    if (is_array($ar)){
	    	
	    	while ($ar['id'] != '') {
				$lp_title = stripslashes($ar['name']);
				echo "<tr><td>";
				echo "<a href='".$_SERVER['PHP_SELF']."?view=".$view."&scormcontopen=".$ar['id']."' class='specialLink'>$lp_title</a>";
				echo "</td></tr>";
				if ($ar['id']==$scormcontopen) { //have to list the students here
					$contentId=$ar['id'];
					$sql2 = "SELECT u.user_id, u.lastname, u.firstname " .
							"FROM  $tbl_learnpath_view sd " .
							"INNER JOIN $table_user u " .
							"ON u.user_id = sd.user_id " .
		                    "WHERE sd.lp_id=$contentId group by u.user_id";
		            //error_log($sql2,0);
					$result2=api_sql_query($sql2,__FILE__,__LINE__);
					
					if(mysql_num_rows($result2)>0){
						
						echo "<tr><td align='center'><table cellspacing='0' cellpadding='0' style='margin-left: 15px;margin-right: 15px; margin-top: 5px; margin-bottom: 5px; width: 97%;'>";
						
						$isFirstLine=true;
						
					    $ar2=Database::fetch_array($result2);
						while ($ar2 != '') {
							
							if (isset($_REQUEST["scormstudentopen"]) && $ar2['user_id']==$scormstudentopen) {
							
							echo "<tr><td align='left' class='secLine' style=''><a href='".$_SERVER['PHP_SELF']."?view=".$view."&scormcontopen=".$ar['id']."&scormstudentopen=".$ar2['user_id']."' class='specialLink'>{$ar2['lastname']} {$ar2['firstname']}</a>";
							echo "</td></tr>";
							
							}
							
							else{
								
								if($isFirstLine){
									echo "<tr><td align='left' style='border-top: 1px solid #b0b0b0;'><a href='".$_SERVER['PHP_SELF']."?view=".$view."&scormcontopen=".$ar['id']."&scormstudentopen=".$ar2['user_id']."' class='specialLink'>{$ar2['lastname']} {$ar2['firstname']}</a>";
									echo "</td></tr>";
									$isFirstLine=false;
								}
								
								else{
								
									echo "<tr><td align='left'><a href='".$_SERVER['PHP_SELF']."?view=".$view."&scormcontopen=".$ar['id']."&scormstudentopen=".$ar2['user_id']."' class='specialLink'>{$ar2['lastname']} {$ar2['firstname']}</a>";
									echo "</td></tr>";
								
								}
			
							}
							
							$isFirstLine=false;
							
							
							if ($ar2['user_id']==$scormstudentopen) { //have to list the student's results
							
								echo "<tr><td align='center'><table style='margin-left: 15px;margin-right: 15px; margin-top: 5px; margin-bottom: 5px; width: 97%;'>";
								
								$studentId=$ar2['user_id'];
								$sql3 = "SELECT iv.status, iv.score, i.title, iv.total_time " .
										"FROM $tbl_learnpath_item i " .
										"INNER JOIN $tbl_learnpath_item_view iv ON i.id=iv.lp_item_id " .
										"INNER JOIN $tbl_learnpath_view v ON iv.lp_view_id=v.id " .
										"WHERE (v.user_id=$studentId and v.lp_id=$contentId) ORDER BY v.id, i.id";
								$result3=api_sql_query($sql3,__FILE__,__LINE__);
							    $ar3=Database::fetch_array($result3);
						        echo "<tr><td class='secLine'>
					                &nbsp;".get_lang('ScormTitleColumn')."&nbsp;
					                </td>
					                <td class='secLine'>
					                &nbsp;".get_lang('ScormStatusColumn')."&nbsp;
					                </td>
					                <td class='secLine'>
					                &nbsp;".get_lang('ScormScoreColumn')."&nbsp;
					                </td>
					                <td class='secLine'>
					                &nbsp;".get_lang('ScormTimeColumn')."&nbsp;
					                </td>
						            </tr>";
								while ($ar3['status'] != '') {
									require_once('../newscorm/learnpathItem.class.php');
									$time = learnpathItem::get_scorm_time('php',$ar3['total_time']);
									$title = htmlentities($ar3['title'],ENT_QUOTES,$lp_charset);
									
									$mylanglist = array(
										'completed' => 'ScormCompstatus',
										'incomplete'=> 'ScormIncomplete',
										'failed'	=> 'ScormFailed',
										'passed'	=> 'ScormPassed',
										'browsed'	=> 'ScormBrowsed',
										'not attempted' => 'ScormNotAttempted',
									);
									
									echo "<tr><td>";
									echo "$title</td><td align=right>".get_lang($mylanglist[$ar3['status']])."</td><td align=right>{$ar3['score']}</td><td align=right>$time</td>";
									echo "</tr>";
									$ar3=Database::fetch_array($result3);
								}
								
								echo "</td></tr></table>";
								
							}
						
							$ar2=Database::fetch_array($result2);
						}
						
						echo "</td></tr></table>";
						
					}

				}
				
				$ar=Database::fetch_array($result);
				
			}
	    	
    	}
    	
    	else{
    		 echo "<tr>";
             echo "<td colspan='3'><center>".get_lang('NoResult')."</center></td>";
             echo"</tr>";
    	}
	    
	    echo '</table>';
        
    }

    
}
// not allowed
else
{
    if(!$_configuration['tracking_enabled'])
    {
        echo get_lang('TrackingDisabled');
    }
    else
    {
        api_not_allowed();
    }
}
?>
</table>

<?php
Display::display_footer();
?>
