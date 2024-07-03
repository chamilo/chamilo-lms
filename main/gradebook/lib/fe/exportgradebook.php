<?php
/* For licensing terms, see /license.txt */
/**
 * Script.
 */

/**
 * Prints an HTML page with a table containing the gradebook data.
 *
 * @param    array    Array containing the data to be printed in the table
 * @param    array    Table headers
 * @param    string    View to print as a title for the table
 * @param    string    Course name to print as title for the table
 *
 * @return string
 */
function print_table($data_array, $header_names, $view, $coursename)
{
    $styleWebPath = api_get_path(WEB_PUBLIC_PATH).'assets/bootstrap/dist/css/bootstrap.min.css';

    $printdata = '<!DOCTYPE html>
        <html lang="'.api_get_language_isocode().'">
        <head>
        <title>'.get_lang('Print').'</title>
        <meta http-equiv="Content-Type" content="text/html; charset='.api_get_system_encoding().'" />
        '.api_get_css(api_get_cdn_path($styleWebPath), 'screen,print').'
        </head>
        <body dir="'.api_get_text_direction().'"><div id="main">';

    $printdata .= '<h2>'.$view.' : '.$coursename.'</h2>';

    $table = new HTML_Table(['class' => 'table table-bordered']);
    $table->setHeaders($header_names);
    $table->setData($data_array);

    $printdata .= $table->toHtml();
    $printdata .= '</div></body></html>';

    return $printdata;
}

/**
 * This function get a content html for export inside a pdf file.
 *
 * @param    array    table headers
 * @param    array    table body
 * @param    array    pdf headers
 * @param    array    pdf footers
 */
function export_pdf_with_html($headers_table, $data_table, $headers_pdf, $footers_pdf, $title_pdf)
{
    $headers_in_pdf = '<img src="'.api_get_path(WEB_CSS_PATH).api_get_setting('stylesheets').'/images/header-logo.png">';

    if (is_array($headers_pdf)) {
        // preparing headers pdf
        $header = '<br/><br/>
                        <table width="100%" cellspacing="1" cellpadding="5" border="0" class="strong">
                        <tr>
                            <td width="100%" style="text-align: center;" class="title" colspan="4">
                            <h1>'.$title_pdf.'</h1></td></tr>';
        foreach ($headers_pdf as $header_pdf) {
            if (!empty($header_pdf[0]) && !empty($header_pdf[1])) {
                $header .= '<tr><td><strong>'.$header_pdf[0].'</strong> </td><td>'.$header_pdf[1].'</td></tr>';
            }
        }
        $header .= '</table><br />';
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
    $css = api_get_print_css();
    $items_per_page = 30;
    $count_pages = ceil(count($data_table) / $items_per_page);
    for ($x = 0; $x < $count_pages; $x++) {
        $content_table .= '<table width="100%" border="1" style="border-collapse:collapse">';
        // header table
        $content_table .= '<tr>';
        $i = 0;
        if (is_array($headers_table)) {
            foreach ($headers_table as $head_table) {
                if (!empty($head_table[0])) {
                    $width = (!empty($head_table[1]) ? $head_table[1].'%' : '');
                    $content_table .= '<th width="'.$width.'">'.$head_table[0].'</th>';
                    $i++;
                }
            }
        }
        $content_table .= '</tr>';
        // body table

        if (is_array($data_table) && count($data_table) > 0) {
            $offset = $x * $items_per_page;
            $data_table = array_slice($data_table, $offset, count($data_table));
            $i = 1;
            $item = $offset + 1;
            foreach ($data_table as $data) {
                $content_table .= '<tr>';
                $content_table .= '<td>'.($item < 10 ? '0'.$item : $item).'</td>';
                foreach ($data as $key => $content) {
                    if (isset($content)) {
                        $key == 1 ? $align = 'align="left"' : $align = 'align="center"';
                        $content_table .= '<td '.$align.' style="padding:4px;" >'.$content.'</td>';
                    }
                }
                $content_table .= '</tr>';
                $i++;
                $item++;
                if ($i > $items_per_page) {
                    break;
                }
            }
        } else {
            $content_table .= '<tr colspan="'.$i.'"><td>'.get_lang('Empty').'</td></tr>';
        }
        $content_table .= '</table>';
        if ($x < ($count_pages - 1)) {
            $content_table .= '<pagebreak />';
        }
    }
    $pdf = new PDF();
    $pdf->set_custom_footer($footer);
    $pdf->set_custom_header($headers_in_pdf);
    $pdf->content_to_pdf($header.$content_table, $css, $title_pdf);
    exit;
}

/**
 * Exports the data as a table on a PDF page.
 *
 * @param    resource    The PDF object (ezpdf class) used to generate the file
 * @param    array        The data array
 * @param    array        Table headers
 * @param    string        Format (portrait or landscape)
 */
function export_pdf($pdf, $newarray, $header_names, $format)
{
    $pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
    $pdf->ezSetCmMargins(0, 0, 0, 0);
    $pdf->ezSetY(($format == 'portrait') ? '820' : '570');
    $pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
    if ('portrait' == $format) {
        $pdf->line(40, 790, 540, 790);
        $pdf->line(40, 40, 540, 40);
    } else {
        $pdf->line(40, 540, 790, 540);
        $pdf->line(40, 40, 790, 40);
    }
    $pdf->ezSetY(($format == 'portrait') ? '750' : '520');
    $pdf->ezTable($newarray, $header_names, '', [
        'showHeadings' => 1,
        'shaded' => 1,
        'showLines' => 1,
        'rowGap' => 3,
        'width' => (($format == 'portrait') ? '500' : '750'),
    ]);
    $pdf->ezStream();
}
