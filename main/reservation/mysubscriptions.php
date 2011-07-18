<?php
/* For licensing terms, see /license.txt */

/**
        An overview with a list of upcoming reservations where
        the user has subscribed to (may also be viewable in the agenda)

        Later: links to m_item & m_reservation for every item your group (class) owns and
        the possibility (links) for adding new items or reservations
 */
require_once('rsys.php');

Rsys::protect_script('mysubscriptions');
$tool_name = get_lang('Booking');

/**
 *  Filter to display the modify-buttons
 */
function modify_filter($id){
     return ' <a href="mysubscriptions.php?action=delete&amp;reservation_id='.substr($id,0,strpos($id,'-')).'&amp;dummy='.substr($id,strrpos($id,'-')+1).'" title="'.get_lang("DeleteSubscription").'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmDeleteSubscription")))."'".')) return false;"><img alt="" src="../img/delete.gif" /></a>';
}

switch ($_GET['action']) {
    case 'delete' :
        Rsys :: delete_subscription($_GET['reservation_id'],$_GET['dummy']);
        ob_start();
        Display :: display_normal_message(Rsys::get_return_msg(get_lang('SubscriptionDeleted'),"mysubscriptions.php",$tool_name),false);
        $msg=ob_get_contents();
		ob_end_clean();
    default :
        $NoSearchResults=get_lang('NoReservations');
        Display :: display_header($tool_name);
        api_display_tool_title($tool_name);

        if (api_is_allowed_to_create_course()) {
	        echo '<div class="actions">';
			echo '<div style="float: right;"><a href="reservation.php">'.Display::return_icon('sessions.gif',get_lang('BookingCalendarView')).'&nbsp;'.get_lang('GoToCalendarView').'</a></div>';
			echo '<a href="m_item.php?view=list">'.Display::return_icon('cube.png',get_lang('Resources')).'&nbsp;'.get_lang('Resources').'</a>';
			echo '&nbsp;&nbsp;<a href="m_reservation.php?view=list">'.Display::return_icon('calendar_day.gif',get_lang('BookingPeriods')).'&nbsp;'.get_lang('BookingPeriods').'</a>';
			echo '&nbsp;&nbsp;<a href="m_reservation.php?action=add&view=list">'.Display::return_icon('calendar_add.gif',get_lang('BookIt')).'&nbsp;'.get_lang('BookIt').'</a>';

			if (api_is_platform_admin()) {
				//echo '&nbsp;&nbsp;<a href="m_category.php">'.Display::return_icon('settings.gif',get_lang('Configuration')).'&nbsp;'.get_lang('Configuration').'</a>';
			}
			echo '</div><br />';
        }

        if (isset ($_POST['action'])) {
            switch ($_POST['action']) {
                case 'delete_subscriptions' :
                    $ids = $_POST['subscriptions'];
                    if (count($ids) > 0) {
                        foreach ($ids as $id)
                            Rsys :: delete_subscription(substr($id,0,strpos($id,'-')),substr($id,strrpos($id,'-')+1));
                    }
                    break;
            }
        }

        $table = new SortableTable('subscription', array('Rsys','get_num_subscriptions'),array('Rsys','get_table_subscriptions'),2);
        $table->set_header(0, '', false,array('style'=>'width:10px'));
        $table->set_header(1, get_lang('ResourceName'), true);
        $table->set_header(2, get_lang('StartDate'), true);
        $table->set_header(3, get_lang('EndDate'), true);
		$table->set_header(4, get_lang('Accept'), true);
        $table->set_header(5, get_lang('Modify'), false,array('style'=>'width:50px;'));
        $table->set_column_filter(5, 'modify_filter');
        $table->set_form_actions(array ('delete_subscriptions' => get_lang('DeleteSelectedSubscriptions')),'subscriptions');
        $table->display();
}

/**
    ---------------------------------------------------------------------
 */

Display :: display_footer();
?>
