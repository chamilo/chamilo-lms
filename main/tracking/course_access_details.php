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
$reqdate = $_REQUEST['reqdate'];
$period = $_REQUEST['period'];
$displayType = $_REQUEST['displayType'];
// name of the language file that needs to be included
$language_file = "tracking";
include('../inc/global.inc.php');

$interbreadcrumb[]= array ("url"=>"courseLog.php", "name"=> get_lang('ToolName'));

$nameTools = get_lang('TrafficDetails');

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
//@todo use Database library
$TABLETRACK_ACCESS = $_configuration['statistics_database']."`.`track_e_access";
Display::display_header($nameTools,"Tracking");
include(api_get_path(LIBRARY_PATH)."statsUtils.lib.inc.php");

// the variables for the days and the months
// Defining the shorts for the days
$DaysShort = api_get_week_days_short();
// Defining the days of the week to allow translation of the days
$DaysLong = api_get_week_days_long();
// Defining the months of the year to allow translation of the months
$MonthsLong = api_get_months_long();

$is_allowedToTrack = $is_courseAdmin;

?>
<h3>
    <?php echo $nameTools ?>
</h3>
<table width="100%" cellpadding="2" cellspacing="3" border="0">
<?php
    if( $is_allowedToTrack && $_configuration['tracking_enabled'])
    {
        if( !isset($reqdate) || $reqdate < 0 || $reqdate > 2149372861 )
                $reqdate = time();
        //** dislayed period
        echo "<tr><td><b>";
            switch($period)
            {
                case "year" :
                    echo date(" Y", $reqdate);
                    break;
                case "month" :
                    echo $MonthsLong[date("n", $reqdate)-1].date(" Y", $reqdate);
                    break;
                // default == day
                default :
                    $period = "day";
                case "day" :
                    echo $DaysLong[date("w" , $reqdate)].date(" d " , $reqdate).$MonthsLong[date("n", $reqdate)-1].date(" Y" , $reqdate);
                    break;
            }
        echo "</b></tr></td>";
        //** menu
        echo "<tr>
                <td>
        ";
        echo "  ".get_lang('PeriodToDisplay')." : [<a href='".api_get_self()."?period=year&reqdate=$reqdate' class='specialLink'>".get_lang('PeriodYear')."</a>]
                [<a href='".api_get_self()."?period=month&reqdate=$reqdate' class='specialLink'>".get_lang('PeriodMonth')."</a>]
                [<a href='".api_get_self()."?period=day&reqdate=$reqdate' class='specialLink'>".get_lang('PeriodDay')."</a>]
                &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;
                ".get_lang('DetailView')." :
        ";
        switch($period)
        {
            case "year" :
                    //-- if period is "year" display can be by month, day or hour
                    echo "  [<a href='".api_get_self()."?period=$period&reqdate=$reqdate&displayType=month' class='specialLink'>".get_lang('PeriodMonth')."</a>]";
            case "month" :
                    //-- if period is "month" display can be by day or hour
                    echo "  [<a href='".api_get_self()."?period=$period&reqdate=$reqdate&displayType=day' class='specialLink'>".get_lang('PeriodDay')."</a>]";
            case "day" :
                    //-- if period is "day" display can only be by hour
                    echo "  [<a href='".api_get_self()."?period=$period&reqdate=$reqdate&displayType=hour' class='specialLink'>".get_lang('PeriodHour')."</a>]";
                    break;
        }

        echo "&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;";

        switch($period)
        {
            case "year" :
                // previous and next date must be evaluated
                // 30 days should be a good approximation
                $previousReqDate = mktime(1,1,1,1,1,date("Y",$reqdate)-1);
                $nextReqDate = mktime(1,1,1,1,1,date("Y",$reqdate)+1);
                echo   "
                    [<a href='".api_get_self()."?period=$period&reqdate=$previousReqDate&displayType=$displayType' class='specialLink'>".get_lang('PreviousYear')."</a>]
                    [<a href='".api_get_self()."?period=$period&reqdate=$nextReqDate&displayType=$displayType' class='specialLink'>".get_lang('NextYear')."</a>]
                ";
                break;
            case "month" :
                // previous and next date must be evaluated
                // 30 days should be a good approximation
                $previousReqDate = mktime(1,1,1,date("m",$reqdate)-1,1,date("Y",$reqdate));
                $nextReqDate = mktime(1,1,1,date("m",$reqdate)+1,1,date("Y",$reqdate));
                echo   "
                    [<a href='".api_get_self()."?period=$period&reqdate=$previousReqDate&displayType=$displayType' class='specialLink'>".get_lang('PreviousMonth')."</a>]
                    [<a href='".api_get_self()."?period=$period&reqdate=$nextReqDate&displayType=$displayType' class='specialLink'>".get_lang('NextMonth')."</a>]
                ";
                break;
            case "day" :
                // previous and next date must be evaluated
                $previousReqDate = $reqdate - 86400;
                $nextReqDate = $reqdate + 86400;
                echo   "
                    [<a href='".api_get_self()."?period=$period&reqdate=$previousReqDate&displayType=$displayType' class='specialLink'>".get_lang('PreviousDay')."</a>]
                    [<a href='".api_get_self()."?period=$period&reqdate=$nextReqDate&displayType=$displayType' class='specialLink'>".get_lang('NextDay')."</a>]
                ";
                break;
        }
        echo "
                </td>
              </tr>
        ";
        //**
        // display information about this period
        switch($period)
        {
            // all days
            case "year" :
                $sql = "SELECT UNIX_TIMESTAMP( `access_date` )
                            FROM `$TABLETRACK_ACCESS`
                            WHERE YEAR( `access_date` ) = YEAR( FROM_UNIXTIME( '$reqdate' ) )
                            AND `access_cours_code` = '$_cid'
                            AND `access_tool` IS NULL ";
                if($displayType == "month")
                {
                    $sql .= "ORDER BY UNIX_TIMESTAMP( `access_date`)";
                    $month_array = monthTab($sql);
                    makeHitsTable($month_array,get_lang('PeriodMonth'));
                }
                elseif($displayType == "day")
                {
                    $sql .= "ORDER BY DAYOFYEAR( `access_date`)";
                    $days_array = daysTab($sql);
                    makeHitsTable($days_array,get_lang('PeriodDay'));
                }
                else // by hours by default
                {
                    $sql .= "ORDER BY HOUR( `access_date`)";
                    $hours_array = hoursTab($sql);
                    makeHitsTable($hours_array,get_lang('PeriodHour'));
                }
                break;
            // all days
            case "month" :
                $sql = "SELECT UNIX_TIMESTAMP( `access_date` )
                            FROM `$TABLETRACK_ACCESS`
                            WHERE MONTH(`access_date`) = MONTH (FROM_UNIXTIME( '$reqdate' ) )
                            AND YEAR( `access_date` ) = YEAR( FROM_UNIXTIME( '$reqdate' ) )
                            AND `access_cours_code` = '$_cid'
                            AND `access_tool` IS NULL ";
                if($displayType == "day")
                {
                    $sql .= "ORDER BY DAYOFYEAR( `access_date`)";
                    $days_array = daysTab($sql);
                    makeHitsTable($days_array,get_lang('PeriodDay'));
                }
                else // by hours by default
                {
                    $sql .= "ORDER BY HOUR( `access_date`)";
                    $hours_array = hoursTab($sql);
                    makeHitsTable($hours_array,get_lang('PeriodHour'));
                }
                break;
            // all hours
            case "day"  :
                $sql = "SELECT UNIX_TIMESTAMP( `access_date` )
                            FROM `$TABLETRACK_ACCESS`
                            WHERE DAYOFMONTH(`access_date`) = DAYOFMONTH(FROM_UNIXTIME( '$reqdate' ) )
                            AND MONTH(`access_date`) = MONTH (FROM_UNIXTIME( '$reqdate' ) )
                            AND YEAR( `access_date` ) = YEAR( FROM_UNIXTIME( '$reqdate' ) )
                            AND `access_cours_code` = '$_cid'
                            AND `access_tool` IS NULL
                            ORDER BY HOUR( `access_date` )";
                $hours_array = hoursTab($sql,$reqdate);
                makeHitsTable($hours_array,get_lang('PeriodHour'));
                break;
        }
    }
    else // not allowed to track
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
