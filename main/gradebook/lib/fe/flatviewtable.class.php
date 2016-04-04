<?php
/* For licensing terms, see /license.txt */

set_time_limit(0);

use CpChart\Classes\pCache as pCache;
use CpChart\Classes\pData as pData;
use CpChart\Classes\pImage as pImage;

/**
 * Class FlatViewTable
 * Table to display flat view (all evaluations and links for all students)
 * @author Stijn Konings
 * @author Bert Steppé  - (refactored, optimised)
 * @author Julio Montoya Armas - Gradebook Graphics
 *
 * @package chamilo.gradebook
 */
class FlatViewTable extends SortableTable
{
    public $datagen;
    private $selectcat;
    private $limit_enabled;
    private $offset;
    private $mainCourseCategory;

    /**
     * @param Category $selectcat
     * @param array $users
     * @param array $evals
     * @param array $links
     * @param bool $limit_enabled
     * @param int $offset
     * @param null $addparams
     * @param Category $mainCourseCategory
     */
    public function __construct(
        $selectcat,
        $users = array(),
        $evals = array(),
        $links = array(),
        $limit_enabled = false,
        $offset = 0,
        $addparams = null,
        $mainCourseCategory = null
    ) {
        parent :: __construct('flatviewlist', null, null, api_is_western_name_order() ? 1 : 0);

        $this->selectcat = $selectcat;

        $this->datagen = new FlatViewDataGenerator(
            $users,
            $evals,
            $links,
            array('only_subcat' => $this->selectcat->get_id()),
            $mainCourseCategory
        );

        $this->limit_enabled = $limit_enabled;
        $this->offset = $offset;
        if (isset($addparams)) {
            $this->set_additional_parameters($addparams);
        }

        // step 2: generate rows: students
        $this->datagen->category = $this->selectcat;
        $this->mainCourseCategory = $mainCourseCategory;
    }

    /**
     * @param bool $value
     */
    public function setLimitEnabled($value)
    {
        $this->limit_enabled = (bool) $value;
    }

    /**
     * @return Category
     */
    public function getMainCourseCategory()
    {
        return $this->mainCourseCategory;
    }

    /**
     * Display gradebook graphs
     */
    public function display_graph_by_resource()
    {
        $headerName = $this->datagen->get_header_names();
        $total_users = $this->datagen->get_total_users_count();

        $displayscore = ScoreDisplay::instance();
        $customdisplays = $displayscore->get_custom_score_display_settings();

        if (empty($customdisplays)) {
            echo get_lang('ToViewGraphScoreRuleMustBeEnabled');
            return '';
        }

        $user_results = $this->datagen->get_data_to_graph2(false);

        //if (empty($this->datagen->get_total_items_count()) || empty($total_users)) {
        if (empty($user_results) || empty($total_users)) {

            echo get_lang('NoResults');
            return '';
        }

        // Removing first name
        array_shift($headerName);
        // Removing last name
        array_shift($headerName);

        $pre_result = $new_result = array();
        foreach ($user_results as $result) {
            for ($i = 0; $i < count($headerName); $i++) {
                if (isset($result[$i + 1])) {
                    $pre_result[$i + 3][] = $result[$i + 1];
                }
            }
        }

        $i = 0;
        $show_draw = false;
        $resource_list = array();
        $pre_result2 = array();

        foreach ($pre_result as $key => $res_array) {
            rsort($res_array);
            $pre_result2[] = $res_array;
        }

        //@todo when a display custom does not exist the order of the color does not match
        //filling all the answer that are not responded with 0
        rsort($customdisplays);

        if ($total_users > 0) {
            foreach ($pre_result2 as $key => $res_array) {
                $key_list = array();
                foreach ($res_array as $user_result) {
                    $userResult = isset($user_result[1]) ? $user_result[1] : null;
                    if (!isset($resource_list[$key][$userResult])) {
                        $resource_list[$key][$userResult] = 0;
                    }
                    $resource_list[$key][$userResult] += 1;
                    $key_list[] = $userResult;
                }

                foreach ($customdisplays as $display) {
                    if (!in_array($display['display'], $key_list))
                        $resource_list[$key][$display['display']] = 0;
                }
                $i++;
            }
        }

        //fixing $resource_list
        $max = 0;
        $new_list = array();
        foreach ($resource_list as $key => $value) {
            $new_value = array();

            foreach ($customdisplays as $item) {
                if ($value[$item['display']] > $max) {
                    $max = $value[$item['display']];
                }
                $new_value[$item['display']] = strip_tags($value[$item['display']]);
            }
            $new_list[] = $new_value;
        }
        $resource_list = $new_list;

        $i = 1;

        foreach ($resource_list as $key => $resource) {
            // Reverse array, otherwise we get highest values first
            $resource = array_reverse($resource, true);

            $dataSet = new pData();
            $dataSet->addPoints($resource, 'Serie');
            $dataSet->addPoints(array_keys($resource), 'Labels');
            $dataSet->setSerieDescription('Labels', strip_tags($headerName[$i - 1]));
            $dataSet->setAbscissa('Labels');
            $dataSet->setAbscissaName(get_lang('GradebookSkillsRanking'));
            $dataSet->setAxisName(0, get_lang('Students'));
            $palette = array(
                '0' => array('R' => 186, 'G' => 206, 'B' => 151, 'Alpha' => 100),
                '1' => array('R' => 210, 'G' => 148, 'B' => 147, 'Alpha' => 100),
                '2' => array('R' => 148, 'G' => 170, 'B' => 208, 'Alpha' => 100),
                '3' => array('R' => 221, 'G' => 133, 'B' => 61, 'Alpha' => 100),
                '4' => array('R' => 65, 'G' => 153, 'B' => 176, 'Alpha' => 100),
                '5' => array('R' => 114, 'G' => 88, 'B' => 144, 'Alpha' => 100),
                '6' => array('R' => 138, 'G' => 166, 'B' => 78, 'Alpha' => 100),
                '7' => array('R' => 171, 'G' => 70, 'B' => 67, 'Alpha' => 100),
                '8' => array('R' => 69, 'G' => 115, 'B' => 168, 'Alpha' => 100),
            );
            // Cache definition
            $cachePath = api_get_path(SYS_ARCHIVE_PATH);
            $myCache = new pCache(array('CacheFolder' => substr($cachePath, 0, strlen($cachePath) - 1)));
            $chartHash = $myCache->getHash($dataSet);
            if ($myCache->isInCache($chartHash)) {
                $imgPath = api_get_path(SYS_ARCHIVE_PATH) . $chartHash;
                $myCache->saveFromCache($chartHash, $imgPath);
                $imgPath = api_get_path(WEB_ARCHIVE_PATH) . $chartHash;
            } else {
                /* Create the pChart object */
                $widthSize = 480;
                $heightSize = 250;

                $myPicture = new pImage($widthSize, $heightSize, $dataSet);

                /* Turn of Antialiasing */
                $myPicture->Antialias = false;

                /* Add a border to the picture */
                $myPicture->drawRectangle(
                    0,
                    0,
                    $widthSize - 1,
                    $heightSize - 1,
                    array(
                        'R' => 0,
                        'G' => 0,
                        'B' => 0
                    )
                );

                /* Set the default font */
                $myPicture->setFontProperties(
                    array(
                        'FontName' => api_get_path(SYS_FONTS_PATH) . 'opensans/OpenSans-Regular.ttf',
                        'FontSize' => 10
                    )
                );

                /* Write the chart title */
                $myPicture->drawText(
                    250,
                    30,
                    strip_tags($headerName[$i - 1]),
                    array(
                        'FontSize' => 12,
                        'Align' => TEXT_ALIGN_BOTTOMMIDDLE
                    )
                );

                /* Define the chart area */
                $myPicture->setGraphArea(50, 40, $widthSize - 20, $heightSize - 50);

                /* Draw the scale */
                $scaleSettings = array(
                    'GridR' => 200,
                    'GridG' => 200,
                    'GridB' => 200,
                    'DrawSubTicks' => true,
                    'CycleBackground' => true,
                    'Mode' => SCALE_MODE_START0
                );
                $myPicture->drawScale($scaleSettings);

                /* Turn on shadow computing */
                $myPicture->setShadow(
                    true,
                    array(
                        'X' => 1,
                        'Y' => 1,
                        'R' => 0,
                        'G' => 0,
                        'B' => 0,
                        'Alpha' => 10
                    )
                );

                /* Draw the chart */
                $myPicture->setShadow(
                    true,
                    array(
                        'X' => 1,
                        'Y' => 1,
                        'R' => 0,
                        'G' => 0,
                        'B' => 0,
                        'Alpha' => 10
                    )
                );
                $settings = array(
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
                );
                $myPicture->drawBarChart($settings);

                /* Render the picture (choose the best way) */

                $myCache->writeToCache($chartHash, $myPicture);
                $imgPath = api_get_path(SYS_ARCHIVE_PATH) . $chartHash;
                $myCache->saveFromCache($chartHash, $imgPath);
                $imgPath = api_get_path(WEB_ARCHIVE_PATH) . $chartHash;
            }
            echo '<img src="' . $imgPath . '" >';
            if ($i % 2 == 0 && $i != 0) {
                echo '<br /><br />';
            } else {
                echo '&nbsp;&nbsp;&nbsp;';
            }
            $i++;
        }
    }

    /**
     * Function used by SortableTable to get total number of items in the table
     */
    public function get_total_number_of_items()
    {
        return $this->datagen->get_total_users_count();
    }

    /**
     * Function used by SortableTable to generate the data to display
     */
    public function get_table_data($from = 1, $per_page = null, $column = null, $direction = null, $sort = null)
    {
        $is_western_name_order = api_is_western_name_order();

        // create page navigation if needed
        $totalitems = $this->datagen->get_total_items_count();

        if ($this->limit_enabled && $totalitems > GRADEBOOK_ITEM_LIMIT) {
            $selectlimit = GRADEBOOK_ITEM_LIMIT;
        } else {
            $selectlimit = $totalitems;
        }

        $header = null;
        if ($this->limit_enabled && $totalitems > GRADEBOOK_ITEM_LIMIT) {
            $header .= '<table style="width: 100%; text-align: right; margin-left: auto; margin-right: auto;" border="0" cellpadding="2">'
                . '<tbody>'
                . '<tr>';

            // previous X
            $header .= '<td style="width:100%;">';
            if ($this->offset >= GRADEBOOK_ITEM_LIMIT) {
                $header .= '<a href="' . api_get_self()
                    . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
                    . '&offset=' . (($this->offset) - GRADEBOOK_ITEM_LIMIT)
                    . (isset($_GET['search']) ? '&search=' . Security::remove_XSS($_GET['search']) : '') . '">'
                    . Display::return_icon('action_prev.png', get_lang('PreviousPage'), array(), 32)
                    . '</a>';
            } else {
                $header .= Display::return_icon('action_prev_na.png', get_lang('PreviousPage'), array(), 32);
            }
            $header .= ' ';
            // next X
            $calcnext = (($this->offset + (2 * GRADEBOOK_ITEM_LIMIT)) > $totalitems) ?
                ($totalitems - (GRADEBOOK_ITEM_LIMIT + $this->offset)) : GRADEBOOK_ITEM_LIMIT;

            if ($calcnext > 0) {
                $header .= '<a href="' . api_get_self()
                    . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
                    . '&offset=' . ($this->offset + GRADEBOOK_ITEM_LIMIT)
                    . (isset($_GET['search']) ? '&search=' . Security::remove_XSS($_GET['search']) : '') . '">'
                    . Display::return_icon('action_next.png', get_lang('NextPage'), array(), 32)
                    . '</a>';
            } else {
                $header .= Display::return_icon('action_next_na.png', get_lang('NextPage'), array(), 32);
            }
            $header .= '</td>';
            $header .= '</tbody></table>';
            echo $header;
        }

        // retrieve sorting type

        if ($is_western_name_order) {
            $users_sorting = ($this->column == 0 ? FlatViewDataGenerator :: FVDG_SORT_FIRSTNAME : FlatViewDataGenerator :: FVDG_SORT_LASTNAME);
        } else {
            $users_sorting = ($this->column == 0 ? FlatViewDataGenerator :: FVDG_SORT_LASTNAME : FlatViewDataGenerator :: FVDG_SORT_FIRSTNAME);
        }

        if ($this->direction == 'DESC') {
            $users_sorting |= FlatViewDataGenerator :: FVDG_SORT_DESC;
        } else {
            $users_sorting |= FlatViewDataGenerator :: FVDG_SORT_ASC;
        }

        // step 1: generate columns: evaluations and links
        $header_names = $this->datagen->get_header_names($this->offset, $selectlimit);

        $userRowSpan = false;
        foreach ($header_names as $item) {
            if (is_array($item)) {
                $userRowSpan = true;
                break;
            }
        }

        $thAttributes = '';
        if ($userRowSpan) {
            $thAttributes = 'rowspan=2';
        }

        $this->set_header(0, $header_names[0], true, $thAttributes);
        $this->set_header(1, $header_names[1], true, $thAttributes);

        $column = 2;
        $firstHeader = [];
        while ($column < count($header_names)) {
            $headerData = $header_names[$column];

            if (is_array($headerData)) {
                $countItems = count($headerData['items']);

                $this->set_header(
                    $column,
                    $headerData['header'],
                    false,
                    'colspan="'.$countItems.'"'
                );

                foreach ($headerData['items'] as $item) {
                    $firstHeader[] = '<center>'.$item.'</center>';
                }
            } else {
                $this->set_header($column, $headerData, false, $thAttributes);
            }
            $column++;
        }

        $data_array = $this->datagen->get_data(
            $users_sorting,
            $from,
            $this->per_page,
            $this->offset,
            $selectlimit
        );

        $table_data = array();

        if (!empty($firstHeader)) {
            $table_data[] = $firstHeader;
        }

        foreach ($data_array as $user_row) {
            $user_id = $user_row[0];
            unset($user_row[0]);
            $userInfo = api_get_user_info($user_id);
            if ($is_western_name_order) {
                $user_row[1] = $this->build_name_link($user_id, $userInfo['firstname']);
                $user_row[2] = $this->build_name_link($user_id, $userInfo['lastname']);
            } else {
                $user_row[1] = $this->build_name_link($user_id, $userInfo['lastname']);
                $user_row[2] = $this->build_name_link($user_id, $userInfo['firstname']);
            }
            $user_row = array_values($user_row);

            $table_data[] = $user_row;
        }
        return $table_data;
    }

    /**
     * @param $user_id
     * @param $name
     *
     * @return string
     */
    private function build_name_link($user_id, $name)
    {
        return '<a href="user_stats.php?userid=' . $user_id . '&selectcat=' . $this->selectcat->get_id() . '&'.api_get_cidreq().'">' . $name . '</a>';
    }
}
