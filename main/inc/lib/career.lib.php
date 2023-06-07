<?php

/* For licensing terms, see /license.txt */

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;

/**
 * Class Career.
 */
class Career extends Model
{
    public $table;
    public $columns = [
        'id',
        'name',
        'description',
        'status',
        'parent_id',
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_CAREER);
    }

    public function getCareerFromId($id)
    {
        if (api_get_configuration_value('use_career_external_id_as_identifier_in_diagrams')) {
            // Try with the external career id.
            $careerInfo = $this->getCareerFromExternalToInternal($id);
        } else {
            $careerInfo = $this->get($id);
        }

        return $careerInfo;
    }

    public function getCareerFromExternalToInternal($externalCareerId, $extraFieldVariable = 'external_career_id')
    {
        $careerExtraFieldValue = new ExtraFieldValue('career');
        $careerValue = $careerExtraFieldValue->get_item_id_from_field_variable_and_field_value(
            $extraFieldVariable,
            $externalCareerId
        );

        $careerInfo = [];
        if (isset($careerValue['item_id'])) {
            $careerInfo = $this->get($careerValue['item_id']);
        }

        return $careerInfo;
    }

    public function getCareerIdFromInternalToExternal($internalCareerId)
    {
        $careerExtraFieldValue = new ExtraFieldValue('career');
        $externalCareerValue = $careerExtraFieldValue->get_values_by_handler_and_field_variable(
            $internalCareerId,
            'external_career_id'
        );

        if (!empty($externalCareerValue) && isset($externalCareerValue['value'])) {
            return $externalCareerValue['value'];
        }

        return null;
    }

    /**
     * Get the count of elements.
     *
     * @return int
     */
    public function get_count()
    {
        $row = Database::select(
            'count(*) as count',
            $this->table,
            [],
            'first'
        );

        return $row['count'];
    }

    /**
     * @param array $where_conditions
     *
     * @return array
     */
    public function get_all($where_conditions = [])
    {
        return Database::select(
            '*',
            $this->table,
            ['where' => $where_conditions, 'order' => 'name ASC']
        );
    }

    /**
     * Order the careers by its hierarchy.
     *
     * @param $careers
     */
    public function orderCareersByHierarchy($careers, int $filterId = 0): array
    {
        $orderedCareers = [];
        $filterAux = [];
        foreach ($careers as &$career) {
            if (is_null($career['parent_id'])) {
                $orderedCareers[] = &$career;
            } else {
                $pid = $career['parent_id'];
                if (!isset($careers[$pid])) {
                    // Orphan child
                    break;
                } else {
                    if (!isset($careers[$pid]['children'])) {
                        $careers[$pid]['children'] = [];
                    }
                    $careers[$pid]['children'][] = &$career;
                }
            }
            if (!empty($filterId) && $career['id'] == $filterId) {
                $filterAux[0] = &$career;
            }
        }

        return !empty($filterId) ? $filterAux : $orderedCareers;
    }

    /**
     * Update all promotion status by career.
     *
     * @param int $career_id
     * @param int $status    (1 or 0)
     */
    public function update_all_promotion_status_by_career_id($career_id, $status)
    {
        $promotion = new Promotion();
        $promotion_list = $promotion->get_all_promotions_by_career_id($career_id);
        if (!empty($promotion_list)) {
            foreach ($promotion_list as $item) {
                $params['id'] = $item['id'];
                $params['status'] = $status;
                $promotion->update($params);
                $promotion->update_all_sessions_status_by_promotion_id($params['id'], $status);
            }
        }
    }

    /**
     * Returns HTML the title + grid.
     *
     * @return string
     */
    public function display()
    {
        $html = '<div class="actions" style="margin-bottom:20px">';
        $html .= '<a href="career_dashboard.php">'.
            Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
        if (api_is_platform_admin()) {
            $html .= '<a href="'.api_get_self().'?action=add">'.
                    Display::return_icon('new_career.png', get_lang('Add'), '', ICON_SIZE_MEDIUM).'</a>';
        }
        $html .= '</div>';
        $html .= Display::grid_html('careers');

        return $html;
    }

    /**
     * @return array
     */
    public function get_status_list()
    {
        return [
            CAREER_STATUS_ACTIVE => get_lang('Unarchived'),
            CAREER_STATUS_INACTIVE => get_lang('Archived'),
        ];
    }

    /**
     * Return the name of the careers that can be parents of others.
     */
    public function getHierarchies(int $selfCareer = 0): array
    {
        $return = [];
        $result = Database::select(
            'name, id',
            $this->table,
            [
                'where' => ['id != ?' => $selfCareer],
                'order' => 'id ASC',
            ]
        );
        foreach ($result as $item) {
            $return[$item['id']] = $item['name'];
        }
        array_unshift($return, '--');

        return $return;
    }

    /**
     * Returns a Form validator Obj.
     *
     * @todo the form should be auto generated
     *
     * @param string $url
     * @param string $action add, edit
     *
     * @return FormValidator
     */
    public function return_form($url, $action)
    {
        $form = new FormValidator('career', 'post', $url);
        // Setting the form elements
        $header = get_lang('Add');
        if ($action == 'edit') {
            $header = get_lang('Modify');
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : '';
        $form->addHeader($header);
        $form->addHidden('id', $id);
        $form->addText('name', get_lang('Name'), true, ['size' => '70']);
        $form->addHtmlEditor(
            'description',
            get_lang('Description'),
            false,
            false,
            [
                'ToolbarSet' => 'Careers',
                'Width' => '100%',
                'Height' => '250',
            ]
        );
        $status_list = $this->get_status_list();
        $form->addElement('select', 'status', get_lang('Status'), $status_list);

        if (api_get_configuration_value('career_hierarchy_enable')) {
            $hierarchyList = $this->getHierarchies((int) $id ?? 0);
            $form->addElement('select', 'parent_id', get_lang('ParentCareer'), $hierarchyList);
        }

        if ($action == 'edit') {
            $extraField = new ExtraField('career');
            $extraField->addElements($form, $id);

            $form->addElement('text', 'created_at', get_lang('CreatedAt'));
            $form->freeze('created_at');
            $form->addButtonSave(get_lang('Modify'));
        } else {
            $form->addButtonCreate(get_lang('Add'));
        }

        // Setting the defaults
        $defaults = $this->get($id);

        if (!empty($defaults['created_at'])) {
            $defaults['created_at'] = api_convert_and_format_date($defaults['created_at']);
        }
        if (!empty($defaults['updated_at'])) {
            $defaults['updated_at'] = api_convert_and_format_date($defaults['updated_at']);
        }

        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    /**
     * Copies the career to a new one.
     *
     * @param   int     Career ID
     * @param   bool     Whether or not to copy the promotions inside
     *
     * @return int New career ID on success, false on failure
     */
    public function copy($id, $copy_promotions = false)
    {
        $career = $this->get($id);
        $new = [];
        foreach ($career as $key => $val) {
            switch ($key) {
                case 'id':
                case 'updated_at':
                    break;
                case 'name':
                    $val .= ' '.get_lang('CopyLabelSuffix');
                    $new[$key] = $val;
                    break;
                case 'created_at':
                    $val = api_get_utc_datetime();
                    $new[$key] = $val;
                    break;
                default:
                    $new[$key] = $val;
                    break;
            }
        }
        $cid = $this->save($new);
        if ($copy_promotions) {
            //Now also copy each session of the promotion as a new session and register it inside the promotion
            $promotion = new Promotion();
            $promo_list = $promotion->get_all_promotions_by_career_id($id);
            if (!empty($promo_list)) {
                foreach ($promo_list as $item) {
                    $promotion->copy($item['id'], $cid, true);
                }
            }
        }

        return $cid;
    }

    /**
     * @param int $career_id
     *
     * @return bool
     */
    public function get_status($career_id)
    {
        $table = Database::get_main_table(TABLE_CAREER);
        $career_id = intval($career_id);
        $sql = "SELECT status FROM $table WHERE id = '$career_id'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $data = Database::fetch_array($result);

            return $data['status'];
        } else {
            return false;
        }
    }

    /**
     * @param array $params
     * @param bool  $show_query
     *
     * @return int
     */
    public function save($params, $show_query = false)
    {
        if (isset($params['description'])) {
            $params['description'] = Security::remove_XSS($params['description']);
        }

        $id = parent::save($params);
        if (!empty($id)) {
            Event::addEvent(
                LOG_CAREER_CREATE,
                LOG_CAREER_ID,
                $id,
                api_get_utc_datetime(),
                api_get_user_id()
            );
        }

        return $id;
    }

    /**
     * Delete a record from the career table and report in the default events log table.
     *
     * @param int $id The ID of the career to delete
     *
     * @return bool True if the career could be deleted, false otherwise
     */
    public function delete($id)
    {
        $res = parent::delete($id);
        if ($res) {
            $extraFieldValues = new ExtraFieldValue('career');
            $extraFieldValues->deleteValuesByItem($id);
            Event::addEvent(
                LOG_CAREER_DELETE,
                LOG_CAREER_ID,
                $id,
                api_get_utc_datetime(),
                api_get_user_id()
            );
        }

        return $res;
    }

    /**
     * {@inheritdoc}
     */
    public function update($params, $showQuery = false)
    {
        if (isset($params['description'])) {
            $params['description'] = Security::remove_XSS($params['description']);
        }

        return parent::update($params, $showQuery);
    }

    /**
     * @param array
     * @param Graph $graph
     *
     * @return string
     */
    public static function renderDiagram($careerInfo, $graph)
    {
        if (!($graph instanceof Graph)) {
            return '';
        }

        // Getting max column
        $maxColumn = 0;
        foreach ($graph->getVertices() as $vertex) {
            $groupId = (int) $vertex->getGroup();
            if ($groupId > $maxColumn) {
                $maxColumn = $groupId;
            }
        }

        $list = [];
        /** @var Vertex $vertex */
        foreach ($graph->getVertices() as $vertex) {
            $group = $vertex->getAttribute('Group');
            $groupData = explode(':', $group);
            $group = $groupData[0];
            $groupLabel = isset($groupData[1]) ? $groupData[1] : '';
            $subGroup = $vertex->getAttribute('SubGroup');
            $subGroupData = explode(':', $subGroup);
            $column = $vertex->getGroup();
            $row = $vertex->getAttribute('Row');
            $subGroupId = $subGroupData[0];
            $label = isset($subGroupData[1]) ? $subGroupData[1] : '';
            $list[$group][$subGroupId]['columns'][$column][$row] = $vertex;
            $list[$group][$subGroupId]['label'] = $label;
            $list[$group]['label'] = $groupLabel;
        }

        $maxGroups = count($list);
        $widthGroup = 30;
        if (!empty($maxGroups)) {
            $widthGroup = 85 / $maxGroups;
        }

        $connections = '';
        $groupDrawLine = [];
        $groupCourseList = [];
        // Read Connections column
        foreach ($list as $group => $subGroupList) {
            foreach ($subGroupList as $subGroupData) {
                $columns = isset($subGroupData['columns']) ? $subGroupData['columns'] : [];
                $showGroupLine = true;
                if (count($columns) == 1) {
                    $showGroupLine = false;
                }
                $groupDrawLine[$group] = $showGroupLine;

                //if ($showGroupLine == false) {
                /** @var Vertex $vertex */
                foreach ($columns as $row => $items) {
                    foreach ($items as $vertex) {
                        if ($vertex instanceof Vertex) {
                            $groupCourseList[$group][] = $vertex->getId();
                            $connectionList = $vertex->getAttribute('Connections');
                            $firstConnection = '';
                            $secondConnection = '';
                            if (!empty($connectionList)) {
                                $explode = explode('-', $connectionList);
                                $pos = strpos($explode[0], 'SG');
                                if ($pos === false) {
                                    $pos = strpos($explode[0], 'G');
                                    if (is_numeric($pos)) {
                                        // group_123 id
                                        $groupValueId = (int) str_replace(
                                            'G',
                                            '',
                                            $explode[0]
                                        );
                                        $firstConnection = 'group_'.$groupValueId;
                                        $groupDrawLine[$groupValueId] = true;
                                    } else {
                                        // Course block (row_123 id)
                                        if (!empty($explode[0])) {
                                            $firstConnection = 'row_'.(int) $explode[0];
                                        }
                                    }
                                } else {
                                    // subgroup__123 id
                                    $firstConnection = 'subgroup_'.(int) str_replace('SG', '', $explode[0]);
                                }

                                $pos = strpos($explode[1], 'SG');
                                if ($pos === false) {
                                    $pos = strpos($explode[1], 'G');
                                    if (is_numeric($pos)) {
                                        $groupValueId = (int) str_replace(
                                            'G',
                                            '',
                                            $explode[1]
                                        );
                                        $secondConnection = 'group_'.$groupValueId;
                                        $groupDrawLine[$groupValueId] = true;
                                    } else {
                                        // Course block (row_123 id)
                                        if (!empty($explode[0])) {
                                            $secondConnection = 'row_'.(int) $explode[1];
                                        }
                                    }
                                } else {
                                    $secondConnection = 'subgroup_'.(int) str_replace('SG', '', $explode[1]);
                                }

                                if (!empty($firstConnection) && !empty($firstConnection)) {
                                    $connections .= self::createConnection(
                                        $firstConnection,
                                        $secondConnection,
                                        ['Left', 'Right']
                                    );
                                }
                            }
                        }
                    }
                }
                //}
            }
        }

        $graphHtml = '<div class="container">';
        foreach ($list as $group => $subGroupList) {
            $showGroupLine = false;
            if (isset($groupDrawLine[$group]) && $groupDrawLine[$group]) {
                $showGroupLine = true;
            }
            $graphHtml .= self::parseSubGroups(
                $groupCourseList,
                $group,
                $list[$group]['label'],
                $showGroupLine,
                $subGroupList,
                $widthGroup
            );
        }
        $graphHtml .= '</div>';
        $graphHtml .= $connections;

        return $graphHtml;
    }

    /**
     * @param array    $careerInfo
     * @param Template $tpl
     * @param int      $loadUserIdData
     *
     * @return string
     */
    public static function renderDiagramByColumn($careerInfo, $tpl, $loadUserIdData = 0, $showFooter = true)
    {
        $careerId = $careerInfo['id'] ?? 0;
        if (empty($careerId)) {
            return '';
        }

        $extraFieldValue = new ExtraFieldValue('career');
        $item = $extraFieldValue->get_values_by_handler_and_field_variable(
            $careerId,
            'career_diagram',
            false,
            false,
            false
        );

        $graph = null;
        if (!empty($item) && isset($item['value']) && !empty($item['value'])) {
            /** @var Graph $graph */
            $graph = UnserializeApi::unserialize('career', $item['value']);
        }

        if (!($graph instanceof Graph)) {
            return '';
        }

        // Getting max column
        $maxColumn = 0;
        foreach ($graph->getVertices() as $vertex) {
            $groupId = (int) $vertex->getGroup();
            if ($groupId > $maxColumn) {
                $maxColumn = $groupId;
            }
        }

        $userResult = [];
        if (!empty($loadUserIdData)) {
            $careerData = UserManager::getUserCareer($loadUserIdData, $careerId);
            if (isset($careerData['extra_data']) && !empty($careerData['extra_data'])) {
                $userResult = unserialize($careerData['extra_data']);
            }
        }

        $list = [];
        $subGroups = [];
        /** @var Vertex $vertex */
        foreach ($graph->getVertices() as $vertex) {
            $column = $vertex->getGroup();
            $group = $vertex->getAttribute('Group');

            $groupData = explode(':', $group);
            $group = $groupData[0];
            $groupLabel = isset($groupData[1]) ? $groupData[1] : '';

            $subGroup = $vertex->getAttribute('SubGroup');
            $subGroupData = explode(':', $subGroup);

            $row = $vertex->getAttribute('Row');
            $subGroupId = $subGroupData[0];
            $subGroupLabel = isset($subGroupData[1]) ? $subGroupData[1] : '';

            if (!empty($subGroupId) && !in_array($subGroupId, $subGroups)) {
                $subGroups[$subGroupId]['items'][] = $vertex->getId();
                $subGroups[$subGroupId]['label'] = $subGroupLabel;
            }

            $list[$column]['rows'][$row]['items'][] = $vertex;
            $list[$column]['rows'][$row]['label'] = $subGroupId;
            $list[$column]['rows'][$row]['group'] = $group;
            $list[$column]['rows'][$row]['group_label'] = $groupLabel;
            $list[$column]['rows'][$row]['subgroup'] = $subGroup;
            $list[$column]['rows'][$row]['subgroup_label'] = $subGroupLabel;
            $list[$column]['label'] = $groupLabel;
            $list[$column]['column'] = $column;
        }

        $groupCourseList = [];
        $simpleConnectionList = [];

        // Read Connections column
        foreach ($list as $column => $groupList) {
            foreach ($groupList['rows'] as $subGroupList) {
                /** @var Vertex $vertex */
                foreach ($subGroupList['items'] as $vertex) {
                    if ($vertex instanceof Vertex) {
                        $groupCourseList[$vertex->getAttribute('Column')][] = $vertex->getId();
                        $connectionList = $vertex->getAttribute('Connections');
                        if (empty($connectionList)) {
                            continue;
                        }
                        $simpleFirstConnection = '';
                        $simpleSecondConnection = '';

                        $explode = explode('-', $connectionList);
                        $pos = strpos($explode[0], 'SG');
                        if ($pos === false) {
                            $pos = strpos($explode[0], 'G');
                            if (is_numeric($pos)) {
                                // Is group
                                $groupValueId = (int) str_replace(
                                    'G',
                                    '',
                                    $explode[0]
                                );
                                $simpleFirstConnection = 'g'.(int) $groupValueId;
                            } else {
                                // Course block (row_123 id)
                                if (!empty($explode[0])) {
                                    $simpleFirstConnection = 'v'.$explode[0];
                                }
                            }
                        } else {
                            // subgroup__123 id
                            $simpleFirstConnection = 'sg'.(int) str_replace('SG', '', $explode[0]);
                        }

                        $pos = false;
                        if (isset($explode[1])) {
                            $pos = strpos($explode[1], 'SG');
                        }
                        if ($pos === false) {
                            if (isset($explode[1])) {
                                $pos = strpos($explode[1], 'G');
                                $value = $explode[1];
                            }
                            if (is_numeric($pos)) {
                                $groupValueId = (int) str_replace(
                                    'G',
                                    '',
                                    $value
                                );
                                $simpleSecondConnection = 'g'.(int) $groupValueId;
                            } else {
                                // Course block (row_123 id)
                                if (!empty($explode[0]) && isset($explode[1])) {
                                    $simpleSecondConnection = 'v'.(int) $explode[1];
                                }
                            }
                        } else {
                            $simpleSecondConnection = 'sg'.(int) str_replace('SG', '', $explode[1]);
                        }

                        if (!empty($simpleFirstConnection) && !empty($simpleSecondConnection)) {
                            $simpleConnectionList[] = [
                                'from' => $simpleFirstConnection,
                                'to' => $simpleSecondConnection,
                            ];
                        }
                    }
                }
            }
        }

        $graphHtml = '';
        $groupsBetweenColumns = [];
        foreach ($list as $column => $columnList) {
            foreach ($columnList['rows'] as $subGroupList) {
                $newGroup = $subGroupList['group'];
                $label = $subGroupList['group_label'];
                $newOrder[$newGroup]['items'][] = $subGroupList;
                $newOrder[$newGroup]['label'] = $label;
                $groupsBetweenColumns[$newGroup][] = $subGroupList;
            }
        }

        // Creates graph
        $graph = new stdClass();
        $graph->blockWidth = 280;
        $graph->blockHeight = 150;

        $graph->xGap = 70;
        $graph->yGap = 55;

        $graph->xDiff = 70;
        $graph->yDiff = 55;

        if (!empty($userResult)) {
            $graph->blockHeight = 180;
            $graph->yGap = 60;
            $graph->yDiff = 60;
        }

        foreach ($groupsBetweenColumns as $group => $items) {
            self::parseColumnList($groupCourseList, $items, $graph, $simpleConnectionList, $userResult);
        }

        $graphHtml .= '<style>
             .panel-title {
                font-size: 11px;
                height: 40px;
             }

             .panel-body{
                min-height: 55px;
             }
             </style>';

        // Create groups
        if (!empty($graph->groupList)) {
            $groupList = [];
            $groupDiffX = 20;
            $groupDiffY = 50;
            $style = 'whiteSpace=wrap;rounded;html=1;strokeColor=red;fillColor=none;strokeWidth=2;align=left;verticalAlign=top;';
            foreach ($graph->groupList as $id => $data) {
                if (empty($id)) {
                    continue;
                }

                $x = $data['min_x'] - $groupDiffX;
                $y = $data['min_y'] - $groupDiffY;
                $width = $data['max_width'] + ($groupDiffX * 2);
                $height = $data['max_height'] + $groupDiffY * 2;
                $label = '<h4>'.$data['label'].'</h4>';
                $vertexData = "var g$id = graph.insertVertex(parent, null, '$label', $x, $y, $width, $height, '$style');";
                $groupList[] = $vertexData;
            }
            $tpl->assign('group_list', $groupList);
        }

        // Create subgroups
        $subGroupList = [];
        $subGroupListData = [];
        foreach ($subGroups as $subGroupId => $vertexData) {
            $label = $vertexData['label'];
            $vertexIdList = $vertexData['items'];
            foreach ($vertexIdList as $rowId) {
                $data = $graph->allData[$rowId];
                $originalRow = $data['row'];
                $column = $data['column'];
                $x = $data['x'];
                $y = $data['y'];
                $width = $data['width'];
                $height = $data['height'];

                if (!isset($subGroupListData[$subGroupId])) {
                    $subGroupListData[$subGroupId]['min_x'] = 1000;
                    $subGroupListData[$subGroupId]['min_y'] = 1000;
                    $subGroupListData[$subGroupId]['max_width'] = 0;
                    $subGroupListData[$subGroupId]['max_height'] = 0;
                    $subGroupListData[$subGroupId]['label'] = $label;
                }

                if ($x < $subGroupListData[$subGroupId]['min_x']) {
                    $subGroupListData[$subGroupId]['min_x'] = $x;
                }

                if ($y < $subGroupListData[$subGroupId]['min_y']) {
                    $subGroupListData[$subGroupId]['min_y'] = $y;
                }

                $subGroupListData[$subGroupId]['max_width'] = ($column + 1) * ($width + $graph->xGap) - $subGroupListData[$subGroupId]['min_x'];
                $subGroupListData[$subGroupId]['max_height'] = ($originalRow + 1) * ($height + $graph->yGap) - $subGroupListData[$subGroupId]['min_y'];
            }

            $style = 'whiteSpace=wrap;rounded;dashed=1;strokeColor=blue;fillColor=none;strokeWidth=2;align=left;verticalAlign=bottom;';
            $subGroupDiffX = 5;
            foreach ($subGroupListData as $subGroupId => $data) {
                $x = $data['min_x'] - $subGroupDiffX;
                $y = $data['min_y'] - $subGroupDiffX;

                $spaceForSubGroupTitle = 0;
                if (!empty($data['label'])) {
                    $spaceForSubGroupTitle = 40;
                }

                $width = $data['max_width'] + $subGroupDiffX * 2;
                $height = $data['max_height'] + $subGroupDiffX * 2 + $spaceForSubGroupTitle;

                $label = '<h4 style="background: white">'.$data['label'].'</h4>';
                $vertexData = "var sg$subGroupId = graph.insertVertex(parent, null, '$label', $x, $y, $width, $height, '$style');";
                $subGroupList[] = $vertexData;
            }
        }

        // Create connections (arrows)
        if (!empty($simpleConnectionList)) {
            $connectionList = [];
            //$style = 'endArrow=classic;html=1;strokeWidth=4;exitX=1;exitY=0.5;entryX=0;entryY=0.5;';
            $style = '';
            foreach ($simpleConnectionList as $connection) {
                $from = $connection['from'];
                $to = $connection['to'];
                $vertexData = "var e1 = graph.insertEdge(parent, null, '', $from, $to, '$style')";
                $connectionList[] = $vertexData;
            }
            $tpl->assign('connections', $connectionList);
        }

        $tpl->assign('subgroup_list', $subGroupList);
        $tpl->assign('vertex_list', $graph->elementList);

        $graphHtml .= '<div id="graphContainer"></div>';
        if ($showFooter) {
            $graphHtml .= self::renderDiagramFooter();
        }

        return $graphHtml;
    }

    /**
     * @param $groupCourseList
     * @param $columnList
     * @param $graph
     * @param $connections
     * @param $userResult
     *
     * @return string
     */
    public static function parseColumnList($groupCourseList, $columnList, &$graph, &$connections, $userResult)
    {
        $graphHtml = '';
        $newOrder = [];
        foreach ($columnList as $subGroupList) {
            $newGroup = $subGroupList['group'];
            $label = $subGroupList['group_label'];
            $newOrder[$newGroup]['items'][] = $subGroupList;
            $newOrder[$newGroup]['label'] = $label;
        }

        foreach ($newOrder as $newGroup => $data) {
            $label = $data['label'];
            $subGroupList = $data['items'];

            if (!isset($graph->groupList[$newGroup])) {
                $graph->groupList[$newGroup]['min_x'] = 1000;
                $graph->groupList[$newGroup]['min_y'] = 1000;
                $graph->groupList[$newGroup]['max_width'] = 0;
                $graph->groupList[$newGroup]['max_height'] = 0;
                $graph->groupList[$newGroup]['label'] = $label;
            }

            $maxColumn = 0;
            $maxRow = 0;
            $minColumn = 100;
            $minRow = 100;
            foreach ($subGroupList as $item) {
                /** @var Vertex $vertex */
                foreach ($item['items'] as $vertex) {
                    $column = $vertex->getAttribute('Column');
                    $realRow = $vertex->getAttribute('Row');

                    if ($column > $maxColumn) {
                        $maxColumn = $column;
                    }
                    if ($realRow > $maxRow) {
                        $maxRow = $realRow;
                    }

                    if ($column < $minColumn) {
                        $minColumn = $column;
                    }
                    if ($realRow < $minRow) {
                        $minRow = $realRow;
                    }
                }
            }

            if (!empty($newGroup)) {
                $graphHtml .= '<div
                    id ="group_'.$newGroup.'"
                    class="group'.$newGroup.' group_class"
                    style="display:grid;
                        align-self: start;
                        grid-gap: 10px;
                        justify-items: stretch;
                        align-items: start;
                        align-content: start;
                        justify-content: stretch;
                        grid-area:'.$minRow.'/'.$minColumn.'/'.$maxRow.'/'.$maxColumn.'">'; //style="display:grid"
            }

            $addRow = 0;
            if (!empty($label)) {
                $graphHtml .= "<div class='my_label' style='grid-area:$minRow/$minColumn/$maxRow/$maxColumn'>$label</div>";
                $addRow = 1;
            }

            foreach ($subGroupList as $item) {
                $graphHtml .= self::parseVertexList(
                    $groupCourseList,
                    $item['items'],
                    $addRow,
                    $graph,
                    $newGroup,
                    $connections,
                    $userResult
                );
            }

            if (!empty($newGroup)) {
                $graphHtml .= '</div >';
            }
        }

        return $graphHtml;
    }

    /**
     * @param array    $groupCourseList
     * @param array    $vertexList
     * @param int      $addRow
     * @param stdClass $graph
     * @param int      $group
     * @param array    $connections
     * @param array    $userResult
     *
     * @return string
     */
    public static function parseVertexList($groupCourseList, $vertexList, $addRow, &$graph, $group, &$connections, $userResult)
    {
        if (empty($vertexList)) {
            return '';
        }

        $graphHtml = '';
        /** @var Vertex $vertex */
        foreach ($vertexList as $vertex) {
            $borderColor = 'green';
            $column = $vertex->getAttribute('Column');
            $realRow = $originalRow = $vertex->getAttribute('Row');
            if ($addRow) {
                $realRow = $realRow + $addRow;
            }
            $id = $vertex->getId();
            $area = "$realRow/$column";
            $graphHtml .= '<div
                id = "row_wrapper_'.$id.'"
                data= "'.$originalRow.'-'.$column.'"
                style="
                    align-self: start;
                    justify-content: stretch;
                    grid-area:'.$area.'"
            >';
            $color = '';
            if (!empty($vertex->getAttribute('DefinedColor'))) {
                $color = $vertex->getAttribute('DefinedColor');
            }
            $content = '<div class="pull-left">'.$vertex->getAttribute('Notes').'</div>';
            $content .= '<div class="pull-right">['.$id.']</div>';

            if (!empty($userResult) && isset($userResult[$id]) && !empty($userResult[$id])) {
                // Order by SortDate
                $sortedByDate = $userResult[$id];
                foreach ($sortedByDate as $resultId => &$result) {
                    $result['resultId'] = $resultId;
                }

                usort($sortedByDate, function ($item1, $item2) {
                    if (!isset($item1['SortDate']) || !isset($item2['SortDate'])) {
                        return false;
                    }

                    return $item1['SortDate'] > $item2['SortDate'];
                });

                $lastItem = end($sortedByDate);
                if ($lastItem && isset($lastItem['BgColor']) && !empty($lastItem['BgColor'])) {
                    $color = $lastItem['BgColor'].'; color: '.$lastItem['Color'];
                    $borderColor = $lastItem['BorderColor'];
                }
                $results = '';
                $size = 2;
                foreach ($sortedByDate as $iconData) {
                    $resultId = $iconData['resultId'];
                    $icon = '';
                    switch ($iconData['Icon']) {
                        case 0:
                            $icon = Display::returnFontAwesomeIcon('times-circle', $size);
                            break;
                        case 1:
                            $icon = Display::returnFontAwesomeIcon('check-circle', $size);
                            break;
                        case 2:
                            $icon = Display::returnFontAwesomeIcon('info-circle', $size);
                            break;
                    }

                    if (substr($resultId, 0, 1) == 2) {
                        $iconData['Description'] = 'Result Id = '.$resultId;
                    }

                    if ('Joe Anonymous' === $iconData['TeacherUsername']) {
                        $iconData['TeacherUsername'] = '';
                    }

                    if (!empty($icon)) {
                        $params = [
                            'id' => 'course_'.$id.'_'.$resultId,
                            'data-toggle' => 'popover',
                            'title' => 'Popover title',
                            'class' => 'popup',
                            'data-description' => $iconData['Description'],
                            'data-period' => $iconData['Period'],
                            'data-teacher-text' => $iconData['TeacherText'],
                            'data-teacher' => $iconData['TeacherUsername'],
                            'data-score' => $iconData['ScoreText'],
                            'data-score-value' => $iconData['ScoreValue'],
                            'data-info' => $iconData['Info'],
                            'data-background-color' => $iconData['BgColor'],
                            'data-color' => $iconData['Color'],
                            'data-border-color' => $iconData['BorderColor'],
                            'style' => 'color:'.$iconData['IconColor'],
                        ];
                        $results .= Display::url($icon, 'javascript:void(0);', $params);
                    }
                }

                if (!empty($results)) {
                    $content .= '<div class="row"></div><div class="pull-left">'.$results.'</div>';
                }
            }

            $title = $vertex->getAttribute('graphviz.label');
            if (!empty($vertex->getAttribute('LinkedElement'))) {
                $title = Display::url($title, $vertex->getAttribute('LinkedElement').'&iframe=1');
            }

            $originalRow--;
            $column--;

            $graphHtml .= Display::panel(
                $content,
                $title,
                null,
                null,
                null,
                "row_$id",
                $color
            );

            $panel = Display::panel(
                $content,
                $title,
                null,
                null,
                null,
                "row_$id",
                $color
            );

            $x = $column * $graph->blockWidth + $graph->xDiff;
            $y = $originalRow * $graph->blockHeight + $graph->yDiff;

            $width = $graph->blockWidth - $graph->xGap;
            $height = $graph->blockHeight - $graph->yGap;

            $style = 'text;html=1;strokeColor='.$borderColor.';fillColor=#ffffff;overflow=fill;rounded=0;align=left;';

            $panel = str_replace(["\n", "\r"], '', $panel);
            $vertexData = "var v$id = graph.insertVertex(parent, null, '".addslashes($panel)."', $x, $y, $width, $height, '$style');";

            $graph->elementList[$id] = $vertexData;
            $graph->allData[$id] = [
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height,
                'row' => $originalRow,
                'column' => $column,
                'label' => $title,
            ];

            if ($x < $graph->groupList[$group]['min_x']) {
                $graph->groupList[$group]['min_x'] = $x;
            }

            if ($y < $graph->groupList[$group]['min_y']) {
                $graph->groupList[$group]['min_y'] = $y;
            }

            $graph->groupList[$group]['max_width'] = ($column + 1) * ($width + $graph->xGap) - $graph->groupList[$group]['min_x'];
            $graph->groupList[$group]['max_height'] = ($originalRow + 1) * ($height + ($graph->yGap)) - $graph->groupList[$group]['min_y'];

            $graphHtml .= '</div>';
            $arrow = $vertex->getAttribute('DrawArrowFrom');
            $found = false;
            if (!empty($arrow)) {
                $pos = strpos($arrow, 'SG');
                if ($pos === false) {
                    $pos = strpos($arrow, 'G');
                    if (is_numeric($pos)) {
                        $parts = explode('G', $arrow);
                        if (empty($parts[0]) && count($parts) == 2) {
                            $groupArrow = $parts[1];
                            $graphHtml .= self::createConnection(
                                "group_$groupArrow",
                                "row_$id",
                                ['Left', 'Right']
                            );
                            $found = true;
                            $connections[] = [
                              'from' => "g$groupArrow",
                              'to' => "v$id",
                            ];
                        }
                    }
                } else {
                    // Case is only one subgroup value example: SG1
                    $parts = explode('SG', $arrow);
                    if (empty($parts[0]) && count($parts) == 2) {
                        $subGroupArrow = $parts[1];
                        $graphHtml .= self::createConnection(
                            "subgroup_$subGroupArrow",
                            "row_$id",
                            ['Left', 'Right']
                        );
                        $found = true;
                        $connections[] = [
                            'from' => "sg$subGroupArrow",
                            'to' => "v$id",
                        ];
                    }
                }

                if ($found == false) {
                    // case is connected to 2 subgroups: Example SG1-SG2
                    $parts = explode('-', $arrow);
                    if (count($parts) == 2 && !empty($parts[0]) && !empty($parts[1])) {
                        $defaultArrow = ['Top', 'Bottom'];
                        $firstPrefix = '';
                        $firstId = '';
                        $secondId = '';
                        $secondPrefix = '';
                        if (is_numeric($pos = strpos($parts[0], 'SG'))) {
                            $firstPrefix = 'sg';
                            $firstId = str_replace('SG', '', $parts[0]);
                        }

                        if (is_numeric($pos = strpos($parts[1], 'SG'))) {
                            $secondPrefix = 'sg';
                            $secondId = str_replace('SG', '', $parts[1]);
                        }
                        if (!empty($secondId) && !empty($firstId)) {
                            $connections[] = [
                                'from' => $firstPrefix.$firstId,
                                'to' => $secondPrefix.$secondId,
                                $defaultArrow,
                            ];
                            $found = true;
                        }
                    }
                }

                if ($found == false) {
                    // case DrawArrowFrom is an integer
                    $defaultArrow = ['Left', 'Right'];
                    if (isset($groupCourseList[$column]) &&
                        in_array($arrow, $groupCourseList[$column])
                    ) {
                        $defaultArrow = ['Top', 'Bottom'];
                    }
                    $graphHtml .= self::createConnection(
                        "row_$arrow",
                        "row_$id",
                        $defaultArrow
                    );

                    $connections[] = [
                        'from' => "v$arrow",
                        'to' => "v$id",
                    ];
                }
            }
        }

        return $graphHtml;
    }

    /**
     * @param array  $groupCourseList list of groups and their courses
     * @param int    $group
     * @param string $groupLabel
     * @param bool   $showGroupLine
     * @param array  $subGroupList
     * @param $widthGroup
     *
     * @return string
     */
    public static function parseSubGroups(
        $groupCourseList,
        $group,
        $groupLabel,
        $showGroupLine,
        $subGroupList,
        $widthGroup
    ) {
        $topValue = 90;
        $defaultSpace = 40;
        $leftGroup = $defaultSpace.'px';
        if ($group == 1) {
            $leftGroup = 0;
        }

        $groupIdTag = "group_$group";
        $borderLine = $showGroupLine === true ? 'border-style:solid;' : '';

        $graphHtml = '<div
            id="'.$groupIdTag.'" class="career_group"
            style=" '.$borderLine.' padding:15px; float:left; margin-left:'.$leftGroup.'; width:'.$widthGroup.'%">';

        if (!empty($groupLabel)) {
            $graphHtml .= '<h3>'.$groupLabel.'</h3>';
        }

        foreach ($subGroupList as $subGroup => $subGroupData) {
            $subGroupLabel = isset($subGroupData['label']) ? $subGroupData['label'] : '';
            $columnList = isset($subGroupData['columns']) ? $subGroupData['columns'] : [];

            if (empty($columnList)) {
                continue;
            }

            $line = '';
            if (!empty($subGroup)) {
                $line = 'border-style:solid;';
            }

            // padding:15px;
            $graphHtml .= '<div
                id="subgroup_'.$subGroup.'" class="career_subgroup"
                style="'.$line.' margin-bottom:20px; padding:15px; float:left; margin-left:0px; width:100%">';
            if (!empty($subGroupLabel)) {
                $graphHtml .= '<h3>'.$subGroupLabel.'</h3>';
            }
            foreach ($columnList as $column => $rows) {
                $leftColumn = $defaultSpace.'px';
                if ($column == 1) {
                    $leftColumn = 0;
                }
                if (count($columnList) == 1) {
                    $leftColumn = 0;
                }

                $widthColumn = 85 / count($columnList);
                $graphHtml .= '<div
                    id="col_'.$column.'" class="career_column"
                    style="padding:15px;float:left; margin-left:'.$leftColumn.'; width:'.$widthColumn.'%">';
                $maxRow = 0;
                foreach ($rows as $row => $vertex) {
                    if ($row > $maxRow) {
                        $maxRow = $row;
                    }
                }

                $newRowList = [];
                $defaultSubGroup = -1;
                $subGroupCountList = [];
                for ($i = 0; $i < $maxRow; $i++) {
                    /** @var Vertex $vertex */
                    $vertex = isset($rows[$i + 1]) ? $rows[$i + 1] : null;
                    if (!is_null($vertex)) {
                        $subGroup = $vertex->getAttribute('SubGroup');
                        if ($subGroup == '' || empty($subGroup)) {
                            $defaultSubGroup = 0;
                        } else {
                            $defaultSubGroup = (int) $subGroup;
                        }
                    }
                    $newRowList[$i + 1][$defaultSubGroup][] = $vertex;
                    if (!isset($subGroupCountList[$defaultSubGroup])) {
                        $subGroupCountList[$defaultSubGroup] = 1;
                    } else {
                        $subGroupCountList[$defaultSubGroup]++;
                    }
                }

                $subGroup = null;
                $subGroupAdded = [];
                /** @var Vertex $vertex */
                foreach ($newRowList as $row => $subGroupList) {
                    foreach ($subGroupList as $subGroup => $vertexList) {
                        if (!empty($subGroup) && $subGroup != -1) {
                            if (!isset($subGroupAdded[$subGroup])) {
                                $subGroupAdded[$subGroup] = 1;
                            } else {
                                $subGroupAdded[$subGroup]++;
                            }
                        }

                        foreach ($vertexList as $vertex) {
                            if (is_null($vertex)) {
                                $graphHtml .= '<div class="career_empty" style="height: 130px">';
                                $graphHtml .= '</div>';
                                continue;
                            }

                            $id = $vertex->getId();
                            $rowId = "row_$row";
                            $graphHtml .= '<div id = "row_'.$id.'" class="'.$rowId.' career_row" >';
                            $color = '';
                            if (!empty($vertex->getAttribute('DefinedColor'))) {
                                $color = $vertex->getAttribute('DefinedColor');
                            }
                            $content = $vertex->getAttribute('Notes');
                            $content .= '<div class="pull-right">['.$id.']</div>';

                            $title = $vertex->getAttribute('graphviz.label');
                            if (!empty($vertex->getAttribute('LinkedElement'))) {
                                $title = Display::url($title, $vertex->getAttribute('LinkedElement'));
                            }

                            $graphHtml .= Display::panel(
                                $content,
                                $title,
                                null,
                                null,
                                null,
                                null,
                                $color
                            );
                            $graphHtml .= '</div>';

                            $arrow = $vertex->getAttribute('DrawArrowFrom');
                            $found = false;
                            if (!empty($arrow)) {
                                $pos = strpos($arrow, 'SG');
                                if ($pos === false) {
                                    $pos = strpos($arrow, 'G');
                                    if (is_numeric($pos)) {
                                        $parts = explode('G', $arrow);
                                        if (empty($parts[0]) && count($parts) == 2) {
                                            $groupArrow = $parts[1];
                                            $graphHtml .= self::createConnection(
                                                "group_$groupArrow",
                                                "row_$id",
                                                ['Left', 'Right']
                                            );
                                            $found = true;
                                        }
                                    }
                                } else {
                                    $parts = explode('SG', $arrow);
                                    if (empty($parts[0]) && count($parts) == 2) {
                                        $subGroupArrow = $parts[1];
                                        $graphHtml .= self::createConnection(
                                            "subgroup_$subGroupArrow",
                                            "row_$id",
                                            ['Left', 'Right']
                                        );
                                        $found = true;
                                    }
                                }
                            }

                            if ($found == false) {
                                $defaultArrow = ['Left', 'Right'];
                                if (isset($groupCourseList[$group]) &&
                                    in_array($arrow, $groupCourseList[$group])
                                ) {
                                    $defaultArrow = ['Top', 'Bottom'];
                                }
                                $graphHtml .= self::createConnection(
                                    "row_$arrow",
                                    "row_$id",
                                    $defaultArrow
                                );
                            }
                        }
                    }
                }
                $graphHtml .= '</div>';
            }
            $graphHtml .= '</div>';
        }
        $graphHtml .= '</div>';

        return $graphHtml;
    }

    /**
     * @param string $source
     * @param string $target
     * @param array  $anchor
     *
     * @return string
     */
    public static function createConnection($source, $target, $anchor = [])
    {
        if (empty($anchor)) {
            // Default
            $anchor = ['Bottom', 'Right'];
        }

        $anchor = implode('","', $anchor);
        $html = '<script>

        var connectorPaintStyle = {
            strokeWidth: 2,
            stroke: "#a31ed3",
            joinstyle: "round",
            outlineStroke: "white",
            outlineWidth: 2
        },
        // .. and this is the hover style.
        connectorHoverStyle = {
            strokeWidth: 3,
            stroke: "#216477",
            outlineWidth: 5,
            outlineStroke: "white"
        },
        endpointHoverStyle = {
            fill: "#E80CAF",
            stroke: "#E80CAF"
        };
        jsPlumb.ready(function() { ';
        $html .= 'jsPlumb.connect({
            source:"'.$source.'",
            target:"'.$target.'",
            endpoint:[ "Rectangle", { width:1, height:1 }],
            connector: ["Flowchart"],
            paintStyle: connectorPaintStyle,
            hoverPaintStyle: endpointHoverStyle,
            anchor: ["'.$anchor.'"],
            overlays: [
                [
                    "Arrow",
                    {
                        location:1,
                        width:11,
                        length:11
                    }
                ],
            ],
        });';
        $html .= '});</script>'.PHP_EOL;

        return $html;
    }

    public static function renderDiagramFooter(): string
    {
        $footer = '';
        if (api_get_configuration_value('career_diagram_legend')) {
            $footer .= get_lang('CareerDiagramLegend');
        }
        if (api_get_configuration_value('career_diagram_disclaimer')) {
            $footer .= get_lang('CareerDiagramDisclaimer');
        }

        return $footer;
    }

    public static function addCareerFieldsToForm(FormValidator $form, array $values = [])
    {
        $career = new self();
        $careerList = $career->get_all();
        $list = array_column($careerList, 'name', 'id');

        $url = api_get_path(WEB_AJAX_PATH).'career.ajax.php';

        $form->addHtml('<script>
                $(function () {
                    var url = "'.$url.'";
                    var $txtPromotion = $("#promotion_id");

                    $("#career_id").on("change", function () {
                        var id = this.value;

                        $txtPromotion.empty().append($("<option>", {
                            value: 0,
                            text: "'.get_lang('All').'"
                        }));

                        $.getJSON(url, {
                            "career_id": id,
                            "a": "get_promotions"
                        }).done(function (data) {
                            $.each(data, function (index, value) {
                                $txtPromotion.append($("<option>", {
                                    value: value.id,
                                    text: value.name
                                }));
                            });

                            $txtPromotion.selectpicker("refresh");
                        });
                    });
                });
            </script>');
        $form->addSelect(
            'career_id',
            get_lang('Career'),
            $list,
            [
                'placeholder' => get_lang('SelectAnOption'),
                'id' => 'career_id',
            ]
        );

        $options = [
            '0' => get_lang('All'),
        ];
        if (isset($values['promotion_id'])) {
            $promotion = new Promotion();
            $promotion = $promotion->get($values['promotion_id']);
            if ($promotion) {
                $options = [$promotion['id'] => $promotion['name']];
            }
        }

        $form->addSelect(
            'promotion_id',
            get_lang('Promotion'),
            $options,
            ['id' => 'promotion_id']
        );
    }
}
