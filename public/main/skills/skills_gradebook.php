<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
SkillModel::isAllowed();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'display';

// setting breadcrumbs
$tool_name = get_lang('Skills and assessments');
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('Administration'),
];
if ('add_skill' == $action) {
    $interbreadcrumb[] = ['url' => 'skills_gradebook.php', 'name' => get_lang('Skills and assessments')];
    $tool_name = get_lang('Add');
}

$gradebook = new Gradebook();
switch ($action) {
    case 'display':
        $content = $gradebook->returnGrid();
        break;
    case 'add_skill':
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $gradebook_info = $gradebook->get($id);
        $url = api_get_self().'?action='.$action.'&id='.$id;
        $form = $gradebook->show_skill_form($id, $url, $gradebook_info['title']);
        if ($form->validate()) {
            $values = $form->exportValues();
            $gradebook->updateSkillsToGradeBook($values['id'], $values['skill']);
            Display::addFlash(Display::return_message(get_lang('Item added'), 'confirm'));
            header('Location: '.api_get_self());
            exit;
        }
        $content = $form->returnForm();
        break;
}

Display::display_header($tool_name);

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_gradebooks';

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Title'),
    get_lang('Certificates'),
    get_lang('Skills'),
    get_lang('Detail'),
];

// Column config
$column_model = [
    [
        'name' => 'title',
        'index' => 'title',
        'width' => '150',
        'align' => 'left',
    ],
    [
        'name' => 'certificate',
        'index' => 'certificate',
        'width' => '25',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'skills',
        'index' => 'skills',
        'width' => '300',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '30',
        'align' => 'left',
        'formatter' => 'action_formatter',
        'sortable' => 'false',
    ],
];

$extra_params['autowidth'] = true;
$extra_params['shrinkToFit'] = true;
$extra_params['forceFit'] = true;
$extra_params['height'] = 'auto';

$iconAdd = Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_SMALL, addslashes(get_lang('Add skill')));
$iconAddNa = Display::getMdiIcon(
    ActionIcon::ADD,
    'ch-tool-icon-disabled',
    null,
    ICON_SIZE_SMALL,
    addslashes(get_lang('Your gradebook first needs a certificate in order to be linked to a skill'))
);

// With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
    // certificates
    if (rowObject[4] == 1) {
        return \'<a href="?action=add_skill&id=\'+options.rowId+\'">'.$iconAdd.'</a>'.'\';
    } else {
        return \''.$iconAddNa.'\';
    }
}';
?>
    <style>
        /* Ensure the grid wrapper can grow to full width on this page */
        #gradebooks-grid-container { width: 100% !important; }

        /* jqGrid wrappers */
        #gradebooks-grid-container .ui-jqgrid,
        #gradebooks-grid-container .ui-jqgrid-view,
        #gradebooks-grid-container .ui-jqgrid-hdiv,
        #gradebooks-grid-container .ui-jqgrid-bdiv,
        #gradebooks-grid-container .ui-jqgrid-pager,
        #gradebooks-grid-container .ui-jqgrid .ui-jqgrid-htable,
        #gradebooks-grid-container .ui-jqgrid .ui-jqgrid-btable {
            width: 100% !important;
            box-sizing: border-box;
        }

        /* Specific ids created by jqGrid */
        #gbox_gradebooks,
        #gview_gradebooks {
            width: 100% !important;
            box-sizing: border-box;
        }
    </style>

    <script>
        $(function() {
            <?php
            echo Display::grid_js(
                'gradebooks',
                $url,
                $columns,
                $column_model,
                $extra_params,
                [],
                $action_links,
                true
            );
            ?>

            // Expand the grid container if it was rendered inside a half-width bootstrap column (span6, col-6, etc).
            function expandGradebooksColumn() {
                var $anchor = $("#gbox_gradebooks");
                if (!$anchor.length) {
                    return;
                }

                // Bootstrap 2: spanX -> span12
                var $span = $anchor.closest('[class*="span"]');
                if ($span.length) {
                    var cls = $span.attr("class") || "";
                    if (/\bspan\d+\b/.test(cls) && !/\bspan12\b/.test(cls)) {
                        $span.removeClass(function (i, c) {
                            var m = c.match(/\bspan\d+\b/g);
                            return m ? m.join(" ") : "";
                        });
                        $span.addClass("span12");
                    }
                    $span.css("width", "100%");
                }

                // Bootstrap 3/4/5: col-*-X -> col-12
                var $col = $anchor.closest('[class*="col-"]');
                if ($col.length) {
                    var colCls = $col.attr("class") || "";
                    if (/\bcol-(xs|sm|md|lg|xl|xxl)-\d+\b/.test(colCls) || /\bcol-\d+\b/.test(colCls)) {
                        $col.removeClass(function (i, c) {
                            var m = c.match(/\bcol-(xs|sm|md|lg|xl|xxl)-\d+\b/g) || [];
                            var m2 = c.match(/\bcol-\d+\b/g) || [];
                            return m.concat(m2).join(" ");
                        });
                        $col.addClass("col-12");
                    }
                    $col.css("width", "100%");
                }
            }

            function getGradebooksTargetWidth() {
                // Prefer our explicit wrapper
                var $container = $("#gradebooks-grid-container");
                var w = $container.innerWidth();

                // Fallbacks if needed
                if (!w) {
                    w = $("#main_content, #content, .page-content, .container-fluid").first().innerWidth();
                }
                if (!w) {
                    w = $(window).width();
                }
                return w;
            }

            function resizeGradebooksGrid() {
                var $grid = $("#gradebooks");
                if (!$grid.length) {
                    return false;
                }

                expandGradebooksColumn();

                var newWidth = getGradebooksTargetWidth();
                if (newWidth && newWidth > 0) {
                    $grid.jqGrid("setGridWidth", newWidth, true);
                    return true;
                }
                return false;
            }

            // Retry a few times because jqGrid can finalize widths after initial DOM paint.
            function resizeWithRetry(attempt) {
                attempt = attempt || 0;

                if (resizeGradebooksGrid()) {
                    return;
                }

                if (attempt < 10) {
                    setTimeout(function () {
                        resizeWithRetry(attempt + 1);
                    }, 120);
                }
            }

            // Initial sizing
            resizeWithRetry(0);

            // Keep it responsive
            $(window).on("resize.gradebooks", function () {
                resizeGradebooksGrid();
            });
        });
    </script>
<?php

// Print content. On display action, wrap it so we can force full width reliably.
if ('display' === $action) {
    echo '<div id="gradebooks-grid-container">';
    echo $content;
    echo '</div>';
} else {
    echo $content;
}

Display::display_footer();
