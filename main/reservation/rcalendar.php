<?php
/* For licensing terms, see /license.txt */
/**
    The rCalendar
 */
$DaysShort = api_get_week_days_short();
$DaysLong = api_get_week_days_long();
$MonthsLong = api_get_months_long();

class rCalendar {
	function rCalendar() {
	}
	function get_short_day($day) {
		global $DaysShort;
		return $DaysShort[$day];
	}
	function get_long_day($day) {
		global $DaysLong;
		return $DaysLong[$day];
	}
	function get_long_month($month) {
		global $MonthsLong;
		return $MonthsLong[$month];
	}

    /*
     * Deze methode retourneert de maandkalender waarbij dagen MET RESERVATIES groen worden en dagen zonder, grijs
     *
     */
	function get_mini_month($month, $year, $extra, $itemid) {
		global $DaysShort;
		$month = intval($month);
		if ($month < 10)
			$month = '0'.$month;
		$stamp = Rsys :: mysql_datetime_to_timestamp($year.'-'.$month.'-01 00:00:00');
		$daysinmonth = date('t', $stamp);
		$dayofweek = date('w', $stamp); // 0-6
		if ($dayofweek == 0)
			$dayofweek = 7; // 1-7...
		echo '<table style="padding:0.2em;border:1px solid #000" cellspacing="1"><tr><th><a style="font-size: 11px;font-family: Verdana,sans-serif;" href="reservation.php?item='.$itemid.'&amp;viewday&amp;changemonth=yes&amp;date='. ($month == 1 ? $year -1 : $year).'-'. ($month == 1 ? 12 : $month -1).'-1'.$extra.'">&laquo;</a></th><th colspan="5" style="font-family: Verdana,sans-serif;border:2px inset #000;background-color:#FF0;font-size:11px">'.$this->get_long_month($month -1).' '.$year.'</th><th><a href="reservation.php?item='.$itemid.'&amp;viewday&amp;changemonth=yes&amp;date='. ($month == 12 ? $year +1 : $year).'-'. ($month == 12 ? 1 : $month +1).'-1'.$extra.'" style="font-size: 11px;font-family: Verdana,sans-serif;">&raquo;</a></th></tr><tr>';
		for ($i = 1; $i <= 7; $i ++) {
			echo '<th style="color:#036;font-size: 10px;font-family: Verdana,sans-serif; padding: 3px 0.2em 3px 0.2em">'.$this->get_short_day($i == 7 ? 0 : $i).'</th>';
		}
		echo '</tr><tr>';
		for ($i = 1; $i <= 42; $i ++) {
			if ($i - $dayofweek < $daysinmonth && $i >= $dayofweek)
				echo '<td style="border-bottom:1px solid #CCE;cursor: pointer;border-right:1px solid #CCE;text-align: center;font-size: 11px;font-family: Verdana,sans-serif;" onmouseout="this.style.background=\'#FFF\'" onmouseover="this.style.background=\'#FF0\'" onclick="document.location.href=\'?viewday&amp;date='.$year.'-'.$month.'-'. ($i - $dayofweek +1).$extra.'\'"><a href="?viewday&amp;date='.$year.'-'.$month.'-'. ($i - $dayofweek +1).$extra.'" style="color:'

                 // Hier wordt een boolean bepaald voor elke dag, TRUE=reservaties op die dag>>Groen, FALSE=geen reservaties op die dag>>Grijs
                 // (TODO: legende-kleuren retourneren ipv TRUE of FALSE)
                 //.(Rsys :: check_date_month_calendar($year,$month,($i - $dayofweek +1),$itemid) ? "#0C0" : "#666").
				//.Rsys :: check_date_month_calendar($year,$month,($i - $dayofweek +1),$itemid).
                 .(Rsys :: check_date_month_calendar($year.'-'.$month.'-'. ($i - $dayofweek +1),$itemid) ? "#0C0" : "#666").

                    '">'.($i - $dayofweek +1).'</a></td>';
			elseif ($i < $dayofweek) echo '<td>&nbsp;</td>';
			else
				break;
			if ($i +1 - $dayofweek < $daysinmonth && $i +1 > $dayofweek && $i % 7 == 0)
				echo '</tr><tr>';
		}
		echo '</tr></table>';
	}

    /*
     * Deze methode retourneert een balkje van een gegeven $width met een bepaalde kleur ($color) met evt. een titel
     *
     */
	function get_bar($width, $color, $link = '', $title = '', $itemid=null, $cat=null) {
		// 1) width herberekenen om afrondingsfouten te beperken
		if($GLOBALS['weekday_pointer']!=$GLOBALS['last_weekday_pointer']) {
			$GLOBALS['daytotal']=0;
			$GLOBALS['lasttotal']=0;
			$GLOBALS['last_weekday_pointer']=$GLOBALS['weekday_pointer'];
		}

		$GLOBALS['daytotal']+=$width;
		$GLOBALS['rounded_total']=round($GLOBALS['daytotal']);
		$width=$GLOBALS['rounded_total']-$GLOBALS['lasttotal'];
		$GLOBALS['lasttotal']=$GLOBALS['rounded_total'];
		// 2) kleur aanpassen indien item op blackout staat
		if($GLOBALS['bblackout'] && $color!='red' && $color!='orange' && $color!='grey') {
			$color='black';
			$link='';
		}
		$img='';
		// 3) html code returnen voor gekleurde bar
		if (!empty($itemid) && !empty($cat)&& !empty($link)) {
			$link.='&cat='.$cat.'&item='.$itemid;
			$img = '<img src="../img/px_'.$color.'.gif" alt="" style="height: 15px;width: '.$width.'px'. (!empty ($link) ? ';cursor: pointer;" onclick="window.location.href=\''.$link.'\'"' : (!empty ($title) ? ';cursor: help;"' : '"')). (!empty ($title) ? ' title="'.$title.'"' : '').' />';
		}
		else {
			$img = '<img src="../img/px_'.$color.'.gif" alt="" style="height: 15px;width: '.$width.'px'. (!empty ($link) ? ';cursor: pointer;" onclick="window.location.href=\''.$link.'\'"' : (!empty ($title) ? ';cursor: help;"' : '"')). (!empty ($title) ? ' title="'.$title.'"' : '').' />';
		}
		return $img;

	}

    /*
     * Deze methode retourneert de weekkalender
     *
     *  - Deze methode ontvangt een item-id & 1 dag als parameter (dag+maand+jaar), de omliggende week (met reservaties etc.) wordt dan opgehaald
     *  - hoe groter de $day_scale, hoe kleiner de tabel (86400/$day_scale=breedte van 1 dag)
     */
	function get_week_view($day, $month, $year, $itemid, $day_scale = 180,$cat) {
        // 1) Item is blackout? >> True of False ... wordt gebruikt in get_bar methode om kleur indien nodig om te zetten
        $GLOBALS['bblackout']=Rsys::is_blackout($itemid);

        // 2) $day_scale controleren en aanpassen indien nodig
        // Day_scale mag niet groter zijn dan 3600 (anders wordt de tabel te klein)
        	if ($day_scale > 3600)
	     		$day_scale = 3600;
        // Day_scale mag niet kleiner zijn dan  1 (anders wordt de tabel te groot)
	 	elseif ($day_scale < 1)
            		$day_scale = 1;

        // 3) Een '0' voor de maand-integer zetten indien deze kleiner is dan 10, nodig voor datetime
        // bv voor 1 december 1985: '01-12-1985' ipv '1-12-1985' (S�ba's geboortedatum ^^)
        	if ($month < 10)
	      		$month = '0'.$month;

        // 4) Bepaal de juiste start datum (maandag) van de omliggende week
        // (verander eventueel maand (en jaar) wanneer de maandag in de vorige maand/jaar ligt)
		$fromdate = $year.'-'.$month.'-'. ($day < 10 ? '0'.$day : $day).' 00:00:00';
		$stamp = Rsys :: mysql_datetime_to_timestamp($fromdate);
		$dayofweek = date('w', $stamp);
		if ($dayofweek == 0) {
			$stamp = $stamp - (1);
			$datum = Rsys :: mysql_datetime_to_array(Rsys :: timestamp_to_datetime($stamp));
			$day = $datum['day'];
			$month = $datum['month'];
			$year = $datum['year'];
		}
		$fromdate = $year.'-'.$month.'-'.$day.' 00:00:00';
		$stamp = Rsys :: mysql_datetime_to_timestamp($fromdate);
		$dayofweek = date('w', $stamp);
		if ($day - $dayofweek < 0) {
			$stamp = $stamp - (60 * 60 * 24 * ($dayofweek -1));
			$fromdate = Rsys :: timestamp_to_datetime($stamp);
			$tilldate = $stamp;
		} else {
			$day = $day - $dayofweek +1; // M-FIX: sunday + 1 = monday
			$fromdate = $year.'-'.$month.'-'. ($day < 10 ? '0'.$day : $day).' 00:00:00';
			$tilldate = Rsys :: mysql_datetime_to_timestamp($fromdate);
		}
       //zeven Dagen opvullen en eind-datum bepalen (= startdatum + 7 dagen)
		for ($i = 1; $i <= 7; $i ++) {
			$day_start_dates[$i] = $tilldate;
			$tilldate += 60 * 60 * 24;
		}

       // 5) Haal de reservaties periodes (+ reservaties) op tussen de start en einddatum
		$arr = Rsys :: get_item_reservations($fromdate, Rsys :: timestamp_to_datetime($tilldate), $itemid);

	// 6) Doorloop $arr met reservatie periodes en vul de $days array op met balkjes (bars) voor elke dag
		$weekday_pointer = $GLOBALS['weekday_pointer'] = 1; // Stel de weekdag pointer in op 1 (=maandag)
		$one_day = 60 * 60 * 24 - 1; // Het aantal seconden dat ��n dag in beslag neemt (23:59:59)
		$last_end = $day_start_dates[1]; // Stel de $last_end pointer in op het begin van maandag (00:00:00)
		if (count($arr['reservations'])>0) {
			foreach ($arr['reservations'] as $res_id => $res) {
				// 6.1) Stel basis variabelen in
				$r = $res['info'];       // Reservatie periode informatie (start en eind tijd etc.)
				$s = $res['subscriptions']; // Reservaties (inschrijvingen) op reservatie periode
				$start = Rsys :: mysql_datetime_to_timestamp($r['start_at']); // Start tijd van reservatie periode blok
				$end = Rsys :: mysql_datetime_to_timestamp($r['end_at']); // Eind tijd van reservatie periode blok
		            	$timepicker_min = $r['timepicker_min'];
				$timepicker_max = $r['timepicker_max'];
				$chunk_size = $end - $start; // Unscaled chunk_size (of "reservation-period-block") // een "chunk" is dus een balkje (bar)

				// 6.2) Als de weekdag van de huidige start-tijd (van de reservatieperiode) niet overeenkomt met de huidige weekdag-pointer (=$weekday_pointer)
				if (date('w', $start) != $weekday_pointer) {
			              	// a) Wijzig de $weekday_pointer naar de weekdag waarin de start-tijd (van de reservatieperiode) zich bevindt
					$weekday_pointer = $GLOBALS['weekday_pointer'] = date('w', $start);
					if ($weekday_pointer == 0)
						$weekday_pointer = 7;
	                		// b) Stel de $last_end in op het begin van de nieuwe dag
					$last_end = $day_start_dates[$weekday_pointer];
				}

				// 6.3) Vul de ruimte tussen $last_end en de huidige start-tijd met een grijze balk
				if ($start - $last_end > 0)
					$days[$weekday_pointer] .= $this->get_bar(($start - $last_end) / $day_scale, 'grey');

				// 6.4.A) Indien het NIET om een timepicker gaat... (= 1 reservatie-blok)
			       	if ($r['timepicker'] != 1) {
					// ..a1) bepaal kleur en link (of geen link) op basis van de inschrijvingsperiode en het aantal en het maximaal aantal inschrijvingen
			              	if ($r['subscribers'] < $r['max_users'] && Rsys :: mysql_datetime_to_timestamp($r['start_at']) > time() && ($r['subscribe_from']=='0000-00-00 00:00:00'||(Rsys :: mysql_datetime_to_timestamp($r['subscribe_from']) < time() && Rsys :: mysql_datetime_to_timestamp($r['subscribe_until']) > time()))) {
						// Subscription is allowed
						$color = "green";
						$link = "subscribe.php?rid=".$r['id'];
					}
					elseif ($r['subscribers'] < $r['max_users'] && (Rsys :: mysql_datetime_to_timestamp($r['subscribe_from']) > time() || Rsys :: mysql_datetime_to_timestamp($r['subscribe_until']) < time() || Rsys :: mysql_datetime_to_timestamp($r['start_at']) < time())) {
						// Subscribe_from is not yet reached
						$color = "orange";
						$link = null;
					}
					else {
						// Subscription is not allowed
						$color = "red";
						$link = null;
					}

	                // ..a2) stel titel in (dat je ziet als je over het balkje zweeft met je muis)
	                $title = date('H:i (d/m/Y)', $start).' &raquo; '.date('H:i (d/m/Y)', $end);

			        // ..a3) controleer of het reservatie-periode-blok de huidige dag overschrijdt
					if ($end > $day_start_dates[$weekday_pointer] + $one_day) {
						// indien ja, cree�r dan balkjes voor elke volgende dag
						$trimmed_chunk_size = $chunk_size - ($end - ($day_start_dates[$weekday_pointer] + $one_day));
						$days[$weekday_pointer] .= $this->get_bar($trimmed_chunk_size / $day_scale, $color, $link, $title, $itemid, $cat);
						$new_day = true;
						while ($new_day && $weekday_pointer < 7) {
							$weekday_pointer ++;
		                    $GLOBALS['weekday_pointer']++;
							$start = $days[$weekday_pointer];
							$chunk_size = $end - $start;
							if ($end > $day_start_dates[$weekday_pointer] + $one_day) { // If still larger than one day, trim chunk and continue
								$days[$weekday_pointer] .= $this->get_bar($one_day / $day_scale, $color, $link, $title, $itemid, $cat);
							} else {
								$trimmed_chunk_size = $end - $day_start_dates[$weekday_pointer];
								$days[$weekday_pointer] .= $this->get_bar($trimmed_chunk_size / $day_scale, $color, $link, $title, $itemid, $cat);
								$new_day = false;
							}
						}
					}
					else // indien niet, voeg dan gewoon het balkje toe aan de huidige dag
						$days[$weekday_pointer] .= $this->get_bar($chunk_size / $day_scale, $color, $link, $title, $itemid, $cat);

					// 6.4.B) Indien het WEL om een timepicker gaat... (max_users telt hier niet)
				    }
					else
					{
						$timepicker_min *= 60;
						$timepicker_max *= 60;
						$minute_interval = 30;
						$minute_interval *= 60;
						$color = "blue";
						$pickedcolor = "red";
						$tosmallchunkcolor = "yellow";
						$start_pointer = $start;

						$link = "subscribe.php?rid=".$r['id'].'&amp;timestart='; // + (start)tijd waarop geklikt werd = volwaardige link

		if(count($s)==0) {
			if ($start > time())
			{
				//controle of dat de time tussen $r['subscribe_from'] en $r['subscribe_until'] ligt
				if((Rsys :: mysql_datetime_to_timestamp($r['subscribe_from']) <= time() && Rsys :: mysql_datetime_to_timestamp($r['subscribe_until']) > time()) || ($r['subscribe_from'] == '0000-00-00 00:00:00' && $r['subscribe_until'] == '0000-00-00 00:00:00'))				{
					$days[$weekday_pointer] .= $this->get_bar(($end - $start) / $day_scale, $color, $link.$start_pointer, date('H:i', $start).' &raquo; '.date('H:i', $end), $itemid, $cat);
				}
				else
				{
					$days[$weekday_pointer] .= $this->get_bar(($end - $start) / $day_scale, 'orange', null, date('H:i', $start).' &raquo; '.date('H:i', $end));
				}
			}
			else
			{
				if (time() < $end)
				{
					//eerst oranje daarna blauw of geel
					$days[$weekday_pointer] .= $this->get_bar((time() - $start) / $day_scale, 'orange', null, date('H:i', $start).' &raquo; '.date('H:i', time()));
					//controle of het stuk nog kan gereserveerd worden -> stuk > timepicker_min
					if((Rsys :: mysql_datetime_to_timestamp($r['subscribe_from']) <= time() && Rsys :: mysql_datetime_to_timestamp($r['subscribe_until']) > time()) || ($r['subscribe_from'] == '0000-00-00 00:00:00' && $r['subscribe_until'] == '0000-00-00 00:00:00'))					{
						if (($end - time()) >= $timepicker_min)
						{
							$days[$weekday_pointer] .= $this->get_bar(($end - time()) / $day_scale, $color, $link.$start_pointer, date('H:i', time()).' &raquo; '.date('H:i', $end), $itemid, $cat);
						}
						else
						{
							$days[$weekday_pointer] .= $this->get_bar(($end - time()) / $day_scale, $tosmallchunkcolor, null, date('H:i', time()).' &raquo; '.date('H:i', $end));
						}
					}
					else
					{
						$days[$weekday_pointer] .= $this->get_bar(($end - time()) / $day_scale, 'orange', null, date('H:i', time()).' &raquo; '.date('H:i', $end));
					}
				}
				else
				{
					//volledig oranje blok
					$days[$weekday_pointer] .= $this->get_bar(($end - $start) / $day_scale, 'orange', null, date('H:i', $start).' &raquo; '.date('H:i', $end));
				}
			}
		}
		else
		{
			$i = 0;
			foreach ($s as $key => $sub) {
				$start = Rsys :: mysql_datetime_to_timestamp($sub['start_at']);
				$einde = Rsys :: mysql_datetime_to_timestamp($sub['end_at']);

				if (Rsys :: mysql_datetime_to_timestamp($sub['start_at']) - $start_pointer <= 0) {
					//start onmiddelijk met een rood stuk
					$start_tijd = date('H:i',$start_pointer);
					$eind_tijd = date('H:i',$einde);
					$days[$weekday_pointer] .= $this->get_bar(($einde - $start_pointer) / $day_scale, $pickedcolor, null, $start_tijd.' &raquo; '.$eind_tijd);
				}
				else {
					//start met een blauw of oranje stuk
					//kijken of dat de start_tijd al buiten de huidige tijd ligt ->
					if ($start_pointer > time()) {
						$start_tijd = date('H:i',$start_pointer);
						$eind_tijd = date('H:i',$start);
						if((Rsys :: mysql_datetime_to_timestamp($r['subscribe_from']) <= time() && Rsys :: mysql_datetime_to_timestamp($r['subscribe_until']) > time()) || ($r['subscribe_from'] == '0000-00-00 00:00:00' && $r['subscribe_until'] == '0000-00-00 00:00:00')) {
							//niet buiten tijd!
							//blauw stuk maken indien groter dan timepicker_min anders geel
							if (($start - $start_pointer) >= $timepicker_min) {
								$days[$weekday_pointer] .= $this->get_bar(($start - $start_pointer) / $day_scale, $color, $link.$start_pointer, $start_tijd.' &raquo; '.$eind_tijd, $itemid, $cat);
							}
							else {
								$days[$weekday_pointer] .= $this->get_bar(($start - $start_pointer) / $day_scale, $tosmallchunkcolor, null, $start_tijd.' &raquo; '.$eind_tijd);
							}
						}
						else
						{
							$days[$weekday_pointer] .= $this->get_bar(($start - $start_pointer) / $day_scale, 'orange', null, $start_tijd.' &raquo; '.$eind_tijd);
						}
					}
					else
					{
						//controleren of dat tijd nu groter is dan de start van het gereserveerde stuk
						//Ja -> stuk voor de start oranje maken
						//Neen -> stuk voor de start oranje maken en wat er nog overblijft vr de $start blauw maken
						if (time() >= $start)
						{
							$days[$weekday_pointer] .= $this->get_bar(($start - $start_pointer) / $day_scale, 'orange', null, date('H:i', $start_pointer).' &raquo; '.date('H:i', $start));
						}
						else
						{
							//buiten tijd! -> stuk opdelen in een gedeelte buiten tijd en een gedeelte timepicker
							//oranje stuk
							$days[$weekday_pointer] .= $this->get_bar((time() - $start_pointer) / $day_scale, 'orange', null, date('H:i', $start_pointer).' &raquo; '.date('H:i', time()));
							if((Rsys :: mysql_datetime_to_timestamp($r['subscribe_from']) <= time() && Rsys :: mysql_datetime_to_timestamp($r['subscribe_until']) > time()) || ($r['subscribe_from'] == '0000-00-00 00:00:00' && $r['subscribe_until'] == '0000-00-00 00:00:00'))							{
								//blauw stuk maken indien groter dan timepicker_min anders geel
								if (($start - time()) >= $timepicker_min)
								{
									$days[$weekday_pointer] .= $this->get_bar(($start - time()) / $day_scale, $color, $link.time(), date('H:i', time()).' &raquo; '.date('H:i', $start), $itemid, $cat);
								}
								else
								{
									$days[$weekday_pointer] .= $this->get_bar(($start - time()) / $day_scale, $tosmallchunkcolor, null, date('H:i', time()).' &raquo; '.date('H:i', $start), $itemid, $cat);
								}
							}
							else
							{
								$days[$weekday_pointer] .= $this->get_bar(($start - time()) / $day_scale, 'orange', null, date('H:i', time()).' &raquo; '.date('H:i', $start));
							}
						}

					}
					$start_tijd = date('H:i',$start);
					$eind_tijd = date('H:i',$einde);
					$days[$weekday_pointer] .= $this->get_bar(($einde - $start) / $day_scale, $pickedcolor, null, $start_tijd.' &raquo; '.$eind_tijd);
				}

				//print_r($days);
				$start_pointer = $einde;// + 1;
			}
			//indien er nog een stuk blauw moet gemaakt worden op het einde vd reservering
			if ($start_pointer < $end)
			{
				//controleren of dat tijd nu groter is dan de start van het gereserveerde stuk
				//Ja -> stuk voor de start oranje maken
				//Neen -> stuk voor de start oranje maken en wat er nog overblijft vr de $start blauw maken
				if (time() >= $end)
				{
					$days[$weekday_pointer] .= $this->get_bar(($end - $start_pointer) / $day_scale, 'orange', null, date('H:i', $start_pointer).' &raquo; '.date('H:i', $end));
				}
				else
				{
					if (time() >= $start_pointer)
					{
						//buiten tijd! -> stuk opdelen in een gedeelte buiten tijd en een gedeelte timepicker
						//oranje stuk
						$days[$weekday_pointer] .= $this->get_bar((time() - $start_pointer) / $day_scale, 'orange', null, date('H:i', $start_pointer).' &raquo; '.date('H:i', time()));
						if((Rsys :: mysql_datetime_to_timestamp($r['subscribe_from']) <= time() && Rsys :: mysql_datetime_to_timestamp($r['subscribe_until']) > time()) || ($r['subscribe_from'] == '0000-00-00 00:00:00' && $r['subscribe_until'] == '0000-00-00 00:00:00'))						{
							//blauw stuk maken indien groter dan timepicker_min anders geel
							if (($end - time()) >= $timepicker_min)
							{
								//blauwe stuk
								$days[$weekday_pointer] .= $this->get_bar(($end - time()) / $day_scale, $color, $link.time(), date('H:i', time()).' &raquo; '.date('H:i', $end), $itemid, $cat);
							}
							else
							{
								//geel stuk
								$days[$weekday_pointer] .= $this->get_bar(($end - time()) / $day_scale, $tosmallchunkcolor, null, date('H:i', time()).' &raquo; '.date('H:i', $end));
							}
						}
						else
						{
							$days[$weekday_pointer] .= $this->get_bar(($end - time()) / $day_scale, 'orange', null, date('H:i', time()).' &raquo; '.date('H:i', $end));
						}
					}
					else
					{
						if((Rsys :: mysql_datetime_to_timestamp($r['subscribe_from']) <= time() && Rsys :: mysql_datetime_to_timestamp($r['subscribe_until']) > time()) || ($r['subscribe_from'] == '0000-00-00 00:00:00' && $r['subscribe_until'] == '0000-00-00 00:00:00')) {
							//blauw stuk maken indien groter dan timepicker_min anders geel
							if (($end - $start_pointer) >= $timepicker_min) {
								//blauwe stuk
								$days[$weekday_pointer] .= $this->get_bar(($end - $start_pointer) / $day_scale, $color, $link.$start_pointer, date('H:i', $start_pointer).' &raquo; '.date('H:i', $end), $itemid, $cat);
							}
							else {
								//gele stuk
								$days[$weekday_pointer] .= $this->get_bar(($end - $start_pointer) / $day_scale, $tosmallchunkcolor, null, date('H:i', $start_pointer).' &raquo; '.date('H:i', $end));
							}
						}
						else {
							$days[$weekday_pointer] .= $this->get_bar(($end - $start_pointer) / $day_scale, 'orange', null, date('H:i', $start_pointer).' &raquo; '.date('H:i', $end));
						}
					}
				}
			}
		}
	}


			// 6.5) Zet de $last_end pointer op de eindtijd van de huidige reservatie periode
			$last_end = $end;
			}// end if
		}

		// 7) loop through all days of the week and fill them with the contents of $days
		$firstcol = 120; // Width of first col (in pixels)
		$x=0;
        	$borderstyle = "border-bottom: 1px solid #003;";
        	echo '<table cellspacing="0" style="padding: 1px;width: '. (round($one_day / $day_scale) + $firstcol + $x).'px;border: 1px solid #003;border-bottom: 0"><tr><td style="'.$borderstyle.'width: '.$firstcol.'px;background-color: #069; ">&nbsp;</td><td style="'.$borderstyle.'background-color: #069; color: #FFF; white-space: nowrap; font-family: Arial, sans-serif; font-size:10px;width: '. (round($one_day / $day_scale) + $x).'px">';
		$vast = (3600 * 2) / $day_scale;
	       $lasttotal=0;
		for ($i = 0; $i < 22; $i = $i +2) {
            		$w=$vast;
            		$total+=$w;
            		$rounded_total=round($total);
            		$w=$rounded_total-$lasttotal;
			echo '<div style="float:left; width: '. $w .'px">'.$i.'</div>';
            		$lasttotal=$rounded_total;
		}
		echo '<div style="float:left;">22</div><div style="float: right">0</float>';
		echo '</td></tr>';
		for ($i = 1; $i <= 7; $i ++) {
			echo '<tr><td style="background-color: #069; color: #FFF; width:'.$firstcol.'px; font-family: Verdana, Arial, sans-serif; font-weight: bold;font-size:10px;'.$borderstyle.';cursor: help" title="'.date('d/m/Y',$day_start_dates[$i]).'">'.$this->get_long_day($i == 7 ? 0 : $i).'</td><td style="'.$borderstyle.' background-color: #CCC; width:'. (round($one_day / $day_scale) + $x).'px; white-space: nowrap;">';
			if (is_array($days) && array_key_exists($i, $days))
				echo $days[$i];
			else
				echo '&nbsp;';
			echo '</td></tr>'."\n";
		}
		echo '</table>';
        	$GLOBALS['weekstart']=date('d/m/Y',$day_start_dates[1]);
        	$GLOBALS['weekend']=date('d/m/Y',$day_start_dates[7]);
	}
}