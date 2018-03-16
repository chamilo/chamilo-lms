<?php
/**
 * This file is part of teacher graph block plugin for dashboard,
 * it should be required inside dashboard controller for showing it into dashboard interface from plattform.
 *
 * @package chamilo.dashboard
 *
 * @author Christian Fasanando
 */

/**
 * required files for getting data.
 */
use CpChart\Cache as pCache;
use CpChart\Data as pData;
use CpChart\Image as pImage;

/**
 * This class is used like controller for teacher graph block plugin,
 * the class name must be registered inside path.info file (e.g: controller = "BlockTeacherGraph"), so dashboard controller will be instantiate it.
 *
 * @package chamilo.dashboard
 */
class BlockTeacherGraph extends Block
{
    private $user_id;
    private $teachers;
    private $path;
    private $permission = [DRH];

    /**
     * Controller.
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->path = 'block_teacher_graph';
        if ($this->is_block_visible_for_user($user_id)) {
            $this->teachers = UserManager::get_users_followed_by_drh($user_id, COURSEMANAGER);
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
     * This method return content html containing information about teachers and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller.
     *
     * @return array column and content html
     */
    public function get_block()
    {
        global $charset;
        $column = 1;
        $data = [];
        $teacher_information_graph = $this->get_teachers_information_graph();
        $html = '
                <div class="panel panel-default" id="intro">
                    <div class="panel-heading">'.get_lang('TeachersInformationsGraph').'
                        <div class="pull-right"><a class="btn btn-danger btn-xs"  onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">
                        <em class="fa fa-times"></em>
                        </a></div>
                    </div>
                    <div class="panel-body" align="center">
                        <div style="padding:10px;"><strong>'.get_lang('TimeSpentOnThePlatformLastWeekByDay').'</strong></div>
                        '.$teacher_information_graph.'
                    </div>
                </div>
                ';

        $data['column'] = $column;
        $data['content_html'] = $html;

        return $data;
    }

    /**
     * This method return a content html, it's used inside get_block method for showing it inside dashboard interface.
     *
     * @return string content html
     */
    public function get_teachers_information_graph()
    {
        $teachers = $this->teachers;
        $graph = '';
        $user_ids = array_keys($teachers);
        $a_last_week = get_last_week();

        if (is_array($user_ids) && count($user_ids) > 0) {
            $dataSet = new pData();
            foreach ($user_ids as $user_id) {
                $teacher_info = api_get_user_info($user_id);
                $username = $teacher_info['username'];
                $time_by_days = [];
                foreach ($a_last_week as $day) {
                    // day is received as y-m-d 12:00:00
                    $start_date = api_get_utc_datetime($day);
                    $end_date = api_get_utc_datetime($day + (3600 * 24 - 1));

                    $time_on_platform_by_day = Tracking::get_time_spent_on_the_platform(
                        $user_id,
                        'custom',
                        $start_date,
                        $end_date
                    );
                    $hours = floor($time_on_platform_by_day / 3600);
                    $min = floor(($time_on_platform_by_day - ($hours * 3600)) / 60);
                    $time_by_days[] = $min;
                }
                $dataSet->addPoints($time_by_days, $username);
            }

            $last_week = date('Y-m-d', $a_last_week[0]).' '.get_lang('To').' '.date('Y-m-d', $a_last_week[6]);
            $days_on_week = [];
            foreach ($a_last_week as $weekday) {
                $days_on_week[] = date('d/m', $weekday);
            }

            $dataSet->addPoints($days_on_week, 'Days');
            $dataSet->setAbscissaName($last_week);
            $dataSet->setAxisName(0, get_lang('Minutes'));
            $dataSet->setAbscissa('Days');
            $dataSet->loadPalette(api_get_path(SYS_CODE_PATH).'palettes/pchart/default.color', true);

            // Cache definition
            $cachePath = api_get_path(SYS_ARCHIVE_PATH);
            $myCache = new pCache(['CacheFolder' => substr($cachePath, 0, strlen($cachePath) - 1)]);
            $chartHash = $myCache->getHash($dataSet);
            if ($myCache->isInCache($chartHash)) {
                $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
                $myCache->saveFromCache($chartHash, $imgPath);
                $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
            } else {
                /* Create the pChart object */
                $widthSize = 440;
                $heightSize = 350;
                $angle = 50;
                $myPicture = new pImage($widthSize, $heightSize, $dataSet);

                /* Turn of Antialiasing */
                $myPicture->Antialias = false;

                /* Add a border to the picture */
                $myPicture->drawRectangle(0, 0, $widthSize - 1, $heightSize - 1, ['R' => 0, 'G' => 0, 'B' => 0]);

                /* Set the default font */
                $myPicture->setFontProperties(['FontName' => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf', 'FontSize' => 10]);

                /* Do NOT Write the chart title */

                /* Define the chart area */
                $myPicture->setGraphArea(40, 40, $widthSize - 20, $heightSize - 80);

                /* Draw the scale */
                $scaleSettings = [
                    'GridR' => 200,
                    'GridG' => 200,
                    'GridB' => 200,
                    'DrawSubTicks' => true,
                    'CycleBackground' => true,
                    'Mode' => SCALE_MODE_ADDALL_START0,
                    'LabelRotation' => $angle,
                ];

                $myPicture->drawScale($scaleSettings);

                /* Turn on shadow computing */
                $myPicture->setShadow(true, ['X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10]);

                /* Draw the chart */
                $myPicture->setShadow(true, ['X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10]);
                $settings = [
                    'DisplayValues' => true,
                    'DisplayR' => 0,
                    'DisplayG' => 0,
                    'DisplayB' => 0,
                ];
                $myPicture->drawFilledSplineChart($settings);
                $myPicture->drawLegend(40, 20, ['Mode' => LEGEND_HORIZONTAL]);

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
     * Get number of teachers.
     *
     * @return int
     */
    public function get_number_of_teachers()
    {
        return count($this->teachers);
    }
}
