<?php
/* For licensing terms, see /license.txt */

/**
* Reports
* @author Arnaud Ligot <arnaud@cblue.be>
* @copyrights CBLUE SPRL 2011
* @package chamilo.reports
*/

// name of the language file that needs to be included
$language_file = array('reportlib');
$cidReset = true;

// including files 
require_once '../inc/global.inc.php';
require_once 'reports.lib.php';
require_once 'multiquery.lib.php';

// protect script
api_block_anonymous_users();

// defining constants

// current section
$this_section = SECTION_REPORTS;

// setting the name of the tool
$tool_name=get_lang('Reports');

// loading templates
reports_loadTemplates();

// random suffix for div id (to enable multiple report per page)
$idsuffix = rand();

// "Link" type
if ($_REQUEST['format'] == 'link') {
	// converting post vars to get uri
	$params = '';
	$kv = array();
	foreach ($_POST as $key => $value)
		if ($key != 'format')
			$kv[] = $key.'='.urlencode($value);
	$query_string = join("&", $kv);
	die('<a href="reports.php?format=directlink&'.$query_string.'">'.get_lang('ReportTypeLink').'</a>');
}

if ($_REQUEST['format'] == 'directlink') {
	foreach (array('jquery.js', 'jquery.dataTables.min.js') as $js)
		$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/'.$js.'" type="text/javascript" language="javascript"></script>'."\n";

	$htmlCSSXtra[] = 'dataTable.css';

?>
    <script type="text/javascript">
		function setSubDataUri(elem, uri) {
			$.ajax({
				url: uri,
				success: function(data) {
					$(elem).closest('.result').nextAll().html('');
					$(elem).closest('.result').next().html(data);
				}
			});
		}
    </script>
<?php
	Display::display_header($tool_name);
	echo '<div id="result" class="result">';
}

// outputing a link to csv file instead of outputing csv data directly
if ($_REQUEST['format'] == 'csv')  {
	// converting post vars to get uri
	$params = '';
	$kv = array();
	foreach ($_POST as $key => $value)
		if ($key != 'format')
			$kv[] = $key.'='.urlencode($value);
	$query_string = join("&", $kv);
	die('<a href="reports.php?format=downloadcsv&'.$query_string.'">'.get_lang('DownloadFile').'</a>');
} else if ($_REQUEST['format'] == 'downloadcsv') {
	if ((strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') == false)) {
		header("Pragma: must-revalidate");
		header("Cache-Control: must-revalidate");
		header("Content-type: application/vnd.ms-excel");
	}
	else {
		header("Content-type: text/csv");
	}
	$date = date("Y-m-d");
	$filename = "reporting-$date.csv";
	header("Content-Disposition: attachment; filename=$filename");
	$_REQUEST['format'] = 'csv';
}



if (is_array($reports_template[$_REQUEST['type']])) {
	$query = $reports_template[$_REQUEST['type']]['getSQL']();
	if (! is_array($query))
		$query = array($query);
	if ($_REQUEST['format'] == 'sql')
		die(var_export($query, true));

	$result = multiquery_query($query);
	

	// check number of result
	$numberOfResult = multiquery_num_rows($result);
	if ($numberOfResult == 0) 
		die(get_lang('NoDataAvailable'));
} else {
	die('<b>'.get_lang('ErrorWhileBuildingReport').'</b>');
}

if ($_REQUEST['format'] == 'html' || $_REQUEST['format'] == 'directlink') {
	if (isset($reports_template[$_REQUEST['type']]['html_header'])) {
		echo $reports_template[$_REQUEST['type']]['html_header'];
	}
	echo '<script type="text/javascript" charset="utf-8">
			$(document).ready(function() {

				$("#reportsData'.$idsuffix.'").dataTable({
					"oLanguage":
						{
							"sProcessing":   "Traitement en cours...",
							"sLengthMenu":   "Afficher _MENU_ éléments",
							"sZeroRecords":  "Aucun élément à afficher",
							"sInfo":         "Affichage de l'."'".'élement _START_ à _END_ sur _TOTAL_ éléments",
							"sInfoEmpty":    "Affichage de l'."'".'élement 0 à 0 sur 0 éléments",
							"sInfoFiltered": "(filtré de _MAX_ éléments au total)",
							"sInfoPostFix":  "",
							"sSearch":       "Rechercher :",
							"sUrl":          "",
							"oPaginate": {
								"sFirst":    "Premier",
								"sPrevious": "Précédent",
								"sNext":     "Suivant",
								"sLast":     "Dernier"
							}
						}
				});
			} );
		</script>';
	echo '<table id="reportsData'.$idsuffix.'" class="display">'; // FIXME style

	// counting fields
	$nfields = multiquery_num_fields($result);
	$columns = array();	
	$columns_islink = array();
	echo '<thead><tr>';
	for ($i=0; $i < $nfields; $i++)	{
		$columns[$i] = multiquery_field_name($result, $i);
		if (substr($columns[$i], -5, 5) != '_link') {
			$column_islink[$i] = false;
			echo '<th>'.$columns[$i].'</th>';
		} else 
			$columns_islink[$i] = true;
	}

	// checking resolving link column id
	$columns_flip = array_flip($columns);
	$columns_link = array();
	for ($i=0; $i < $nfields; $i++)
		if ($column_islink[$i] == false && array_key_exists($columns[$i].'_link', $columns_flip))
			$columns_link[$i] = $columns_flip[$columns[$i].'_link'];
		else
			$columns_link[$i] = '';
	echo '</tr></thead><tbody>';
	while ($row = multiquery_fetch_row($result)) {
		echo '<tr>';
		for ($i = 0; $i<$nfields; $i++)
			if (!$columns_islink[$i]){ // ignore links
				if ($columns_link[$i] != '') // link is defined
					if (substr($columns_link[$i],0,10) == 'javascript') {
						echo '<td><a href="#" onclick="'.$row[$columns_link[$i]].'">'.$row[$i].'</a></td>'; 
					}
					else {
						echo '<td><a href="'.$row[$columns_link[$i]].'">'.$row[$i].'</a></td>'; 
					}
				else
					echo '<td>'.$row[$i].'</td>';
			}
		echo "</tr>\n";
	}
	echo '</tbody></table>';
	if ($_REQUEST['format'] == 'directlink') {
		echo '</div>
		<div id="result2" class="result" style="margin: 50px;">
		</div>
		<div id="result3" class="result" style="margin: 100px;">
		</div>
		<div id="result4" class="result" style="margin: 150px;">
		</div>';

		Display::display_footer();
		
	}
} else if ($_REQUEST['format'] == 'csv') {
	$nfields = multiquery_num_fields($result);
	$columns = array();	
	$columns_islink = array();
	for ($i=0; $i < $nfields; $i++)	{
		$columns[$i] = multiquery_field_name($result, $i);
		if (substr($columns[$i], -5, 5) != '_link') {
			$column_islink[$i] = false;
			echo csv_escaping($columns[$i]).',';
		} else 
			$columns_islink[$i] = true;
	}

	echo "\n";
	while ($row = multiquery_fetch_row($result)) {
		for ($i = 0; $i<$nfields; $i++)
			if (!$columns_islink[$i]) // ignore links
				echo csv_escaping($row[$i]).',';  // fixme
		echo "\n";
	}
} else die(get_lang('UnknownFormat'));

function csv_escaping($value, $csv_separator = ',') {
	$value = str_replace('"','""',$value);
	if (strpos($value, '""') or strpos($value, $csv_separator) or $value != trim($value) ) {
		$value = '"'.$value.'"';
	}
	return $value;
}
?>
