<?php
/* For licensing terms, see /license.txt */

/**
 * Gradebook results class
 * @author Yannick Warnier
 * @package chamilo.gradebook
 */
class GradeBookResult
{
    private $gradebook_list = array(); //stores the list of exercises
    private $results = array(); //stores the results

    /**
     * constructor of the class
     */
    public function __construct($get_questions=false,$get_answers=false)
    {
    }

    /**
     * Exports the complete report as a CSV file
     * @param	string		Document path inside the document tool
     * @param	integer		Optional user ID
     * @param	boolean		Whether to include user fields or not
     * @return	boolean		False on error
     */
    public function exportCompleteReportCSV($dato)
    {
        $filename = 'gradebook_results_'.gmdate('YmdGis').'.csv';
        if (!empty($user_id)) {
            $filename = 'gradebook_results_user_'.$user_id.'_'.gmdate('YmdGis').'.csv';
        }
        $data = '';
        //build the results
        //titles

        foreach ($dato[0] as $header_col) {
            if(!empty($header_col)) {
                $data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($header_col))).';';
            }
        }

        $data .="\r\n";
        $cant_students = count($dato[1]);
        //print_r($data);		exit();

        for($i=0;$i<$cant_students;$i++) {
            $column = 0;
            foreach($dato[1][$i] as $col_name) {
                $data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($col_name))).';';
            }
            $data .="\r\n";
        }

        //output the results
        $len = strlen($data);
        header('Content-type: application/octet-stream');
        header('Content-Type: application/force-download');
        header('Content-length: '.$len);
        if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT'])) {
            header('Content-Disposition: filename= '.$filename);
        } else {
            header('Content-Disposition: attachment; filename= '.$filename);
        } if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            header('Pragma: ');
            header('Cache-Control: ');
            header('Cache-Control: public'); // IE cannot download from sessions without a cache
        }
        header('Content-Description: '.$filename);
        header('Content-transfer-encoding: binary');
        echo $data;

        return true;
    }

    /**
     * Exports the complete report as an XLS file
     * @return	boolean		False on error
     */
    public function exportCompleteReportXLS($data)
    {
        $filename = 'gradebook-results-'.api_get_local_time().'.xls';

        $spreadsheet = new PHPExcel();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();

        $line = 0;
        $column = 0; //skip the first column (row titles)

        //headers
        foreach ($data[0] as $header_col) {
            $worksheet->SetCellValueByColumnAndRow($line, $column, html_entity_decode(strip_tags($header_col)));
            $column++;
        }
        $line++;

        $cant_students = count($data[1]);

        for ($i = 0; $i < $cant_students; $i++) {
            $column = 0;
            foreach ($data[1][$i] as $col_name) {
                $worksheet->SetCellValueByColumnAndRow($line,$column, html_entity_decode(strip_tags($col_name)));
                $column++;
            }
            $line++;
        }

        $file = api_get_path(SYS_ARCHIVE_PATH).api_replace_dangerous_char($filename);
        $writer = new PHPExcel_Writer_Excel2007($spreadsheet);
        $writer->save($file);
        DocumentManager::file_send_for_download($file, true, $filename);
        exit;
    }

    /**
     * Exports the complete report as a DOCX file
     * @return	boolean		False on error
     */
    public function exportCompleteReportDOC($data)
    {
        $_course = api_get_course_info();
        $filename = 'gb_results_'.$_course['code'].'_'.gmdate('YmdGis');
        $filepath = api_get_path(SYS_ARCHIVE_PATH).$filename;
        require_once api_get_path(LIBRARY_PATH).'phpdocx/classes/CreateDocx.inc';
        $docx = new CreateDocx();
        $paramsHeader = array(
            'font' => 'Courrier',
            'jc' => 'left',
            'textWrap' => 5,
        );
        $docx->addHeader(get_lang('FlatView'), $paramsHeader);
        $params = array(
            'font' => 'Courrier',
            'border' => 'single',
            'border_sz' => 20
        );
        $lines = 0;
        $values[] = implode("\t",$data[0]);
        foreach ($data[1] as $line) {
            $values[] = implode("\t",$line);
            $lines++;
        }
        //$data = array();
        //$docx->addTable($data, $params);
        $docx->addList($values, $params);
        //$docx->addFooter('', $paramsHeader);
        $paramsPage = array(
            //    'titlePage' => 1,
            'orient' => 'landscape',
            //    'top' => 4000,
            //    'bottom' => 4000,
            //    'right' => 4000,
            //    'left' => 4000
        );
        $docx->createDocx($filepath,$paramsPage);
        //output the results
        $data = file_get_contents($filepath.'.docx');
        $len = strlen($data);
        //header("Content-type: application/vnd.ms-word");
        header('Content-type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        //header('Content-Type: application/force-download');
        header('Content-length: '.$len);
        header("Content-Disposition: attachment; filename=\"$filename.docx\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0,pre-check=0');
        header('Pragma: public');
        echo $data;

        return true;
    }
}
