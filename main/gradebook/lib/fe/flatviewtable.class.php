<?php
/* For licensing terms, see /license.txt */

set_time_limit(0);

use CpChart\Cache as pCache;
use CpChart\Data as pData;
use CpChart\Image as pImage;

/**
 * Class FlatViewTable
 * Table to display flat view (all evaluations and links for all students).
 *
 * @author Stijn Konings
 * @author Bert Steppé  - (refactored, optimised)
 * @author Julio Montoya Armas - Gradebook Graphics
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
     * @param array    $users
     * @param array    $evals
     * @param array    $links
     * @param bool     $limit_enabled
     * @param int      $offset
     * @param null     $addparams
     * @param Category $mainCourseCategory
     */
    public function __construct(
        $selectcat,
        $users = [],
        $evals = [],
        $links = [],
        $limit_enabled = false,
        $offset = 0,
        $addparams = null,
        $mainCourseCategory = null
    ) {
        parent::__construct(
            'flatviewlist',
            null,
            null,
            api_is_western_name_order() ? 1 : 0
        );

        $this->selectcat = $selectcat;
        $this->datagen = new FlatViewDataGenerator(
            $users,
            $evals,
            $links,
            ['only_subcat' => $this->selectcat->get_id()],
            $mainCourseCategory
        );

        $this->limit_enabled = $limit_enabled;
        $this->offset = $offset;
        if (isset($addparams)) {
            $this->set_additional_parameters($addparams ?: []);
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
     * Display gradebook graphs.
     */
    public function display_graph_by_resource()
    {
        $headerName = $this->datagen->get_header_names();
        $total_users = $this->datagen->get_total_users_count();
        $customdisplays = ScoreDisplay::instance()->get_custom_score_display_settings();

        if (empty($customdisplays)) {
            echo get_lang('ToViewGraphScoreRuleMustBeEnabled');

            return '';
        }

        $user_results = $this->datagen->get_data_to_graph2(false);

        if (empty($user_results) || empty($total_users)) {
            echo get_lang('NoResults');

            return '';
        }

        // Removing first name
        array_shift($headerName);
        // Removing last name
        array_shift($headerName);
        // Removing username
        array_shift($headerName);

        $pre_result = [];
        foreach ($user_results as $result) {
            for ($i = 0; $i < count($headerName); $i++) {
                if (isset($result[$i + 1])) {
                    $pre_result[$i + 3][] = $result[$i + 1];
                }
            }
        }

        $i = 0;
        $resource_list = [];
        $pre_result2 = [];
        foreach ($pre_result as $key => $res_array) {
            rsort($res_array);
            $pre_result2[] = $res_array;
        }

        //@todo when a display custom does not exist the order of the color does not match
        //filling all the answer that are not responded with 0
        rsort($customdisplays);

        if ($total_users > 0) {
            foreach ($pre_result2 as $key => $res_array) {
                $key_list = [];
                foreach ($res_array as $user_result) {
                    $userResult = isset($user_result[1]) ? $user_result[1] : null;
                    if (!isset($resource_list[$key][$userResult])) {
                        $resource_list[$key][$userResult] = 0;
                    }
                    $resource_list[$key][$userResult]++;
                    $key_list[] = $userResult;
                }

                foreach ($customdisplays as $display) {
                    if (!in_array($display['display'], $key_list)) {
                        $resource_list[$key][$display['display']] = 0;
                    }
                }
                $i++;
            }
        }

        //fixing $resource_list
        $max = 0;
        $new_list = [];
        foreach ($resource_list as $key => $value) {
            $new_value = [];
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
        // Cache definition
        $cachePath = api_get_path(SYS_ARCHIVE_PATH);
        foreach ($resource_list as $key => $resource) {
            // Reverse array, otherwise we get highest values first
            $resource = array_reverse($resource, true);

            $dataSet = new pData();
            $dataSet->addPoints($resource, 'Serie');
            $dataSet->addPoints(array_keys($resource), 'Labels');
            $header = $headerName[$i - 1];
            if (is_array($header) && isset($header['header'])) {
                $header = $header['header'];
            }
            $header = strip_tags(api_html_entity_decode($header));
            $dataSet->setSerieDescription('Labels', $header);
            $dataSet->setAbscissa('Labels');
            $dataSet->setAbscissaName(get_lang('GradebookSkillsRanking'));
            $dataSet->setAxisName(0, get_lang('Students'));
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
            $myCache = new pCache(['CacheFolder' => substr($cachePath, 0, strlen($cachePath) - 1)]);
            $chartHash = $myCache->getHash($dataSet);
            if ($myCache->isInCache($chartHash)) {
                $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
                $myCache->saveFromCache($chartHash, $imgPath);
                $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
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
                /* Write the chart title */
                $myPicture->drawText(
                    250,
                    30,
                    $header,
                    [
                        'FontSize' => 12,
                        'Align' => TEXT_ALIGN_BOTTOMMIDDLE,
                    ]
                );

                /* Define the chart area */
                $myPicture->setGraphArea(50, 40, $widthSize - 20, $heightSize - 50);

                /* Draw the scale */
                $scaleSettings = [
                    'GridR' => 200,
                    'GridG' => 200,
                    'GridB' => 200,
                    'DrawSubTicks' => true,
                    'CycleBackground' => true,
                    'Mode' => SCALE_MODE_START0,
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

                /* Render the picture (choose the best way) */
                $myCache->writeToCache($chartHash, $myPicture);
                $imgPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
                $myCache->saveFromCache($chartHash, $imgPath);
                $imgPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;
            }
            echo '<img src="'.$imgPath.'" >';
            if ($i % 2 == 0 && $i != 0) {
                echo '<br /><br />';
            } else {
                echo '&nbsp;&nbsp;&nbsp;';
            }
            $i++;
        }
    }

    /**
     * Function used by SortableTable to get total number of items in the table.
     */
    public function get_total_number_of_items()
    {
        return $this->datagen->get_total_users_count();
    }

    /**
     * Function used by SortableTable to generate the data to display.
     */
    public function get_table_data(
        $from = 1,
        $per_page = null,
        $column = null,
        $direction = null,
        $sort = null
    ) {
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
            $header .= '<table
                    style="width: 100%; text-align: right; margin-left: auto; margin-right: auto;"
                    border="0" cellpadding="2"><tbody>
                    <tr>';
            // previous X
            $header .= '<td style="width:100%;">';
            if ($this->offset >= GRADEBOOK_ITEM_LIMIT) {
                $header .= '<a
                    href="'.api_get_self().'?selectcat='.(int) $_GET['selectcat'].'&offset='.(($this->offset) - GRADEBOOK_ITEM_LIMIT)
                    .(isset($_GET['search']) ? '&search='.Security::remove_XSS($_GET['search']) : '').'">'
                    .Display::return_icon(
                        'action_prev.png',
                        get_lang('PreviousPage'),
                        [],
                        ICON_SIZE_MEDIUM
                    )
                    .'</a>';
            } else {
                $header .= Display::return_icon(
                    'action_prev_na.png',
                    get_lang('PreviousPage'),
                    [],
                    ICON_SIZE_MEDIUM
                );
            }
            $header .= ' ';
            // next X
            $calcnext = (($this->offset + (2 * GRADEBOOK_ITEM_LIMIT)) > $totalitems) ?
                ($totalitems - (GRADEBOOK_ITEM_LIMIT + $this->offset)) : GRADEBOOK_ITEM_LIMIT;

            if ($calcnext > 0) {
                $header .= '<a href="'.api_get_self()
                    .'?selectcat='.Security::remove_XSS($_GET['selectcat'])
                    .'&offset='.($this->offset + GRADEBOOK_ITEM_LIMIT)
                    .(isset($_GET['search']) ? '&search='.Security::remove_XSS($_GET['search']) : '').'">'
                    .Display::return_icon('action_next.png', get_lang('NextPage'), [], ICON_SIZE_MEDIUM)
                    .'</a>';
            } else {
                $header .= Display::return_icon(
                    'action_next_na.png',
                    get_lang('NextPage'),
                    [],
                    ICON_SIZE_MEDIUM
                );
            }
            $header .= '</td>';
            $header .= '</tbody></table>';
            echo $header;
        }

        // retrieve sorting type
        if ($is_western_name_order) {
            $users_sorting = ($this->column == 0 ? FlatViewDataGenerator::FVDG_SORT_FIRSTNAME : FlatViewDataGenerator::FVDG_SORT_LASTNAME);
        } else {
            $users_sorting = ($this->column == 0 ? FlatViewDataGenerator::FVDG_SORT_LASTNAME : FlatViewDataGenerator::FVDG_SORT_FIRSTNAME);
        }

        if ('DESC' === $this->direction) {
            $users_sorting |= FlatViewDataGenerator::FVDG_SORT_DESC;
        } else {
            $users_sorting |= FlatViewDataGenerator::FVDG_SORT_ASC;
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

                if (count($headerData['items']) > 0) {
                    foreach ($headerData['items'] as $item) {
                        $firstHeader[] = '<span class="text-center">'.$item.'</span>';
                    }
                } else {
                    $firstHeader[] = '&mdash;';
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

        $table_data = [];

        if (!empty($firstHeader)) {
            $table_data[] = $firstHeader;
        }

        $columnOffset = empty($this->datagen->params['show_official_code']) ? 0 : 1;

        foreach ($data_array as $user_row) {
            $user_id = $user_row[0];
            unset($user_row[0]);
            $userInfo = api_get_user_info($user_id);
            if ($is_western_name_order) {
                $user_row[1 + $columnOffset] = $this->build_name_link(
                    $user_id,
                    $userInfo['firstname']
                );
                $user_row[2 + $columnOffset] = $this->build_name_link(
                    $user_id,
                    $userInfo['lastname']
                );
            } else {
                $user_row[1 + $columnOffset] = $this->build_name_link(
                    $user_id,
                    $userInfo['lastname']
                );
                $user_row[2 + $columnOffset] = $this->build_name_link(
                    $user_id,
                    $userInfo['firstname']
                );
            }
            $user_row = array_values($user_row);

            $table_data[] = $user_row;
        }

        return $table_data;
    }

    /**
     * @param $userId
     * @param $name
     *
     * @return string
     */
    private function build_name_link($userId, $name)
    {
        return '<a
            href="user_stats.php?userid='.$userId.'&selectcat='.$this->selectcat->get_id().'&'.api_get_cidreq().'">'.
            $name.'</a>';
    }
}
