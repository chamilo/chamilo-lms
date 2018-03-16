<?php
/* For licensing terms, see /license.txt */

/**
 * This file is part of student graph block plugin for dashboard,
 * it should be required inside dashboard controller for showing it into dashboard interface from plattform.
 *
 * @package chamilo.dashboard
 *
 * @author Christian Fasanando
 * @author Julio Montoya <gugli100@gmail.com>
 */
use CpChart\Cache as pCache;
use CpChart\Data as pData;
use CpChart\Image as pImage;

/**
 * This class is used like controller for student graph block plugin,
 * the class name must be registered inside path.info file
 * (e.g: controller = "BlockStudentGraph"), so dashboard controller will be instantiate it.
 *
 * @package chamilo.dashboard
 */
class BlockStudentGraph extends Block
{
    private $user_id;
    private $students;
    private $path;
    private $permission = [DRH];

    /**
     * Constructor.
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->path = 'block_student_graph';
        if ($this->is_block_visible_for_user($user_id)) {
            /*if (api_is_platform_admin()) {
                $this->students = UserManager::get_user_list(array('status' => STUDENT));
            } else if (api_is_drh()) {*/
            $this->students = UserManager::get_users_followed_by_drh($user_id, STUDENT);
            //}
        }
    }

    /**
     * This method check if a user is allowed to see the block inside dashboard interface.
     *
     * @param int        User id
     *
     * @return bool Is block visible for user
     */
    public function is_block_visible_for_user($user_id)
    {
        $user_info = api_get_user_info($user_id);
        $user_status = $user_info['status'];
        $is_block_visible_for_user = false;
        if (UserManager::is_admin($user_id) || in_array($user_status, $this->permission)) {
            $is_block_visible_for_user = true;
        }

        return $is_block_visible_for_user;
    }

    /**
     * This method return content html containing information about students
     * and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for being used from dashboard controller.
     *
     * @return array column and content html
     */
    public function get_block()
    {
        global $charset;
        $column = 1;
        $data = [];
        $students_attendance_graph = $this->get_students_attendance_graph();

        $html = '<div class="panel panel-default" id="intro">
                    <div class="panel-heading">
                        '.get_lang('StudentsInformationsGraph').'
                        <div class="pull-right"><a class="btn btn-danger btn-xs" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">
                        <em class="fa fa-times"></em>
                        </a></div>
                    </div>
                    <div class="panel-body" align="center">
                        <div style="padding:10px;"><strong>'.get_lang('AttendancesFaults').'</strong></div>
                        '.$students_attendance_graph.'
                    </div>
                </div>';
        $data['column'] = $column;
        $data['content_html'] = $html;

        return $data;
    }

    /**
     * This method return a graph containing information about students evaluation,
     * it's used inside get_block method for showing it inside dashboard interface.
     *
     * @return string img html
     */
    public function get_students_attendance_graph()
    {
        $students = $this->students;
        $attendance = new Attendance();

        // get data
        $attendances_faults_avg = [];
        if (is_array($students) && count($students) > 0) {
            foreach ($students as $student) {
                $student_id = $student['user_id'];
                //$student_info = api_get_user_info($student_id);
                // get average of faults in attendances by student
                $results_faults_avg = $attendance->get_faults_average_inside_courses($student_id);

                if (!empty($results_faults_avg)) {
                    $attendances_faults_avg[$student['lastname']] = $results_faults_avg['porcent'];
                } else {
                    $attendances_faults_avg[$student['lastname']] = 0;
                }
            }
        }

        arsort($attendances_faults_avg);
        $usernames = array_keys($attendances_faults_avg);

        $faults = [];
        foreach ($usernames as $username) {
            $faults[] = $attendances_faults_avg[$username];
        }

        $graph = '';
        $img_file = '';
        if (is_array($usernames) && count($usernames) > 0) {
            // Defining data
            $dataSet = new pData();
            $dataSet->addPoints($faults, 'Serie1');
            $dataSet->addPoints($usernames, 'Labels');
            $dataSet->setSerieDescription('Series1', get_lang('Average'));
            $dataSet->setSerieDescription('Labels', get_lang('User'));
            $dataSet->setAbscissa('Labels');
            $dataSet->setAbscissaName(get_lang('User'));
            $dataSet->setAxisName(0, get_lang('Attendance'));
            $palette = [
                '0' => ['R' => 186, 'G' => 206, 'B' => 151, 'Alpha' => 100],
                '1' => ['R' => 210, 'G' => 148, 'B' => 147, 'Alpha' => 100],
                '2' => ['R' => 148, 'G' => 170, 'B' => 208, 'Alpha' => 100],
                '3' => ['R' => 221, 'G' => 133, 'B' => 61, 'Alpha' => 100],
                '4' => ['R' => 65, 'G' => 153, 'B' => 176, 'Alpha' => 100],
                '5' => ['R' => 114, 'G' => 88, 'B' => 144, 'Alpha' => 100],
                '6' => ['R' => 138, 'G' => 166, 'B' => 78, 'Alpha' => 100],
                '7' => ['R' => 171, 'G' => 70, 'B' => 67, 'Alpha' => 100],
                '8' => ['R' => 69, 'G' => 115, 'B' => 168, 'Alpha' => 100],
            ];
            // Cache definition
            $cachePath = api_get_path(SYS_ARCHIVE_PATH);
            $myCache = new pCache(
                [
                    'CacheFolder' => substr(
                        $cachePath,
                        0,
                        strlen($cachePath) - 1
                    ),
                ]
            );
            $chartHash = $myCache->getHash($dataSet);
            if ($myCache->isInCache($chartHash)) {
                $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
                $myCache->saveFromCache($chartHash, $imgPath);
                $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
            } else {
                $maxCounts = max(count($usernames), count($faults));
                if ($maxCounts < 5) {
                    $heightSize = 200;
                } else {
                    $heightSize = $maxCounts * 40;
                }

                /* Create the pChart object */
                $widthSize = 480;
                $angle = 40;

                $myPicture = new pImage($widthSize, $heightSize, $dataSet);

                /* Turn of Antialiasing */
                $myPicture->Antialias = false;

                /* Add a border to the picture */
                $myPicture->drawRectangle(0, 0, $widthSize - 1, $heightSize - 1, ['R' => 0, 'G' => 0, 'B' => 0]);

                /* Set the default font */
                $myPicture->setFontProperties(
                    [
                        'FontName' => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf',
                        'FontSize' => 10,
                    ]
                );

                /* Do NOT Write the chart title */

                /* Define the chart area */
                $myPicture->setGraphArea(80, 40, $widthSize - 20, $heightSize - 40);

                /* Draw the scale */
                $scaleSettings = [
                    'GridR' => 200,
                    'GridG' => 200,
                    'GridB' => 200,
                    'DrawSubTicks' => true,
                    'CycleBackground' => true,
                    'Mode' => SCALE_MODE_ADDALL_START0,
                    'Pos' => SCALE_POS_TOPBOTTOM,
                    'DrawXLines' => false,
                    'LabelRotation' => $angle,
                ];

                $myPicture->drawScale($scaleSettings);

                /* Turn on shadow computing */
                $myPicture->setShadow(true, ['X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10]);

                /* Draw the chart */
                $myPicture->setShadow(true, ['X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10]);
                $settings = [
                    'OverrideColors' => $palette,
                    'Gradient' => false,
                    'GradientMode' => GRADIENT_SIMPLE,
                    'DisplayPos' => LABEL_POS_TOP,
                    'DisplayValues' => true,
                    'DisplayR' => 0,
                    'DisplayG' => 0,
                    'DisplayB' => 0,
                    'DisplayShadow' => true,
                    'Surrounding' => 10,
                ];
                $myPicture->drawBarChart($settings);

                /* Write and save into cache */
                $myCache->writeToCache($chartHash, $myPicture);
                $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
                $myCache->saveFromCache($chartHash, $imgPath);
                $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
            }
            $graph = '<img src="'.$imgPath.'" >';
        } else {
            $graph = '<p>'.api_convert_encoding(get_lang('GraphicNotAvailable'), 'UTF-8').'</p>';
        }

        return $graph;
    }

    /**
     * Get number of students.
     *
     * @return int
     */
    public function get_number_of_students()
    {
        return count($this->students);
    }
}
