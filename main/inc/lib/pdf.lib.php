<?php
/* See license terms in /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

/**
 * Class PDF
 * @package chamilo.library
 *
 */
class PDF
{
    public $pdf;
    public $custom_header = array();
    public $custom_footer = array();
    public $params = array();
    public $template;

    /**
     * Creates the mPDF object
     * @param string  $pageFormat format A4 A4-L see  http://mpdf1.com/manual/index.php?tid=184&searchstring=format
     * @param string  $orientation orientation "P" = Portrait "L" = Landscape
     * @param array $params
     * @param Template $template
     */
    public function __construct(
        $pageFormat = 'A4',
        $orientation = 'P',
        $params = array(),
        $template = null
    ) {
        $this->template = $template;
        /* More info @ http://mpdf1.com/manual/index.php?tid=184&searchstring=mPDF
         * mPDF ([ string $mode [, mixed $format [, float $default_font_size [, string $default_font [, float $margin_left , float $margin_right , float $margin_top , float $margin_bottom , float $margin_header , float $margin_footer [, string $orientation ]]]]]])
         */
        if (!in_array($orientation, array('P','L'))) {
            $orientation = 'P';
        }
        //$this->pdf = $pdf = new mPDF('UTF-8', $pageFormat, '', '', 30, 20, 27, 25, 16, 13, $orientation);
        //left, right, top, bottom, margin_header, margin footer

        $params['left'] = isset($params['left']) ? $params['left'] : 15;
        $params['right'] = isset($params['right']) ? $params['right'] : 15;
        $params['top'] = isset($params['top']) ? $params['top'] : 20;
        $params['bottom'] = isset($params['bottom']) ? $params['bottom'] : 15;

        $this->params['filename'] = isset($params['filename']) ? $params['filename'] : api_get_local_time();
        $this->params['pdf_title'] = isset($params['pdf_title']) ? $params['pdf_title'] : get_lang('Untitled');
        $this->params['course_info'] = isset($params['course_info']) ? $params['course_info'] : api_get_course_info();
        $this->params['session_info'] = isset($params['session_info']) ? $params['session_info'] : api_get_session_info(api_get_session_id());
        $this->params['course_code'] = isset($params['course_code']) ? $params['course_code'] : api_get_course_id();
        $this->params['add_signatures'] = isset($params['add_signatures']) ? $params['add_signatures'] : false;
        $this->params['show_real_course_teachers'] = isset($params['show_real_course_teachers']) ? $params['show_real_course_teachers'] : false;
        $this->params['student_info'] = isset($params['student_info']) ? $params['student_info'] : false;
        $this->params['show_grade_generated_date'] = isset($params['show_grade_generated_date']) ? $params['show_grade_generated_date'] : false;
        $this->params['show_teacher_as_myself'] = isset($params['show_teacher_as_myself']) ? $params['show_teacher_as_myself'] : true;
        $this->params['pdf_date'] = isset($params['pdf_date']) ? $params['pdf_date'] : api_format_date(api_get_local_time(), DATE_TIME_FORMAT_LONG);

        $this->pdf = new mPDF(
            'UTF-8',
            $pageFormat,
            '',
            '',
            $params['left'],
            $params['right'],
            $params['top'],
            $params['bottom'],
            8,
            8,
            $orientation
        );
    }

    /**
     * Export the given HTML to PDF, using a global template
     *
     * @uses export/table_pdf.tpl

     * @param $content
     * @param bool|false $saveToFile
     * @param bool|false $returnHtml
     *
     * @return string
     */
    public function html_to_pdf_with_template($content, $saveToFile = false, $returnHtml = false)
    {
        if (empty($this->template)) {
            $tpl = new Template('', false, false, false);
        } else {
            $tpl = $this->template;
        }

        // Assignments
        $tpl->assign('pdf_content', $content);

        $organization = ChamiloApi::getPlatformLogo();
        // Use custom logo image.
        $pdfLogo = api_get_setting('pdf_logo_header');
        if ($pdfLogo === 'true') {
            $visualTheme = api_get_visual_theme();
            $img = api_get_path(SYS_CSS_PATH).'themes/'.$visualTheme.'/images/pdf_logo_header.png';
            if (file_exists($img)) {
                $img = api_get_path(WEB_CSS_PATH) . 'themes/' . $visualTheme . '/images/pdf_logo_header.png';
                $organization = "<img src='$img'>";
            }
        }

        $tpl->assign('organization', $organization);

        // Showing only the current teacher/admin instead the all teacher list name see BT#4080
        if (isset($this->params['show_real_course_teachers']) &&
            $this->params['show_real_course_teachers']
        ) {
            if (isset($this->params['session_info']) &&
                !empty($this->params['session_info'])
            ) {
                $teacher_list = SessionManager::getCoachesByCourseSessionToString(
                    $this->params['session_info']['id'],
                    $this->params['course_info']['real_id']

                );
            } else {
                $teacher_list = CourseManager::get_teacher_list_from_course_code_to_string(
                    $this->params['course_code']
                );
            }
        } else {
            $user_info = api_get_user_info();

            if ($this->params['show_teacher_as_myself']) {
                $teacher_list = $user_info['complete_name'];
            }
        }

        $tpl->assign('pdf_course', $this->params['course_code']);
        $tpl->assign('pdf_course_info', $this->params['course_info']);
        $tpl->assign('pdf_session_info', $this->params['session_info']);
        $tpl->assign('pdf_date', $this->params['pdf_date']);
        $tpl->assign('pdf_teachers', $teacher_list);
        $tpl->assign('pdf_title', $this->params['pdf_title']);
        $tpl->assign('pdf_student_info', $this->params['student_info']);
        $tpl->assign('show_grade_generated_date', $this->params['show_grade_generated_date']);
        $tpl->assign('add_signatures', $this->params['add_signatures']);

        // Getting template
        $tableTemplate = $tpl->get_template('export/table_pdf.tpl');
        $html = $tpl->fetch($tableTemplate);
        $html = api_utf8_encode($html);

        $css_file = api_get_path(SYS_CSS_PATH).'/print.css';
        $css = file_exists($css_file) ? @file_get_contents($css_file) : '';

        $html = self::content_to_pdf(
            $html,
            $css,
            $this->params['filename'],
            $this->params['course_code'],
            'D',
            $saveToFile,
            null,
            $returnHtml
        );

        if ($returnHtml) {
            return $html;
        }
    }

    /**
     * Converts HTML files to PDF
     * @param mixed $html_file_array could be an html file path or an array
     * with paths example:
     * /var/www/myfile.html or array('/myfile.html','myotherfile.html') or
     * even an indexed array with both 'title' and 'path' indexes
     * for each element like
     * array(
     *     0 => array('title'=>'Hello','path'=>'file.html'),
     *     1 => array('title'=>'Bye','path'=>'file2.html')
     * );
     * @param   string  pdf name
     * @param   string  course code (if you are using html that are located in the document tool you must provide this)
     * @param bool Whether to print the header, footer and watermark (true) or just the content (false)
     * @return bool
     */
    public function html_to_pdf(
        $html_file_array,
        $pdf_name = '',
        $course_code = null,
        $print_title = false,
        $complete_style = true,
        $addStyle = true
    ) {
        if ($complete_style === false) {
            error_log(__FUNCTION__.' with no style');
        }

        if (empty($html_file_array)) {
            return false;
        }

        if (is_array($html_file_array)) {
            if (count($html_file_array) == 0) {
                return false;
            }
        } else {
            if (!file_exists($html_file_array)) {
                return false;
            }
            // Converting the string into an array
            $html_file_array = array($html_file_array);
        }

        if (!empty($course_code)) {
            $course_data = api_get_course_info($course_code);
        } else {
            $course_data = api_get_course_info();
        }

        // Clean styles and javascript document
        $clean_search = array(
            '@<script[^>]*?>.*?</script>@si',
            '@<style[^>]*?>.*?</style>@si'
        );

        // Formatting the pdf
        self::format_pdf($course_data, $complete_style);
        $counter = 1;
        foreach ($html_file_array as $file) {
            //Add a page break per file
            $page_break = '<pagebreak>';
            if ($counter == count($html_file_array)) {
                $page_break = '';
            }
            $counter++;

            //if the array provided contained subarrays with 'title' entry,
            // then print the title in the PDF
            if (is_array($file) && isset($file['title'])) {
                $html_title = $file['title'];
                $file  = $file['path'];
            } else {
                //we suppose we've only been sent a file path
                $html_title = basename($file);
            }

            if (empty($file) && !empty($html_title)) {
                //this is a chapter, print title & skip the rest
                if ($print_title) {
                    $this->pdf->WriteHTML(
                        '<html><body><h3>'.$html_title.'</h3></body></html>'.$page_break
                    );
                }
                continue;
            }

            if (!file_exists($file)) {
                //the file doesn't exist, skip
                continue;
            }

            if ($addStyle) {
                $css_file = api_get_path(SYS_CSS_PATH).'/print.css';
                $css = file_exists($css_file) ? @file_get_contents($css_file) : '';

                $this->pdf->WriteHTML($css, 1);
            }

            //it's not a chapter but the file exists, print its title
            if ($print_title) {
                $this->pdf->WriteHTML(
                    '<html><body><h3>' . $html_title . '</h3></body></html>'
                );
            }

            $file_info = pathinfo($file);
            $extension = $file_info['extension'];

            if (in_array($extension, array('html', 'htm'))) {
                $dirName = $file_info['dirname'];
                $filename = $file_info['basename'];
                $filename = str_replace('_', ' ', $filename);

                if ($extension === 'html') {
                    $filename = basename($filename, '.html');
                } elseif($extension === 'htm'){
                    $filename = basename($filename, '.htm');
                }

                $document_html = @file_get_contents($file);
                $document_html = preg_replace($clean_search, '', $document_html);

                //absolute path for frames.css //TODO: necessary?
                $absolute_css_path = api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/frames.css';
                $document_html = str_replace('href="./css/frames.css"', $absolute_css_path, $document_html);

                if (!empty($course_data['path'])) {
                    $document_html= str_replace('../', '', $document_html);
                    $document_path = api_get_path(SYS_COURSE_PATH).$course_data['path'].'/document/';

                    $doc = new DOMDocument();
                    $result = @$doc->loadHTML($document_html);

                    //Fixing only images @todo do the same thing with other elements
                    $elements = $doc->getElementsByTagName('img');
                    if (!empty($elements)) {
                        foreach ($elements as $item) {
                            $old_src = $item->getAttribute('src');
                            if (strpos($old_src, 'http') === false) {
                                if (strpos($old_src, '/main/default_course_document') === false) {
                                    $old_src_fixed = '';

                                    if (strpos($old_src, '/main/img') === false) {
                                        if (api_get_path(REL_PATH) != '/') {
                                            $old_src_fixed = str_replace(
                                                api_get_path(REL_PATH).'courses/'.$course_data['path'].'/document/',
                                                '',
                                                $old_src
                                            );

                                            // Try with the dirname if exists
                                            if ($old_src_fixed == $old_src) {
                                                if (file_exists($dirName.'/'.$old_src)) {
                                                    $document_path = '';
                                                    $old_src_fixed = $dirName.'/'.$old_src;
                                                }
                                            }
                                        } else {
                                            if (strpos($old_src, 'courses/'.$course_data['path'].'/document/') !== false) {
                                                $old_src_fixed = str_replace('courses/'.$course_data['path'].'/document/', '', $old_src);
                                            } else {

                                                // Try with the dirname if exists
                                                if (file_exists($dirName.'/'.$old_src)) {
                                                    $document_path = '';
                                                    $old_src_fixed = $dirName.'/'.$old_src;
                                                } else {
                                                    $document_path = '';
                                                    $old_src_fixed = $old_src;
                                                }
                                            }
                                        }

                                        $new_path = $document_path.$old_src_fixed;
                                    } else {
                                        $new_path = $old_src;
                                    }
                                    $document_html = str_replace($old_src, $new_path, $document_html);
                                }
                            } else {
                                //Check if this is a complete URL
                                /*if (strpos($old_src, 'courses/'.$course_data['path'].'/document/') === false) {

                                } else {
                                    $old_src_fixed = str_replace(api_get_path(SYS_COURSE_PATH).$course_data['path'].'/document/', '', $old_src);
                                    $new_path = $document_path.$old_src_fixed;
                                    $document_html= str_replace($old_src, $new_path, $document_html);
                                }*/
                            }
                        }
                    }
                }

                api_set_encoding_html($document_html, 'UTF-8'); // The library mPDF expects UTF-8 encoded input data.
                // TODO: Maybe it is better idea the title to be passed through
                $title = api_get_title_html($document_html, 'UTF-8', 'UTF-8');
                // $_GET[] too, as it is done with file name.
                // At the moment the title is retrieved from the html document itself.
                //echo $document_html;exit;
                if (empty($title)) {
                    $title = $filename; // Here file name is expected to contain ASCII symbols only.
                }
                if (!empty($document_html)) {
                    $this->pdf->WriteHTML($document_html.$page_break);
                }
            } elseif (in_array($extension, array('jpg','jpeg','png','gif'))) {
                //Images
                $image = Display::img($file);
                $this->pdf->WriteHTML('<html><body>'.$image.'</body></html>'.$page_break);
            }
        }

        if (empty($pdf_name)) {
            $output_file = 'pdf_'.date('Y-m-d-his').'.pdf';
        } else {
            $pdf_name = api_replace_dangerous_char($pdf_name);
            $output_file = $pdf_name.'.pdf';
        }
        // F to save the pdf in a file
        $this->pdf->Output($output_file, 'D');
        exit;
    }

    /**
     * Converts an html string to PDF
     * @param   string $document_html valid html
     * @param   string $css CSS content of a CSS file
     * @param   string $pdf_name pdf name
     * @param   string $course_code course code
     * (if you are using html that are located in the document tool you must provide this)
     * @param string $outputMode the MPDF output mode can be:
     * 'I' (print on standard output),
     * 'D' (download file) (this is the default value),
     * 'F' (save to local file) or
     * 'S' (return as a string)
     * @return  string  Web path
     */
    public function content_to_pdf(
        $document_html,
        $css = '',
        $pdf_name = '',
        $course_code = null,
        $outputMode = 'D',
        $saveInFile = false,
        $fileToSave = null,
        $returnHtml = false
    ) {
        global $_configuration;

        if (empty($document_html)) {
            return false;
        }

        //clean styles and javascript document
        $clean_search = array(
            '@<script[^>]*?>.*?</script>@si',
            '@<style[^>]*?>.*?</style>@siU'
        );

        // Formatting the pdf
        $course_data = api_get_course_info($course_code);

        self::format_pdf($course_data);

        $document_html = preg_replace($clean_search, '', $document_html);

        //absolute path for frames.css //TODO: necessary?
        $absolute_css_path = api_get_path(WEB_CSS_PATH).api_get_setting('stylesheets').'/frames.css';
        $document_html = str_replace('href="./css/frames.css"','href="'.$absolute_css_path.'"', $document_html);

        $document_html = str_replace('../../', '', $document_html);
        $document_html = str_replace('../', '', $document_html);
        $document_html = str_replace(
            (empty($_configuration['url_append']) ? '' : $_configuration['url_append'].'/').'courses/'.$course_code.'/document/',
            '',
            $document_html
        );

        if (!empty($course_data['path'])) {
            $document_path = api_get_path(SYS_COURSE_PATH).$course_data['path'].'/document/';

            $doc = new DOMDocument();
            $result = @$doc->loadHTML($document_html);

            //Fixing only images @todo do the same thing with other elements
            $elements = $doc->getElementsByTagName('img');
            if (!empty($elements)) {
                foreach ($elements as $item) {
                    $old_src = $item->getAttribute('src');
                    //$old_src= str_replace('../','',$old_src);
                    if (strpos($old_src, 'http') === false) {
                        if (strpos($old_src, '/main/default_course_document') === false) {
                            if (strpos($old_src, '/main/inc/lib/') === false) {
                                $old_src_fixed = str_replace(api_get_path(REL_COURSE_PATH).$course_data['path'].'/document/', '', $old_src);
                                $old_src_fixed = str_replace('courses/'.$course_data['path'].'/document/', '', $old_src_fixed);
                                $new_path = $document_path.$old_src_fixed;
                                $document_html= str_replace($old_src, $new_path, $document_html);

                            }
                        }
                    }
                }
            }
        }

        //replace relative path by absolute path for resources
        //$document_html= str_replace('src="/chamilo/main/default_course_document/', 'temp_template_path', $document_html);// before save src templates not apply
        //$document_html= str_replace('src="/', 'temp_template_path', $document_html);// before save src templates not apply
        //$document_html= str_replace('src="/chamilo/main/default_course_document/', 'temp_template_path', $document_html);// before save src templates not apply

        //$src_http_www= 'src="'.api_get_path(WEB_COURSE_PATH).$course_data['path'].'/document/';
        //$document_html= str_replace('src="',$src_http_www, $document_html);
        //$document_html= str_replace('temp_template_path', 'src="/main/default_course_document/', $document_html);// restore src templates

        api_set_encoding_html($document_html, 'UTF-8'); // The library mPDF expects UTF-8 encoded input data.
        $title = api_get_title_html($document_html, 'UTF-8', 'UTF-8');  // TODO: Maybe it is better idea the title to be passed through
        // $_GET[] too, as it is done with file name.
        // At the moment the title is retrieved from the html document itself.

        if ($returnHtml) {
            return "<style>$css</style>".$document_html;
        }

        if (!empty($css)) {
            $this->pdf->WriteHTML($css, 1);
        }
        $this->pdf->WriteHTML($document_html);

        if (empty($pdf_name)) {
            $output_file = 'pdf_'.date('Y-m-d-his').'.pdf';
        } else {
            $pdf_name = api_replace_dangerous_char($pdf_name);
            $output_file = $pdf_name.'.pdf';
        }
        //$this->pdf->Output($output_file, $outputMode); // F to save the pdf in a file

        if ($outputMode == 'F') {
            $output_file = api_get_path(SYS_ARCHIVE_PATH) . $output_file;
        }

        if ($saveInFile) {
            $fileToSave = !empty($fileToSave) ? $fileToSave : api_get_path(SYS_ARCHIVE_PATH).uniqid();

            $this->pdf->Output(
                $fileToSave,
                $outputMode
            ); // F to save the pdf in a file

        } else {
            $this->pdf->Output(
                $output_file,
                $outputMode
            ); // F to save the pdf in a file
        }

        if ($outputMode != 'F') {
            exit;
        }
    }

    /**
     * Gets the watermark from the platform or a course
     * @param   string  course code (optional)
     * @param   mixed   web path of the watermark image, false if there is nothing to return
     */
    public static function get_watermark($course_code = null)
    {
        $web_path = false;
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $course_info = api_get_course_info($course_code);
            $store_path = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/'.api_get_current_access_url_id().'_pdf_watermark.png';   // course path
            if (file_exists($store_path)) {
                $web_path   = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/'.api_get_current_access_url_id().'_pdf_watermark.png';
            }
        } else {
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document/images/'.api_get_current_access_url_id().'_pdf_watermark.png';   // course path
            if (file_exists($store_path))
                $web_path   = api_get_path(WEB_CODE_PATH).'default_course_document/images/'.api_get_current_access_url_id().'_pdf_watermark.png';
        }
        return $web_path;
    }

    /**
     * Deletes the watermark from the Platform or Course
     * @param   string  $course_code course code (optional)
     * @param   mixed   web path of the watermark image, false if there is nothing to return
     * @return bool
     */
    public function delete_watermark($course_code = null)
    {
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $course_info = api_get_course_info($course_code);
            // course path
            $store_path  = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/'.api_get_current_access_url_id().'_pdf_watermark.png';

        } else {
            // course path
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document/images/'.api_get_current_access_url_id().'_pdf_watermark.png';
        }
        if (file_exists($store_path)) {
            unlink($store_path);
            return true;
        }
        return false;
    }

    /**
     * Uploads the pdf watermark in the main/default_course_document directory or in the course directory
     * @param	string	$filename filename
     * @param	string	$source_file path of the file
     * @param	string	$course_code
     * @return 	mixed	web path of the file if sucess, false otherwise
     */
    public function upload_watermark($filename, $source_file, $course_code = null)
    {
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $course_info = api_get_course_info($course_code);
            $store_path = api_get_path(SYS_COURSE_PATH).$course_info['path'];   // course path
            $web_path   = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/pdf_watermark.png';
        } else {
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document/images';   // course path
            $web_path   = api_get_path(WEB_CODE_PATH).'default_course_document/images/'.api_get_current_access_url_id().'_pdf_watermark.png';
        }
        $course_image = $store_path.'/'.api_get_current_access_url_id().'_pdf_watermark.png';

        if (file_exists($course_image)) {
            @unlink($course_image);
        }
        $my_image = new Image($source_file);
        $result = $my_image->send_image($course_image, -1, 'png');
        if ($result) {
            $result = $web_path;
        }
        return $result;
    }

    /**
     * Returns the default header
     */
    public function get_header($course_code = null)
    {
        /*$header = api_get_setting('pdf_export_watermark_text');
    	if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $header = api_get_course_setting('pdf_export_watermark_text');
        }
        return $header;*/
    }

    /**
     * Sets the PDF footer
     */
    public function set_footer()
    {
        $this->pdf->defaultfooterfontsize = 12;   // in pts
        $this->pdf->defaultfooterfontstyle = 'B';   // blank, B, I, or BI
        $this->pdf->defaultfooterline = 1;        // 1 to include line below header/above footer
        $platform_name   = api_get_setting('Institution');
        $left_content    = $platform_name;
        $center_content  = '';
        $right_content   = '{PAGENO} / {nb}';

        //@todo remove this and use a simpler way
        $footer = array(
            'odd' => array(
                'L' => array(
                    'content' => $left_content,
                    'font-size' => 10,
                    'font-style' => 'B',
                    'font-family' => 'serif',
                    'color' => '#000000'
                ),
                'C' => array(
                    'content' => $center_content,
                    'font-size' => 10,
                    'font-style' => 'B',
                    'font-family' => 'serif',
                    'color' => '#000000'
                ),
                'R' => array(
                    'content' => $right_content,
                    'font-size' => 10,
                    'font-style' => 'B',
                    'font-family' => 'serif',
                    'color' => '#000000'
                ),
                'line' => 1,
            ),
            'even' => array(
                'L' => array(
                    'content' => $left_content,
                    'font-size' => 10,
                    'font-style' => 'B',
                    'font-family' => 'serif',
                    'color' => '#000000'
                ),
                'C' => array(
                    'content' => $center_content,
                    'font-size' => 10,
                    'font-style' => 'B',
                    'font-family' => 'serif',
                    'color' => '#000000'
                ),
                'R' => array(
                    'content' => $right_content,
                    'font-size' => 10,
                    'font-style' => 'B',
                    'font-family' => 'serif',
                    'color' => '#000000'
                ),
                'line' => 1,
            ),
        );
        // defines footer for Odd and Even Pages - placed at Outer margin see http://mpdf1.com/manual/index.php?tid=151&searchstring=setfooter
        $this->pdf->SetFooter($footer);
    }

    /**
     * Sets the PDF header
     * @param array $course_data
     */
    public function set_header($course_data)
    {
        $this->pdf->defaultheaderfontsize   = 10;   // in pts
        $this->pdf->defaultheaderfontstyle  = 'BI';   // blank, B, I, or BI
        $this->pdf->defaultheaderline       = 1;    // 1 to include line below header/above footer

        if (!empty($course_data['code'])) {
            $teacher_list = CourseManager::get_teacher_list_from_course_code($course_data['code']);

            $teachers = '';
            if (!empty($teacher_list)) {
                foreach ($teacher_list as $teacher) {
                    $teachers[]= $teacher['firstname'].' '.$teacher['lastname'];
                }
                if (count($teachers) > 1) {
                    $teachers = get_lang('Teachers').': '.implode(', ', $teachers);
                } else {
                    $teachers = get_lang('Teacher').': '.implode('', $teachers);
                }

                // Do not show the teacher list see BT#4080 only the current teacher name
                $user_info = api_get_user_info();
                $teachers = $user_info['complete_name'];
            }

            $left_content    = '';
            $center_content  = '';
            $right_content   = $teachers;

            $header = array(
                'odd' => array(
                    'L' => array(
                        'content' => $left_content,
                        'font-size' => 10,
                        'font-style' => 'B',
                        'font-family' => 'serif',
                        'color'=>'#000000'
                    ),
                    'C' => array(
                        'content' => $center_content,
                        'font-size' => 10,
                        'font-style' => 'B',
                        'font-family' => 'serif',
                        'color'=>'#000000'
                    ),
                    'R' => array(
                        'content' => $right_content,
                        'font-size' => 10,
                        'font-style' => 'B',
                        'font-family' => 'serif',
                        'color'=>'#000000'
                    ),
                    'line' => 1,
                ),
                'even' => array(
                    'L' => array(
                        'content' => $left_content,
                        'font-size' => 10,
                        'font-style' => 'B',
                        'font-family' => 'serif',
                        'color'=>'#000000'
                    ),
                    'C' => array(
                        'content' => $center_content,
                        'font-size' => 10,
                        'font-style' => 'B',
                        'font-family' => 'serif',
                        'color'=>'#000000'
                    ),
                    'R' => array(
                        'content' => $right_content,
                        'font-size' => 10,
                        'font-style' => 'B',
                        'font-family' => 'serif',
                        'color'=>'#000000'
                    ),
                    'line' => 1,
                ),
            );
            $this->pdf->SetHeader($header);// ('{DATE j-m-Y}|{PAGENO}/{nb}|'.$title);
        }
    }

    /**
     * @param array $header html content
     */
    public function set_custom_header($header)
    {
        $this->custom_header = $header;
    }

    /**
     * @param array $footer html content
     */
    public function set_custom_footer($footer)
    {
        $this->custom_footer = $footer;
    }

    /**
     * Pre-formats a PDF to the right size and, if not stated otherwise, with
     * header, footer and watermark (if any)
     * @param array $course_data General course information (to fill headers)
     * @param bool $complete Whether we want headers, footers and watermark or not
     */
    public function format_pdf($course_data, $complete = true)
    {
        if ($complete === false) {
            error_log('Asked with no decoration');
        }
        $course_code = null;
        if (!empty($course_data)) {
            $course_code = $course_data['code'];
        }

        /*$pdf->SetAuthor('Documents Chamilo');
        $pdf->SetTitle('title');
        $pdf->SetSubject('Exported from Chamilo Documents');
        $pdf->SetKeywords('Chamilo Documents');
        */
        // TODO: To be read from the html document.
        $this->pdf->directionality = api_get_text_direction();
        $this->pdf->useOnlyCoreFonts = true;
        // Use different Odd/Even headers and footers and mirror margins
        $this->pdf->mirrorMargins       = 1;

        // Add decoration only if not stated otherwise
        if ($complete) {
            // Adding watermark
            if (api_get_setting('pdf_export_watermark_enable') == 'true') {
                $watermark_file = self::get_watermark($course_code);

                if ($watermark_file) {
                    //http://mpdf1.com/manual/index.php?tid=269&searchstring=watermark
                    $this->pdf->SetWatermarkImage($watermark_file);
                    $this->pdf->showWatermarkImage = true;
                } else {
                    $watermark_file = self::get_watermark(null);
                    if ($watermark_file) {
                        $this->pdf->SetWatermarkImage($watermark_file);
                        $this->pdf->showWatermarkImage = true;
                    }
                }
                if ($course_code) {
                    $watermark_text = api_get_course_setting('pdf_export_watermark_text');
                    if (empty($watermark_text)) {
                        $watermark_text = api_get_setting('pdf_export_watermark_text');
                    }
                } else {
                    $watermark_text = api_get_setting('pdf_export_watermark_text');
                }
                if (!empty($watermark_text)) {
                    $this->pdf->SetWatermarkText(strcode2utf($watermark_text),0.1);
                    $this->pdf->showWatermarkText = true;
                }
            }

            if (empty($this->custom_header)) {
                self::set_header($course_data);
            } else {
                $this->pdf->SetHTMLHeader($this->custom_header,'E');
                $this->pdf->SetHTMLHeader($this->custom_header,'O');
            }

            if (empty($this->custom_footer)) {
                self::set_footer();
            } else {
                $this->pdf->SetHTMLFooter($this->custom_footer);
            }
        }
    }
}
