<?php
// $Id: m_reservation.php,v 1.26 2006/05/12 08:38:34 kvansteenkiste Exp $
/*
==============================================================================
    Dokeos - elearning and course management software

    Copyright (c) 2004-2008 Dokeos SPRL
    Copyright (c) Sebastien Jacobs (www.spiritual-coder.com)
    Copyright (c) Kristof Van Steenkiste
    Copyright (c) Julio Montoya Armas

    For a full list of contributors, see "credits.txt".
    The full license can be read in "license.txt".

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    See the GNU General Public License for more details.

    Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
    Mail: info@dokeos.com
==============================================================================
*/
/**
    ---------------------------------------------------------------------
                Reservation-manager (add, edit & delete)
    ---------------------------------------------------------------------
 */
require_once('rsys.php');

Rsys :: protect_script('m_reservation', $_GET['item_id']);
$tool_name = get_lang('BookingPeriodList');

/**
    ---------------------------------------------------------------------
 */

/**
 *  Filter to display the modify-buttons
 * 
 *  @param  -   int     $id     The reservation-id
 */
function modify_filter($id) {
		$out = '<a href="m_reservation.php?action=edit&amp;id='.$id.'" title="'.get_lang("EditBookingPeriod").'"><img alt="" src="../img/edit.gif" /></a>';
		$out .= ' <a href="m_reservation.php?action=delete&amp;id='.$id.'" title="'.get_lang("DeleteBookingPeriod").'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang("ConfirmDeleteBookingPeriod")))."'".')) return false;"><img alt="" src="../img/delete.gif" /></a>';
		$out .= ' <a href="m_reservation.php?action=accept&amp;rid='.$id.'" title="'.get_lang("AutoAccept").'"><img alt="" src="../img/visible.gif" /></a>';
	return $out;
}

if (isset ($_POST['action'])) {
	switch ($_POST['action']) {
		case 'accept_users' :
			$ids = $_POST['accepting'];
			//echo count($ids);
			if (count($ids) > 0) {
				foreach ($ids as $id) {
					//echo $id;
					$result = Rsys :: set_accepted($id, 1);
				}
			}
			$_GET['action'] = 'accept';
			break;
		case 'unaccept_users' :
			$ids = $_POST['accepting'];
			//echo count($ids);
			if (count($ids) > 0) {
				foreach ($ids as $id) {
					//echo $id;
					$result = Rsys :: set_accepted($id, 0);
				}
			}
			$_GET['action'] = 'accept';
			break;
		case 'delete_subscriptions' :
			$res_id = $_GET['rid'];
			$ids = $_POST['accepting'];
			//echo count($ids);
			if (count($ids) > 0) {
				foreach ($ids as $id) {
					$result = Rsys :: delete_subscription($res_id,$id);
				}
			}
			$_GET['action'] = 'accept';
			break;
	}
}

/**
    ---------------------------------------------------------------------
 */
 
switch ($_GET['action']) {
	case 'overviewsubscriptions' :
	
		$interbreadcrumb[] = array ("url" => "mysubscriptions.php", "name" => get_lang('Booking'));		
		$interbreadcrumb[] = array ("url" => "m_reservation.php", "name" => get_lang('ManageBookingPeriods'));		
		
		Display :: display_header(get_lang('OverviewSubscriptions'));
		api_display_tool_title(get_lang('Overview'));
		echo '<div class="actions">';   
		echo '<form id="cat_form" action="m_reservation.php" method="get">';		
		echo '<input type="hidden" name="action" value="overviewsubscriptions"/>';
		echo '<input type="text" name="keyword" /><input type="submit" value="'.get_lang('Search').'" /></form>';
		echo '</div><br>';		
		
		$table = new SortableTable('reservation', array ('Rsys', 'get_num_subscriptions_overview'), array ('Rsys', 'get_table_subcribed_reservations'), 1);		
		$table->set_additional_parameters(array ('action' => 'overviewsubscriptions','keyword' => $_GET['keyword']));
		$table->set_header(0, get_lang('ResourceName'), true);
		$table->set_header(1, get_lang('ResourceTypeName'), true);
		$table->set_header(2, get_lang('StartDate'), true);
		$table->set_header(3, get_lang('EndDate'), true);
		$table->set_header(4, get_lang('SubscribedPerson'), true);
		$table->set_header(5, get_lang('SubscribedStartDate'), true);
		$table->set_header(6, get_lang('SubscribedEndDate'), true);
		$table->set_header(7, get_lang('Accept'), true);
		$table->display();
		break;
	case 'accept' :
		$NoSearchResults = get_lang('NoReservation');
		if (empty ($_GET['rid'])) {
			$_GET['rid'] = $_POST['rid'];
		}
		if ($_GET['switch'] == 'edit') {
			Rsys :: set_accepted($_GET['dummy'], $_GET['set']);
		}
		if ($_GET['switch'] == 'delete') {
			Rsys :: delete_subscription($_GET['rid'],$_GET['dummy']);
		}

		$interbreadcrumb[] = array ("url" => "mysubscriptions.php", "name" => get_lang('Booking'));		
		$interbreadcrumb[] = array ("url" => "m_reservation.php", "name" => get_lang('ManageBookingPeriods'));
		
		
		Display :: display_header(get_lang('AutoAccept'));
		api_display_tool_title(get_lang('AutoAccept'));

		$table = new SortableTable('accepting', array ('Rsys', 'get_num_waiting_users'), array ('Rsys', 'get_table_waiting_users'), 1);
		$table->set_additional_parameters(array ('rid' => $_GET['rid'], 'action' => 'accept'));
		$table->set_header(0, '', false, array ('style' => 'width:10px'));
		$table->set_header(1, get_lang('SubscribedPerson'), true);
		$table->set_header(2, get_lang('Class'), true);
		$table->set_header(3, get_lang('SubscribedStartDate'), true);
		$table->set_header(4, get_lang('SubscribedEndDate'), true);		
		$table->set_header(5, get_lang('Accept'), true, array ('style' => 'width:30px;'));
		$table->set_header(6, get_lang('Delete'), true, array ('style' => 'width:30px;'));
		$table->set_form_actions(array ('accept_users' => get_lang('AcceptUsers'), 'unaccept_users' => get_lang('UnacceptedUsers'), 'delete_subscriptions' => get_lang('Delete_subscriptions')), 'accepting');
		//$table->set_form_actions(array ('accept_users' => get_lang('AcceptUsers'), 'unaccept_users' => get_lang('UnacceptedUsers')), 'accepting');
		$table->display();
		break;
	case 'add' :
		if (!isset ($_GET['cat_id']))
			$_GET['cat_id'] = 0;

		$interbreadcrumb[] = array ("url" => "mysubscriptions.php", "name" => get_lang('Booking'));		
		$interbreadcrumb[] = array ("url" => "m_reservation.php", "name" => get_lang('ManageBookingPeriods'));
		
		
		Display :: display_header(get_lang('AddNewBookingPeriod'));
		api_display_tool_title(get_lang('AddNewBookingPeriod'));

		ob_start();

		$cats = Rsys :: get_category_rights();
		echo '<form id="cat_form" action="m_reservation.php" method="get">';		
		echo '<input type="hidden" name="action" value="add"/>';		
		echo '<div class="row">';		
			echo '<div class="label">'.get_lang('ResourceType').': </div>';
			echo '<div class="formw">';
		
			echo '<select name="cat_id" onchange="this.form.submit();">';		
			echo '<option value="0">&nbsp;</option>';
			foreach ($cats as $catid => $cat)
				echo '<option value="'.$catid.'"'. ($catid == $_GET['cat_id'] ? ' selected="selected"' : '').'>'.$cat.'</option>';
			echo '</select></div>';	
		echo '</div></form>';
		
		$itemlist = Rsys :: get_cat_r_items($_GET['cat_id']);
		$form = new FormValidator('reservation', 'post', 'm_reservation.php?action=add&cat_id='.$_GET['cat_id']);
		$choices[] = $form->createElement('radio', 'forever', '', get_lang('NoPeriod'), 0, array ('onclick' => 'javascript:window_hide(\'forever_timewindow\')'));
		$choices[] = $form->createElement('radio', 'forever', '', get_lang('FixedPeriod'), 1 , array ('onclick' => 'javascript:window_show(\'forever_timewindow\')'));
		$form->addElement('select', 'itemid', get_lang('Resource'), $itemlist);
		
		$form->add_timewindow('start', 'end', get_lang('StartDate'), get_lang('EndDate'));		
		$form->addElement('html', '<div class="row"><div class="label"></div><div class="formw">'.get_lang('TimePickerLimitation').'</div></div><br />');
				
		$form->addElement('text', 'maxuser', get_lang('MaxUsers'));		
		$form->addElement('html', '<div class="row"><div class="label"></div><div class="formw">'.get_lang('TimePickerMaxUsers').'</div></div><br />');
		
		$form->addGroup($choices, null, get_lang('SubscriptionPeriod'), '<br />', false);
		$form->addElement('html', '<div style="margin-left:25px;display:block;" id="forever_timewindow">');
		$form->add_timewindow('subscribe_from', 'subscribe_until', '', '');
		$form->addElement('html', '</div>');
		
		$form->addElement('checkbox', 'auto_accept', get_lang('AutoAccept'));
		//$form->addElement('checkbox', 'timepicker', get_lang('TimePicker'));
		$timepicker_arr[] = $form->createElement('radio', 'timepicker', '', get_lang('NoTimePicker'), 0, array ('onclick' => 'javascript:window_hide(\'timepicker_timewindow\')'));
		$timepicker_arr[] = $form->createElement('radio', 'timepicker', '', get_lang('TimePicker'), 1 , array ('onclick' => 'javascript:window_show(\'timepicker_timewindow\')'));
		$form->addGroup($timepicker_arr, null, get_lang('TimePicker'), '<br />', false);
		$form->addElement('html', '<div style="margin-left:25px;display:block;" id="timepicker_timewindow">');
		
   		$min_arr = array();
   		//todo this will be fixed  
   		$min_arr[10] = 10;
   		$min_arr[20] = 20;
   		$min_arr[30] = 30;
   		$min_arr[40] = 40;
   		$min_arr[50] = 50;
   		$min_arr[60] = 60;  		
   		
		for ($i = 0; $i < 1441; $i++) {
      		//$min_arr[$i] = $i;
   		}
   		
		$max_arr = $min_arr;
		
		$form->addElement('select',min,get_lang('Minimum'),$min_arr);
		$form->addElement('select',max,get_lang('Maximum'),$max_arr);
				
		$form->addElement('html', '<div class="row"><div class="label"></div><div class="formw">'.get_lang('TimePickerMinMaxNull').'</div></div><br />');				
		$form->addElement('html', '</div>');

		$recurrence[] = $form->createElement('radio', 'recurrence_c', '', get_lang('NoRecurrence'), 0, array ('onclick' => 'javascript:window_hide(\'recurrence_timewindow\')'));
		$recurrence[] = $form->createElement('radio', 'recurrence_c', '', get_lang('UntilRecurrence'), 1 , array ('onclick' => 'javascript:window_show(\'recurrence_timewindow\')'));
		$form->addGroup($recurrence, null, get_lang('Recurrence'), '<br />', false);
		$form->addElement('html', '<div style="margin-left:25px;display:block;" id="recurrence_timewindow">');
		$list_time = Rsys:: recurrence_list();
		$period[] = $form->createElement('text','repeater','',array('size'=>'3'));
		$period[] = $form->createElement('select','recurrence_selector','',$list_time);
		$form->addGroup($period, null, get_lang('RepeatFor'), '   ', false);
		$form->add_datepicker('recurrence_until',get_lang('RepeatUntil'));
		$form->addElement('html', '</div>');
		$form->addElement('textarea', 'notes', get_lang('Notes'), array ('cols' => 40, 'rows' => 4));
		$form->addElement('submit', 'submit', get_lang('Ok'));
		$str='';
		
		if(!$_POST['forever']) $str.="window_hide('forever_timewindow');";
		if(!$_POST['recurrence_c']) $str.="window_hide('recurrence_timewindow');";
		if(!$_POST['timepicker']) $str.="window_hide('timepicker_timewindow');";

		$form->addElement('html', "<script type=\"text/javascript\">
											/* <![CDATA[ */
											".$str."
											function window_show(item) {
											el = document.getElementById(item);
											el.style.display='';
											}
											function window_hide(item) {
											el = document.getElementById(item);
											el.style.display='none';
											}
											/* ]]> */
											</script>\n");
		if (count($itemlist) > 0) {
			// here we set the default start and end time that we will see in the form 1h 30m after now()
			
			// 1h after now
			$date_defaults_start = array(
		        'd' => date('d'),        
		        'M' => date('n'),
		        'Y' => date('Y'),
		        'H' => date('H',time()+60*60),
		        'i' => '00'
		    );
		    
		    //2h after now
		    $date_defaults_end = array(
		        'd' => date('d'),        
		        'M' => date('n'),
		        'Y' => date('Y'),
		        'H' => date('H',time()+60*60*2),
		        'i' => '00'
		    );
		    
		    
		    	// 1h after now
			$date_defaults_start_sub = array(
		        'd' => date('d'),        
		        'M' => date('n'),
		        'Y' => date('Y'),
		        'H' => date('H',time()-60*60),
		        'i' => '00'
		    );
		    
		    //2h after now
		    $date_defaults_end_sub = array(
		        'd' => date('d'),        
		        'M' => date('n'),
		        'Y' => date('Y'),
		        'H' => date('H',time()),
		        'i' => '00'
		    );
		    
		    
		    
		    			 
			$defaultvalues['start'] = $date_defaults_start;
			$defaultvalues['end'] = $date_defaults_end;			
			$defaultvalues['subscribe_from'] = $date_defaults_start_sub;
			$defaultvalues['subscribe_until'] = $date_defaults_end_sub;
			$defaultvalues['recurrence_until'] = $date_defaults_end;
			
			$defaultvalues['recurrence_c'] = '0';
			$defaultvalues['forever'] = '0';
			$defaultvalues['timepicker'] = '0';
			
			$defaultvalues['maxuser'] = '1';
			$defaultvalues['repeater'] = '1';
			$defaultvalues['auto_accept'] = '1';
			
			$form->setDefaults($defaultvalues);
			$form->Display();
		} 
		else {
			if ($_GET['cat_id'] != 0)
				Display :: display_normal_message(get_lang('NoItems'),false);
		}

		$buffer = ob_get_contents();
		ob_end_clean();
		
		if ($form->validate()) {
			$values = $form->exportValues();			
			if ($values['forever'] == 0) {
				$values['subscribe_from'] = 0;
				$values['subscribe_until'] = 0;
			}
			$msg_number = Rsys :: add_reservation($values['itemid'], $values['auto_accept'], $values['maxuser'], $values['start'], $values['end'], $values['subscribe_from'], $values['subscribe_until'], $values['notes'], $values['timepicker'],$values['min'],$values['max'],0);
			switch($msg_number) {
				case 0 :
					Display :: display_normal_message(Rsys :: get_return_msg(get_lang('BookingPeriodAdded'), "m_reservation.php", $tool_name),false);
					break;
				case 1 :
					Display :: display_normal_message(str_replace('#END#', "<b>".$GLOBALS['end_date']."</b>",str_replace('#START#', "<b>".$GLOBALS['start_date']."</b>",get_lang('BookingPeriodDateOverlap'))),false);
					break;
				case 2 :		
					Display :: display_normal_message(get_lang('BookingPeriodSubscribeUntilAfterStart'),false);
					break;
				case 3:
					Display :: display_normal_message(get_lang('BookingPeriodPast'),false);
					break;
				case 4:
					Display :: display_normal_message(get_lang('BookingPeriodTimePickerLimitation'),false);
					break;
				case 5:
					Display :: display_normal_message(get_lang('BookingPeriodTimePickerError1'),false);
					break;
				case 6:
					Display :: display_normal_message(get_lang('BookingPeriodTimePickerError2'),false);
					break;
				case 7:
					Display :: display_normal_message(get_lang('BookingPeriodTimePickerError3'),false);
					break;
				default :
					break;
			}
		}
		
		if($_POST['recurrence_c'] && $msg_number == 0){
				$Inserted_id = mysql_insert_id();
				$recurrence_date_start = Rsys :: mysql_datetime_to_timestamp($values['start']);
				$recurrence_date_end = Rsys :: mysql_datetime_to_timestamp($values['end']);
				$recurrence_period_end = Rsys :: mysql_datetime_to_timestamp($values['recurrence_until']);
				$recurrence_subscribe_from = Rsys :: mysql_datetime_to_timestamp($values['subscribe_from']);
				$count = 0;
				$recurrence_date_start = $recurrence_date_start + (60 * 60 * 24 * $values['repeater'] * $values['recurrence_selector']);
				$recurrence_date_end = $recurrence_date_end + (60 * 60 * 24 * $values['repeater'] * $values['recurrence_selector']);
				while($recurrence_date_end < $recurrence_period_end){
					if ($values['forever'] == 0) {
						$recurrence_subscribe_from = 0;
						$recurrence_subscribe_until = 0;
					}else{
						$recurrence_subscribe_from = $recurrence_subscribe_from + (60 * 60 * 24 * $values['repeater'] * $values['recurrence_selector']);
						$recurrence_subscribe_until = $recurrence_subscribe_until + (60 * 60 * 24 * $values['repeater'] * $values['recurrence_selector']);
					}
					$errors[]=Rsys :: add_reservation($values['itemid'], $values['auto_accept'], $values['maxuser'], Rsys :: timestamp_to_datetime($recurrence_date_start), Rsys :: timestamp_to_datetime($recurrence_date_end), $values['forever'] == 0 ? 0 : Rsys :: timestamp_to_datetime($recurrence_subscribe_from), $values['forever'] == 0 ? 0 : Rsys :: timestamp_to_datetime($recurrence_subscribe_until), $values['notes'], $values['timepicker'],$values['min'],$values['max'],$Inserted_id);
					if($errors[$count] <> 0){
						$msg .= str_replace('#START#', "<b>".Rsys :: timestamp_to_datetime($recurrence_date_start)."</b>",str_replace('#END#', "<b>".Rsys :: timestamp_to_datetime($recurrence_date_end)."</b>",get_lang('ReservationFromUntilError')));
					}
					$count++;
					$recurrence_date_start = $recurrence_date_start + (60 * 60 * 24 * $values['repeater'] * $values['recurrence_selector']);
					$recurrence_date_end = $recurrence_date_end + (60 * 60 * 24 * $values['repeater'] * $values['recurrence_selector']);
				}
		}
		if(!empty ($msg))
		Display :: display_normal_message($msg);
		
		echo $buffer;

		break;
	case 'edit' :
		if (isset ($_GET["id"]))
			$Reservation_id = $_GET["id"];
		else
			$Reservation_id = $_POST["id"];

		$result = Rsys :: get_num_subscriptions_ReservationPeriods($Reservation_id);
		
		if($result != '0')
		{
			$interbreadcrumb[] = array ("url" => "mysubscriptions.php", "name" => get_lang('Booking'));		
			$interbreadcrumb[] = array ("url" => "m_reservation.php", "name" => get_lang('ManageBookingPeriods'));		
			Display :: display_header('');
			api_display_tool_title($tool_name);
			Display :: display_normal_message(Rsys :: get_return_msg(str_replace('#NUM#', $result, get_lang('BookingPeriodHasSubscriptions')),"m_reservation.php",get_lang('BookingPeriodList')),false);
		}
		else
		{			
			$interbreadcrumb[] = array ("url" => "mysubscriptions.php", "name" => get_lang('Booking'));		
			$interbreadcrumb[] = array ("url" => "m_reservation.php", "name" => get_lang('ManageBookingPeriods'));
					
			Display :: display_header(get_lang('EditBookingPeriod'));
			api_display_tool_title(get_lang('EditNewBookingPeriod'));
			
			$reservation = Rsys :: get_reservation($Reservation_id);
			$item_category = Rsys :: get_item($reservation[0][2]);
			$categori = Rsys :: get_category($item_category[1]);
			$cats = Rsys :: get_category_rights();
			$tijdelijke_cat = $categori[0];
			if (isset ($_GET['cat_id']))
				$categori[0] = $_GET['cat_id'];
	
			ob_start();
	
			echo '<form id="cat_form" action="m_reservation.php" method="get">';
			echo '<input type="hidden" name="id" value="'.$Reservation_id.'" />';
			echo '<input type="hidden" name="action" value="edit"/>';
			
			echo '<div class="row">';		
			echo '<div class="label">'.get_lang('ResourceType').': </div>';
			echo '<div class="formw">';
		
			echo '<select name="cat_id" onchange="this.form.submit();">';
			echo '<option value="0">&nbsp;</option>';
			foreach ($cats as $catid => $cat)
				echo '<option value="'.$catid.'"'. ($catid == $categori[0] ? ' selected="selected"' : '').'>'.$cat.'</option>';
			echo '</select></div>';
			
			echo '</div>';			
			echo '</form>';		
	
			$itemlist = Rsys :: get_cat_r_items($categori[0]);
			$form = new FormValidator('reservation', 'post', 'm_reservation.php?action=edit&id='.$Reservation_id);
			$choices[] = $form->createElement('radio', 'forever', '', get_lang('NoPeriod'), '0', array ('onclick' => 'javascript:timewindow_hide(\'forever_timewindow\')'));
			$choices[] = $form->createElement('radio', 'forever', '', get_lang('FixedPeriod'), '1', array ('onclick' => 'javascript:timewindow_show(\'forever_timewindow\')'));
			$form->addElement('select', 'item_id', get_lang('Resource'), $itemlist);
			
			$form->add_timewindow('start', 'end', get_lang('StartDate'), get_lang('EndDate'));			
			$form->addElement('html', '<div class="row"><div class="label"></div><div class="formw">'.get_lang('TimePickerLimitation').'</div></div><br />');
			
			$form->addElement('text', 'maxuser', get_lang('MaxUsers'));
			$form->addGroup($choices, null, get_lang('SubscriptionPeriod'), '<br />', false);
			
			$form->addElement('html', '<div style="margin-left:25px;display:block;" id="forever_timewindow">');
			$form->add_timewindow('subscribe_from', 'subscribe_until', '', '');
			$form->addElement('html', '</div>');
			$form->addElement('html', "<script type=\"text/javascript\">
												/* <![CDATA[ */
												". ($reservation[0][7] == '0000-00-00 00:00:00' && $reservation[0][8] == '0000-00-00 00:00:00' ? "timewindow_hide('forever_timewindow');" : "")."
												function timewindow_show(item) {
												el = document.getElementById(item);
												el.style.display='';
												}
												function timewindow_hide(item) {
												el = document.getElementById(item);
												el.style.display='none';
												}
												/* ]]> */
												</script>\n");
			$form->addElement('checkbox', 'auto_accept', get_lang('AutoAccept'));
			$form->addElement('checkbox', 'timepicker', get_lang('TimePicker'),'',array('disabled'=>'disabled'));
			$form->addElement('textarea', 'notes', get_lang('Notes'), array ('cols' => 40, 'rows' => 4));
			$form->addElement('submit', 'submit', get_lang('Ok'));
			$form->addElement('hidden', 'id', $Reservation_id);
			$form->addElement('hidden', 'timepicker2');
			$form->addElement('hidden', 'period', ($reservation[0][7] == '0000-00-00 00:00:00' && $reservation[0][8] == '0000-00-00 00:00:00' ? 0 : 1));
		    		
			if ($categori[0] == $tijdelijke_cat)
				$defaultvalues['item_id'] = $reservation[0][2];
				
			$defaultvalues['auto_accept'] = $reservation[0][3];
			$defaultvalues['maxuser'] = $reservation[0][4];
			$defaultvalues['start'] = $reservation[0][5];
			$defaultvalues['end'] = $reservation[0][6];
			
			$defaultvalues['forever']=($reservation[0][7] == '0000-00-00 00:00:00' && $reservation[0][8] == '0000-00-00 00:00:00' ? 0 : 1);
			
			$my_start_date = Rsys :: mysql_datetime_to_timestamp($reservation[0][5]);
			
			if ($defaultvalues['forever']==0) {
				//here we set the default dates 	
				$defaultvalues['subscribe_from'] = $my_start_date - 60*60 ;
				$defaultvalues['subscribe_until'] = $my_start_date - 60 ;
			}
			else {
				$defaultvalues['subscribe_from'] = $reservation[0][7];
				$defaultvalues['subscribe_until'] = $reservation[0][8];				
			}
			
			
			$defaultvalues['notes'] = $reservation[0][10];
			$defaultvalues['timepicker'] = $reservation[0][11];
			$defaultvalues['timepicker2'] = $reservation[0][11];
			$form->setDefaults($defaultvalues);
	
			if (count($reservation) > 0) {
				$form->Display();
			} else {
				Display :: display_normal_message(get_lang('NoItems'),false);
			}
			$buffer = ob_get_contents();
			ob_end_clean();
			if ($form->validate()) {
				$values = $form->exportValues();
				//print_r($values);
				$auto_accept = true;
				if (($values['forever'] == $values['period']) || $values['forever']=='0') {
					$values['subscribe_from'] = 0;
					$values['subscribe_until'] = 0;
				}
				$msg_number = Rsys :: edit_reservation($values['id'], $_POST['item_id'], $values['auto_accept'], $values['maxuser'], $values['start'], $values['end'], $values['subscribe_from'], $values['subscribe_until'], $values['notes'], $values['timepicker2']);
				switch ($msg_number) {
					case 0 :
						Display :: display_normal_message(Rsys :: get_return_msg(get_lang('BookingPeriodEdited'), "m_reservation.php", $tool_name),false);
						break;
					case 1 :
						Display :: display_normal_message(str_replace('#END#', "<b>".$GLOBALS['end_date']."</b>",str_replace('#START#', "<b>".$GLOBALS['start_date']."</b>",get_lang('BookingPeriodDateOverlap'))),false);
						break;
					case 2 :
						Display :: display_normal_message(get_lang('BookingPeriodSubscribeUntilAfterStart'),false);
						break;
					case 3:
						Display :: display_normal_message(get_lang('ReservationMaxUsersOverrun'),false);
						break;
					case 4:
						Display :: display_normal_message(get_lang('BookingPeriodTimepickerLimitation'),false);
						break;
					default :
						break;
				}
			}
	
			echo $buffer;
		}
		break;
	case 'delete' :
		Rsys :: delete_reservation($_GET["id"]);
		ob_start();
		Display :: display_normal_message(Rsys :: get_return_msg(get_lang('BookingPeriodDeleted'), "m_reservation.php", $tool_name),false);
		$msg = ob_get_contents();
		ob_end_clean();
	default :
		$NoSearchResults = get_lang('NoReservations');
		
		if ($_GET['view']=='calendar') {
			$interbreadcrumb[] = array ("url" => "reservation.php", "name" => get_lang('Booking'));						
		}
		else {
			$interbreadcrumb[] = array ("url" => "mysubscriptions.php", "name" => get_lang('Booking'));						
		}
		$interbreadcrumb[] = array ("url" => "m_reservation.php", "name" => get_lang('ManageBookingPeriods'));
		
		Display :: display_header('');
		api_display_tool_title($tool_name);
	
		echo '<form id="cat_form" action="m_reservation.php" method="get">';
		
		echo '<div class="actions">';
		echo '<a href="m_reservation.php?action=add"><img src="../img/view_more_stats.gif" border="0" alt="" title="'.get_lang('AddNewBookingPeriod').'"/>'.get_lang('AddNewBookingPeriod').'</a>';
		echo '&nbsp;&nbsp;&nbsp;<a href="m_reservation.php?action=overviewsubscriptions">'.get_lang('OverviewReservedPeriods').'</a>';
		echo '</div>';
		echo '<div style="text-align: right; "><input type="text" name="keyword" /><input type="submit" value="'.get_lang('Search').'" /></div></form>';
		
		echo '<br>';
		if (isset ($_POST['action'])) {
			switch ($_POST['action']) {
				case 'delete_reservations' :
					$ids = $_POST['reservations'];
					if (count($ids) > 0) {
						foreach ($ids as $index => $id)
							Rsys :: delete_reservation($id);
					}
					break;
			}
		}
		$table = new SortableTable('reservation', array ('Rsys', 'get_num_reservations'), array ('Rsys', 'get_table_reservations'), 1);
		$table->set_additional_parameters(array ('keyword' => $_GET['keyword']));
		$table->set_header(0, '', false, array ('style' => 'width:10px'));
		$table->set_header(1, get_lang('ResourceName'), true);
		$table->set_header(2, get_lang('StartDate'), true);
		$table->set_header(3, get_lang('EndDate'), true);
		$table->set_header(4, get_lang('SubscribeFrom'), true);
		$table->set_header(5, get_lang('SubscribeUntil'), true);
		$table->set_header(6, get_lang('Subscribers'), true);
		$table->set_header(7, get_lang('Notes'), false);
		$table->set_header(8, '', false, array ('style' => 'width:65px;'));
		$table->set_column_filter(8, 'modify_filter');
		$table->set_form_actions(array ('delete_reservations' => get_lang('DeleteSelectedBookingPeriod')), 'reservations');
		$table->display();
		
}

/**
    ---------------------------------------------------------------------
 */

Display :: display_footer();
?>
