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
*
*	@package dokeos.tracking
==============================================================================
*/

// TODO: Is this file deprecated?

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$uInfo = $_REQUEST['uInfo'];
if( !isset($_REQUEST['reqdate']) )
	$reqdate = time();
else
	$reqdate = $_REQUEST['reqdate'];
$period = $_REQUEST['period'];
if(!isset($_REQUEST['view']))
	$view ="0000000";
else
	$view = $_REQUEST['view'];

// name of the language file that needs to be included
$language_file = "tracking";
include('../inc/global.inc.php');

$interbreadcrumb[]= array ("url"=>"../user/user.php", "name"=> get_lang('Users'));

$nameTools = get_lang('ToolName');

$htmlHeadXtra[] = "<style type='text/css'>
/*<![CDATA[*/
.secLine {background-color : #E6E6E6;}
.content {padding-left : 15px;padding-right : 15px; }
.specialLink{color : #0000FF;}
/*]]>*/
</style>
<style media='print' type='text/css'>
/*<![CDATA[*/
td {border-bottom: thin dashed gray;}
/*]]>*/
</style>";

$TABLECOURSUSER	        = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE_GROUPSUSER = Database::get_course_table(TABLE_GROUP_USER);
$TABLEUSER	        = Database::get_main_table(TABLE_MAIN_USER);
$TABLETRACK_ACCESS      = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
Display::display_header($nameTools, "Tracking");
include(api_get_path(LIBRARY_PATH)."statsUtils.lib.inc.php");

// the variables for the days and the months
// Defining the shorts for the days
$DaysShort = api_get_week_days_short();
// Defining the days of the week to allow translation of the days
$DaysLong = api_get_week_days_long();
// Defining the months of the year to allow translation of the months
$MonthsLong = api_get_months_long();
// Defining the months of the year to allow translation of the months
$MonthsShort = api_get_months_short();

$is_allowedToTrack = $is_groupTutor; // allowed to track only user of one group
$is_allowedToTrackEverybodyInCourse = $is_courseAdmin; // allowed to track all student in course
?>
<h3>
    <?php echo $nameTools ?>
</h3>
<table width="100%" cellpadding="2" cellspacing="3" border="0">
<?php
// check if uid is tutor of this group

if( ( $is_allowedToTrack || $is_allowedToTrackEverybodyInCourse ) && $_configuration['tracking_enabled'] )
{
    if( $is_allowedToTrackEverybodyInCourse )
    {
        $sql = "SELECT `u`.`firstname`,`u`.`lastname`, `u`.`email`
                    FROM $TABLECOURSUSER cu , $TABLEUSER u
                    WHERE `cu`.`user_id` = `u`.`user_id`
                        AND `cu`.`course_code` = '$_cid'
                        AND `u`.`user_id` = '$uInfo'";
    }
    else
    {
        $sql = "SELECT `u`.`firstname`,`u`.`lastname`, `u`.`email`
                    FROM $TABLECOURSE_GROUPSUSER gu , $TABLEUSER u
                    WHERE `gu`.`user_id` = `u`.`user_id`
                        AND `gu`.`group_id` = '$_gid'
                        AND `u`.`user_id` = '$uInfo'";
    }
    $query = Database::query($sql,__FILE__,__LINE__);
    $res = @Database::fetch_array($query);
    if(is_array($res))
    {
        $res[2] == "" ? $res2 = get_lang('NoEmail') : $res2 = Display::encrypted_mailto_link($res[2]);

        echo "<tr><td>";
        echo $informationsAbout." : <br>";
        echo "<ul>\n"
                ."<li>".get_lang('FirstName')." : ".$res[0]."</li>\n"
                ."<li>".get_lang('LastName')." : ".$res[1]."</li>\n"
                ."<li>".get_lang('Email')." : ".$res2."</li>\n"
                ."</ul>";
        echo "</td></tr>";
        /******* MENU ********/
        echo "<tr>
                <td>
                [<a href='userLog.php?uInfo=$uInfo&view=$view'>".get_lang('Back')."</a>]
        ";
        echo "  &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;
                [<a href='".api_get_self()."?uInfo=$uInfo&view=$view&period=week&reqdate=$reqdate' class='specialLink'>".get_lang('PeriodWeek')."</a>]
                [<a href='".api_get_self()."?uInfo=$uInfo&view=$view&period=month&reqdate=$reqdate' class='specialLink'>".get_lang('PeriodMonth')."</a>]
                &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;
        ";
        switch($period)
        {
            case "week" :
                // previous and next date must be evaluated
                $previousReqDate = $reqdate - 7*86400;
                $nextReqDate = $reqdate + 7*86400;
                echo   "
                    [<a href='".api_get_self()."?uInfo=$uInfo&view=$view&period=week&reqdate=$previousReqDate' class='specialLink'>".get_lang('PreviousWeek')."</a>]
                    [<a href='".api_get_self()."?uInfo=$uInfo&view=$view&period=week&reqdate=$nextReqDate' class='specialLink'>".get_lang('NextWeek')."</a>]
                ";
                break;
            default :
                $period = "month";
            case "month" :
                // previous and next date must be evaluated
                // 30 days should be a good approximation
                $previousReqDate = mktime(1,1,1,date("m",$reqdate)-1,1,date("Y",$reqdate));
                $nextReqDate = mktime(1,1,1,date("m",$reqdate)+1,1,date("Y",$reqdate));
                echo   "
                    [<a href='".api_get_self()."?uInfo=$uInfo&view=$view&period=month&reqdate=$previousReqDate' class='specialLink'>".get_lang('PreviousMonth')."</a>]
                    [<a href='".api_get_self()."?uInfo=$uInfo&view=$view&period=month&reqdate=$nextReqDate' class='specialLink'>".get_lang('NextMonth')."</a>]
                ";
                break;


        }
        echo "
                </td>
            </tr>
        ";
        /******* END OF MENU ********/

        switch($period)
        {
            case "month" :
				$sql = "SELECT `access_date`
                            FROM $TABLETRACK_ACCESS
                            WHERE `access_user_id` = '$uInfo'
                            AND `access_cours_code` = '".$_cid."'
                            AND MONTH(`access_date`) = MONTH( FROM_UNIXTIME('$reqdate') )
                            AND YEAR(`access_date`) = YEAR(FROM_UNIXTIME('$reqdate'))
							GROUP BY DAYOFMONTH(`access_date`)
                            ORDER BY `access_date` ASC";
                $displayedDate = $MonthsLong[date("n", $reqdate)-1].date(" Y", $reqdate);
                 break;
            case "week" :
				$sql = "SELECT `access_date`
                            FROM $TABLETRACK_ACCESS
                            WHERE `access_user_id` = '$uInfo'
                            AND `access_cours_code` = '".$_cid."'
                            AND WEEK(`access_date`) = WEEK( FROM_UNIXTIME('$reqdate') )
                            AND YEAR(`access_date`) = YEAR(FROM_UNIXTIME('$reqdate'))
							GROUP BY DAYOFMONTH(`access_date`)
                            ORDER BY `access_date` ASC";
                $weeklowreqdate = ($reqdate-(86400*date("w" , $reqdate)));
                $weekhighreqdate = ($reqdate+(86400*(6-date("w" , $reqdate)) ));
                $displayedDate = get_lang('From')." ".date("d " , $weeklowreqdate).$MonthsLong[date("n", $weeklowreqdate)-1].date(" Y" , $weeklowreqdate)
                                ." ".get_lang('To')." ".date("d " , $weekhighreqdate ).$MonthsLong[date("n", $weekhighreqdate)-1].date(" Y" , $weekhighreqdate);
                break;
        }
        echo "<tr><td>";
        $results = getManyResults1Col($sql);
        /*** display of the displayed period  ***/
        echo "<table width='100%' cellpadding='2' cellspacing='1' border='0' align=center>";
        echo "<td bgcolor='#E6E6E6'>".$displayedDate."</td>";
        if (is_array($results))
        {
            for ($j = 0 ; $j < sizeof($results); $j++)
            {
                $timestamp = strtotime($results[$j]);
                //$beautifulDate = $langDay_of_weekNames['long'][date("w" , $timestamp)].date(" d " , $timestamp);
                //$beautifulHour = date("H : i" , $timestamp);
                $beautifulDateTime = format_locale_date($dateTimeFormatLong,$timestamp);
                echo "<tr>";
                echo "<td style='padding-left : 40px;' valign='top'>".$beautifulDateTime."</td>";
                echo"</tr>";
                // $limit is used to select only results between $results[$j] (current login) and next one
                if( $j == ( sizeof($results) - 1 ) )
                    $limit = date("Y-m-d H:i:s",$nextReqDate);
                else
                    $limit = $results[$j+1];
                // select all access to tool between displayed date and next displayed date or now() if
                // displayed date is the last login date
                $sql = "SELECT `access_tool`, count(`access_tool`)
                            FROM $TABLETRACK_ACCESS
                            WHERE `access_user_id` = '$uInfo'
                                AND `access_tool` IS NOT NULL
                                AND `access_date` > '".$results[$j]."'
                                AND `access_date` < '".$limit."'
                                AND `access_cours_code` = '".$_cid."'
                            GROUP BY `access_tool`
                            ORDER BY `access_tool` ASC";
                $results2 = getManyResults2Col($sql);

                if (is_array($results2))
                {
                    echo "<tr><td colspan='2'>\n";
                    echo "<table width='50%' cellpadding='0' cellspacing='0' border='0'>\n";
                    for($k = 0 ; $k < count($results2) ; $k++)
                    {
                            echo "<tr>\n";
                            echo "<td width='70%' style='padding-left : 60px;'>".get_lang($results2[$k][0])."</td>\n";
                            echo "<td width='30%' align='right' style='padding-right : 40px'>".$results2[$k][1]." ".get_lang('Visits')."</td>\n";
                            echo "</tr>";

                    }
                    echo "</table>\n";
                    echo "</td></tr>\n";
                }
                $previousDate = $value;
            }

        }
        else
        {
            echo "<tr>";
            echo "<td colspan='2' bgcolor='#eeeeee'><center>".get_lang('NoResult')."</center></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</td></tr>";
    }
    else
    {
        echo get_lang('ErrorUserNotInGroup');
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