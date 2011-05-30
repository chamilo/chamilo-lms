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

if (api_is_allowed_to_edit(null, true)) {
    $param_gradebook = '';
    if (isset($_SESSION['gradebook'])) {
        $param_gradebook = '&gradebook='.$_SESSION['gradebook'];
    }   
    if (!$is_locked_attendance || api_is_platform_admin()) {
        echo '<div class="actions" style="margin-bottom:30px">';
        echo '<a href="index.php?'.api_get_cidreq().'&action=calendar_list&attendance_id='.$attendance_id.$param_gradebook.'">'.Display::return_icon('attendance_calendar.png',get_lang('AttendanceCalendar'),'','32').'</a>';
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
    
    $form = new FormValidator('filter', 'post', 'index.php?action=attendance_sheet_add&'.api_get_cidreq().$param_gradebook.'&attendance_id='.$attendance_id);
    
    $values = array('all'           => get_lang('All'), 
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
    
    $form->addElement('select', 'filter', get_lang('Filter'), $values);   
    $form->addElement('style_submit_button', null, get_lang('Filter'), 'class="filter"');
    
    if (isset($_REQUEST['filter'])) {        
        if (in_array($_REQUEST['filter'], array_keys($values))) {
            $default_filter = $_REQUEST['filter'];
        }       
    } else {
        $default_filter = 'today';      
    }   
    $form->setDefaults(array('filter'=>$default_filter));
    $param_filter = '&filter='.Security::remove_XSS($default_filter);
    
    
    
    if (count($users_in_course) > 0) { 
        $form->display();  
    ?>
    <script type="text/javascript">
    function UpdateTableHeaders() {
        $("div.divTableWithFloatingHeader").each(function() {
            var originalHeaderRow = $(".tableFloatingHeaderOriginal", this);
            var floatingHeaderRow = $(".tableFloatingHeader", this);
            var offset = $(this).offset();
            var scrollTop = $(window).scrollTop();
            if ((scrollTop > offset.top) && (scrollTop < offset.top + $(this).height())) {
                floatingHeaderRow.css("visibility", "hidden");
                floatingHeaderRow.css("top", Math.min(scrollTop - offset.top, $(this).height() - floatingHeaderRow.height()) + "px");

                // Copy cell widths from original header
                $("th", floatingHeaderRow).each(function(index) {
                    var cellWidth = $("th", originalHeaderRow).eq(index).css('width');
                    $(this).css('width', cellWidth);
                });

                // Copy row width from whole table
                floatingHeaderRow.css("width", $(this).css("width")); 
                floatingHeaderRow.css("visibility", "visible");                  
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
                    <th height="65px" width="10px"><?php echo '#'; ?></th>
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
                    if ($i%2 == 0) {$class='row_odd';}
                    else {$class='row_even';}
                ?>
                    <tr class="<?php echo $class ?>">
                    <td><center><?php echo $i ?></center></td>
                    <td><?php echo $data['photo'] ?></td>
                    <td><?php echo $data['lastname'] ?></td>
                    <td><?php echo $data['firstname'] ?></td>
                    <td>
                        <div style="height:56px">
                        <center>
                        <div class="attendance-faults-bar" style="background-color:<?php echo (!empty($data['result_color_bar'])?$data['result_color_bar']:'none') ?>">
                        <?php echo $data['attendance_result'] ?></div>
                        </center>
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

        <div class="divTableWithFloatingHeader attendance-calendar-table" style="margin:0px;padding:0px;float:left;width:55%;overflow:auto;overflow-y:hidden;">
            <table class="tableWithFloatingHeader data_table" width="100%">
                <thead>         
                <?php
                    if (count($attendant_calendar) > 0 ) {
                        foreach ($attendant_calendar as $calendar) {
                            $date = $calendar['date'];
                            $time = $calendar['time'];
                            $datetime = $date.'<br />'.$time;
                                                        
                            $img_lock = Display::return_icon('lock.gif',get_lang('DateUnLock'),array('class'=>'img_lock','id'=>'datetime_column_'.$calendar['id']));                                                    
                            if (!empty($calendar['done_attendance'])){
                                $datetime = '<font color="blue">'.$date.'<br />'.$time.'</font>';
                            }
                            $disabled_check = 'disabled';
                            $input_hidden = '<input type="hidden" id="hidden_input_'.$calendar['id'].'" name="hidden_input[]" value="" disabled />';                        
                            if ($next_attendance_calendar_id == $calendar['id']) {
                                $input_hidden = '<input type="hidden" id="hidden_input_'.$calendar['id'].'" name="hidden_input[]" value="'.$calendar['id'].'" />';
                                $disabled_check = '';
                                $img_lock = Display::return_icon('unlock.gif',get_lang('DateLock'),array('class'=>'img_unlock','id'=>'datetime_column_'.$calendar['id']));
                            }                                   
                                                
                            $result .= '<th height="65px" width="500px" style="padding:1px 5px;" >';
                            $result .= '<center><div style="font-size:10px;width:80px;">'.$datetime.'&nbsp;';
                            $result .= '<span id="attendance_lock" style="cursor:pointer">'.(!$is_locked_attendance || api_is_platform_admin()?$img_lock:'').'</span>';
                            $result .= '<br /><input type="checkbox" id="checkbox_head_'.$calendar['id'].'" '.$disabled_check.' checked />'.$input_hidden.'</div></center></th>';
                         }                  
                    } else { 
                        $result = '<th height="65px" width="2000px"><span><a href="index.php?'.api_get_cidreq().'&action=calendar_list&attendance_id='.$attendance_id.$param_gradebook.'">';
                        $result .=Display::return_icon('attendance_calendar.png',get_lang('AttendanceCalendar'),'','32').' '.get_lang('GoToAttendanceCalendar').'</a></span></th>';
                    }
                    ?>
                    
                <tr class="tableFloatingHeader row_odd" style="position: absolute; top: 0px; left: 0px; visibility: hidden; margin:0px;padding:0px" >   
                <?php echo $result; ?>
                </tr>
                
                <tr class="tableWithFloatingHeader row_odd">
                <?php echo $result; ?>
                </tr>
                
                </thead>
                <tbody>         
                <?php 
                $i = 0;
                foreach ($users_in_course as $user) { 
                        $class = '';
                        if ($i%2==0) {$class = 'row_even';}
                        else {$class = 'row_odd';}
                ?>          
                    <tr class="<?php echo $class ?>">
                    <?php 
                        if (count($attendant_calendar) > 0 ) {
                            foreach ($attendant_calendar as $calendar) {
                                $checked = 'checked';                           
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
                                    $style_td = 'background-color:#e1e1e1';
                                    $disabled = '';
                                }
                    ?>
                            <td style="<?php echo $style_td ?>" class="checkboxes_col_<?php echo $calendar['id'] ?>">
                                <div style="height:56px">
                                 <center>
                                    <?php if (!$is_locked_attendance || api_is_platform_admin()) { ?>
                                        <input type="checkbox" name="check_presence[<?php echo $calendar['id'] ?>][]" value="<?php echo $user['user_id'] ?>"  <?php echo $disabled.' '.$checked ?> /><span class="<?php echo 'anchor_'.$calendar['id'] ?>"></span>
                                    <?php } else { 
                                                echo $presence?Display::return_icon('checkbox_on.gif',get_lang('Presence')):Display::return_icon('checkbox_off.gif',get_lang('Presence'));
                                        } ?>
                                </center>
                                </div>
                            </td>
                            
                    <?php   } 
                        } else { ?>
                            <td class="checkboxes_col_<?php echo $calendar['id'] ?>">
                                <div style="height:56px">
                                    <center>&nbsp;</center>
                               </div>
                            </td>
                  <?php } ?>
                    </tr>
                <?php $i++ ;            
                } 
                ?>  
                </tbody>            
            </table>
        </div>  
    </div>
    <div class="clear"></div>
    <div style="margin-top:20px;"><?php if (!$is_locked_attendance || api_is_platform_admin()) { ?><button type="submit" class="save"><?php echo get_lang('Save') ?></button><?php } ?></div>
    </form> 
    <?php } else {  
        echo '<div><a href="'.api_get_path(WEB_CODE_PATH).'user/user.php?'.api_get_cidreq().'">'.get_lang('ThereAreNoRegisteredLearnersInsidetheCourse').'</a></div>';  
    }

} else {
    // View for students
?>  
    <h3><?php echo get_lang('AttendanceSheetReport') ?></h3>
    <?php if(!empty($users_presence)) { ?>
        <div>
            <table width="250px;">
            <tr>
                <td><?php echo get_lang('ToAttend').': ' ?></td>
                <td><center><div class="attendance-faults-bar" style="background-color:<?php echo (!empty($faults['color_bar'])?$faults['color_bar']:'none') ?>"><?php echo $faults['faults'].'/'.$faults['total'].' ('.$faults['faults_porcent'].'%)' ?></div></center></td>
            </tr>
            </table>
        </div>
    <?php } ?>
    <div>
    <br />
    <center>
        <table class="data_table">
                <tr class="row_odd" >   
                    <th><?php echo get_lang('AttendanceCalendar')?></th>
                    <th><?php echo get_lang('Attendance')?></th>                
                </tr>
                <?php 
                
                if (!empty($users_presence)) {
                    $i = 0;
                    foreach($users_presence[$user_id] as $presence) { 
                        $class = '';
                        if ($i%2==0) {$class = 'row_even';}
                        else {$class = 'row_odd';}  
                    ?>
                    <tr class="<?php echo $class ?>"><td><?php echo Display::return_icon('lp_calendar_event.png',get_lang('DateTime')).' '.$presence['date_time'] ?></td><td><center><?php echo $presence['presence']?Display::return_icon('checkbox_on.gif',get_lang('Presence')):Display::return_icon('checkbox_off.gif',get_lang('Presence')) ?></center></td></tr>                  
                <?php } 
                } else { ?>
                <tr><td colspan="2"><center><?php echo get_lang('YouDoNotHaveDoneAttendances')?></center></td></tr> 
                <?php }
                
                ?>
        </table>            
    </center>
    </div>
<?php } ?>