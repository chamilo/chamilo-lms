<?php
/* See license terms in /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Utils\UtfString;

/**
 * Class PDF.
 */
class PDF
{
    /** @var Mpdf */
    public $pdf;
    public $custom_header = [];
    public $custom_footer = [];
    public $params = [];
    public $template;

    /**
     * Creates the Mpdf object.
     *
     * @param string   $pageFormat  format A4 A4-L see
     *                              http://mpdf1.com/manual/index.php?tid=184&searchstring=format
     * @param string   $orientation orientation "P" = Portrait "L" = Landscape
     * @param array    $params
     * @param Template $template
     *
     * @throws MpdfException
     */
    public function __construct(
        $pageFormat = 'A4',
        $orientation = 'P',
        $params = [],
        $template = null
    ) {
        $this->template = $template;
        /* More info @ http://mpdf1.com/manual/index.php?tid=184&searchstring=Mpdf */
        if (!in_array($orientation, ['P', 'L'])) {
            $orientation = 'P';
        }
        //left, right, top, bottom, margin_header, margin footer

        $params['left'] = $params['left'] ?? 15;
        $params['right'] = $params['right'] ?? 15;
        $params['top'] = $params['top'] ?? 30;
        $params['bottom'] = $params['bottom'] ?? 30;
        $params['margin_footer'] = $params['margin_footer'] ?? 8;

        $this->params['filename'] = $params['filename'] ?? api_get_local_time();
        $this->params['pdf_title'] = $params['pdf_title'] ?? '';
        $this->params['pdf_description'] = $params['pdf_description'] ?? '';
        $this->params['course_info'] = $params['course_info'] ?? api_get_course_info();
        $this->params['session_info'] = $params['session_info'] ?? api_get_session_info(api_get_session_id());
        $this->params['course_code'] = $params['course_code'] ?? api_get_course_id();
        $this->params['add_signatures'] = $params['add_signatures'] ?? [];
        $this->params['show_real_course_teachers'] = $params['show_real_course_teachers'] ?? false;
        $this->params['student_info'] = $params['student_info'] ?? false;
        $this->params['show_grade_generated_date'] = $params['show_grade_generated_date'] ?? false;
        $this->params['show_teacher_as_myself'] = $params['show_teacher_as_myself'] ?? true;
        $localTime = api_get_local_time();
        $this->params['pdf_date'] = $params['pdf_date'] ?? api_format_date($localTime, DATE_TIME_FORMAT_LONG);
        $this->params['pdf_date_only'] = $params['pdf_date'] ?? api_format_date($localTime, DATE_FORMAT_LONG);

        $this->pdf = new Mpdf(
            [
                'mode' => 'UTF-8',
                'format' => $pageFormat,
                'default_font_size' => '',
                'default_font' => '',
                'margin_left' => $params['left'],
                'margin_right' => $params['right'],
                'margin_top' => $params['top'],
                'margin_bottom' => $params['bottom'],
                'margin_header' => 8,
                'margin_footer' => 8,
                'orientation' => $orientation,
                'tempDir' => api_get_path(SYS_ARCHIVE_PATH).'mpdf/',
            ]
        );

        $this->pdf->margin_footer = $params['margin_footer'];

        // Default value is 96 set in the mpdf library file config.php
        $value = api_get_configuration_value('pdf_img_dpi');
        if (!empty($value)) {
            $this->pdf->img_dpi = (int) $value;
        }
    }

    /**
     * Export the given HTML to PDF, using a global template.
     *
     * @uses \export/table_pdf.tpl
     *
     * @param string     $content
     * @param bool|false $saveToFile
     * @param bool|false $returnHtml
     * @param bool       $addDefaultCss (bootstrap/default/base.css)
     * @param array
     *
     * @throws MpdfException
     *
     * @return string|null
     */
    public function html_to_pdf_with_template(
        $content,
        $saveToFile = false,
        $returnHtml = false,
        $addDefaultCss = false,
        $extraRows = [],
        $outputMode = 'D',
        $fileToSave = null
    ) {
        if (empty($this->template)) {
            $tpl = new Template('', false, false, false, false, true, false);
        } else {
            $tpl = $this->template;
        }

        // Assignments
        $tpl->assign('pdf_content', $content);

        // Showing only the current teacher/admin instead the all teacher list name see BT#4080
        $teacher_list = null;
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
                $teacher_list = CourseManager::getTeacherListFromCourseCodeToString(
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
        $tpl->assign('pdf_date_only', $this->params['pdf_date_only']);
        $tpl->assign('pdf_teachers', $teacher_list);
        $tpl->assign('pdf_title', $this->params['pdf_title']);
        $tpl->assign('pdf_description', $this->params['pdf_description']);
        $tpl->assign('pdf_student_info', $this->params['student_info']);
        $tpl->assign('show_grade_generated_date', $this->params['show_grade_generated_date']);
        $tpl->assign('add_signatures', $this->params['add_signatures']);
        $tpl->assign('extra_rows', $extraRows);

        // Getting template
        $tableTemplate = $tpl->get_template('export/table_pdf.tpl');
        $html = $tpl->fetch($tableTemplate);
        $html = api_utf8_encode($html);

        if ($returnHtml) {
            return $html;
        }

        $css = api_get_print_css();

        self::content_to_pdf(
            $html,
            $css,
            $this->params['filename'],
            $this->params['course_code'],
            $outputMode,
            $saveToFile,
            $fileToSave,
            $returnHtml,
            $addDefaultCss
        );
    }

    /**
     * Converts HTML files to PDF.
     *
     * @param mixed  $html_file_array could be a html file path or an array
     *                                with paths example:
     *                                /var/www/myfile.html or array('/myfile.html','myotherfile.html') or
     *                                even an indexed array with both 'title' and 'path' indexes
     *                                for each element like
     *                                array(
     *                                0 => array('title'=>'Hello','path'=>'file.html'),
     *                                1 => array('title'=>'Bye','path'=>'file2.html')
     *                                );
     * @param string $pdf_name        pdf name
     * @param null   $course_code     (if you are using html that are located
     *                                in the document tool you must provide this)
     * @param bool   $print_title     add title
     * @param bool   $complete_style  show header and footer if true
     * @param bool   $addStyle
     * @param string $mainTitle
     * @param bool   $generateToFile  Optional. When it is TRUE, then the output file is move to app/cache
     *
     * @throws MpdfException
     *
     * @return false|null
     */
    public function html_to_pdf(
        $html_file_array,
        $pdf_name = '',
        $course_code = null,
        $print_title = false,
        $complete_style = true,
        $addStyle = true,
        $mainTitle = '',
        $generateToFile = false
    ) {
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
            $html_file_array = [$html_file_array];
        }

        if (!empty($course_code)) {
            $course_data = api_get_course_info($course_code);
        } else {
            $course_data = api_get_course_info();
        }

        // Clean styles and javascript document
        $clean_search = [
            '@<script[^>]*?>.*?</script>@si',
            '@<style[^>]*?>.*?</style>@si',
        ];

        // Formatting the pdf
        self::format_pdf($course_data, $complete_style);

        $counter = 1;
        foreach ($html_file_array as $file) {
            // Add a page break per file
            $page_break = '<pagebreak>';
            if ($counter == count($html_file_array)) {
                $page_break = '';
            }

            // if the array provided contained subarrays with 'title' entry,
            // then print the title in the PDF
            if (is_array($file) && isset($file['title'])) {
                $html_title = $file['title'];
                $file = $file['path'];
            } else {
                //we suppose we've only been sent a file path
                $html_title = basename($file);
            }

            $counter++;

            if (empty($file) && !empty($html_title)) {
                // this is a chapter, print title & skip the rest
                if ($counter === 2 && !empty($mainTitle)) {
                    $this->pdf->WriteHTML(
                        '<html><body><h2 style="text-align: center">'.$mainTitle.'</h2></body></html>'
                    );
                }
                if ($print_title) {
                    $this->pdf->WriteHTML(
                        '<html><body><h3>'.$html_title.'</h3></body></html>'.$page_break
                    );
                }
                continue;
            } else {
                if ($counter === 2 && !empty($mainTitle)) {
                    $this->pdf->WriteHTML(
                        '<html><body><h2 style="text-align: center">'.$mainTitle.'</h2></body></html>'
                    );
                }
            }

            if (!file_exists($file)) {
                continue;
            }

            if ($addStyle) {
                $basicStyles = [
                    api_get_path(SYS_PATH).'web/assets/bootstrap/dist/css/bootstrap.min.css',
                    api_get_path(SYS_PATH).'web/css/base.css',
                    api_get_path(SYS_PATH).'web/css/themes/'.api_get_visual_theme().'/default.css',
                    api_get_print_css(false),
                ];
                foreach ($basicStyles as $style) {
                    if (file_exists($style)) {
                        $cssContent = file_get_contents($style);
                        try {
                            @$this->pdf->WriteHTML($cssContent, 1);
                        } catch (MpdfException $e) {
                            error_log($e);
                        }
                    }
                }
            }

            // it's not a chapter but the file exists, print its title
            if ($print_title) {
                @$this->pdf->WriteHTML(
                    '<html><body><h3>'.$html_title.'</h3></body></html>'
                );
            }

            $file_info = pathinfo($file);
            $extension = $file_info['extension'];

            if (in_array($extension, ['html', 'htm'])) {
                $dirName = $file_info['dirname'];
                $filename = $file_info['basename'];
                $filename = str_replace('_', ' ', $filename);

                if ($extension === 'html') {
                    $filename = basename($filename, '.html');
                } elseif ($extension === 'htm') {
                    $filename = basename($filename, '.htm');
                }

                $document_html = @file_get_contents($file);
                $document_html = preg_replace($clean_search, '', $document_html);

                //absolute path for frames.css //TODO: necessary?
                $absolute_css_path = api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/frames.css';
                $document_html = str_replace('href="./css/frames.css"', $absolute_css_path, $document_html);

                if (!empty($course_data['path'])) {
                    $document_html = str_replace('../', '', $document_html);

                    // Fix app/upload links convert web to system paths
                    $document_html = str_replace(
                        api_get_path(WEB_UPLOAD_PATH),
                        api_get_path(SYS_UPLOAD_PATH),
                        $document_html
                    );
                }

                $document_html = self::fixImagesPaths($document_html, $course_data, $dirName);

                // The library Mpdf expects UTF-8 encoded input data.
                api_set_encoding_html($document_html, 'UTF-8');
                // TODO: Maybe it is better idea the title to be passed through
                $title = api_get_title_html($document_html, 'UTF-8', 'UTF-8');
                // $_GET[] too, as it is done with file name.
                // At the moment the title is retrieved from the html document itself.
                if (empty($title)) {
                    $title = $filename; // Here file name is expected to contain ASCII symbols only.
                }
                if (!empty($document_html)) {
                    @$this->pdf->WriteHTML($document_html.$page_break);
                }
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                // Images
                $image = Display::img($file);
                @$this->pdf->WriteHTML('<html><body>'.$image.'</body></html>'.$page_break);
            }
        }
        if (empty($pdf_name)) {
            $output_file = 'pdf_'.date('Y-m-d-his').'.pdf';
        } else {
            $pdf_name = api_replace_dangerous_char($pdf_name);
            $output_file = $pdf_name.'.pdf';
        }
        // F to save the pdf in a file
        if ($generateToFile) {
            @$this->pdf->Output(
                api_get_path(SYS_ARCHIVE_PATH).$output_file,
                'F'
            );
        } else {
            @$this->pdf->Output($output_file, 'D');
        }

        exit;
    }

    /**
     * Converts a html string to PDF.
     *
     * @param string $document_html  valid html
     * @param string $css            CSS content of a CSS file
     * @param string $pdf_name       pdf name
     * @param string $course_code    course code
     *                               (if you are using html that are located in the document tool you must provide this)
     * @param string $outputMode     the MPDF output mode can be:
     * @param bool   $saveInFile
     * @param string $fileToSave
     * @param bool   $returnHtml
     * @param bool   $addDefaultCss
     * @param bool   $completeHeader
     *
     * @throws MpdfException
     *
     * 'I' (print on standard output),
     * 'D' (download file) (this is the default value),
     * 'F' (save to local file) or
     * 'S' (return as a string)
     *
     * @return string Web path
     */
    public function content_to_pdf(
        $document_html,
        $css = '',
        $pdf_name = '',
        $course_code = null,
        $outputMode = 'D',
        $saveInFile = false,
        $fileToSave = null,
        $returnHtml = false,
        $addDefaultCss = false,
        $completeHeader = true
    ) {
        $urlAppend = api_get_configuration_value('url_append');

        if (empty($document_html)) {
            return false;
        }

        // clean styles and javascript document
        $clean_search = [
            '@<script[^>]*?>.*?</script>@si',
            '@<style[^>]*?>.*?</style>@siU',
        ];

        // Formatting the pdf
        $course_data = api_get_course_info($course_code);
        self::format_pdf($course_data, $completeHeader);
        $document_html = preg_replace($clean_search, '', $document_html);

        //absolute path for frames.css //TODO: necessary?
        $absolute_css_path = api_get_path(WEB_CSS_PATH).api_get_setting('stylesheets').'/frames.css';
        $document_html = str_replace('href="./css/frames.css"', 'href="'.$absolute_css_path.'"', $document_html);
        $document_html = str_replace('../../', '', $document_html);
        $document_html = str_replace('../', '', $document_html);
        $document_html = str_replace(
            (empty($urlAppend) ? '' : $urlAppend.'/').'courses/'.$course_code.'/document/',
            '',
            $document_html
        );

        if (!empty($course_data['path'])) {
            $document_path = api_get_path(SYS_COURSE_PATH).$course_data['path'].'/document/';

            $doc = new DOMDocument();
            @$doc->loadHTML($document_html);

            //Fixing only images @todo do the same thing with other elements
            $elements = $doc->getElementsByTagName('img');
            $protocol = api_get_protocol();
            $replaced = [];
            if (!empty($elements)) {
                foreach ($elements as $item) {
                    $old_src = $item->getAttribute('src');

                    if (in_array($old_src, $replaced)) {
                        continue;
                    }

                    if (strpos($old_src, $protocol) === false) {
                        if (strpos($old_src, '/main/default_course_document') === false) {
                            if (strpos($old_src, '/main/inc/lib/') === false &&
                                strpos($old_src, '/app/upload/') === false
                            ) {
                                $old_src_fixed = str_replace(
                                    api_get_path(REL_COURSE_PATH).$course_data['path'].'/document/',
                                    '',
                                    $old_src
                                );
                                $old_src_fixed = str_replace(
                                    'courses/'.$course_data['path'].'/document/',
                                    '',
                                    $old_src_fixed
                                );
                                $new_path = $document_path.$old_src_fixed;
                                $document_html = str_replace($old_src, $new_path, $document_html);
                                $replaced[] = $old_src;
                            }
                        }
                    }
                }
            }
        }

        // Use sys path to correct export images
        $document_html = str_replace(
            api_get_path(WEB_CODE_PATH).'img/',
            api_get_path(SYS_CODE_PATH).'img/',
            $document_html
        );

        $theme = api_get_visual_theme();
        $document_html = str_replace(
            api_get_path(WEB_CSS_PATH).'themes/'.$theme,
            api_get_path(SYS_PUBLIC_PATH).'css/themes/'.$theme,
            $document_html
        );

        $document_html = str_replace(api_get_path(WEB_UPLOAD_PATH), api_get_path(SYS_UPLOAD_PATH), $document_html);
        $document_html = str_replace(api_get_path(WEB_ARCHIVE_PATH), api_get_path(SYS_ARCHIVE_PATH), $document_html);

        // The library Mpdf expects UTF-8 encoded input data.
        api_set_encoding_html($document_html, 'UTF-8');
        // At the moment the title is retrieved from the html document itself.
        if ($returnHtml) {
            return "<style>$css</style>".$document_html;
        }

        if (!empty($css)) {
            try {
                @$this->pdf->WriteHTML($css, 1);
            } catch (MpdfException $e) {
                error_log($e);
            }
        }

        $cssBootstrap = file_get_contents(api_get_path(SYS_PATH).'web/assets/bootstrap/dist/css/bootstrap.min.css');
        if ($addDefaultCss) {
            $cssContent = api_get_print_css();
            try {
                @$this->pdf->WriteHTML($cssBootstrap, 1);
                @$this->pdf->WriteHTML($cssContent, 1);
            } catch (MpdfException $e) {
                error_log($e);
            }
        }

        try {
            @$this->pdf->WriteHTML($document_html);
        } catch (MpdfException $e) {
            error_log($e);
        }

        if (empty($pdf_name)) {
            $output_file = 'pdf_'.date('Y-m-d-his').'.pdf';
        } else {
            $pdf_name = api_replace_dangerous_char($pdf_name);
            $output_file = $pdf_name.'.pdf';
        }

        if ($outputMode === 'F') {
            $output_file = api_get_path(SYS_ARCHIVE_PATH).$output_file;
        }

        if ($saveInFile) {
            $fileToSave = !empty($fileToSave) ? $fileToSave : api_get_path(SYS_ARCHIVE_PATH).uniqid();
            try {
                @$this->pdf->Output(
                    $fileToSave,
                    $outputMode
                ); // F to save the pdf in a file
            } catch (MpdfException $e) {
                error_log($e);
            }
        } else {
            try {
                @$this->pdf->Output(
                    $output_file,
                    $outputMode
                ); // F to save the pdf in a file
            } catch (MpdfException $e) {
                error_log($e);
            }
        }

        if ($outputMode != 'F') {
            exit;
        }

        return $output_file;
    }

    /**
     * Gets the watermark from the platform or a course.
     *
     * @param   string  course code (optional)
     * @param   mixed   web path of the watermark image, false if there is nothing to return
     *
     * @return string
     */
    public static function get_watermark($course_code = null)
    {
        $web_path = false;
        $urlId = api_get_current_access_url_id();
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') === 'true') {
            $course_info = api_get_course_info($course_code);
            // course path
            $store_path = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/'.$urlId.'_pdf_watermark.png';
            if (file_exists($store_path)) {
                $web_path = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/'.$urlId.'_pdf_watermark.png';
            }
        } else {
            // course path
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document/images/'.$urlId.'_pdf_watermark.png';
            if (file_exists($store_path)) {
                $web_path = api_get_path(WEB_CODE_PATH).'default_course_document/images/'.$urlId.'_pdf_watermark.png';
            }
        }

        return $web_path;
    }

    /**
     * Deletes the watermark from the Platform or Course.
     *
     * @param string $course_code course code (optional)
     * @param   mixed   web path of the watermark image, false if there is nothing to return
     *
     * @return bool
     */
    public static function delete_watermark($course_code = null)
    {
        $urlId = api_get_current_access_url_id();
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $course_info = api_get_course_info($course_code);
            // course path
            $store_path = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/'.$urlId.'_pdf_watermark.png';
        } else {
            // course path
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document/images/'.$urlId.'_pdf_watermark.png';
        }
        if (file_exists($store_path)) {
            unlink($store_path);

            return true;
        }

        return false;
    }

    /**
     * Uploads the pdf watermark in the main/default_course_document directory or in the course directory.
     *
     * @param string $filename    filename
     * @param string $source_file path of the file
     * @param string $course_code
     *
     * @return mixed web path of the file if sucess, false otherwise
     */
    public static function upload_watermark($filename, $source_file, $course_code = null)
    {
        $urlId = api_get_current_access_url_id();
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $course_info = api_get_course_info($course_code);
            $store_path = api_get_path(SYS_COURSE_PATH).$course_info['path']; // course path
            $web_path = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/pdf_watermark.png';
        } else {
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document/images'; // course path
            $web_path = api_get_path(WEB_CODE_PATH).'default_course_document/images/'.$urlId.'_pdf_watermark.png';
        }
        $course_image = $store_path.'/'.$urlId.'_pdf_watermark.png';

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
     * Returns the default header.
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
     * Sets the PDF footer.
     */
    public function set_footer()
    {
        $this->pdf->defaultfooterfontsize = 12; // in pts
        $this->pdf->defaultfooterfontstyle = 'B'; // blank, B, I, or BI
        $this->pdf->defaultfooterline = 1; // 1 to include line below header/above footer

        $view = new Template('', false, false, false, true, false, false);
        $template = $view->get_template('export/pdf_footer.tpl');
        $footerHTML = $view->fetch($template);

        $this->pdf->SetHTMLFooter($footerHTML, 'E'); //Even pages
        $this->pdf->SetHTMLFooter($footerHTML, 'O'); //Odd pages
    }

    public function setCertificateFooter()
    {
        $this->pdf->defaultfooterfontsize = 12; // in pts
        $this->pdf->defaultfooterfontstyle = 'B'; // blank, B, I, or BI
        $this->pdf->defaultfooterline = 1; // 1 to include line below header/above footer

        $view = new Template('', false, false, false, true, false, false);
        $template = $view->get_template('export/pdf_certificate_footer.tpl');
        $footerHTML = $view->fetch($template);

        $this->pdf->SetHTMLFooter($footerHTML, 'E'); //Even pages
        $this->pdf->SetHTMLFooter($footerHTML, 'O'); //Odd pages
    }

    /**
     * Sets the PDF header.
     *
     * @param array $courseInfo
     */
    public function set_header($courseInfo)
    {
        $this->pdf->defaultheaderfontsize = 10; // in pts
        $this->pdf->defaultheaderfontstyle = 'BI'; // blank, B, I, or BI
        $this->pdf->defaultheaderline = 1; // 1 to include line below header/above footer

        $userId = api_get_user_id();
        if (!empty($courseInfo['code'])) {
            $teacher_list = CourseManager::get_teacher_list_from_course_code($courseInfo['code']);

            $teachers = '';
            if (!empty($teacher_list)) {
                foreach ($teacher_list as $teacher) {
                    if ($teacher['user_id'] != $userId) {
                        continue;
                    }

                    // Do not show the teacher list see BT#4080 only the current teacher name
                    $teachers = api_get_person_name($teacher['firstname'], $teacher['lastname']);
                }
            }

            $organization = null;

            // try getting the course logo
            if (api_get_configuration_value('mail_header_from_custom_course_logo') == true) {
                $organization = CourseManager::getCourseEmailPicture($courseInfo, []);
            }
            // only show platform logo in mail if no course photo available
            if (empty($organization)) {
                $organization = ChamiloApi::getPlatformLogo('', [], false, true);
            }

            // Use custom logo image.
            $pdfLogo = api_get_setting('pdf_logo_header');
            if ($pdfLogo === 'true') {
                $visualTheme = api_get_visual_theme();
                $img = api_get_path(SYS_CSS_PATH).'themes/'.$visualTheme.'/images/pdf_logo_header.png';
                if (file_exists($img)) {
                    $organization = "<img src='$img'>";
                }
            }

            $view = new Template('', false, false, false, true, false, false);
            $view->assign('teacher_name', $teachers);
            $view->assign('organization', $organization);
            $template = $view->get_template('export/pdf_header.tpl');
            $headerHTML = $view->fetch($template);

            $this->pdf->SetHTMLHeader($headerHTML, 'E');
            $this->pdf->SetHTMLHeader($headerHTML, 'O');
        }
    }

    /**
     * @param string $header html content
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
     * header, footer and watermark (if any).
     *
     * @param array $courseInfo General course information (to fill headers)
     * @param bool  $complete   Whether we want headers, footers and watermark or not
     */
    public function format_pdf($courseInfo, $complete = true)
    {
        $courseCode = null;
        if (!empty($courseInfo)) {
            $courseCode = $courseInfo['code'];
        }

        /*$pdf->SetAuthor('Documents Chamilo');
        $pdf->SetTitle('title');
        $pdf->SetSubject('Exported from Chamilo Documents');
        $pdf->SetKeywords('Chamilo Documents');
        */
        // TODO: To be read from the html document.
        $this->pdf->directionality = api_get_text_direction();
        // Use different Odd/Even headers and footers and mirror margins
        $this->pdf->mirrorMargins = 1;

        // Add decoration only if not stated otherwise
        if ($complete) {
            // Adding watermark
            if (api_get_setting('pdf_export_watermark_enable') === 'true') {
                $watermark_file = self::get_watermark($courseCode);
                if ($watermark_file) {
                    //http://mpdf1.com/manual/index.php?tid=269&searchstring=watermark
                    $this->pdf->SetWatermarkImage($watermark_file);
                    $this->pdf->showWatermarkImage = true;
                } else {
                    $watermark_file = self::get_watermark();

                    if ($watermark_file) {
                        $this->pdf->SetWatermarkImage($watermark_file);
                        $this->pdf->showWatermarkImage = true;
                    }
                }

                $watermark_text = api_get_setting('pdf_export_watermark_text');
                if ($courseCode && 'true' === api_get_setting('pdf_export_watermark_by_course')) {
                    $courseWaterMark = api_get_course_setting('pdf_export_watermark_text');
                    if (!empty($courseWaterMark) && -1 != $courseWaterMark) {
                        $watermark_text = $courseWaterMark;
                    }
                }

                if (!empty($watermark_text)) {
                    $this->pdf->SetWatermarkText(
                        UtfString::strcode2utf($watermark_text),
                        0.1
                    );
                    $this->pdf->showWatermarkText = true;
                }
            }

            if (empty($this->custom_header)) {
                self::set_header($courseInfo);
            } else {
                $this->pdf->SetHTMLHeader($this->custom_header, 'E');
                $this->pdf->SetHTMLHeader($this->custom_header, 'O');
            }

            if (empty($this->custom_footer)) {
                self::set_footer();
            } else {
                $this->pdf->SetHTMLFooter($this->custom_footer);
            }
        }
    }

    /**
     * Generate a PDF file from $html in SYS_APP_PATH.
     *
     * @param string $html     PDF content
     * @param string $fileName File name
     * @param string $dest     Optional. Directory to move file
     *
     * @return string The PDF path
     */
    public function exportFromHtmlToFile($html, $fileName, $dest = null)
    {
        $this->template = $this->template ?: new Template('', false, false, false, false, false, false);

        $pdfPath = self::content_to_pdf(
            $html,
            api_get_print_css(),
            $fileName,
            $this->params['course_code'],
            'F'
        );

        if (!$dest) {
            return $pdfPath;
        }

        move($pdfPath, $dest);

        return $dest.basename($pdfPath);
    }

    /**
     * Create a PDF and save it into the documents area.
     *
     * @param string $htmlContent HTML Content
     * @param string $fileName    The file name
     * @param int    $courseId    The course ID
     * @param int    $sessionId   Optional. The session ID
     */
    public function exportFromHtmlToDocumentsArea(
        $htmlContent,
        $fileName,
        $courseId,
        $sessionId = 0
    ) {
        $userId = api_get_user_id();
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseDirectory = api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/document/';

        $docPath = $this->exportFromHtmlToFile(
            $htmlContent,
            $fileName,
            $courseDirectory
        );

        $docId = add_document(
            $courseInfo,
            str_replace($courseDirectory, '/', $docPath),
            'file',
            filesize($docPath),
            $fileName,
            null,
            false,
            true,
            null,
            $sessionId,
            $userId
        );

        api_item_property_update(
            $courseInfo,
            TOOL_DOCUMENT,
            $docId,
            'DocumentAdded',
            $userId
        );

        Display::addFlash(Display::return_message(get_lang('ItemAdded')));
    }

    /**
     * @param string $theme
     * @param bool   $fullPage
     *
     * @throws MpdfException
     */
    public function setBackground($theme, $fullPage = false)
    {
        $themeName = empty($theme) ? api_get_visual_theme() : $theme;
        $themeDir = Template::getThemeDir($themeName);
        $customLetterhead = $themeDir.'images/letterhead.png';
        $urlPathLetterhead = api_get_path(SYS_CSS_PATH).$customLetterhead;

        if (file_exists($urlPathLetterhead)) {
            $urlWebLetterhead = 'url('.api_get_path(WEB_CSS_PATH).$customLetterhead.')';
        } else {
            $urlWebLetterhead = 'url('.api_get_path(WEB_CSS_PATH).'themes/chamilo/images/letterhead.png)';
        }

        if ($fullPage) {
            $this->pdf->SetDisplayMode('fullpage');
            $this->pdf->SetDefaultBodyCSS('background', $urlWebLetterhead);
            $this->pdf->SetDefaultBodyCSS('background-image-resize', '6');
        }
    }

    /**
     * Fix images source paths to allow export to pdf.
     */
    public static function fixImagesPaths(string $documentHtml, array $courseInfo, string $dirName = ''): string
    {
        $documentHtml = '<?xml encoding="utf-8" ?>'.$documentHtml;
        $doc = new DOMDocument();
        @$doc->loadHTML($documentHtml);

        $elements = $doc->getElementsByTagName('img');

        if (empty($elements)) {
            return $doc->saveHTML();
        }

        $protocol = api_get_protocol();
        $sysCodePath = api_get_path(SYS_CODE_PATH);
        $sysCoursePath = api_get_path(SYS_COURSE_PATH);
        $sysUploadPath = api_get_path(SYS_UPLOAD_PATH);

        $documentPath = $courseInfo ? $sysCoursePath.$courseInfo['path'].'/document/' : '';

        $notFoundImagePath = Display::return_icon(
            'closed-circle.png',
            get_lang('FileNotFound'),
            [],
            ICON_SIZE_TINY,
            false,
            true
        );

        /** @var DOMElement $element */
        foreach ($elements as $element) {
            $src = $element->getAttribute('src');
            $src = trim($src);

            if (api_filename_has_blacklisted_stream_wrapper($src)) {
                $element->setAttribute('src', $notFoundImagePath);
                continue;
            }

            if (strpos($src, $protocol) !== false) {
                continue;
            }

            // It's a reference to a file in the system, do not change it
            if (file_exists($src)) {
                continue;
            }

            if (strpos($src, '/main/default_course_document') === 0) {
                $element->setAttribute(
                    'src',
                    str_replace('/main/default_course_document', $sysCodePath.'default_course_document', $src)
                );
                continue;
            }

            if (strpos($src, '/main/img') === 0) {
                $element->setAttribute(
                    'src',
                    str_replace('/main/img/', $sysCodePath.'img/', $src)
                );
                continue;
            }

            if (strpos($src, '/app/upload/') === 0) {
                $element->setAttribute(
                    'src',
                    str_replace('/app/upload/', $sysUploadPath, $src)
                );
                continue;
            }

            if (empty($courseInfo)) {
                continue;
            }

            if (api_get_path(REL_PATH) != '/') {
                $oldSrcFixed = str_replace(
                    api_get_path(REL_PATH).'courses/'.$courseInfo['path'].'/document/',
                    '',
                    $src
                );

                // Try with the dirname if exists
                if ($oldSrcFixed == $src) {
                    if (file_exists($dirName.'/'.$src)) {
                        $documentPath = '';
                        $oldSrcFixed = $dirName.'/'.$src;
                    }
                }
            } else {
                if (strpos($src, 'courses/'.$courseInfo['path'].'/document/') !== false) {
                    $oldSrcFixed = str_replace('courses/'.$courseInfo['path'].'/document/', '', $src);
                } else {
                    // Try with the dirname if exists
                    $documentPath = '';
                    if (file_exists($dirName.'/'.$src)) {
                        $oldSrcFixed = $dirName.'/'.$src;
                    } else {
                        $oldSrcFixed = $src;
                    }
                }
            }

            $element->setAttribute('src', $documentPath.$oldSrcFixed);
        }

        return $doc->saveHTML();
    }
}
