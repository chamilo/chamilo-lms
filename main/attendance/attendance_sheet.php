<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for attendance sheet (list, edit, add) 
* @author Christian Fasanando <christian1827@gmail.com>
* @author Julio Montoya reworked 2010
* @package chamilo.attendance
*/

// protect a course script
api_protect_course_script(true);

if (api_is_allowed_to_edit(null, true) || api_is_coach(api_get_session_id(), api_get_course_id())) {
    
    $param_gradebook = '';
    if (isset($_SESSION['gradebook'])) {
        $param_gradebook = '&gradebook='.$_SESSION['gradebook'];
    }   
    
    $form = new FormValidator('filter', 'post', 'index.php?action=attendance_sheet_list&'.api_get_cidreq().$param_gradebook.'&attendance_id='.$attendance_id, null, array('class' => 'form-search pull-left'));    
    $values = array(
        'all'           => get_lang('All'), 
        'today'         => get_lang('Today'),
        'all_done'      => get_lang('AllDone'), 
        'all_not_done'  => get_lang('AllNotDone')
    );                    
    $today = api_convert_and_format_date(null, DATE_FORMAT_SHORT);
    $exists_attendance_today = false;
    
    if (!empty($attendant_calendar_all)) {
        $values[''] = '---------------';
        foreach($attendant_calendar_all as $attendance_date) {
            if ($today == $attendance_date['date']) {
                $exists_attendance_today = true; 
            }                        
            $values[$attendance_date['id']] = $attendance_date['date_time'];
        }
    }
    
    if (!$exists_attendance_today) {
        Display::display_warning_message(get_lang('ThereIsNoClassScheduledTodayTryPickingAnotherDay'));
    }
    
    $form->addElement('select', 'filter', get_lang('Filter'), $values, array('id' => 'filter_id'));   
    $form->addElement('style_submit_button', null, get_lang('Filter'), 'class="filter"');
    
    if (isset($_REQUEST['filter'])) {        
        if (in_array($_REQUEST['filter'], array_keys($values))) {
            $default_filter = $_REQUEST['filter'];
        }       
    } else {
        $default_filter = 'today';      
    }  
    
    $renderer = $form->defaultRenderer();
    $renderer->setElementTemplate('{label} {element} ');
    
    $form->setDefaults(array('filter'=>$default_filter));
    
    if (!$is_locked_attendance || api_is_platform_admin()) {
        echo '<div class="actions">';
        echo '<a style="float:left;" href="index.php?'.api_get_cidreq().'&action=calendar_list&attendance_id='.$attendance_id.$param_gradebook.'">'.
                Display::return_icon('attendance_calendar.png',get_lang('AttendanceCalendar'),'',ICON_SIZE_MEDIUM).'</a>';
        if (count($users_in_course) > 0) {
            $form->display(); 
        }
        echo '<a id="pdf_export" style="float:left;"  href="index.php?'.api_get_cidreq().'&action=attendance_sheet_export_to_pdf&attendance_id='.$attendance_id.$param_gradebook.'&filter='.$default_filter.'">'.
                Display::return_icon('pdf.png',get_lang('ExportToPDF'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '</div>';
    }
    
    $message_information = get_lang('AttendanceSheetDescription');
    if (!empty($message_information)) {
        $message = '<strong>'.get_lang('Information').'</strong><br />';
        $message .= $message_information;
        Display::display_normal_message($message, false);
    }

    if ($is_locked_attendance) {
        Display::display_warning_message(get_lang('TheAttendanceSheetIsLocked'), false);
    }
    
    $param_filter = '&filter='.Security::remove_XSS($default_filter);
    
    if (count($users_in_course) > 0) {
        
    ?>
    <script>
    var original_url = '';    
    $("#filter_id").on('change', function() {
       filter = $(this).val();
       if (original_url == '') {
          original_url = $("#pdf_export").attr('href');
       }
       new_url =  original_url + "&filter=" +filter
       $("#pdf_export").attr('href', new_url);
       //console.log(new_url);
    });
    
    function UpdateTableHeaders() {
        $("div.divTableWithFloatingHeader").each(function() {
            var originalHeaderRow = $(".tableFloatingHeaderOriginal", this);
            var floatingHeaderRow = $(".tableFloatingHeader", this);
            var offset = $(this).offset();
            var scrollTop = $(window).scrollTop();
            if ((scrollTop > offset.top) && (scrollTop < offset.top + $(this).height())) {
                floatingHeaderRow.css("visibility", "hidden");
                var topbar = 0;
                if ($("#topbar").length != 0) {
                    topbar = $("#topbar").height();
                } else {                
                    if ($(".subnav").length != 0) {
                        topbar = $(".subnav").height();
                    }
                }
                
                var top_value = Math.min(scrollTop - offset.top, $(this).height() - floatingHeaderRow.height()) + topbar;
                
                floatingHeaderRow.css("top",  top_value + "px");

                // Copy cell widths from original header
                $("th", floatingHeaderRow).each(function(index) {
                    var cellWidth = $("th", originalHeaderRow).eq(index).css('width');
                    $(this).css('width', cellWidth);
                });

                // Copy row width from whole table
                floatingHeaderRow.css("width", $(this).css("width")); 
                floatingHeaderRow.css("visibility", "visible");
                floatingHeaderRow.css("z-index", "1000");
                originalHeaderRow.css("height", "80px");                  
            } else {
                floatingHeaderRow.css("visibility", "hidden");
                floatingHeaderRow.css("top", "0px");
            }
        });
    }

    $(document).ready(function() {
		$("table.tableWithFloatingHeader").each(function() {
            $(this).wrap("<div class=\"divTableWithFloatingHeader\" style=\"position:relative\"></div>");
    
            var originalHeaderRow = $("tr:first", this)
            originalHeaderRow.before(originalHeaderRow.clone());
            var clonedHeaderRow = $("tr:first", this)
    
            clonedHeaderRow.addClass("tableFloatingHeader");
            clonedHeaderRow.css("position", "absolute");
            clonedHeaderRow.css("top", "0px");
            clonedHeaderRow.css("left", $(this).css("margin-left"));
            clonedHeaderRow.css("visibility", "hidden");
    
            originalHeaderRow.addClass("tableFloatingHeaderOriginal");
        });
    
        UpdateTableHeaders();
        $(window).scroll(UpdateTableHeaders);
        $(window).resize(UpdateTableHeaders);
    });
	</script>

    <form method="post" action="index.php?action=attendance_sheet_add&<?php echo api_get_cidreq().$param_gradebook.$param_filter ?>&attendance_id=<?php echo $attendance_id?>" >
    
    <div class="attendance-sheet-content" style="width:100%;background-color:#E1E1E1;margin-top:20px;">
        <div class="divTableWithFloatingHeader attendance-users-table" style="width:45%;float:left;margin:0px;padding:0px;">
            <table class="tableWithFloatingHeader data_table" width="100%">
                <thead>
                <tr class="tableFloatingHeader" style="position: absolute; top: 0px; left: 0px; visibility: hidden; margin:0px;padding:0px" >   
                    <th width="10px"><?php echo '#'; ?></th>
                    <th width="10px"><?php echo get_lang('Photo')?></th>
                    <th width="100px"><?php echo get_lang('LastName')?></th>
                    <th width="100px"><?php echo get_lang('FirstName')?></th>
                    <th width="100px"><?php echo get_lang('AttendancesFaults')?></th>
                </tr>
                <tr class="tableFloatingHeaderOriginal" >   
                    <th height="65px" width="10px"><?php echo '#';?></th>
                    <th width="10px"><?php echo get_lang('Photo')?></th>
                    <th width="150px"><?php echo get_lang('LastName')?></th>
                    <th width="140px"><?php echo get_lang('FirstName')?></th>
                    <th width="100px"><?php echo get_lang('AttendancesFaults')?></th>
                </tr>
                </thead>
                                
                <tbody>
                <?php 
                $i = 1;
                $class = '';                
                foreach ($users_in_course as $data) {
                    $faults = 0;
                    if ($i%2 == 0) {
                        $class='row_odd';                        
                    } else {
                        $class='row_even';
                    }
                    $username = api_htmlentities(sprintf(get_lang('LoginX'), $data['username']), ENT_QUOTES);
                ?>
                    <tr class="<?php echo $class ?>">
                        <td><center><?php echo $i ?></center></td>
                        <td><?php echo $data['photo'] ?></td>
                        <td><span title="<?php echo $username ?>"><?php echo $data['lastname'] ?></span></td>
                        <td><?php echo $data['firstname'] ?></td>
                        <td>
                            <div class="attendance-faults-bar" style="background-color:<?php echo (!empty($data['result_color_bar'])?$data['result_color_bar']:'none') ?>">
                                <?php echo $data['attendance_result'] ?>
                            </div>                        
                        </td>
                    </tr>
                <?php
                    $i++;
                }
                ?>
                </tbody>
            </table>
        </div>
        
        <?php     
        
        echo '<div class="divTableWithFloatingHeader attendance-calendar-table" style="margin:0px;padding:0px;float:left;width:55%;overflow:auto;overflow-y:hidden;">';
        echo '<table class="tableWithFloatingHeader data_table" width="100%">';
        echo '<thead>';
        if (count($attendant_calendar) > 0 ) {
            foreach ($attendant_calendar as $calendar) {
                $date = $calendar['date'];
                $time = $calendar['time'];
                $datetime = $date.'<br />'.$time;

                $img_lock = Display::return_icon('lock.gif',get_lang('DateUnLock'),array('class'=>'img_lock','id'=>'datetime_column_'.$calendar['id']));

                if (!empty($calendar['done_attendance'])){
                    $datetime = '<font color="blue">'.$date.'<br />'.$time.'</font>';
                }
                $disabled_check = 'disabled = "true"';
                $input_hidden = '<input type="hidden" id="hidden_input_'.$calendar['id'].'" name="hidden_input[]" value="" disabled />';                        
                if ($next_attendance_calendar_id == $calendar['id']) {
                    $input_hidden = '<input type="hidden" id="hidden_input_'.$calendar['id'].'" name="hidden_input[]" value="'.$calendar['id'].'" />';
                    $disabled_check = '';
                    $img_lock = Display::return_icon('unlock.gif',get_lang('DateLock'),array('class'=>'img_unlock','id'=>'datetime_column_'.$calendar['id']));
                }                                   

                $result .= '<th width="800px">';
                $result .= '<center><div style="font-size:10px;width:125px;">'.$datetime.'&nbsp;';

                if (api_is_allowed_to_edit(null, true)) {
                    $result .= '<span id="attendance_lock" style="cursor:pointer">'.(!$is_locked_attendance || api_is_platform_admin()?$img_lock:'').'</span>';
                }

                if ($is_locked_attendance == false) {
                    if (api_is_allowed_to_edit(null, true)) {
                        $result .= '<br /><input type="checkbox" class="checkbox_head_'.$calendar['id'].'" id="checkbox_head_'.$calendar['id'].'" '.$disabled_check.' checked="checked" />'.$input_hidden.'</div></center></th>';
                    }
                }
             }                  
        } else {
            $result  = '<th width="2000px"><span><a href="index.php?'.api_get_cidreq().'&action=calendar_list&attendance_id='.$attendance_id.$param_gradebook.'">';
            $result .= Display::return_icon('attendance_calendar.png',get_lang('AttendanceCalendar'),'',ICON_SIZE_MEDIUM).' '.get_lang('GoToAttendanceCalendar').'</a></span></th>';
        }

        echo '<tr class="tableFloatingHeader row_odd" style="position: absolute; top: 0px; left: 0px; visibility: hidden; margin:0px;padding:0px">';
        echo $result; 
        echo '</tr>';                
        echo '<tr class="tableWithFloatingHeader row_odd">';
        echo $result;
        echo '</tr>';
        echo '</thead>';
        
        echo '<tbody>';
        $i = 0;
        foreach ($users_in_course as $user) {
            $class = '';
            if ($i%2 == 0) {
                $class = 'row_even';                        
            } else {
                $class = 'row_odd';
            }
            echo '<tr class="'.$class.'">';

            if (count($attendant_calendar) > 0 ) {                            
                foreach ($attendant_calendar as $calendar) {
                    $checked = 'checked';
                    $presence = -1;

                    if (isset($users_presence[$user['user_id']][$calendar['id']]['presence'])) {
                        $presence = $users_presence[$user['user_id']][$calendar['id']]['presence'];                                    
                        if (intval($presence) == 1) {
                            $checked = 'checked';
                        } else {
                            $checked = '';
                        }
                    } else {
                        //if the user wasn't registered at that time, consider unchecked
                        if ($next_attendance_calendar_datetime == 0 || $calendar['date_time'] < $next_attendance_calendar_datetime) {
                            $checked = '';
                        }
                    }
                    $disabled = 'disabled';
                    $style_td = '';

                    if ($next_attendance_calendar_id == $calendar['id']) {
                        if ($i%2==0)
                            $style_td = 'background-color:#eee;';
                        else 
                            $style_td = 'background-color:#dcdcdc;';
                        $disabled = '';
                    }

                    echo '<td style="'.$style_td.'" class="checkboxes_col_'.$calendar['id'].'">';
                    echo '<div style="height:20px">';
                    echo '<center>';
                    if (api_is_allowed_to_edit(null, true)) {
                        if (!$is_locked_attendance || api_is_platform_admin()) {
                            echo '<input type="checkbox" name="check_presence['.$calendar['id'].'][]" value="'.$user['user_id'].'" '.$disabled.' '.$checked.' />';
                            echo '<span class="anchor_'.$calendar['id'].'"></span>';
                        } else { 
                            echo $presence ? Display::return_icon('checkbox_on.gif',get_lang('Presence')) : Display::return_icon('checkbox_off.gif',get_lang('Presence'));                            
                        }
                    } else {
                        switch($presence) {
                            case 1:
                                echo Display::return_icon('accept.png',get_lang('Attended'));
                                break;
                            case 0:
                                echo Display::return_icon('exclamation.png',get_lang('NotAttended'));
                                break;
                            case -1:                                
                                //echo Display::return_icon('warning.png',get_lang('NotAttended'));
                                break;
                        }                        
                    }
                    echo '</center>';
                    echo '</div>';
                    echo '</td>';
                }
            } else { 
                echo '<td class="checkboxes_col_'.$calendar['id'].'">';
                echo '<div style="height:20px">';
                echo '<center>&nbsp;</center>
                        </div>
                        </td>';
            }
            echo '</tr>';
            $i++ ;            
        }
        echo '</tbody></table>';
        echo '</div></div>';
    ?>
    <div class="clear"></div>
    <div style="margin-top:20px;">
        <?php if (!$is_locked_attendance || api_is_platform_admin()) { 
                if (api_is_allowed_to_edit(null, true)) {
            ?>
            <button type="submit" class="save"><?php echo get_lang('Save') ?></button>
        <?php }
            }
        ?>
    </div>
    </form> 
    <?php 
    } else {  
        echo Display::display_warning_message('<a href="'.api_get_path(WEB_CODE_PATH).'user/user.php?'.api_get_cidreq().'">'.get_lang('ThereAreNoRegisteredLearnersInsidetheCourse').'</a>', false);  
    }
} else {
    echo Display::page_header(get_lang('AttendanceSheetReport'));
    // View for students
?>      
    <?php if(!empty($users_presence)) { ?>
        <div>
            <table width="250px;">
            <tr>
                <td><?php echo get_lang('ToAttend').': ' ?></td>
                <td><center><div class="attendance-faults-bar" style="background-color:<?php echo (!empty($faults['color_bar'])?$faults['color_bar']:'none') ?>">
                    <?php echo $faults['faults'].'/'.$faults['total'].' ('.$faults['faults_porcent'].'%)' ?></div></center>
                </td>
            </tr>
            </table>
        </div>
    <?php } ?>
    <table class="data_table">
        <tr class="row_odd" >
            <th><?php echo get_lang('Attendance')?></th>
        </tr>
        <?php 
        
        if (!empty($users_presence)) {
            $i = 0;
            foreach ($users_presence[$user_id] as $presence) { 
                $class = '';
                if ($i%2==0) {$class = 'row_even';}
                else {$class = 'row_odd';}  
            ?>
            <tr class="<?php echo $class ?>">                    
                <td>                        
                    <?php echo $presence['presence']?Display::return_icon('checkbox_on.gif',get_lang('Presence')):Display::return_icon('checkbox_off.gif',get_lang('Presence')) ?>
                    <?php echo "&nbsp; ".$presence['date_time'] ?>                                                                       
                </td>
            </tr>                  
        <?php } 
        } else { ?>
            <tr><td>
                <center><?php echo get_lang('YouDoNotHaveDoneAttendances')?></center></td>
            </tr> 
        <?php }            
        ?>
    </table>
<?php } ?>