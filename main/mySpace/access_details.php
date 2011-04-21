<?php
/* For licensing terms, see /license.txt */
/**
*	This is the tracking library for Chamilo.
*
*	@package chamilo.library
*
* Calculates the time spent on the course
* @param integer $user_id the user id
* @param string $course_code the course code
* @author Julio Montoya <gugli100@gmail.com>
* @author Jorge Frisancho Jibaja - select between dates
* 
*/

// name of the language file that needs to be included
$language_file = array ('registration', 'index', 'tracking');

require_once '../inc/global.inc.php';

// including additional libraries
require_once api_get_path(LIBRARY_PATH).'pchart/pData.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pChart.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pCache.class.php';

require_once 'myspace.lib.php';

// the section (for the tabs)
$this_section = SECTION_TRACKING;

/* MAIN */
$user_id = intval($_REQUEST['student']);
$session_id = intval($_GET['id_session']);
$type = Security::remove_XSS($_REQUEST['type']);
$course_code = Security::remove_XSS($_REQUEST['course']);
$connections = MySpace::get_connections_to_course($user_id, $course_code, $session_id);

$quote_simple = "'";

$htmlHeadXtra[] = api_get_jquery_ui_js();

$htmlHeadXtra[] = '<script src="slider.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="slider.css" />';

$htmlHeadXtra[] = '<script type="text/javascript">
$(function() {
    var dates = $( "#date_from, #date_to" ).datepicker({
        dateFormat: '.$quote_simple.'yy-mm-dd'.$quote_simple.',
        changeMonth: true,
    changeYear: true,
        onSelect: function( selectedDate ) {
            var foo = areBothFilled();
            var option = this.id == "date_from" ? "minDate" : "maxDate",
                instance = $( this ).data( "datepicker" );
                date = $.datepicker.parseDate(
                    instance.settings.dateFormat ||
                    $.datepicker._defaults.dateFormat,
                    selectedDate, instance.settings );
            dates.not( this ).datepicker( "option", option, date );
            
            if (foo){
                var start_date  = document.getElementById("date_from").value;
                var end_date    = document.getElementById("date_to").value;
                changeHREF(start_date,end_date);
                var foo_student = '.$user_id.';
                var foo_course  = "'.$course_code.'";
                var graph_type  = "'.$type.'";
                var foo_slider_state = getSliderState();

                if (foo_slider_state == "open"){
                    sliderAction();
                }
                $.post("'.api_get_path(WEB_AJAX_PATH).'myspace.ajax.php?a=access_detail_by_date", {startDate: start_date, endDate: end_date, course: foo_course, student: foo_student, type: graph_type}, function(db)
                {
                    if (!db.is_empty){
                        // Display confirmation message to the user
                        $("#messages").html(db.result).stop().css("opacity", 1).fadeIn(30);
                        $("#cev_cont_stats").html(db.stats);
                        $( "#ui-tabs-1" ).empty();
                        $( "#ui-tabs-2" ).empty();
                        $( "#ui-tabs-1" ).html(db.graph_result);
                        $( "#ui-tabs-2" ).html(db.graph_result);
                    }
                    else{
                        $("#messages").text("No existen registros para este rango");
                        $("#messages").addClass("warning-message");
                        $("#cev_cont_stats").html(db.stats);
                        $( "#ui-tabs-1" ).empty();
                        $( "#ui-tabs-1" ).html(db.graph_result);
                        controlSliderMenu(foo_height);
                    }
                    var foo_height = sliderGetHeight("#messages");
                    sliderSetHeight(".slider",foo_height);
                    controlSliderMenu(foo_height);
                    //$("#messages").css("height", foo_height);
                    // Hide confirmation message and enable stars for "Rate this" control, after 2 sec...
                    /*setTimeout(function(){
                            $("#messages").fadeOut(1000, function(){ui.enable()})
                    }, 2000);*/
                }, "json");
                
                $( "#cev_slider" ).empty();
                // Create element to use for confirmation messages
                $('.$quote_simple .'<div id="messages"/>'.$quote_simple .').appendTo("#cev_slider");
                
            }
        }
     });
    if (areBothFilled()){
        runEffect();        
    }
});

</script>';


$htmlHeadXtra[] = '<script type="text/javascript">

function changeHREF(sd,ed) {
    var i       = 0;
    var href    = "";
    var href1   = "";
    $('.$quote_simple .'#container-9 a'.$quote_simple .').each(function() {
        href = $.data(this, '.$quote_simple .'href.tabs'.$quote_simple .');
        href1= href+"&sd="+sd+"&ed="+ed+"&range=1";
        $("#container-9").tabs("url", i, href1);
        var href1 = $.data(this, '.$quote_simple .'href.tabs'.$quote_simple .');
        i++
    })
}

function runEffect(){
    //most effect types need no options passed by default
    var options = {};
     //run the effect
    $("#cev_button").show('.$quote_simple .'slide'.$quote_simple .',options,500,cev_effect());
}

//callback function to bring a hidden box back
function cev_effect(){
    setTimeout(function(){
        $("#cev_button:visible").removeAttr('.$quote_simple .'style'.$quote_simple .').hide().fadeOut();
    }, 1000);
}

function areBothFilled() {
        var returnValue = false;
        if ((document.getElementById("date_from").value != "") && (document.getElementById("date_to").value != "")){
            returnValue = true;
        }
        return returnValue;
}
</script>';

$htmlHeadXtra[] = '<script type="text/javascript">
$(function() {
        $("#cev_button").hide();
    $("#container-9").tabs({remote: true});
});
</script>';

//Changes END

Display :: display_header('');
$tbl_userinfo_def = Database :: get_course_table(TABLE_USER_INFO);
$main_user_info = api_get_user_info($user_id);

$result_to_print = '';
$main_date_array = array();

$sql_result      = MySpace::get_connections_to_course($user_id, $course_code);
$result_to_print = convert_to_string($sql_result);

api_display_tool_title(get_lang('DetailsStudentInCourse'));
?>
<div id="cev_results_header" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
<div id="cev_cont" class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
<?php echo '<strong>'.get_lang('User').': '.api_get_person_name($main_user_info['firstName'], $main_user_info['lastName']).'</strong> <br /> <strong>'.get_lang('Course').': </strong>'.$course_code; ?></div>
<br />
<form action="javascript:get(document.getElementById('myform'));" name="myform" id="myform">
<div id="cev_cont_header">
    <p><?php echo get_lang('SelectADateRange')?></p>
    <label for="to"><?php echo get_lang('From')?></label>
    <input type="text" id="date_from" name="from"/>
    <label for="to"><?php echo get_lang('Until')?></label>
    <input type="text" id="date_to" name="to"/>
</div><br /><br />
</form>
<input id="cev_button" type=button class="ui-state-default ui-corner-all" value="Resetear Fechas" onClick="javascript:window.location='access_details.php?course=<?php echo $course_code?>&student=<?php echo $user_id?>&cidReq=<?php echo $course_code ?>';" >
</div><br />

<div id="cev_results" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
    <div class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all"><?php echo get_lang('Statistics'); ?></div><br />
    <div id="cev_cont_stats">
        <?php
        if ($result_to_print != "")  {
            $rst                = get_stats($user_id, $course_code);            
            $foo_stats           = '<strong>'.get_lang('Total').': </strong>'.$rst['total'].'<br />';
            $foo_stats          .= '<strong>'.get_lang('Average').': </strong>'.$rst['avg'].'<br />';
            $foo_stats          .= '<strong>'.get_lang('Quantity').' : </strong>'.$rst['times'].'<br />';            
            echo $foo_stats;
        } else {
            echo Display::display_warning_message(get_lang('NoDataAvailable'));
        }
        ?>
    </div><br />
</div><br />

<div id="container-9">
    <ul>
        <li><a href="<?php echo api_get_path(WEB_AJAX_PATH).'myspace.ajax.php?a=access_detail&type=day&course='.$course_code.'&student='.$user_id?>"><span> <?php echo api_ucfirst(get_lang('Day')); ?></span></a></li>
        <li><a href="<?php echo api_get_path(WEB_AJAX_PATH).'myspace.ajax.php?a=access_detail&type=month&course='.$course_code.'&student='.$user_id?>"><span> <?php echo api_ucfirst(get_lang('MinMonth')); ?></span></a></li>        
    </ul>
</div>


<div id="cev_results" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
    <div class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all"><?php echo get_lang('DateAndTimeOfAccess'),' - ', get_lang('Duration') ?></div><br />
    <div id="cev_cont_results" >
    <div id="cev_slider" class="slider">
        <?php
        if ($result_to_print != "")  {
            echo $result_to_print;
        } else {
            Display::display_warning_message(get_lang('NoDataAvailable'));
        }        
        ?>
    </div>
    <?php
    if ($result_to_print != "")  {
        echo ('<br /><div class="slider_menu">
        <a href="#" onclick="return sliderAction();">'.get_lang('More').'</a>
        </div><br />');
    }?>
    </div>
</div><br />
<?php
Display:: display_footer();