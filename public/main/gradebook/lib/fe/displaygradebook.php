<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

/**
 * Class DisplayGradebook.
 */
class DisplayGradebook
{
    /**
     * Displays the header for the result page containing the navigation tree and links.
     *
     * @param Evaluation $evalobj
     * @param int        $selectcat
     * @param string     $page
     */
    public static function display_header_result($evalobj, $selectcat, $page)
    {
        $injectAnchorClasses = static function (string $html, string $classes): string {
            if (false === strpos($html, '<a ')) {
                return $html;
            }

            // If anchor already has class="", append.
            if (preg_match('/<a\\b[^>]*\\bclass=(["\'])(.*?)\\1/i', $html)) {
                return preg_replace(
                    '/<a\\b([^>]*?)\\bclass=(["\'])(.*?)\\2/i',
                    '<a$1class=$2$3 '.$classes.'$2',
                    $html,
                    1
                );
            }

            // Otherwise inject new class=""
            return preg_replace('/<a\\b/i', '<a class="'.$classes.'"', $html, 1);
        };

        $links = [];
        if (api_is_allowed_to_edit(null, true)) {
            if ('statistics' !== $page) {
                $links[] = '<a href="'.Category::getUrl().'selectcat='.$selectcat.'">'.
                    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assessment home'))
                    .'</a>';
                if ('view_result' === $page) {
                    if (!empty($evalobj->getCourseId()) && !$evalobj->has_results()) {
                        $links[] = '<a href="gradebook_add_result.php?'.api_get_cidreq().'&selectcat='.$selectcat.'&selecteval='.$evalobj->get_id().'">'.
                            Display::getMdiIcon(ActionIcon::GRADE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Grade learners')).
                            '</a>';
                    }

                    if (api_is_platform_admin() || false == $evalobj->is_locked()) {
                        $links[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&selecteval='.$evalobj->get_id().'&import=">'.
                            Display::getMdiIcon(ActionIcon::IMPORT_ARCHIVE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Import marks')).
                            '</a>';
                    }

                    if ($evalobj->has_results()) {
                        $links[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&selecteval='.$evalobj->get_id().'&export=">'.
                            Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('PDF Report')).
                            '</a>';

                        if (api_is_platform_admin() || false == $evalobj->is_locked()) {
                            $links[] = '<a href="gradebook_edit_result.php?'.api_get_cidreq().'&selecteval='.$evalobj->get_id().'">'.
                                Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Grade learners')).
                                '</a>';

                            $links[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&selecteval='.$evalobj->get_id().'&deleteall=" onclick="return confirmationall();">'.
                                Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete marks')).
                                '</a>';
                        }
                    }

                    $links[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&print=&selecteval='.$evalobj->get_id().'" target="_blank">'.
                        Display::getMdiIcon(ActionIcon::PRINT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Print')).
                        '</a>';
                }
            } else {
                $links[] = '<a href="gradebook_view_result.php?'.api_get_cidreq().'&selecteval='.Security::remove_XSS($_GET['selecteval']).'">'.
                    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assessment home')).
                    '</a>';
            }
        }

        // Add "Graphical view" link (teacher only)
        if ('statistics' != $page) {
            if (api_is_allowed_to_edit(null, true)) {
                $links[] = '<a href="gradebook_statistics.php?'.api_get_cidreq().'&selecteval='.Security::remove_XSS($_GET['selecteval']).'">'.
                    Display::getMdiIcon('chart-box', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Graphical view')).
                    '</a>';
            }
        }

        // Compute average / student score blocks using legacy logic.
        $scoredisplay = ScoreDisplay::instance();
        $averageDisplay = null;
        $studentScoreHtml = '';
        $model = null;

        if ($evalobj->has_results()) {
            $score = $evalobj->calc_score();
            if (null != $score) {
                $model = ExerciseLib::getCourseScoreModel();
                if (empty($model)) {
                    $averageDisplay = $scoredisplay->display_score($score, SCORE_AVERAGE);

                    $student_score = $evalobj->calc_score(api_get_user_id());
                    $studentScoreHtml = Display::tag(
                        'div',
                        get_lang('Score').': '.$scoredisplay->display_score($student_score, SCORE_DIV_PERCENT),
                        ['class' => 'mt-3 text-sm text-gray-700']
                    );

                    $allowMultipleAttempts = ('true' === api_get_setting('gradebook.gradebook_multiple_evaluation_attempts'));
                    if ($allowMultipleAttempts) {
                        $results = Result::load(null, api_get_user_id(), $evalobj->get_id());
                        if (!empty($results)) {
                            foreach ($results as $resultData) {
                                $studentScoreHtml .= ResultTable::getResultAttemptTable($resultData);
                            }
                        }
                    }
                }
            }
        }

        // Description line
        $descriptionValue = '';
        if ('' == !$evalobj->get_description()) {
            $descriptionValue = $evalobj->get_description();
        }

        $courseId = (int) $evalobj->getCourseId();
        if (empty($courseId)) {
            $courseTitle = get_lang('Independent from course');
        } else {
            $courseInfo = api_get_course_info_by_id($courseId);
            $courseTitle = !empty($courseInfo['title']) ? $courseInfo['title'] : get_lang('Unknown course');
        }
        $wrapStart = '<div class="px-4">';
        $wrapEnd = '</div>';

        // Toolbar
        $buttonClasses = 'inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-25 bg-white text-gray-700 hover:bg-gray-10 shadow-sm';
        $linksStyled = [];
        foreach ($links as $linkHtml) {
            $linksStyled[] = $injectAnchorClasses($linkHtml, $buttonClasses);
        }

        echo $wrapStart;
        echo '<div class="mt-2 flex flex-wrap items-center gap-2">'.implode('', $linksStyled).'</div>';

        // Info card
        echo '<div class="mt-4 bg-white border border-gray-25 rounded-2xl shadow-sm overflow-hidden">';
        echo '  <div class="p-6">';
        echo '    <div class="flex items-start justify-between gap-4">';
        echo '      <div class="min-w-0">';
        echo '        <h1 class="text-2xl font-semibold text-gray-900">'.Security::remove_XSS($evalobj->get_name()).'</h1>';
        echo '        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-2 text-sm text-gray-700">';

        if (!empty($descriptionValue)) {
            echo '          <div><span class="font-medium text-gray-900">'.get_lang('Description').':</span> '.Security::remove_XSS($descriptionValue).'</div>';
        }
        echo '          <div><span class="font-medium text-gray-900">'.get_lang('Course').':</span> '.Security::remove_XSS($courseTitle).'</div>';

        if (empty($model)) {
            echo '          <div><span class="font-medium text-gray-900">'.get_lang('Maximum score').':</span> '.Security::remove_XSS((string) $evalobj->get_max()).'</div>';
            if (null !== $averageDisplay) {
                echo '      <div><span class="font-medium text-gray-900">'.get_lang('Average').':</span> '.$averageDisplay.'</div>';
            }
        }

        echo '        </div>';

        if (!$evalobj->has_results()) {
            echo '      <div class="mt-3 text-sm text-gray-500 italic">'.get_lang('No results in evaluation for now').'</div>';
        }

        if (!api_is_allowed_to_edit()) {
            // Student view: show personal score
            echo $studentScoreHtml;
        }

        echo '      </div>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';

        echo $wrapEnd;
    }

    /**
     * Displays the header for the flatview page containing filters.
     *
     * @param $catobj
     * @param $showeval
     * @param $showlink
     */
    public static function display_header_reduce_flatview($catobj, $showeval, $showlink, $simple_search_form)
    {
        $header = '<div class="actions">';
        if (0 == $catobj->get_parent_id()) {
            $select_cat = $catobj->get_id();
            $url = Category::getUrl();
        } else {
            $select_cat = $catobj->get_parent_id();
            $url = 'gradebook_flatview.php';
        }
        $header .= '<a href="'.$url.'?'.api_get_cidreq().'&selectcat='.$select_cat.'">'.
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assessment home')).'</a>';

        $pageNum = isset($_GET['flatviewlist_page_nr']) ? (int) $_GET['flatviewlist_page_nr'] : null;
        $perPage = isset($_GET['flatviewlist_per_page']) ? (int) $_GET['flatviewlist_per_page'] : null;
        $offset = isset($_GET['offset']) ? $_GET['offset'] : '0';

        $exportCsvUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'export_format' => 'csv',
            'export_report' => 'export_report',
            'selectcat' => $catobj->get_id(),
        ]);

        $header .= Display::url(
            Display::getMdiIcon(ActionIcon::EXPORT_CSV, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('CSV export')),
            $exportCsvUrl
        );

        $exportXlsUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'export_format' => 'xls',
            'export_report' => 'export_report',
            'selectcat' => $catobj->get_id(),
        ]);

        $header .= Display::url(
            Display::getMdiIcon(ActionIcon::EXPORT_SPREADSHEET, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Excel export')),
            $exportXlsUrl
        );

        $exportDocUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'export_format' => 'doc',
            'export_report' => 'export_report',
            'selectcat' => $catobj->get_id(),
        ]);

        $header .= Display::url(
            Display::getMdiIcon(ActionIcon::EXPORT_DOC, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export as .doc')),
            $exportDocUrl
        );

        $exportPrintUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
                'print' => '',
                'selectcat' => $catobj->get_id(),
            ]);

        $header .= Display::url(
            Display::getMdiIcon(ActionIcon::PRINT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Print')),
            $exportPrintUrl,
            ['target' => '_blank']
        );

        $exportPdfUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'exportpdf' => '',
            'selectcat' => $catobj->get_id(),
            'offset' => $offset,
            'flatviewlist_page_nr' => $pageNum,
            'flatviewlist_per_page' => $perPage,
        ]);

        $header .= Display::url(
            Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to PDF')),
            $exportPdfUrl
        );

        $header .= '</div>';
        echo $header;
    }

    /**
     * Displays the header for the gradebook containing the navigation tree and links.
     *
     * @param Category      $catobj
     * @param int           $showtree               '1' will show the browse tree and naviation buttons
     * @param               $selectcat
     * @param bool          $is_course_admin
     * @param bool          $is_platform_admin
     * @param FormValidator $simple_search_form
     * @param bool          $show_add_qualification Whether to show or not the link to add a new qualification
     *                                              (we hide it in case of the course-embedded tool where we have
     *                                              only one per course or session)
     * @param bool          $show_add_link          Whether to show or not the link to add a new item inside
     *                                              the qualification (we hide it in case of the course-embedded tool
     *                                              where we have only one qualification per course or session)
     * @param array         $certificateLinkInfo
     */
    public static function header(
        $catobj,
        $showtree,
        $selectcat,
        $is_course_admin,
        $is_platform_admin,
        $simple_search_form,
        $show_add_qualification = true,
        $show_add_link = true,
        $certificateLinkInfo = []
    ) {
        $userId = api_get_user_id();
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        if (!$is_course_admin) {
            $model = ExerciseLib::getCourseScoreModel();
            if (!empty($model)) {
                return '';
            }
        }

        // Student.
        $status = CourseManager::getUserInCourseStatus($userId, $courseId);
        $sessionStatus = 0;

        if (!empty($sessionId)) {
            $sessionStatus = SessionManager::get_user_status_in_course_session(
                $userId,
                $courseId,
                $sessionId
            );
        }

        $courseId = CourseManager::get_course_by_category($selectcat);
        $messageResource = Category::show_message_resource_delete($courseId);
        $grade_model_id = $catobj->get_grade_model_id();
        $header = null;
        if (isset($catobj) && !empty($catobj)) {
            $categories = Category::load(
                null,
                null,
                0,
                $catobj->get_id(),
                null,
                $sessionId
            );
        }

        if (!$is_course_admin && (1 != $status || 0 == $sessionStatus) && 0 != $selectcat) {
            $catcourse = Category::load($catobj->get_id());
            /** @var Category $category */
            $category = $catcourse[0];
            $main_weight = $category->get_weight();
            $scoredisplay = ScoreDisplay::instance();
            $allevals = $category->get_evaluations($userId, true);
            $alllinks = $category->get_links($userId, true);
            $allEvalsLinks = array_merge($allevals, $alllinks);
            $item_value_total = 0;
            $scoreinfo = null;

            for ($count = 0; $count < count($allEvalsLinks); $count++) {
                $item = $allEvalsLinks[$count];
                $score = $item->calc_score($userId);
                if (!empty($score)) {
                    $divide = 0 == $score[1] ? 1 : $score[1];
                    $item_value = $score[0] / $divide * $item->get_weight();
                    $item_value_total += $item_value;
                }
            }

            $item_total = $main_weight;
            $total_score = [$item_value_total, $item_total];
            $scorecourse_display = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT);

            if ('0' == !$catobj->get_id() && !isset($_GET['studentoverview']) && !isset($_GET['search'])) {
                $additionalButtons = null;
                if (!empty($certificateLinkInfo)) {
                    $additionalButtons .= '<div class="btn-group pull-right">';
                    $additionalButtons .= isset($certificateLinkInfo['certificate_link']) ? $certificateLinkInfo['certificate_link'] : '';
                    $additionalButtons .= isset($certificateLinkInfo['badge_link']) ? $certificateLinkInfo['badge_link'] : '';
                    $additionalButtons .= '</div>';
                }
                $scoreinfo .= '<strong>'.sprintf(get_lang('Total: %s'), $scorecourse_display.$additionalButtons).'</strong>';
            }
            echo Display::return_message($scoreinfo, 'normal', false);
        }

        // show navigation tree and buttons?
        if ('1' == $showtree || isset($_GET['studentoverview'])) {
            $header = '<div class="actions"><table>';
            $header .= '<tr>';
            if ('0' == !$selectcat) {
                $header .= '<td><a href="'.api_get_self().'?selectcat='.$catobj->get_parent_id().'">'.
                    Display::getMdiIcon(
                        ActionIcon::BACK,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_MEDIUM,
                        get_lang('Back to').' '.get_lang('Main folder')
                    ).
                    '</a></td>';
            }
            $header .= '<td>'.get_lang('Current course').'</td>'.
                    '<td><form name="selector"><select name="selectcat" onchange="document.selector.submit()">';
            $cats = Category::load();

            $tree = $cats[0]->get_tree();
            unset($cats);
            $line = null;
            foreach ($tree as $cat) {
                for ($i = 0; $i < $cat[2]; $i++) {
                    $line .= '&mdash;';
                }
                $line = isset($line) ? $line : '';
                if (isset($_GET['selectcat']) && $_GET['selectcat'] == $cat[0]) {
                    $header .= '<option selected value='.$cat[0].'>'.$line.' '.$cat[1].'</option>';
                } else {
                    $header .= '<option value='.$cat[0].'>'.$line.' '.$cat[1].'</option>';
                }
                $line = '';
            }
            $header .= '</select></form></td>';
            if (!empty($simple_search_form) && empty($messageResource)) {
                $header .= '<td style="vertical-align: top;">'.$simple_search_form->toHtml().'</td>';
            } else {
                $header .= '<td></td>';
            }
            if (!($is_course_admin &&
                empty($messageResource) &&
                isset($_GET['selectcat']) && 0 != $_GET['selectcat']) &&
                isset($_GET['studentoverview'])
            ) {
                $header .= '<td style="vertical-align: top;">
                                <a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&exportpdf=&selectcat='.$catobj->get_id().'" target="_blank">
							 '.Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to PDF')).'
							'.get_lang('Export to PDF').'</a>';
            }
            $header .= '</td></tr>';
            $header .= '</table></div>';
        }

        // for course admin & platform admin add item buttons are added to the header
        $actionsLeft = '';
        $actionsRight = '';
        $my_api_cidreq = api_get_cidreq();
        $isCoach = api_is_coach(api_get_session_id(), api_get_course_int_id());
        $accessToRead = api_is_allowed_to_edit(null, true) || $isCoach;
        $accessToEdit = api_is_allowed_to_edit(null, true);

        if ($accessToRead) {
            $my_category = $catobj->showAllCategoryInfo($catobj->get_id());
            if ('0' != $selectcat && $accessToEdit) {
                if ('' == $my_api_cidreq) {
                    $my_api_cidreq = 'cid='.$my_category['c_id'];
                }
                if ($show_add_link && empty($messageResource)) {
                    $actionsLeft .= '<a href="gradebook_add_eval.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'" >'.
                        Display::getMdiIcon('table-plus', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add classroom activity')).'</a>';
                    $cats = Category::load($selectcat);

                    if (isset($cats[0]) && !empty($cats[0]->getCourseId()) && empty($messageResource)) {
                        $actionsLeft .= '<a href="gradebook_add_link.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                            Display::getMdiIcon('link-plus', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add online activity')).'</a>';
                    } else {
                        $actionsLeft .= '<a href="gradebook_add_link_select_course.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                            Display::getMdiIcon('link-plus', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add online activity')).'</a>';
                    }
                }
            }
            if ((empty($grade_model_id) || -1 == $grade_model_id) && $accessToEdit) {
                $actionsLeft .= '<a href="gradebook_add_cat.php?'.api_get_cidreq().'&selectcat='.$catobj->get_id().'">'.
                    Display::getMdiIcon(ActionIcon::CREATE_FOLDER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add assessment')).'</a></td>';
            }

            if ('0' != $selectcat && $accessToRead) {
                if (empty($messageResource)) {
                    $actionsLeft .= '<a href="gradebook_flatview.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                        Display::getMdiIcon('chart-box', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List View')).'</a>';

                    if (1 == $my_category['generate_certificates']) {
                        $actionsLeft .= Display::url(
                            Display::getMdiIcon('format-list-text', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('See list of learner certificates')),
                            "gradebook_display_certificate.php?$my_api_cidreq&cat_id=".$selectcat
                        );
                    }

                    $actionsLeft .= Display::url(
                        Display::getMdiIcon('account', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Students list report')),
                        "gradebook_display_summary.php?$my_api_cidreq&selectcat=".$selectcat
                    );

                    $allow = api_get_configuration_value('gradebook_custom_student_report');
                    if ($allow) {
                        $actionsLeft .= Display::url(
                            get_lang('Generate custom report'),
                            api_get_path(WEB_AJAX_PATH)."gradebook.ajax.php?$my_api_cidreq&a=generate_custom_report",
                            ['class' => 'btn btn--plain ajax']
                        );
                    }

                    // Right icons
                    if ($accessToEdit) {
                        $actionsRight = '<a href="gradebook_edit_cat.php?editcat='.$catobj->get_id(
                            ).'&cid='.$catobj->getCourseId().'&sid='.$catobj->get_session_id().'">'.
                            Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit')).'</a>';

                        if ('true' == api_get_plugin_setting('customcertificate', 'enable_plugin_customcertificate') &&
                            1 == api_get_course_setting('customcertificate_course_enable')
                        ) {
                            $actionsRight .= '<a href="'.api_get_path(
                                    WEB_PLUGIN_PATH
                                ).'CustomCertificate/src/index.php?'.
                                $my_api_cidreq.'&origin=gradebook&selectcat='.$catobj->get_id().'">'.
                                Display::getMdiIcon('certificate', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Attach certificate')).'</a>';
                        } else {
                            $course = api_get_course_entity($courseId);
                            $resourceId = $course->resourceNode->getId();
                            $certificateLink = api_get_path(WEB_PATH) . 'resources/document/'.$resourceId.'/?'.api_get_cidreq().'&filetype=certificate';
                            $actionsRight .= '<a href="'.$certificateLink.'">'.
                                Display::getMdiIcon('certificate', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Attach certificate')).'</a>';
                        }

                        if (empty($categories)) {
                            $actionsRight .= '<a href="gradebook_edit_all.php?id_session='.api_get_session_id(
                                ).'&'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                                Display::getMdiIcon('percent-box', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Weight in Report')).'</a>';
                        }
                        $score_display_custom = api_get_setting('gradebook_score_display_custom');
                        if ('true' == api_get_setting('teachers_can_change_score_settings') &&
                            'true' == $score_display_custom
                        ) {
                            $actionsRight .= '<a href="gradebook_scoring_system.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                                Display::getMdiIcon('podium', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Skills ranking')).'</a>';
                        }
                    }
                }
            }
        } elseif (isset($_GET['search'])) {
            echo $header = '<b>'.get_lang('Search results').' :</b>';
        }

        $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
            api_get_user_id(),
            api_get_course_info()
        );

        if ($isDrhOfCourse) {
            $actionsLeft .= '<a href="gradebook_flatview.php?'.$my_api_cidreq.'&selectcat='.$catobj->get_id().'">'.
                Display::getMdiIcon('chart-box', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List View')).
                '</a>';
        }

        if ($isCoach || api_is_allowed_to_edit(null, true)) {
            echo $toolbar = Display::toolbarAction(
                'gradebook-actions',
                [$actionsLeft, $actionsRight]
            );
        }

        if ($accessToEdit || api_is_allowed_to_edit(null, true)) {
            $weight = intval($catobj->get_weight()) > 0 ? $catobj->get_weight() : 0;
            $weight = '<strong>'.get_lang('Total weight').' : </strong>'.$weight;
            $min_certification = intval($catobj->getCertificateMinScore() > 0) ? $catobj->getCertificateMinScore() : 0;

            if (!empty($min_certification)) {
                $model = ExerciseLib::getCourseScoreModel();
                if (!empty($model)) {
                    $defaultCertification = api_number_format($min_certification, 2);
                    $questionWeighting = $catobj->get_weight();
                    foreach ($model['score_list'] as $item) {
                        $i = api_number_format($item['score_to_qualify'] / 100 * $questionWeighting, 2);
                        $model = ExerciseLib::getModelStyle($item, $i);
                        if ($defaultCertification == $i) {
                            $min_certification = $model;
                            break;
                        }
                    }
                }
            }

            $min_certification = get_lang('Minimum certification score').' : '.$min_certification;
            $edit_icon = '<a href="gradebook_edit_cat.php?editcat='.$catobj->get_id().'&cid='.$catobj->getCourseId().'&sid='.$catobj->get_session_id().'">'.
                Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit')).'</a>';

            $msg = $weight.' - '.$min_certification.$edit_icon;
            //@todo show description
            $description = (('' == $catobj->get_description() || is_null($catobj->get_description())) ? '' : '<strong>'.get_lang('Assessment description').'</strong>'.': '.$catobj->get_description());
            echo Display::return_message($msg, 'normal', false);
            if (!empty($description)) {
                echo Display::div($description, []);
            }
        }
    }

    /**
     * @param Category $catobj
     * @param $is_course_admin
     * @param $is_platform_admin
     * @param $simple_search_form
     * @param bool $show_add_qualification
     * @param bool $show_add_link
     */
    public function display_reduce_header_gradebook(
        $catobj,
        $is_course_admin,
        $is_platform_admin,
        $simple_search_form,
        $show_add_qualification = true,
        $show_add_link = true
    ) {
        //student
        if (!$is_course_admin) {
            $user = api_get_user_info(api_get_user_id());
            $catcourse = Category::load($catobj->get_id());
            $scoredisplay = ScoreDisplay::instance();
            $scorecourse = $catcourse[0]->calc_score(api_get_user_id());
            $scorecourse_display = isset($scorecourse) ? $scoredisplay->display_score($scorecourse, SCORE_AVERAGE) : get_lang('No results available');
            $cattotal = Category::load(0);
            $scoretotal = $cattotal[0]->calc_score(api_get_user_id());
            $scoretotal_display = isset($scoretotal) ? $scoredisplay->display_score($scoretotal, SCORE_PERCENT) : get_lang('No results available');
            $scoreinfo = get_lang('Statistics of').' :<b> '.$user['complete_name'].'</b><br />';
            if (('0' == !$catobj->get_id()) && (!isset($_GET['studentoverview'])) && (!isset($_GET['search']))) {
                $scoreinfo .= '<br />'.get_lang('Total for this category.').' : <b>'.$scorecourse_display.'</b>';
            }
            $scoreinfo .= '<br />'.get_lang('Total').' : <b>'.$scoretotal_display.'</b>';
            Display::addFlash(
                Display::return_message($scoreinfo, 'normal', false)
            );
        }
        // show navigation tree and buttons?
        $header = '<div class="actions">';

        if ($is_course_admin) {
            $header .= '<a href="gradebook_flatview.php?'.api_get_cidreq().'&selectcat='.$catobj->get_id().'">'.Display::getMdiIcon('chart-box', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List View')).'</a>';
            $header .= '<a href="gradebook_scoring_system.php?'.api_get_cidreq().'&selectcat='.$catobj->get_id().'">'.Display::getMdiIcon('cog', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Skills ranking')).'</a>';
        } elseif (!(isset($_GET['studentoverview']))) {
            $header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&selectcat='.$catobj->get_id().'">'.Display::getMdiIcon('format-list-text', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List View')).' '.get_lang('List View').'</a>';
        } else {
            $header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&exportpdf=&selectcat='.$catobj->get_id().'" target="_blank">'.Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to PDF')).'</a>';
        }
        $header .= '</div>';
        echo $header;
    }

    /**
     * @param int $userId
     * @param int $categoryId
     *
     * @return string
     */
    public static function display_header_user($userId, $categoryId)
    {
        $user = api_get_user_info($userId);
        if (empty($user)) {
            return '';
        }

        $catcourse = Category::load($categoryId);
        $scoredisplay = ScoreDisplay::instance();

        // generating the total score for a course
        $allevals = $catcourse[0]->get_evaluations(
            $userId,
            true,
            api_get_course_int_id()
        );
        $alllinks = $catcourse[0]->get_links(
            $userId,
            true,
            api_get_course_int_id()
        );
        $evals_links = array_merge($allevals, $alllinks);
        $item_value = 0;
        $item_total = 0;
        for ($count = 0; $count < count($evals_links); $count++) {
            $item = $evals_links[$count];
            $score = $item->calc_score($userId);
            if ($score) {
                $my_score_denom = (0 == $score[1]) ? 1 : $score[1];
                $item_value += $score[0] / $my_score_denom * $item->get_weight();
            }
            $item_total += $item->get_weight();
        }
        $item_value = api_number_format($item_value, 2);
        $total_score = [$item_value, $item_total];
        $scorecourse_display = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT);

        $info = '<div class="row"><div class="col-md-3">';
        $info .= '<div class="thumbnail"><img src="'.$user['avatar'].'" /></div>';
        $info .= '</div>';
        $info .= '<div class="col-md-6">';
        $info .= get_lang('Name').' :  '.$user['complete_name_with_message_link'].'<br />';

        if ('true' == api_get_setting('show_email_addresses')) {
            $info .= get_lang('E-mail').' : <a href="mailto:'.$user['email'].'">'.$user['email'].'</a><br />';
        }

        $info .= get_lang('Total for user').' : <b>'.$scorecourse_display.'</b>';
        $info .= '</div>';
        $info .= '</div>';
        echo $info;
    }
}
