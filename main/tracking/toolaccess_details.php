<?php // $Id: toolaccess_details.php 20472 2009-05-11 10:02:06Z ivantcholakov $
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

// TODO: Is this file deprecated?

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

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

$tool = $_REQUEST['tool'];
$period = $_REQUEST['period'];
$reqDate = $_REQUEST['reqDate'];
// name of the language file that needs to be included
$language_file = "tracking";

include('../inc/global.inc.php');

$nameTools = get_lang('ToolName');

$interbreadcrumb[]= array ("url"=>"courseLog.php", "name"=> "Statistics");

$htmlHeadXtra[] = "<style type='text/css'>
/*<![CDATA[*/
.mainLine {font-weight : bold;color : #FFFFFF;background-color : $colorDark;padding-left : 15px;padding-right : 15px;}
.secLine {color : #000000;background-color : $666666;padding-left : 15px;padding-right : 15px;}
.content {padding-left : 25px;}
.specialLink{color : #0000FF;}
.minilink{}
.minitext{}
/*]]>*/
</style>
<style media='print' type='text/css'>
/*<![CDATA[*/
td {border-bottom: thin dashed gray;}
/*]]>*/
</style>";
Display::display_header($nameTools,"Tracking");
?>

<h3>
    <?php echo $nameTools; ?>
</h3>

<?php
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

$tool=$_REQUEST['tool'];
$period=$_REQUEST['period'];
$reqdate=$_REQUEST['reqdate'];
?>
<table width="100%" cellpadding="2" cellspacing="0" border="0">
<?php


    $TABLETRACK_ACCESS = $_configuration['statistics_database']."`.`track_e_access";

    if(isset($_cid)) //stats for the current course
    {
        // to see stats of one course user must be courseAdmin of this course
        $is_allowedToTrack = $is_courseAdmin;
        $courseCodeEqualcidIfNeeded = "AND `access_cours_code` = '$_cid'";
    }
    else // stats for all courses
    {
        // to see stats of all courses user must be platformAdmin
        $is_allowedToTrack = $is_platformAdmin;
        $courseCodeEqualcidIfNeeded = "";
    }
    if( $is_allowedToTrack && $_configuration['tracking_enabled'])
    {
        // list of all tools
        if (!isset($tool))
        {
            $sql = "SELECT `access_tool`, count( access_tool )
                        FROM `$TABLETRACK_ACCESS`
                        WHERE `access_tool` IS NOT NULL
                            ".$courseCodeEqualcidIfNeeded."
                        GROUP BY `access_tool`";
            echo "<tr><td>";
            echo "<tr>
                    <td>
                    ";
            if(isset($_cid)) echo "<b>$_cid : </b>";
            echo "		<b>".get_lang('ToolList')."</b>
					</td>
                </tr>
            ";

            $results = getManyResults2Col($sql);
            echo "<table cellpadding='0' cellspacing='0' border='0' align=center>";
            echo "<tr bgcolor='#E6E6E6'>
                    <td width='70%'>
                    $langToolTitleToolnameColumn
                    </td>
                    <td width='30%'>
                    $langToolTitleCountColumn
                    </td>
                </tr>";
            if (is_array($results))
            {
                for($j = 0 ; $j < count($results) ; $j++)
                {
                        echo "<tr>";
                        echo "<td><a href='toolaccess_details.php?tool=".urlencode($results[$j][0])."'>".get_lang($results[$j][0])."</a></td>";
                        echo "<td align='right'>".$results[$j][1]."</td>";
                        echo"</tr>";
                }

            }
            else
            {
                echo "<tr>";
                echo "<td colspan='2'><center>".get_lang('NoResult')."</center></td>";
                echo"</tr>";
            }
            echo "</table></td></tr>";
        }
        else
        {
            // this can prevent bug if there is special chars in $tool
            $encodedTool = urlencode($tool);
            $tool = urldecode($tool);

            if( !isset($reqdate) )
                $reqdate = time();
            echo "<tr>
                    <td>
					";
            if(isset($_cid)) echo "<b>$_cid : </b>";
            echo "        <b>".get_lang($tool)."</b>
					</td>
                </tr>
            ";

            /* ------ display ------ */
            // displayed period
            echo "<tr><td>";
            switch($period)
            {
                case "month" :
                    echo $MonthsLong[date("n", $reqdate)-1].date(" Y", $reqdate);
                    break;
                case "week" :
                    $weeklowreqdate = ($reqdate-(86400*date("w" , $reqdate)));
                    $weekhighreqdate = ($reqdate+(86400*(6-date("w" , $reqdate)) ));
                    echo "<b>".$langFrom."</b> ".date("d " , $weeklowreqdate).$MonthsLong[date("n", $weeklowreqdate)-1].date(" Y" , $weeklowreqdate);
                    echo " <b>".$langTo."</b> ".date("d " , $weekhighreqdate ).$MonthsLong[date("n", $weekhighreqdate)-1].date(" Y" , $weekhighreqdate);
                    break;
                // default == day
                default :
                    $period = "day";
                case "day" :
                    echo $DaysLong[date("w" , $reqdate)].date(" d " , $reqdate).$MonthsLong[date("n", $reqdate)-1].date(" Y" , $reqdate);
                    break;
            }
            echo "</tr></td>";
            // periode choice
            echo "<tr>
                    <td>
                    <small>
                    [<a href='".api_get_self()."?tool=$encodedTool&period=day&reqdate=$reqdate' class='specialLink'>$langPeriodDay</a>]
                    [<a href='".api_get_self()."?tool=$encodedTool&period=week&reqdate=$reqdate' class='specialLink'>$langPeriodWeek</a>]
                    [<a href='".api_get_self()."?tool=$encodedTool&period=month&reqdate=$reqdate' class='specialLink'>$langPeriodMonth</a>]
                    &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;

                    ";
            switch($period)
            {
                case "month" :
                    // previous and next date must be evaluated
                    // 30 days should be a good approximation
                    $previousReqDate = mktime(1,1,1,date("m",$reqdate)-1,1,date("Y",$reqdate));
                    $nextReqDate = mktime(1,1,1,date("m",$reqdate)+1,1,date("Y",$reqdate));
                    echo   "
                        [<a href='".api_get_self()."?tool=$encodedTool&period=month&reqdate=$previousReqDate' class='specialLink'>$langPreviousMonth</a>]
                        [<a href='".api_get_self()."?tool=$encodedTool&period=month&reqdate=$nextReqDate' class='specialLink'>$langNextMonth</a>]
                    ";
                    break;
                case "week" :
                    // previous and next date must be evaluated
                    $previousReqDate = $reqdate - 7*86400;
                    $nextReqDate = $reqdate + 7*86400;
                    echo   "
                        [<a href='".api_get_self()."?tool=$encodedTool&period=week&reqdate=$previousReqDate' class='specialLink'>$langPreviousWeek</a>]
                        [<a href='".api_get_self()."?tool=$encodedTool&period=week&reqdate=$nextReqDate' class='specialLink'>$langNextWeek</a>]
                    ";
                    break;
                case "day" :
                    // previous and next date must be evaluated
                    $previousReqDate = $reqdate - 86400;
                    $nextReqDate = $reqdate + 86400;
                    echo   "
                        [<a href='".api_get_self()."?tool=$encodedTool&period=day&reqdate=$previousReqDate' class='specialLink'>$langPreviousDay</a>]
                        [<a href='".api_get_self()."?tool=$encodedTool&period=day&reqdate=$nextReqDate' class='specialLink'>$langNextDay</a>]
                    ";
                    break;
            }

            echo"   &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;
                    [<a href='".api_get_self()."' class='specialLink'>$langViewToolList</a>]
                    </small>
                    </td>
                </tr>
            ";
            // display information about this period
            switch($period)
            {
                // all days
                case "month" :
                    $sql = "SELECT UNIX_TIMESTAMP(`access_date`)
                            FROM `$TABLETRACK_ACCESS`
                            WHERE `access_tool` = '$tool'
                                ".$courseCodeEqualcidIfNeeded."
                                AND MONTH(`access_date`) = MONTH(FROM_UNIXTIME('$reqdate'))
                                AND YEAR(`access_date`) = YEAR(FROM_UNIXTIME('$reqdate'))
                                ORDER BY `access_date` ASC";

                    $days_array = daysTab($sql);
                    makeHitsTable($days_array,$langDay);
                    break;
                // all days
                case "week" :
                    $sql = "SELECT UNIX_TIMESTAMP(`access_date`)
                            FROM `$TABLETRACK_ACCESS`
                            WHERE `access_tool` = '$tool'
                                ".$courseCodeEqualcidIfNeeded."
                                AND WEEK(`access_date`) = WEEK(FROM_UNIXTIME('$reqdate'))
                                AND YEAR(`access_date`) = YEAR(FROM_UNIXTIME('$reqdate'))
                                ORDER BY `access_date` ASC";

                    $days_array = daysTab($sql);
                    makeHitsTable($days_array,$langDay);
                    break;
                // all hours
                case "day"  :
                    $sql = "SELECT UNIX_TIMESTAMP(`access_date`)
                                FROM `$TABLETRACK_ACCESS`
                                WHERE `access_tool` = '$tool'
                                    ".$courseCodeEqualcidIfNeeded."
                                    AND DAYOFYEAR(`access_date`) = DAYOFYEAR(FROM_UNIXTIME('$reqdate'))
                                    AND YEAR(`access_date`) = YEAR(FROM_UNIXTIME('$reqdate'))
                                ORDER BY `access_date` ASC";

                    $hours_array = hoursTab($sql,$reqdate);
                    makeHitsTable($hours_array,$langHour);
                    break;
            }
        }
    }
    else // not allowed to track
    {
        if(!$_configuration['tracking_enabled'])
        {
            echo $langTrackingDisabled;
        }
        else
        {
            echo get_lang('NotAllowed');
        }
    }


?>
</table>
<?php
// footer
Display::display_footer();
?>
