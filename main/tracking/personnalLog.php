<?php
/* For licensing terms, see /license.txt */
/**
*	@author Thomas Depraetere
*	@author Hugues Peeters
*	@author Christophe Gesche
*	@author Sebastien Piraux
*
*	@package chamilo.tracking
*/

// TODO: Is this file deprecated?

/*
		INIT SECTION
*/

// name of the language file that needs to be included
$language_file = "tracking";
include('../inc/global.inc.php');

$interbreadcrumb[]= array ("url"=>"../auth/profile.php", "name"=> get_lang('ModifyProfile'));
$nameTools = get_lang('ToolName');

$htmlHeadXtra[] = "<style type=\"text/css\">
/*<![CDATA[*/
.secLine {background-color : #E6E6E6;}
.content {padding-left : 15px; padding-right : 15px;}
.specialLink{color : #0000FF;}
/*]]>*/
</style>
<style media='print' type='text/css'>
/*<![CDATA[*/
td {border-bottom: thin dashed gray;}
/*]]>*/
</style>";

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
//Remove all characters different than 0 and 1 from $view parameter
$view = preg_replace('/[^01]/','',$_REQUEST['view']);

$TABLECOURSUSER			= Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLETRACK_ACCESS      = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
$TABLETRACK_LINKS 		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
$TABLETRACK_DOWNLOADS 	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
$TABLETRACK_LOGIN 		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
$TABLETRACK_EXERCICES   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);


$limitOfDisplayedLogins = 25; // number of logins to display
include(api_get_path(LIBRARY_PATH)."statsUtils.lib.inc.php");

Display::display_header($nameTools,"Tracking");
api_display_tool_title($nameTools);

//		MAIN SECTION

// show all : view must be equal to the sum of all view values (1024+512+...+64)
// show none : 0

        echo "
<table width=\"100%\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\">
    <tr>
        <td class='minilink'>
                [<a href='".api_get_self()."?view=1111111'>".get_lang('ShowAll')."</a>]
                [<a href='".api_get_self()."?view=0000000'>".get_lang('ShowNone')."</a>]
            </td>
        </tr>
        ";
    if(empty($view)) $view ="0000000";

    /***************************************************************************
     *
     *		Logins
     *
     ***************************************************************************/
    $tempView = $view;
    if($tempView[0] == '1')
    {
        $tempView[0] = '0';
        echo "
    <tr>
        <td valign='top'>
            <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font>
            <b>".get_lang('Logins')."</b>
            &nbsp;&nbsp;&nbsp;
                [<a href='".api_get_self()."?view=".$tempView."'>".get_lang('Close')."</a>]
        </td>
    </tr>";
        $sql = "SELECT login_date
                    FROM ".$TABLETRACK_LOGIN."
                    WHERE login_user_id = '".$_user['user_id']."'
                    ORDER BY login_date DESC
                    LIMIT ".$limitOfDisplayedLogins."";
        echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('LoginsExplaination')."<br/>";
        $results = getManyResults1Col($sql);
        echo "
<table width='100%' cellpadding='2' cellspacing='1' border='0' align='center'>";
        if (is_array($results))
        {

            while ( list($key,$value) = each($results))
            {
                $beautifulDate = api_convert_and_format_date($value, null, date_default_timezone_get());
                echo "
    <tr>
        <td class='secLine'>
            ".$beautifulDate."
        </td>
    </tr>";

                if(!isset($previousDate))
                {
                    $sql = "SELECT NOW()";
                    $previousDate = getOneResult($sql);
                }



                $sql = "SELECT access_tool, count(access_tool), access_cours_code
                            FROM $TABLETRACK_ACCESS
                            WHERE access_user_id = '".$_user['user_id']."'".
                                //AND access_tool IS NOT NULL
                                "AND access_date > '".$value."'
                                AND access_date < '".$previousDate."'
                            GROUP BY access_tool, access_cours_code
                            ORDER BY access_cours_code ASC";

                $results2 = getManyResults3Col($sql);

                if (is_array($results2))
                {
                    echo "
    <tr>
        <td colspan='2'>
            <table width='50%' cellpadding='0' cellspacing='0' border='0' >";

                    $previousCourse = "???";
                    for($j = 0 ; $j < count($results2) ; $j++)
                    {
                        // if course is different, write the name of the course
                        if($results2[$j][2] != $previousCourse)
                        {
                            echo "
                <tr>
                    <td colspan='2' width='100%' style='padding-left : 40px;'>
                            ".$results2[$j][2]."
                    </td>
                </tr>";
                        }
                        // if count != de 0 then display toolname et number of visits, else its a course visit
                        if( $results2[$j][1] != 0 )
                        {
                            echo "<tr>";
                            echo "<td width='70%' style='padding-left : 60px;'>".get_lang(ucfirst($results2[$j][0]))."</td>";
                            echo "<td width='30%' align='right'>".$results2[$j][1]." ".get_lang('Visits')."</td>";
                            echo "</tr>";
                        }
                        $previousCourse = $results2[$j][2];
                    }
                    echo "</table>";
                    echo "</td></tr>";
                }
                $previousDate = $value;
            }

        }
        else
        {
            echo "<tr>";
            echo "<td colspan='2' bgcolor='#eeeeee' align='center' >".get_lang('NoResult')."</td>";
            echo"</tr>";
        }
        echo "</table>";
        echo "</td></tr>";
    }
    else
    {
        $tempView[0] = '1';
        echo "
            <tr>
                    <td valign='top'>
                    +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?view=".$tempView."' class='specialLink'>".get_lang('Logins')."</a>
                    </td>
            </tr>
        ";
    }


    /***************************************************************************
     *
     *		Exercices
     *
     ***************************************************************************/
    /*
    $tempView = $view;
    if($view[1] == '1')
    {
        $tempView[1] = '0';
        echo "
            <tr>
                    <td valign='top'>
                    <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".get_lang('ExercicesResults')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?view=".$tempView."'>".get_lang('Close')."</a>]
                    </td>
            </tr>
        ";
        echo " Ceci est amen  etre dplac vers la page de garde des exercices ";
        $sql = "SELECT ce.title, te.exe_result , te.exe_weighting, te.exe_date
                    FROM $TABLECOURSE_EXERCICES AS ce , $TABLETRACK_EXERCICES AS te
                    WHERE te.exe_user_id = '".$_user['user_id']."'
                        AND te.exe_exo_id = ce.id
                    ORDER BY te.exe_cours_id ASC, ce.title ASC, te.exe_date ASC";

        echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
        $results = getManyResultsXCol($sql,4);
        echo "<table cellpadding='2' cellspacing='1' border='0' align='center'>";
        echo "<tr>
                <td class='secLine' width='60%'>
                ".get_lang('ExercicesTitleExerciceColumn')."
                </td>
                <td class='secLine' width='20%'>
                ".get_lang('Date')."
                </td>
                <td class='secLine' width='20%'>
                ".get_lang('ExercicesTitleScoreColumn')."
                </td>
            </tr>";
        if (is_array($results))
        {
            for($i = 0; $i < sizeof($results); $i++)
            {
                if( $results[$i][1] < ($results[$i][2]/2) )
                    $scoreColor = "red";
                elseif( $results[$i][1] > ($results[$i][2]/100*60) )
                    $scoreColor = "green";
                else
                    $scoreColor = "#FF8C00";
                echo "<tr>";
                echo "<td class='content'>".$results[$i][0]."</td>";
                echo "<td class='content'>".$results[$i][3]."</td>";
                echo "<td valign='top' align='right' class='content'><font color=$scoreColor>".$results[$i][1]." / ".$results[$i][2]."</font></td>";
                echo"</tr>";
            }

        }
        else
        {
            echo "<tr>";
            echo "<td colspan='2' align='center'>".get_lang('NoResult')."</td>";
            echo"</tr>";
        }
        echo "</table>";
        echo "</td></tr>";

    }
    else
    {
        $tempView[1] = '1';
        echo "
            <tr>
                    <td valign='top'>
                    <font color='#0000FF'>+&nbsp;&nbsp;</font><a href='".api_get_self()."?view=".$tempView."' class='specialLink'>".get_lang('ExercicesResults')."</a>
                    </td>
            </tr>
        ";
    }
    */
    echo "</table>";
Display::display_footer();