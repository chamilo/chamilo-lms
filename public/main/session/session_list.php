<?php

/* For licensing terms, see /license.txt */

ini_set('memory_limit', '2024M');

/**
 * List sessions in an efficient and usable way.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

SessionManager::protectSession(null, false);

$action = $_REQUEST['action'] ?? null;
$idChecked = $_REQUEST['idChecked'] ?? null;
$idMultiple = $_REQUEST['id'] ?? null;
$listType = isset($_REQUEST['list_type']) ? Security::remove_XSS($_REQUEST['list_type']) : SessionManager::getDefaultSessionTab();
$copySessionContent = isset($_REQUEST['copy_session_content']);
$addSessionContent = 'true' === api_get_setting('session.duplicate_specific_session_content_on_session_copy');

if (!$addSessionContent) {
    $copySessionContent = false;
}

switch ($action) {
    case 'delete_multiple':
        $sessionList = explode(',', $idMultiple);
        foreach ($sessionList as $id) {
            $sessionInfo = api_get_session_info($id);
            if ($sessionInfo) {
                $response = SessionManager::delete($id);
            }
        }
        echo 1;
        exit;
    case 'delete':
        $sessionInfo = api_get_session_info($idChecked);
        if ($sessionInfo) {
            $response = SessionManager::delete($idChecked);
            if ($response) {
                Display::addFlash(
                    Display::return_message(get_lang('Deleted').': '.Security::remove_XSS($sessionInfo['title']))
                );
            }
        }
        $url = 'session_list.php';
        if ('custom' !== $listType) {
            $url = 'session_list.php?list_type='.$listType;
        }
        header('Location: '.$url);
        exit();
    case 'copy':
        $result = SessionManager::copy(
            (int) $idChecked,
            true,
            true,
            false,
            false,
            $copySessionContent
        );
        if ($result) {
            Display::addFlash(Display::return_message(get_lang('Item copied')));
        } else {
            Display::addFlash(Display::return_message(get_lang('There was an error.'), 'error'));
        }
        $url = 'session_list.php';
        if ('custom' !== $listType) {
            $url = 'session_list.php?list_type='.$listType;
        }
        header('Location: '.$url);
        exit;
    case 'copy_multiple':
        $sessionList = explode(',', $idMultiple);
        foreach ($sessionList as $id) {
            $sessionIdCopied = SessionManager::copy((int) $id);
            if ($sessionIdCopied) {
                $sessionInfo = api_get_session_info($sessionIdCopied);
                Display::addFlash(Display::return_message(get_lang('Item copied').' - '.$sessionInfo['name']));
            } else {
                Display::addFlash(Display::return_message(get_lang('There was an error.'), 'error'));
            }
        }
        $url = 'session_list.php';
        if ('custom' !== $listType) {
            $url = 'session_list.php?list_type='.$listType;
        }
        header('Location: '.$url);
        exit;
    case 'export_csv':
        $selectedSessions = explode(',', $idMultiple);
        SessionManager::exportSessionsAsCSV($selectedSessions);
        break;

    case 'export_multiple':
        $sessionList = explode(',', $idMultiple);
        SessionManager::exportSessionsAsZip($sessionList);
        break;
}

$tool_name = get_lang('Session list');
Display::display_header($tool_name);

$courseId = $_GET['course_id'] ?? null;

$sessionFilter = new FormValidator(
    'course_filter',
    'get',
    '',
    '',
    [],
    FormValidator::LAYOUT_INLINE
);
$courseSelect = $sessionFilter->addSelectAjax(
    'course_name',
    null,
    [],
    [
        'id' => 'course_name',
        'placeholder' => get_lang('Search courses'),
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course',
    ]
);

if (!empty($courseId)) {
    $courseInfo = api_get_course_info_by_id($courseId);
    $courseSelect->addOption($courseInfo['title'], $courseInfo['code'], ['selected' => 'selected']);
}

$selfUrl = api_get_self();
$actions = '
<style>
#session-table {
    width: 100%;
    overflow-x: auto;
}
#session-table .ui-jqgrid {
    max-width: 100%;
}
#session-table.sessions-grid-wrap .ui-jqgrid tr.jqgrow,
#session-table.sessions-grid-wrap .ui-jqgrid tr.jqgrow td {
    height: auto !important;
}
#session-table.sessions-grid-wrap .ui-jqgrid tr.jqgrow td {
    white-space: normal !important;
    line-height: 1.25 !important;
    padding-top: 6px !important;
    padding-bottom: 6px !important;
    vertical-align: top !important;
    overflow: visible !important;
    text-overflow: clip !important;
    word-break: break-word;
    overflow-wrap: anywhere;
}
#session-table.sessions-grid-wrap .ui-jqgrid-htable th div {
    white-space: normal !important;
    height: auto !important;
    line-height: 1.2 !important;
}
#session-table.sessions-grid-wrap .ui-jqgrid .ui-jqgrid-bdiv {
    overflow-x: auto !important;
}
#session-table #gbox_sessions,
#session-table #gview_sessions,
#session-table #gview_sessions .ui-jqgrid-hdiv,
#session-table #gview_sessions .ui-jqgrid-bdiv,
#session-table #gview_sessions .ui-jqgrid-pager {
  width: 100% !important;
}
.sessions-advanced-search-float {
    position: fixed !important;
    z-index: 3000 !important;
    width: 420px;
    max-width: calc(100vw - 24px);
    border: 1px solid #d1d5db !important;
    border-radius: 10px !important;
    background: #fff !important;
    box-shadow: 0 10px 30px rgba(0,0,0,.15);
}
.sessions-advanced-search-float .ui-jqgrid {
    font-size: 13px;
}
#sessions_pager .ui-pg-button.ui-state-hover .ch-tool-icon,
#sessions_pager .ui-pg-button:hover .ch-tool-icon,
#sessions_pager .ui-pg-button.ui-state-hover .mdi,
#sessions_pager .ui-pg-button:hover .mdi {
  color: #fff !important;
  -webkit-text-fill-color: #fff !important;
}
#sessions_pager .ui-pg-button.ui-state-hover svg,
#sessions_pager .ui-pg-button:hover svg {
  fill: #fff !important;
  stroke: #fff !important;
}
</style>
<script>
$(function() {
    $("#course_name").on("change", function() {
       var courseId = $(this).val();
       if (!courseId) {
        return;
       }
       window.location = "'.$selfUrl.'?course_id="+courseId;
    });
});
</script>';

switch ($listType) {
    case 'replication':
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&list_type=replication';
        break;
    default:
        if (!empty($courseId)) {
            $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions&course_id='.$courseId;
        } else {
            $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions';
        }
        break;
}

if (isset($_REQUEST['keyword'])) {
    //Begin with see the searchOper param
    $filter = new stdClass();
    $filter->groupOp = 'OR';
    $rule = new stdClass();
    $rule->field = 'category_name';
    $rule->op = 'in';
    $rule->data = Security::remove_XSS($_REQUEST['keyword']);
    $filter->rules[] = $rule;
    $filter->groupOp = 'OR';

    $filter = json_encode($filter);
    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?'
        .http_build_query([
            'a' => 'get_sessions',
            '_force_search' => 'true',
            'rows' => 20,
            'page' => 1,
            'sidx' => '',
            'sord' => 'asc',
            'filters' => $filter,
            'searchField' => 's.title',
            'searchString' => Security::remove_XSS($_REQUEST['keyword']),
            'searchOper' => 'in',
        ]);
}

if (isset($_REQUEST['id_category'])) {
    $sessionCategory = SessionManager::get_session_category($_REQUEST['id_category']);
    if (!empty($sessionCategory)) {
        //Begin with see the searchOper param
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?'
            .http_build_query([
                'a' => 'get_sessions',
                '_force_search' => 'true',
                'rows' => 20,
                'page' => 1,
                'sidx' => '',
                'sord' => 'asc',
                'filters' => '',
                'searchField' => 'sc.title',
                'searchString' => Security::remove_XSS($sessionCategory['title']),
                'searchOper' => 'in',
            ]);
    }
}

$url .= '&list_type='.$listType;
$result = SessionManager::getGridColumns($listType);
$columns = $result['columns'];
$column_model = $result['column_model'];
$extra_params['autowidth'] = 'true';
$extra_params['height'] = 'auto';
$extra_params['shrinkToFit'] = false;
$extra_params['forceFit'] = false;

switch ($listType) {
    case 'custom':
        $extra_params['sortname'] = 'display_end_date';
        $extra_params['sortorder'] = 'desc';
        break;
}

if (!isset($_GET['keyword'])) {
    $extra_params['postData'] = [
        'filters' => [
            'groupOp' => 'AND',
            'rules' => $result['rules'],
        ],
    ];
}

$hideSearch = ('true' === api_get_setting('session.hide_search_form_in_session_list'));
$confirmMsg = addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES));
$deleteTitle = addslashes(get_lang('Delete'));
$iconEdit = addslashes(Display::getMdiIcon('pencil', 'ch-tool-icon', null, 22, get_lang('Edit')));
$iconUsers = addslashes(Display::getMdiIcon('account-multiple-plus', 'ch-tool-icon', null, 22, get_lang('Subscribe users to this session')));
$iconCourses = addslashes(Display::getMdiIcon('book-open-page-variant', 'ch-tool-icon', null, 22, get_lang('Add courses to this session')));
$iconCopy = addslashes(Display::getMdiIcon('text-box-plus', 'ch-tool-icon', null, 22, get_lang('Copy')));
$iconDelete = addslashes(Display::getMdiIcon('delete', 'ch-tool-icon', null, 22, get_lang('Delete')));

// Optional action: copy with session content
$copyWithContentJs = '';
if ($addSessionContent) {
    $iconCopyWithContent = addslashes(
        Display::getMdiIcon('content-duplicate', 'ch-tool-icon', null, 22, get_lang('Copy with session content'))
    );
    $copyWithContentJs =
        " + '&nbsp;<a onclick=\"if(!confirm(\\'{$confirmMsg}\\')) return false;\""
        ." href=\"session_list.php?copy_session_content=1&list_type={$listType}&action=copy&idChecked=' + options.rowId + '\">"
        ."{$iconCopyWithContent}</a>'";
}

// Build JS formatter using ONLY JS concatenation (+)
$action_links = "function action_formatter(cellvalue, options, rowObject) {
    return ''
        + '<a href=\"session_edit.php?page=resume_session.php&id=' + options.rowId + '\">{$iconEdit}</a>'
        + '&nbsp;<a href=\"add_users_to_session.php?page=session_list.php&id_session=' + options.rowId + '\">{$iconUsers}</a>'
        + '&nbsp;<a href=\"add_courses_to_session.php?page=session_list.php&id_session=' + options.rowId + '\">{$iconCourses}</a>'
        + '&nbsp;<a onclick=\"if(!confirm(\\'{$confirmMsg}\\')) return false;\" href=\"session_list.php?action=copy&idChecked=' + options.rowId + '\">{$iconCopy}</a>'"
    . $copyWithContentJs .
    " + '<button type=\"button\" title=\"{$deleteTitle}\" onclick=\"if(confirm(\\'{$confirmMsg}\\')) window.location = \\'session_list.php?action=delete&idChecked=' + options.rowId + '\\';\">{$iconDelete}</button>';
}";

$urlAjaxExtraField = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?1=1';
$orderUrl = api_get_path(WEB_AJAX_PATH).'session.ajax.php?a=order';
$deleteUrl = api_get_self().'?list_type='.$listType.'&action=delete_multiple';
$copyUrl = api_get_self().'?list_type='.$listType.'&action=copy_multiple';
$exportUrl = api_get_self().'?list_type='.$listType.'&action=export_multiple';
$exportCsvUrl = api_get_self().'?list_type='.$listType.'&action=export_csv';
$extra_params['multiselect'] = true;

?>
    <script>
        function setSearchSelect(columnName) {
            $("#sessions").jqGrid("setColProp", columnName, {});
        }
        var added_cols = [];
        var original_cols = [];
        var second_filters = [];

        function clean_cols(grid, added_cols) {
            // Cleaning
            for (key in added_cols) {
                grid.hideCol(key);
            }
            grid.showCol('title');
            grid.showCol('display_start_date');
            grid.showCol('display_end_date');
            grid.showCol('course_title');
        }

        function show_cols(grid, added_cols) {
            grid.showCol('title').trigger('reloadGrid');
            for (key in added_cols) {
                grid.showCol(key);
            }
        }

        $(function() {
            date_pick_today = function(elem) {
                $(elem).datetimepicker({dateFormat: "yy-mm-dd"});
                $(elem).datetimepicker('setDate', (new Date()));
            }
            date_pick_one_month = function(elem) {
                $(elem).datetimepicker({dateFormat: "yy-mm-dd"});
                next_month = Date.today().next().month();
                $(elem).datetimepicker('setDate', next_month);
            }

            // Great hack
            register_second_select = function(elem) {
                second_filters[$(elem).val()] = $(elem);
            }

            fill_second_select = function(elem) {
                $(elem).on("change", function() {
                    composed_id = $(this).val();
                    field_id = composed_id.split("#")[0];
                    id = composed_id.split("#")[1];

                    $.ajax({
                        url: "<?php echo $urlAjaxExtraField; ?>&a=get_second_select_options",
                        dataType: "json",
                        data: "type=session&field_id="+field_id+"&option_value_id="+id,
                        success: function(data) {
                            my_select = second_filters[field_id];
                            my_select.empty();
                            $.each(data, function(index, value) {
                                my_select.append($("<option/>", {
                                    value: index,
                                    text: value
                                }));
                            });
                        }
                    });
                });
            }

            <?php
            echo Display::grid_js(
                'sessions',
                $url,
                $columns,
                $column_model,
                $extra_params,
                [],
                $action_links,
                true
            );
            ?>

            setSearchSelect("status");
            var grid = $("#sessions");

            function resizeSessionsGrid() {
                var container = document.getElementById("session-table");
                if (!container) {
                    return;
                }

                var rect = container.getBoundingClientRect();
                var newWidth = Math.floor(rect.width);

                // Avoid applying invalid widths (e.g. while hidden inside a tab)
                if (!newWidth || newWidth < 200) {
                    return;
                }

                grid.jqGrid("setGridWidth", newWidth, true);
            }

            function applySessionsTooltips() {
                // Add a tooltip with full text to each simple cell (skip action/icon cells).
                var $cells = grid.closest("#gbox_sessions").find("tr.jqgrow td");
                $cells.each(function() {
                    var $td = $(this);
                    if ($td.attr("title")) {
                        return;
                    }
                    if ($td.find("a,button,svg,i").length) {
                        return;
                    }
                    var text = $.trim($td.text());
                    if (text) {
                        $td.attr("title", text);
                    }
                });
            }

            // Re-apply width and tooltips after each data render
            grid.jqGrid("setGridParam", {
                gridComplete: function () {
                    setTimeout(function () {
                        resizeSessionsGrid();
                        applySessionsTooltips();
                    }, 0);
                }
            });

            // Ensure width is correct after page load (fonts/tabs/layout final width)
            $(window).on("load", function () {
                resizeSessionsGrid();
                applySessionsTooltips();
            });

            // If tabs affect layout, recalc when tab is shown (Bootstrap) + fallback click
            $(document).on("shown.bs.tab", 'a[data-toggle="tab"]', function () {
                setTimeout(function () {
                    resizeSessionsGrid();
                }, 50);
            });
            $(document).on("click", ".nav-tabs a", function () {
                setTimeout(function () {
                    resizeSessionsGrid();
                }, 50);
            });

            // Advanced search is enabled unless disabled by platform setting
            var advancedSearchEnabled = <?php echo (true !== $hideSearch) ? 'true' : 'false'; ?>;

            function getSearchDialog() {
                return $("#searchmodfbox_" + grid[0].id);
            }

            function positionSearchDialog() {
                var $dlg = getSearchDialog();
                if (!$dlg.length) {
                    return;
                }

                // Place it under the toolbar, aligned to the right.
                var top = 110;
                var $toolbar = $("#toolbar");
                if ($toolbar.length) {
                    // Convert document coordinates to viewport coordinates for "fixed" positioning.
                    top = $toolbar.offset().top + $toolbar.outerHeight() + 12 - $(window).scrollTop();
                    top = Math.max(70, Math.min(top, 220));
                }

                $dlg.addClass("sessions-advanced-search-float");
                $dlg.css({
                    top: top + "px",
                    right: "16px",
                    left: "auto"
                });
            }

            function hideAdvancedSearch() {
                var $dlg = getSearchDialog();
                if ($dlg.length && $dlg.is(":visible")) {
                    // jqGrid modal helper (works even with overlay:false)
                    $.jgrid.hideModal("#searchmodfbox_" + grid[0].id);
                }
                $("#sessions-advanced-search-toggle").attr("aria-expanded", "false");
            }

            function toggleAdvancedSearch() {
                if (!advancedSearchEnabled) {
                    return;
                }

                var $dlg = getSearchDialog();
                if ($dlg.length && $dlg.is(":visible")) {
                    hideAdvancedSearch();
                    return;
                }

                grid.jqGrid("searchGrid", prmSearch);
            }

            var prmSearch = {
                multipleSearch: true,
                overlay: false,
                width: "auto",
                caption: "<?php echo addslashes(get_lang('Search')); ?>",
                formclass: "data_table",

                afterShowSearch: function() {
                    // Keep the dialog floating and well-positioned.
                    positionSearchDialog();

                    // Select first elements by default (UX: avoid confusing empty selectors)
                    $(".input-elm").each(function(){
                        $(this).find("option:first").attr("selected", "selected");
                    });

                    $("#sessions-advanced-search-toggle").attr("aria-expanded", "true");
                },

                onSearch: function() {
                    var postdata = grid.jqGrid("getGridParam", "postData");

                    if (postdata && postdata.filters) {
                        filters = jQuery.parseJSON(postdata.filters);
                        clean_cols(grid, added_cols);
                        added_cols = [];
                        $.each(filters, function(key, value) {
                            if (key == "rules") {
                                $.each(value, function(subkey, subvalue) {
                                    if (subvalue.data == undefined) {
                                    }
                                    added_cols[subvalue.field] = subvalue.field;
                                });
                            }
                        });
                        show_cols(grid, added_cols);
                    }
                },

                onReset: function() {
                    clean_cols(grid, added_cols);
                },

                onClose: function() {
                    $("#sessions-advanced-search-toggle").attr("aria-expanded", "false");
                }
            };

            original_cols = grid.jqGrid("getGridParam", "colModel");

            options = {
                update: function (e, ui) {
                    var rowNum = jQuery("#sessions").getGridParam('rowNum');
                    var page = jQuery("#sessions").getGridParam('page');
                    page = page - 1;
                    var start = rowNum * page;
                    var list = jQuery('#sessions').jqGrid('getRowData');
                    var orderList = [];
                    $(list).each(function(index, e) {
                        index = index + start;
                        orderList.push({'order':index, 'id': e.id});
                    });
                    orderList = JSON.stringify(orderList);
                    $.get("<?php echo $orderUrl; ?>", "order="+orderList, function (result) {
                    });
                }
            };

            // Sortable rows
            grid.jqGrid('sortableRows', options);

            // navGrid (we disable default search button to avoid confusion; we provide our own toggle)
            grid.jqGrid("navGrid","#sessions_pager",
                {edit:false,add:false,del:true,search:false},
                {height:280,reloadAfterSubmit:false}, // edit options
                {height:280,reloadAfterSubmit:false}, // add options
                {reloadAfterSubmit:true, url: '<?php echo $deleteUrl; ?>' }, // del options
                prmSearch
            ).navButtonAdd('#sessions_pager',{
                caption:"<?php echo addslashes(Display::getMdiIcon('content-duplicate', 'ch-tool-icon', null, 22, get_lang('Copy'))); ?>",
                buttonicon:"ui-icon ui-icon-plus",
                onClickButton: function(a) {
                    var list = $("#sessions").jqGrid('getGridParam', 'selarrrow');
                    if (list.length) {
                        window.location.replace('<?php echo $copyUrl; ?>&id='+list.join(','));
                    } else {
                        alert("<?php echo addslashes(get_lang('Please select an option')); ?>");
                    }
                }
            }).navButtonAdd('#sessions_pager',{
                caption:"<?php echo addslashes(Display::getMdiIcon('archive-arrow-down', 'ch-tool-icon', null, 22, get_lang('Courses reports'))); ?>",
                buttonicon:"ui-icon ui-icon-plus",
                onClickButton: function(a) {
                    var list = $("#sessions").jqGrid('getGridParam', 'selarrrow');
                    if (list.length) {
                        window.location.replace('<?php echo $exportUrl; ?>&id='+list.join(','));
                    } else {
                        alert("<?php echo addslashes(get_lang('Please select an option')); ?>");
                    }
                },
                position:"last"
            }).navButtonAdd("#sessions_pager",{
                caption:"<?php echo addslashes(Display::getMdiIcon('file-delimited-outline', 'ch-tool-icon', null, 22, get_lang('Export courses reports complete'))); ?>",
                buttonicon:"ui-icon ui-icon-plus",
                onClickButton: function(a) {
                    var list = $("#sessions").jqGrid("getGridParam", "selarrrow");
                    if (list.length) {
                        window.location.replace("<?php echo $exportCsvUrl; ?>&id="+list.join(","));
                    } else {
                        alert("<?php echo addslashes(get_lang('Please select an option')); ?>");
                    }
                },
                position:"last"
            });

            // Custom advanced search toggle (toolbar button)
            $(document).on("click", "#sessions-advanced-search-toggle", function(e) {
                e.preventDefault();
                toggleAdvancedSearch();
            });

            // Keep grid width in sync with the page width
            $(window).on("resize", function() {
                resizeSessionsGrid();
                positionSearchDialog();
            });

            // Reposition dialog on scroll (since it's fixed and we compute top)
            $(window).on("scroll", function() {
                positionSearchDialog();
            });

            // Initial paint adjustments
            setTimeout(function() {
                resizeSessionsGrid();
                applySessionsTooltips();
            }, 0);
        });
    </script>
<?php

$actionsRight = '';
$actionsLeft = '<a href="'.api_get_path(WEB_CODE_PATH).'session/session_add.php">'.
    Display::getMdiIcon('google-classroom', 'ch-tool-icon-gradient', null, 32, get_lang('Add a training session')).'</a>';
if (api_is_platform_admin()) {
    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'session/add_many_session_to_category.php">'.
        Display::getMdiIcon('tab-plus', 'ch-tool-icon-gradient', null, 32, get_lang('Add training sessions to categories')).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'session/session_category_list.php">'.
        Display::getMdiIcon('file-tree-outline', 'ch-tool-icon-gradient', null, 32, get_lang('Sessions categories list')).'</a>';
}

echo $actions;
if (api_is_platform_admin()) {
    $actionsRight .= $sessionFilter->returnForm();

    // Create a search-box
    $form = new FormValidator(
        'search_simple',
        'get',
        api_get_self().'?list_type='.$listType,
        '',
        [],
        FormValidator::LAYOUT_INLINE
    );
    $form->addElement('text', 'keyword', null, ['aria-label' => get_lang('Search')]);
    $form->addHidden('list_type', $listType);
    $form->addButtonSearch(get_lang('Search'));
    $actionsRight .= $form->returnForm();

    // Advanced search toggle (do not auto-open)
    if (true !== $hideSearch) {
        $actionsRight .=
            '<button type="button" id="sessions-advanced-search-toggle" class="btn btn-default" aria-expanded="false" title="'.api_htmlentities(get_lang('Advanced search'), ENT_QUOTES).'">'.
            Display::getMdiIcon('magnify', 'ch-tool-icon', null, 22, get_lang('Advanced search')).
            ' '.api_htmlentities(get_lang('Advanced search'), ENT_QUOTES).
            '</button>';
    }
}

echo Display::toolbarAction('toolbar', [$actionsLeft, $actionsRight]);
echo SessionManager::getSessionListTabs($listType);
echo '<div id="session-table" class="table-responsive sessions-grid-wrap">';
echo Display::grid_html('sessions');
echo '</div>';

Display::display_footer();
