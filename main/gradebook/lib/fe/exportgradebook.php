<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Prints an HTML page with a table containing the gradebook data
 * @param	array 	Array containing the data to be printed in the table
 * @param	array	Table headers
 * @param	string	View to print as a title for the table
 * @param	string	Course name to print as title for the table
 */
function print_table ($data_array,$header_names,$view,$coursename) {
	$printdata= '<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.api_get_language_isocode().'" lang="'.api_get_language_isocode().'">
<head>
<title>'.get_lang('Print').'</title>
<meta http-equiv="Content-Type" content="text/html; charset='.api_get_system_encoding().'" />


<style type="text/css">
body {
	font-size: 12px;
	color: #000;
	margin: 10px;
	padding: 0;
}

a:link {text-decoration: none; font-weight : bold; color : black;}
a:visited {text-decoration: none; font-weight : bold; color : black;}
a:active {text-decoration: none; font-weight : bold;  color : black;}

.data_table{
  	border-collapse: collapse;
	width: 100%;
	padding: 5px;
	border: 1px;
}
.data_table th{
  	padding: 5px;
	vertical-align: top;
  	border-top: 1px solid black;
  	border-bottom: 1px solid black;
  	border-right: 1px solid black;
  	border-left: 1px solid black;
}
.data_table tr.row_odd{
  	background-color: #fafafa;
  }
.data_table tr.row_even{
  	background-color: #fff;
}
.data_table td{
  	padding: 5px;
	  vertical-align: top;
  	border-bottom: 1px solid black;
  	border-right: 1px solid black;
  	border-left: 1px solid black;
}
</style>
</head>
<body dir="'.api_get_text_direction().'"><div id="main">';

	$printdata .= '<h2>'.$view.' : '.$coursename.'</h2>';
	//@todo not necessary here
	//$printdata .= '<h3>'.get_lang('Date').' : '.api_convert_and_format_date(null, DATE_FORMAT_SHORT). ' ' . api_convert_and_format_date(null, TIME_NO_SEC_FORMAT).'</h3>';
	$printdata .= '<table border="1" width="90%" cellspacing="1" cellpadding="1">';
	foreach ($header_names as $header) {
		$printdata .= '<th>'.$header.'</th>';
	}

	foreach ($data_array as $data) {
		$printdata .= '<tr>';
		foreach ($data as $rowdata) {
			$printdata .= '<td>'.$rowdata.'</td>';
		}
		$printdata .= '</tr>';

	}
	$printdata .= '</table></div></body></html>';
	return $printdata;
}


/**
 * This function get a content html for export inside a pdf file
 * @param   array   table headers
 * @param   array   table body
 * @param   array   pdf headers
 * @param   array   pdf footers
 * @return void
 */
function export_pdf_attendance($headers_table, $data_table, $headers_pdf, $footers_pdf, $title_pdf) {
    require_once api_get_path(LIBRARY_PATH).'mpdf/mpdf.php';

    $mpdf = new mPDF('UTF-8', 'A4-L', '', '', 15, 10, 35, 20, 4, 2, 'L');
    $mpdf->useOnlyCoreFonts = true;
    $mpdf->mirrorMargins = 0;      // Use different Odd/Even headers and footers and mirror margins

    if (is_array($headers_pdf)) {
        // preparing headers pdf
        $header = '<table width="100%"  cellspacing="1" cellpadding="1" border="0" class="strong">
                                <tr>
                                   <td ROWSPAN="3" style="text-align: left;" class="title"><img src="'.api_get_path(WEB_CSS_PATH).api_get_setting('stylesheets').'/images/header-logo.png"></td><td  colspan="3"><h1>'.$title_pdf.'</h1></td>
                                <tr>
                                    <td></td>
                                    <td><strong>'.$headers_pdf[0][0].'</strong> </td><td> <strong>'.$headers_pdf[0][1].'</strong></td>
                                    <td><strong>'.$headers_pdf[1][0].'</strong> </td><td> <strong>'.$headers_pdf[1][1].'</strong></td>

                                </tr>
                                <tr>
                                    <td></td>
                                    <td><strong>'.$headers_pdf[2][0].'</strong> </td><td> <strong>'.$headers_pdf[2][1].'</strong></td>
                                    <td><strong>'.$headers_pdf[3][0].' </strong></td><td> <strong>'.$headers_pdf[3][1].'</strong></td>
                                </tr>
                                <tr>
                                    <td></td><td></td>
                                    <td><strong>'.$headers_pdf[4][0].'</strong></td><td> <strong>'.$headers_pdf[4][1].'</strong></td>
                                    <td><strong>'.$headers_pdf[5][0].'</strong> </td><td> <strong>'.$headers_pdf[5][1].'</strong></td>
                                </tr>
                            </table>';
    }

    // preparing footer pdf
    $footer = '<table width="100%" cellspacing="2" cellpadding="10" border="0">';
    if (is_array($footers_pdf)) {
        $footer .= '<tr>';
        foreach ($footers_pdf as $foot_pdf) {
            $footer .= '<td width="33%" style="text-align: center;">'.$foot_pdf.'</td>';
        }
        $footer .= '</tr>';
    }
    $footer .= '</table>';

    $footer .= '<div align="right" style="font-weight: bold;">{PAGENO}/{nb}</div>';

    // preparing content pdf
    $css_file = api_get_path(TO_SYS, WEB_CSS_PATH).api_get_setting('stylesheets').'/print.css';
    if (file_exists($css_file)) {
        $css = @file_get_contents($css_file);
    } else {
        $css = '';
    }

    if(count($data_table) > 30)
            $items_per_page = (count($data_table)/2);
        else
            $items_per_page = count($data_table);

    $count_pages = ceil(count($data_table) / $items_per_page);
    for ($x = 0; $x<$count_pages; $x++) {
        $content_table .= '<table width="100%" border="1" style="border-collapse:collapse">';
        // header table
        $content_table .= '<tr>';
        $i = 0;
        if (is_array($headers_table)) {

            foreach ($headers_table as $head_table) {
                if (!empty($head_table[0])) {
                    $width = (!empty($head_table[1])?$head_table[1].'%':'');
                    $content_table .= '<th width="'.$width.'">'.$head_table[0].'</th>';
                    $i++;
                }
            }
        }
        $content_table .= '</tr>';
        // body table
        if (is_array($data_table) && count($data_table) > 0) {
            $offset = $x*$items_per_page;
            $data_table = array_slice ($data_table, $offset, count($data_table));
            $i = 1;
            $item = $offset+1;
            foreach ($data_table as $data) {
                $content_table .= '<tr>';
                $content_table .= '<td>'.($item<10?'0'.$item:$item).'</td>';
                foreach ($data as  $key => $content) {
                    if (isset($content)) {
                        $key == 1 ? $align='align="left"':$align='align="center"';
                        $content_table .= '<td '.$align.' style="padding:4px;" >'.$content.'</td>';
                    }
                }
                $content_table .= '</tr>';
                $i++;
                $item++;
                if ($i > $items_per_page) { break; }
            }
        } else {
            $content_table .= '<tr colspan="'.$i.'"><td>'.get_lang('Empty').'</td></tr>';
        }
        $content_table .= '</table>';
        if ($x < ($count_pages - 1)) { $content_table .= '<pagebreak />'; }
    }

    $html = $content_table;


    // set attributes for pdf
    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter($footer);
    if (!empty($css)) {
        $mpdf->WriteHTML($css, 1);
        $mpdf->WriteHTML($html, 2);
    } else {
        $mpdf->WriteHTML($html);
    }
    $mpdf->Output(replace_dangerous_char($title_pdf.'.pdf'), 'D');
    exit;
}


/**
 * This function get a content html for export inside a pdf file
 * @param	array	table headers
 * @param	array 	table body
 * @param	array	pdf headers
 * @param	array	pdf footers
 * @return void
 */
function export_pdf_with_html($headers_table, $data_table, $headers_pdf, $footers_pdf, $title_pdf) {
	
	require_once api_get_path(LIBRARY_PATH).'pdf.lib.php';
	$headers_in_pdf = '<img src="'.api_get_path(WEB_CSS_PATH).api_get_setting('stylesheets').'/images/header-logo.png">';
    
	if (is_array($headers_pdf)) {
		// preparing headers pdf
		$header = '<br/><br/><table width="100%" cellspacing="1" cellpadding="5" border="0" class="strong">							
					        <tr><td width="100%" style="text-align: center;" class="title" colspan="4"><h1>'.$title_pdf.'</h1></td></tr>';		
		foreach($headers_pdf as $header_pdf) {			
			if (!empty($header_pdf[0]) && !empty($header_pdf[1])) {
				$header.= '<tr><td><strong>'.$header_pdf[0].'</strong> </td><td>'.$header_pdf[1].'</td></tr>';
			}
		}		
		$header.='</table><br />';
	}
		
	// preparing footer pdf
	$footer = '<table width="100%" cellspacing="2" cellpadding="10" border="0">';
	if (is_array($footers_pdf)) {
		$footer .= '<tr>';	
		foreach ($footers_pdf as $foot_pdf) {
			$footer .= '<td width="33%" style="text-align: center;">'.$foot_pdf.'</td>';
		}
		$footer .= '</tr>';	
	}
	$footer .= '</table>';	
	$footer .= '<div align="right" style="font-weight: bold;">{PAGENO}/{nb}</div>';
	
	// preparing content pdf		
	$css_file = api_get_path(TO_SYS, WEB_CSS_PATH).api_get_setting('stylesheets').'/print.css';
	if (file_exists($css_file)) {
		$css = @file_get_contents($css_file);
	} else {
		$css = '';
	}	
	$items_per_page = 30;
	$count_pages = ceil(count($data_table) / $items_per_page);  
	for ($x = 0; $x<$count_pages; $x++) {
		$content_table .= '<table width="100%" border="1" style="border-collapse:collapse">';	
		// header table
		$content_table .= '<tr>';
		$i = 0;
		if (is_array($headers_table)) {
			foreach ($headers_table as $head_table) {
				if (!empty($head_table[0])) {
					$width = (!empty($head_table[1])?$head_table[1].'%':'');
					$content_table .= '<th width="'.$width.'">'.$head_table[0].'</th>';
					$i++;	
				}			
			}		
		}	
		$content_table .= '</tr>';			
		// body table
		
		if (is_array($data_table) && count($data_table) > 0) {
			$offset = $x*$items_per_page;				
			$data_table = array_slice ($data_table, $offset, count($data_table));
			$i = 1;
			$item = $offset+1;
			foreach ($data_table as $data) {			
				$content_table .= '<tr>';
				$content_table .= '<td>'.($item<10?'0'.$item:$item).'</td>';
				foreach ($data as  $key => $content) {							
					if (isset($content)) {
						$key == 1 ? $align='align="left"':$align='align="center"';
						$content_table .= '<td '.$align.' style="padding:4px;" >'.$content.'</td>';	
					}					
				}
				$content_table .= '</tr>';
				$i++;
				$item++;
				if ($i > $items_per_page) { break; }
			}			
		} else {
			$content_table .= '<tr colspan="'.$i.'"><td>'.get_lang('Empty').'</td></tr>';
		}	
		$content_table .= '</table>';				
		if ($x < ($count_pages - 1)) { $content_table .= '<pagebreak />'; }		
	}	
	$pdf = new PDF();
    $pdf->set_custom_footer($footer);
    $pdf->set_custom_header($headers_in_pdf);
	$pdf->content_to_pdf($header.$content_table, $css, $title_pdf );
	exit;

}

/**
 * Exports the data as a table on a PDF page
 * @param	resource	The PDF object (ezpdf class) used to generate the file
 * @param	array		The data array
 * @param	array		Table headers
 * @param	string		Format (portrait or landscape)
 */
function export_pdf($pdf,$newarray,$header_names,$format) {
	$pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
	$pdf->ezSetCmMargins(0,0,0,0);
	$pdf->ezSetY(($format=='portrait')?'820':'570');
	$pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
	//$pdf->ezText(get_lang('FlatView').' ('. api_convert_and_format_date(null, DATE_FORMAT_SHORT). ' ' . api_convert_and_format_date(null, TIME_NO_SEC_FORMAT) .')',12,array('justification'=>'center'));
	if ($format=='portrait') {
		$pdf->line(40,790,540,790);
		$pdf->line(40,40,540,40);
	} else {
		$pdf->line(40,540,790,540);
		$pdf->line(40,40,790,40);
	}
	$pdf->ezSetY(($format=='portrait')?'750':'520');
	$pdf->ezTable($newarray,$header_names,'',array('showHeadings'=>1,'shaded'=>1,'showLines'=>1,'rowGap'=>3,'width'=>(($format=='portrait')?'500':'750')));
	$pdf->ezStream();

}
