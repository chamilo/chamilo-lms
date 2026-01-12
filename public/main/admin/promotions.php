<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Throwable;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

// Breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'career_dashboard.php', 'name' => get_lang('Careers and promotions')];

$action = $_GET['action'] ?? null;

$check = Security::check_token('request');
$token = Security::get_token();

$listUrl = api_get_self();

// Tool name (header context)
$toolName = get_lang('Promotions');
if ('add' === $action) {
    $toolName = get_lang('Add').' '.get_lang('Promotion');
} elseif ('edit' === $action) {
    $toolName = get_lang('Edit').' '.get_lang('Promotion');
}

/**
 * Handle destructive actions early (before rendering) to avoid "headers already sent".
 */
if ('delete' === $action) {
    $id = (int) ($_GET['id'] ?? 0);

    if ($check && $id > 0) {
        $promotion = new Promotion();
        $res = $promotion->delete($id);
        if ($res) {
            Display::addFlash(Display::return_message(get_lang('Item deleted'), 'confirmation'));
        }
    }

    header('Location: '.$listUrl);
    exit;
}

if ('copy' === $action) {
    if (0 != api_get_session_id() && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed(true);
    }

    $id = (int) ($_GET['id'] ?? 0);

    if ($check && $id > 0) {
        $promotion = new Promotion();

        try {
            // Use 0 instead of null to be compatible with stricter signatures.
            $res = $promotion->copy($id, 0, true);

            if ($res) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('Item copied').' - '.get_lang('Exercises and learning paths are invisible in the new course'),
                        'confirmation'
                    )
                );
            } else {
                Display::addFlash(Display::return_message(get_lang('An error occurred.'), 'error'));
            }
        } catch (Throwable $e) {
            // Do not expose stack traces in UI.
            Display::addFlash(Display::return_message(get_lang('An error occurred.'), 'error'));
        }
    }

    header('Location: '.$listUrl);
    exit;
}

// jqGrid AJAX URL
$ajaxUrl = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_promotions';

// Column definitions (order matters: see model.ajax.php)
$columns = [
    get_lang('Name'),
    get_lang('Career'),
    get_lang('Description'),
    get_lang('Detail'),
];

$column_model = [
    [
        'name' => 'title',
        'index' => 'title',
        'width' => '180',
        'align' => 'left',
    ],
    [
        'name' => 'career',
        'index' => 'career',
        'width' => '180',
        'align' => 'left',
    ],
    [
        'name' => 'description',
        'index' => 'description',
        'width' => '520',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '200',        // IMPORTANT: enough room for 3-4 icons
        'align' => 'left',
        'fixed' => true,         // prevent jqGrid from resizing this column
        'sortable' => 'false',
        'formatter' => 'action_formatter',
    ],
];

$extra_params = [];
$extra_params['autowidth'] = 'true';
$extra_params['height'] = 'auto';

// IMPORTANT: do not shrink columns (otherwise action icons get clipped)
$extra_params['shrinkToFit'] = 'false';
$extra_params['forceFit'] = 'false';

/**
 * Keep jqGrid full width inside the wrapper (per-page only).
 */
$extra_params['gridComplete'] = '
    var $wrap = $("#promotions-grid-wrap");
    if ($wrap.length) {
        var w = $wrap.width();
        if (w && w > 0) {
            $("#promotions").jqGrid("setGridWidth", w, true);
        }
    }
';

/**
 * IMPORTANT: Keep original PHP concatenation, otherwise JS breaks.
 */
$action_links = 'function action_formatter (cellvalue, options, rowObject) {
    return \'<a href="add_sessions_to_promotion.php?id=\'+options.rowId+\'">'.Display::getMdiIcon(ObjectIcon::SESSION, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Subscribe sessions to promotions')).'</a>'.
    '&nbsp;<a href="?action=edit&id=\'+options.rowId+\'">'.Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>'.
    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("Please confirm your choice"), ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=copy&id=\'+options.rowId+\'">'.Display::getMdiIcon(ActionIcon::COPY_CONTENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Copy')).'</a>'.
    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("Please confirm your choice"), ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&id=\'+options.rowId+\'">'.Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a> \';
}';

$promotion = new Promotion();

// Build content first (Promotion::display() echoes)
ob_start();

switch ($action) {
    case 'add':
        if (0 != api_get_session_id() && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }

        $career = new Career();
        $careers = $career->get_all();
        if (empty($careers)) {
            $msg = Display::url(
                get_lang('You will have to create a career before you can add promotions (promotions are sub-elements of a career)'),
                'careers.php?action=add'
            );
            echo Display::return_message($msg, 'normal', false);
            break;
        }

        $formUrl = api_get_self().'?action='.Security::remove_XSS($_GET['action']);
        $form = $promotion->return_form($formUrl, 'add');

        if ($form->validate()) {
            if ($check) {
                $values = $form->exportValues();
                $res = $promotion->save($values);
                if ($res) {
                    Display::addFlash(Display::return_message(get_lang('Item added'), 'confirmation'));
                }
            }
            header('Location: '.$listUrl);
            exit;
        }

        $actions = Display::url(
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            api_get_self()
        );
        echo Display::toolbarAction('promotion_actions', [$actions]);
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);
        $form->display();
        break;

    case 'edit':
        $formUrl = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.(int) ($_GET['id'] ?? 0);
        $form = $promotion->return_form($formUrl, 'edit');

        if ($form->validate()) {
            if ($check) {
                $values = $form->exportValues();
                $res = $promotion->update($values);
                $promotion->update_all_sessions_status_by_promotion_id($values['id'], $values['status']);
                if ($res) {
                    Display::addFlash(
                        Display::return_message(get_lang('Promotion updated successfully').': '.$values['title'], 'confirmation')
                    );
                }
            }
            header('Location: '.$listUrl);
            exit;
        }

        $actions = Display::url(
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            api_get_self()
        );
        echo Display::toolbarAction('promotion_actions', [$actions]);
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);
        $form->display();
        break;

    default:
        $promotion->display();
        break;
}

$content = ob_get_clean();

Display::display_header($toolName);
?>
    <style>
        #promotions-grid-wrap,
        #promotions-grid-wrap .ui-jqgrid,
        #promotions-grid-wrap .ui-jqgrid-view,
        #promotions-grid-wrap .ui-jqgrid-hdiv,
        #promotions-grid-wrap .ui-jqgrid-bdiv,
        #promotions-grid-wrap .ui-jqgrid-pager,
        #promotions-grid-wrap .ui-jqgrid .ui-jqgrid-htable,
        #promotions-grid-wrap .ui-jqgrid .ui-jqgrid-btable,
        #promotions-grid-wrap #gbox_promotions,
        #promotions-grid-wrap #gview_promotions {
            width: 100% !important;
            max-width: 100% !important;
        }

        #promotions td[aria-describedby="promotions_actions"] {
            overflow: visible !important;
            white-space: nowrap !important;
        }

        #promotions-grid-wrap .actions,
        #promotions-grid-wrap #promotion_actions {
            display: none !important;
        }
    </style>

    <div class="w-full max-w-none px-4 lg:px-6 py-4">
        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-90"><?php echo api_htmlentities(get_lang('Promotions')); ?></h1>
                <p class="text-body-2 text-gray-50 mt-1"><?php echo api_htmlentities(get_lang('Careers and promotions')); ?></p>
            </div>

            <div class="flex items-center gap-2">
                <a class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-body-2 font-semibold text-gray-90 ring-1 ring-gray-20 hover:bg-gray-10"
                   href="career_dashboard.php">
                    <?php echo Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Back')); ?>
                    <span><?php echo api_htmlentities(get_lang('Back')); ?></span>
                </a>

                <?php if (empty($action)) : ?>
                    <a class="inline-flex items-center gap-2 rounded-xl bg-primary px-3 py-2 text-body-2 font-semibold text-white hover:bg-primary/90"
                       href="<?php echo api_get_self(); ?>?action=add">
                        <?php echo Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon text-white', null, ICON_SIZE_SMALL, get_lang('Add')); ?>
                        <span><?php echo api_htmlentities(get_lang('Add')); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-20 p-3 md:p-4">
            <div id="promotions-grid-wrap" class="w-full overflow-x-auto">
                <?php echo $content; ?>
            </div>
        </div>
    </div>

    <script>
        $(function () {
            if ($("#promotions").length) {
                <?php
                echo Display::grid_js(
                    'promotions',
                    $ajaxUrl,
                    $columns,
                    $column_model,
                    $extra_params,
                    [],
                    $action_links,
                    true
                );
                ?>

                // Resize grid on window resize (per-page only)
                var resizePromotionsGrid = function () {
                    var $wrap = $("#promotions-grid-wrap");
                    if (!$wrap.length) return;

                    var w = $wrap.width();
                    if (w && w > 0) {
                        $("#promotions").jqGrid("setGridWidth", w, true);
                    }
                };

                $(window).on("resize.promotionsGrid", resizePromotionsGrid);

                // Run twice to handle late layout/font calculations
                resizePromotionsGrid();
                setTimeout(resizePromotionsGrid, 60);
            }
        });
    </script>

<?php
Display::display_footer();
