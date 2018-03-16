<?php
/* For licensing terms, see /license.txt */

use CpChart\Cache as pCache;
use CpChart\Data as pData;
use CpChart\Image as pImage;

/**
 * Class BlockEvaluationGraph
 * This class is used like controller for this evaluations graph block plugin,
 * the class name must be registered inside path.info file
 * (e.g: controller = "BlockEvaluationGraph"),
 * so dashboard controller will be instantiate it.
 *
 * This file is part of evaluation graph block plugin for dashboard,
 * it should be required inside dashboard controller for showing it
 * into dashboard interface from platform
 *
 * @package chamilo.dashboard
 *
 * @author Christian Fasanando
 */
class BlockEvaluationGraph extends Block
{
    private $user_id;
    private $courses;
    private $sessions;
    private $path;
    private $permission = [DRH, SESSIONADMIN];

    /**
     * Constructor.
     */
    public function __construct($user_id)
    {
        $this->path = 'block_evaluation_graph';
        $this->user_id = $user_id;
        $this->bg_width = 450;
        $this->bg_height = 350;
        if ($this->is_block_visible_for_user($user_id)) {
            if (!api_is_session_admin()) {
                $this->courses = CourseManager::get_courses_followed_by_drh($user_id);
            }
            $this->sessions = SessionManager::get_sessions_followed_by_drh($user_id);
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
     * This method return content html containing
     * information about sessions and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller.
     *
     * @return array column and content html
     */
    public function get_block()
    {
        global $charset;
        $column = 1;
        $data = [];
        $evaluations_base_courses_graph = $this->get_evaluations_base_courses_graph();
        $evaluations_courses_in_sessions_graph = $this->get_evaluations_courses_in_sessions_graph();

        $html = '<div class="panel panel-default" id="intro">
                    <div class="panel-heading">
                        '.get_lang('EvaluationsGraph').'
                        <div class="pull-right"><a class="btn btn-danger btn-xs" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">
                        <em class="fa fa-times"></em>
                        </a></div>
                    </div>
                    <div class="panel-body">';
        if (empty($evaluations_base_courses_graph) && empty($evaluations_courses_in_sessions_graph)) {
            $html .= '<p>'.api_convert_encoding(get_lang('GraphicNotAvailable'), 'UTF-8').'</p>';
        } else {
            // display evaluations base courses graph
            if (!empty($evaluations_base_courses_graph)) {
                foreach ($evaluations_base_courses_graph as $course_code => $img_html) {
                    $html .= '<div><strong>'.$course_code.'</strong></div>';
                    $html .= $img_html;
                }
            }
            // display evaluations base courses graph
            if (!empty($evaluations_courses_in_sessions_graph)) {
                foreach ($evaluations_courses_in_sessions_graph as $session_id => $courses) {
                    $session_name = api_get_session_name($session_id);
                    $html .= '<div><strong>'.$session_name.':'.get_lang('Evaluations').'</strong></div>';
                    foreach ($courses as $course_code => $img_html) {
                        $html .= '<div><strong>'.$course_code.'</strong></div>';
                        $html .= $img_html;
                    }
                }
            }
        }
        $html .= '</div>
                 </div>';

        $data['column'] = $column;
        $data['content_html'] = $html;

        return $data;
    }

    /**
     * This method return a graph containing informations about evaluations
     * inside base courses, it's used inside get_block method for showing
     * it inside dashboard interface.
     *
     * @return string img html
     */
    public function get_evaluations_base_courses_graph()
    {
        $graphs = [];
        if (!empty($this->courses)) {
            $courses_code = array_keys($this->courses);
            foreach ($courses_code as $course_code) {
                $cats = Category::load(
                    null,
                    null,
                    $course_code,
                    null,
                    null,
                    null,
                    false
                );

                if (isset($cats) && isset($cats[0])) {
                    $alleval = $cats[0]->get_evaluations(null, true, $course_code);
                    $alllinks = $cats[0]->get_links(null, true);
                    $users = GradebookUtils::get_all_users($alleval, $alllinks);
                    $datagen = new FlatViewDataGenerator($users, $alleval, $alllinks);
                    $evaluation_sumary = $datagen->getEvaluationSummaryResults();
                    if (!empty($evaluation_sumary)) {
                        $items = array_keys($evaluation_sumary);
                        $max = $min = $avg = [];
                        foreach ($evaluation_sumary as $evaluation) {
                            $max[] = $evaluation['max'];
                            $min[] = !empty($evaluation['min']) ? $evaluation['min'] : 0;
                            $avg[] = $evaluation['avg'];
                        }
                        // Dataset definition
                        $dataSet = new pData();
                        $dataSet->addPoints($min, 'Serie3');
                        $dataSet->addPoints($avg, 'Serie2');
                        $dataSet->addPoints($max, 'Serie1');
                        $dataSet->addPoints($items, 'Labels');

                        $dataSet->setSerieDescription('Serie1', get_lang('Max'));
                        $dataSet->setSerieDescription('Serie2', get_lang('Avg'));
                        $dataSet->setSerieDescription('Serie3', get_lang('Min'));
                        $dataSet->setAbscissa('Labels');

                        $dataSet->setAbscissaName(get_lang('EvaluationName'));

                        $dataSet->normalize(100, '%');

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
                            $widthSize = $this->bg_width;
                            $heightSize = $this->bg_height;
                            $fontSize = 8;
                            $angle = 50;

                            $myPicture = new pImage($widthSize, $heightSize, $dataSet);

                            /* Turn of Antialiasing */
                            $myPicture->Antialias = false;

                            /* Add a border to the picture */
                            $myPicture->drawRectangle(
                                0,
                                0,
                                $widthSize - 1,
                                $heightSize - 1,
                                [
                                    'R' => 0,
                                    'G' => 0,
                                    'B' => 0,
                                ]
                            );

                            /* Set the default font */
                            $myPicture->setFontProperties(
                                [
                                    'FontName' => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf',
                                    'FontSize' => 10,
                                ]
                            );

                            /* Do NOT Write the chart title */

                            /* Define the chart area */
                            $myPicture->setGraphArea(
                                50,
                                30,
                                $widthSize - 20,
                                $heightSize - 100
                            );

                            /* Draw the scale */
                            $scaleSettings = [
                                'GridR' => 200,
                                'GridG' => 200,
                                'GridB' => 200,
                                'DrawSubTicks' => true,
                                'CycleBackground' => true,
                                'Mode' => SCALE_MODE_MANUAL,
                                'ManualScale' => [
                                    '0' => [
                                        'Min' => 0,
                                        'Max' => 100,
                                    ],
                                ],
                                'LabelRotation' => $angle,
                            ];
                            $myPicture->drawScale($scaleSettings);

                            /* Turn on shadow computing */
                            $myPicture->setShadow(
                                true,
                                [
                                    'X' => 1,
                                    'Y' => 1,
                                    'R' => 0,
                                    'G' => 0,
                                    'B' => 0,
                                    'Alpha' => 10,
                                ]
                            );

                            /* Draw the chart */
                            $myPicture->setShadow(
                                true,
                                [
                                    'X' => 1,
                                    'Y' => 1,
                                    'R' => 0,
                                    'G' => 0,
                                    'B' => 0,
                                    'Alpha' => 10,
                                ]
                            );
                            $settings = [
                                'DisplayValues' => true,
                                'DisplaySize' => $fontSize,
                                'DisplayR' => 0,
                                'DisplayG' => 0,
                                'DisplayB' => 0,
                                'DisplayOrientation' => ORIENTATION_HORIZONTAL,
                                'Gradient' => false,
                                'Surrounding' => 30,
                                'InnerSurrounding' => 25,
                            ];
                            $myPicture->drawStackedBarChart($settings);

                            $legendSettings = [
                                'Mode' => LEGEND_HORIZONTAL,
                                'Style' => LEGEND_NOBORDER,
                            ];
                            $myPicture->drawLegend($widthSize / 2, 15, $legendSettings);

                            /* Write and save into cache */

                            $myCache->writeToCache($chartHash, $myPicture);
                            $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
                            $myCache->saveFromCache($chartHash, $imgPath);
                            $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
                        }
                        if (!empty($imgPath)) {
                            $courses_graph[$course_code] = '<img src="'.$imgPath.'">';
                        }
                    }
                }
            } // end for
        }

        return $graphs;
    }

    /**
     * This method return a graph containing information about evaluations
     * inside courses in sessions, it's used inside get_block method for
     * showing it inside dashboard interface.
     *
     * @return string img html
     */
    public function get_evaluations_courses_in_sessions_graph()
    {
        $graphs = [];
        if (!empty($this->sessions)) {
            $session_ids = array_keys($this->sessions);
            foreach ($session_ids as $session_id) {
                $courses_code = array_keys(Tracking::get_courses_list_from_session($session_id));
                $courses_graph = [];
                foreach ($courses_code as $course_code) {
                    $cats = Category::load(null, null, $course_code, null, null, $session_id);
                    if (isset($cats) && isset($cats[0])) {
                        $alleval = $cats[0]->get_evaluations(null, true, $course_code);
                        $alllinks = $cats[0]->get_links(null, true);
                        $users = GradebookUtils::get_all_users($alleval, $alllinks);
                        $datagen = new FlatViewDataGenerator($users, $alleval, $alllinks);
                        $evaluation_sumary = $datagen->getEvaluationSummaryResults();
                        if (!empty($evaluation_sumary)) {
                            $items = array_keys($evaluation_sumary);
                            $max = $min = $avg = [];
                            foreach ($evaluation_sumary as $evaluation) {
                                $max[] = $evaluation['max'];
                                $min[] = $evaluation['min'];
                                $avg[] = $evaluation['avg'];
                            }
                            // Dataset definition
                            $dataSet = new pData();
                            $dataSet->addPoints($min, 'Serie3');
                            $dataSet->addPoints($avg, 'Serie2');
                            $dataSet->addPoints($max, 'Serie1');
                            $dataSet->addPoints($items, 'Labels');

                            $dataSet->setSerieDescription('Serie1', get_lang('Max'));
                            $dataSet->setSerieDescription('Serie2', get_lang('Avg'));
                            $dataSet->setSerieDescription('Serie3', get_lang('Min'));
                            $dataSet->setAbscissa('Labels');
                            $dataSet->setAbscissaName(get_lang('EvaluationName'));
                            $dataSet->normalize(100, '%');
                            $dataSet->loadPalette(api_get_path(SYS_CODE_PATH).'palettes/pchart/default.color', true);

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
                                /* Create the pChart object */
                                $widthSize = $this->bg_width;
                                $heightSize = $this->bg_height;
                                $fontSize = 8;
                                $angle = 50;

                                $myPicture = new pImage($widthSize, $heightSize, $dataSet);

                                /* Turn of Antialiasing */
                                $myPicture->Antialias = false;

                                /* Add a border to the picture */
                                $myPicture->drawRectangle(
                                    0,
                                    0,
                                    $widthSize - 1,
                                    $heightSize - 1,
                                    [
                                        'R' => 0,
                                        'G' => 0,
                                        'B' => 0,
                                    ]
                                );

                                /* Set the default font */
                                $myPicture->setFontProperties(
                                    [
                                        'FontName' => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf',
                                        'FontSize' => 10,
                                    ]
                                );

                                /* Do NOT Write the chart title */

                                /* Define the chart area */
                                $myPicture->setGraphArea(50, 30, $widthSize - 20, $heightSize - 100);

                                /* Draw the scale */
                                $scaleSettings = [
                                    'GridR' => 200,
                                    'GridG' => 200,
                                    'GridB' => 200,
                                    'DrawSubTicks' => true,
                                    'CycleBackground' => true,
                                    'Mode' => SCALE_MODE_MANUAL,
                                    'ManualScale' => [
                                        '0' => [
                                            'Min' => 0,
                                            'Max' => 100,
                                        ],
                                    ],
                                    'LabelRotation' => $angle,
                                ];
                                $myPicture->drawScale($scaleSettings);

                                /* Turn on shadow computing */
                                $myPicture->setShadow(
                                    true,
                                    [
                                        'X' => 1,
                                        'Y' => 1,
                                        'R' => 0,
                                        'G' => 0,
                                        'B' => 0,
                                        'Alpha' => 10,
                                    ]
                                );

                                /* Draw the chart */
                                $myPicture->setShadow(
                                    true,
                                    [
                                        'X' => 1,
                                        'Y' => 1,
                                        'R' => 0,
                                        'G' => 0,
                                        'B' => 0,
                                        'Alpha' => 10,
                                    ]
                                );
                                $settings = [
                                    'DisplayValues' => true,
                                    'DisplaySize' => $fontSize,
                                    'DisplayR' => 0,
                                    'DisplayG' => 0,
                                    'DisplayB' => 0,
                                    'DisplayOrientation' => ORIENTATION_HORIZONTAL,
                                    'Gradient' => false,
                                    'Surrounding' => 30,
                                    'InnerSurrounding' => 25,
                                ];
                                $myPicture->drawStackedBarChart($settings);

                                $legendSettings = [
                                    'Mode' => LEGEND_HORIZONTAL,
                                    'Style' => LEGEND_NOBORDER,
                                ];
                                $myPicture->drawLegend($widthSize / 2, 15, $legendSettings);

                                /* Write and save into cache */
                                $myCache->writeToCache($chartHash, $myPicture);
                                $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
                                $myCache->saveFromCache($chartHash, $imgPath);
                                $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
                            }
                            if (!empty($imgPath)) {
                                $courses_graph[$course_code] = '<img src="'.$imgPath.'">';
                            }
                        }
                    }
                }
                if (!empty($courses_graph)) {
                    $graphs[$session_id] = $courses_graph;
                }
            }
        }

        return $graphs;
    }
}
