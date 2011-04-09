<?php

// get fields informations
function multiquery_query($array) {
	$result = array();
	$field = 0;
	for ($i = 0; $i <sizeof($array); $i++) {
		// mysql handler
		$result[$i]['mysql'] = mysql_query($array[$i]);

		if (! $result[$i]['mysql'])
			die("error in query $i : ".$array[$i]);

		// fields
		$result[$i]['num_fields'] = mysql_num_fields($result[$i]['mysql']);
		for ($j = 0; $j < $result[$i]['num_fields']; $j++) {
			$name = mysql_field_name($result[$i]['mysql'], $j);
			$result['field'][$field]['query']=$i;
			$result['field'][$field]['name']=$name;
			$result['field'][$field]['id']=$j;
			$result['field_assoc'][$name][$field];
			$field++;
		}
	}
	$result['num_queries'] = sizeof($array);
	// rows
	$numberOfResult = mysql_num_rows($result[0]['mysql']);
	for ($i = 1; $i <$result['num_queries']; $i++) 
		if ($numberOfResult != mysql_num_rows($result[$i]['mysql']))
			die("wrong number of row: $numberOfResult vs ".
				mysql_num_rows($result[$i]['mysql'])." on query $i");

	$result['num_rows'] = $numberOfResult;
	$result['num_fields'] = $field;

	return $result;
}

function multiquery_num_rows(&$mq_h) {
	return $mq_h['num_rows'];
}

function multiquery_num_fields(&$mq_h) {
	return $mq_h['num_fields'];
}

function multiquery_field_name(&$mq_h, $id) {
	return $mq_h['field'][$id]['name'];
}

function multiquery_fetch_row(&$mq_h) {
	$result = array();
	$pos = 0;
	for ($i = 0; $i < $mq_h['num_queries']; $i++) {
		$row = mysql_fetch_row($mq_h[$i]['mysql']);
		if (!$row) return false; // last line
		for ($j = 0; $j < sizeof($row); $j++) {
			$result[$pos] = $row[$j];
			$pos++;
		}
	}
	return $result;
}
