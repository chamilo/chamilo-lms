<?php
// $Id: subscribe.php,v 1.9 2006/05/11 14:36:10 kvansteenkiste Exp $
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
Rsys :: protect_script('reservation');


if (!empty($_GET['cat']) && !empty($_GET['item'] )) {
	$cat = (int)$_GET['cat'];
	$item = (int)$_GET['item'];
	$interbreadcrumb[] = array ('url' => "reservation.php?cat=$cat&item=$item", 'name' => get_lang('Booking'));
}
else {
	$interbreadcrumb[] = array ('url' => 'reservation.php', 'name' => get_lang('Booking'));	
}


$tool_name = get_lang('BookIt');

Display :: display_header($tool_name);
api_display_tool_title($tool_name);

$reservationid = $_GET['rid'];
$reservation = Rsys :: get_reservation($reservationid);
$item = Rsys :: get_item($reservation[0][2]);
if ($reservation[0][9] < $reservation[0][4]) {

	ob_start();
	
	$form = new FormValidator('reservation', 'post', 'subscribe.php?rid='.$_GET['rid']);
	$form->addElement('hidden', 'timepicker', $reservation[0][11]);
	$form->addElement('hidden', 'accepted', $reservation[0][3]);
	if ($reservation[0][11] == 1) {
		//$subscribe_timepicker_information="Gelieve voor #name# een peroide te kiezen #from_till tussen :#start_end";
		$min_timepicker = $reservation[0][12];
		$max_timepicker = $reservation[0][13];
		$min_timepicker_min = fmod($min_timepicker,60);
		$min_timepicker_hour = floor($min_timepicker/60);
		$max_timepicker_min = fmod($max_timepicker,60);
		$max_timepicker_hour = floor($max_timepicker/60);
		$min_timepicker_show = $min_timepicker_hour."h".$min_timepicker_min."m";
		$max_timepicker_show = $max_timepicker_hour."h".$max_timepicker_min."m";
		
		if (!($min_timepicker == 0 && $max_timepicker == 0)){
			if($min_timepicker_show == $max_timepicker_show)
			{
				$from_till = "van ".$min_timepicker_show;
			}
			else
			{
				$from_till = "van ".$min_timepicker_show." tot ".$max_timepicker_show;
			}
		}
		else
		{
			$from_till = "";
			$min_timepicker = 1;
			//een reservatieperiode moet toch wel minimum 1 minuut zijn
		}
		
		$res_start_at = $reservation[0][5];
		$res_end_at = $reservation[0][6];
		//echo time()."-".$res_start_at;
		if (time() > Rsys :: mysql_datetime_to_timestamp($res_start_at))
		{
			$time_start = time();
		}
		else
		{
			$time_start = Rsys :: mysql_datetime_to_timestamp($res_start_at);
		}
		
		$sql = "SELECT start_at, end_at FROM ".Rsys :: getTable('subscription')." WHERE reservation_id='".$reservationid."' and end_at > NOW() ORDER BY start_at";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if (mysql_num_rows($result) != 0){
			$start_end = "<ul>";
			while ($array = mysql_fetch_array($result)) {
				//print_r($array);
				if (time() < Rsys :: mysql_datetime_to_timestamp($array["start_at"]))
				{ 
					if (((Rsys :: mysql_datetime_to_timestamp($array["start_at"]) - $time_start) >= ($min_timepicker*60)) && ($time_start < Rsys :: mysql_datetime_to_timestamp($array["start_at"])))
					{
						$start_end .= "<li>".Rsys :: timestamp_to_datetime($time_start)." en ".$array["start_at"]."</li>";
					}
				}
				$time_start = Rsys :: mysql_datetime_to_timestamp($array["end_at"]);
				$end_time_last_block = $array["end_at"];
			}
			if ((Rsys :: mysql_datetime_to_timestamp($res_end_at) - Rsys :: mysql_datetime_to_timestamp($end_time_last_block)) >= ($min_timepicker*60))
			{
				$start_end .= "<li>".$end_time_last_block." en ".$res_end_at."</li>";
			}
			$start_end .= "</ul>";
		} else {
			$start_end = " ".Rsys :: timestamp_to_datetime($time_start)." en ".$res_end_at;
		}
		
		//$form->addElement('html', "timestart:".$time_start."-".Rsys :: mysql_datetime_to_timestamp($res_start_at));
		$form->addElement('html', "<p>".str_replace('#start_end',$start_end,str_replace('#from_till', $from_till,str_replace('#name#', "<b>".$item[3]."</b>",str_replace('#start#', "<b>".$reservation[0][5]."</b>", str_replace('#end#', "<b>".$reservation[0][6]."</b>", get_lang("SubscribeTimePickerInformation"))))))." </p>");
		$form->add_timewindow('startpicker', 'endpicker', get_lang('StartDate'), get_lang('EndDate'));
		$form->addElement('hidden', 'min', $reservation[0][12]);
		$form->addElement('hidden', 'max', $reservation[0][13]);
		$datum = $_GET['timestart'];
		$defaultvalues['startpicker'] = Rsys :: timestamp_to_datetime($datum);
		//$defaultvalues['endpicker'] = Rsys :: timestamp_to_datetime($datum +900);
		$defaultvalues['endpicker'] = Rsys :: timestamp_to_datetime($datum +($min_timepicker*60));
		$form->setDefaults($defaultvalues);
	} else {
		$form->addElement('html', "<p> * ".str_replace('#name#', "<b>".$item[3]."</b>",str_replace('#start#', "<b>".$reservation[0][5]."</b>", str_replace('#end#', "<b>".$reservation[0][6]."</b>", get_lang('SubscribeInformation'))))." *</p>");
	}
	$buttons[] = $form->createElement('submit', 'submit', get_lang('Ok'));
	$buttons[] = $form->createElement('button', 'cancel', get_lang('Cancel'), array ('onclick' => 'location.href="reservation.php?cat='.$item[1].'&item='.$item[0].'"'));
	$form->addGroup($buttons, null, '', '', false);
	
	$buffer = ob_get_contents();
	ob_end_clean();
	
	if ($form->validate()) {
		$values = $form->exportValues();
		if ($values['timepicker'] == 0) {
			$result = Rsys :: add_subscription($_GET['rid'], api_get_user_id(),$values['accepted']);
			switch ($result) {
				case 0 :
					Display :: display_normal_message(Rsys :: get_return_msg2(get_lang('ReservationAdded'), "javascript:history.go(-2)", get_lang('BookingView')),false);
					break;
				case 1 :
					Display :: display_normal_message(Rsys :: get_return_msg2(str_replace('#END#', "<b>".$GLOBALS['end_date']."</b>",str_replace('#START#', "<b>".$GLOBALS['start_date']."</b>",get_lang('ReservationAlready'))),"reservation.php?cat=".$item[1]."&item=".$item[0]."", get_lang('BookingView')),false);
					break;
			}

		} else {
			$result = Rsys :: add_subscription_timepicker($_GET['rid'], api_get_user_id(), $values['startpicker'], $values['endpicker'],$values['accepted'],$values['min'],$values['max']);
			switch ($result) {
				case 0 :
					Display :: display_normal_message(Rsys :: get_return_msg2(get_lang('ReservationAdded'), "reservation.php?cat=".$item[1]."&item=".$item[0]."&date=".date( 'Y-m-d',Rsys :: mysql_datetime_to_timestamp($values['startpicker']))."&changemonth=yes", get_lang('BookingView')),false);
					break;
				case 1 :
					Display :: display_normal_message(str_replace('#END#', "<b>".$GLOBALS['end_date']."</b>",str_replace('#START#', "<b>".$GLOBALS['start_date']."</b>",get_lang('ReservationOutOfDate'))),false);
					$form->display();
					echo $buffer;
					break;
				case 2 :
					Display :: display_normal_message(get_lang('BookingPeriodTooSmall'),false);
					$form->display();
					//echo $buffer;
					break;
				case 3 :
					Display :: display_normal_message(get_lang('BookingPeriodTooBig'),false);
					$form->display();
					//echo $buffer;
					break;
			}
		}
	} else
		$form->display();
} else {
	Display :: display_normal_message(Rsys :: get_return_msg2(get_lang('ReservationTresspassing'), "javascript:history.go(-2)", get_lang('BookingView')),false);
}


Display :: display_footer();
?>