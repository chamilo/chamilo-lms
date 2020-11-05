<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is a learning path editor autor.
 *
 * @author Carlos Alvarado
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update
 * @author Julio Montoya  - Improving the list of templates
 *
 * @package chamilo.learnpath
 */
$this_section = SECTION_COURSES;

api_protect_course_script();

$isStudentView = isset($_REQUEST['isStudentView']) ? $_REQUEST['isStudentView'] : null;
$lpId = isset($_REQUEST['lp_id']) ? (int)$_REQUEST['lp_id'] : 0;
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;
$is_allowed_to_edit = api_is_allowed_to_edit(null, false);

$listUrl = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=view&lp_id='.$lpId.'&'.api_get_cidreq().'&isStudentView=true';
if (!$is_allowed_to_edit) {
    header("Location: $listUrl");
    exit;
}

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');

/*
echo "<pre>".var_export($learnPath->authorsAvaible,true)."</pre>";
exit();
*/

if (empty($learnPath)) {
    api_not_allowed();
}

if ($learnPath->get_lp_session_id() != api_get_session_id()) {
    // You cannot edit an LP from a base course.
    header("Location: $listUrl");
    exit;
}

$htmlHeadXtra[] = '<script>'.$learnPath->get_js_dropdown_array()."
function load_cbo(id, previousId) {
    if (!id) {
        return false;
    }

    previousId = previousId || 'previous';

    var cbo = document.getElementById(previousId);
    for (var i = cbo.length - 1; i > 0; i--) {
        cbo.options[i] = null;
    }

    var k=0;
    for (var i = 1; i <= child_name[id].length; i++){
        var option = new Option(child_name[id][i - 1], child_value[id][i - 1]);
        option.style.paddingLeft = '40px';
        cbo.options[i] = option;
        k = i;
    }

    cbo.options[k].selected = true;
    $('#' + previousId).selectpicker('refresh');
}

$(function() {
    if ($('#previous')) {
        if('parent is'+$('#idParent').val()) {
            load_cbo($('#idParent').val());
        }
    }
    $('.lp_resource_element').click(function() {
        window.location.href = $('a', this).attr('href');
    });
    CKEDITOR.on('instanceReady', function (e) {
        showTemplates('content_lp');
    });
});
</script>";

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);
$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=build&lp_id=$lpId&".api_get_cidreq(),
    'name' => $learnPath->getNameNoTags(),
];

switch ($type) {
    case 'dir':
        $interbreadcrumb[] = [
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$learnPath->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('NewStep'),
        ];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('NewChapter')];
        break;
    case 'document':
        $interbreadcrumb[] = [
            'url' => 'lp_controller.php?action=add_item&type=step&lp_id='.$learnPath->get_id().'&'.api_get_cidreq(),
            'name' => get_lang('NewStep'),
        ];
        break;
    default:
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('NewStep')];
        break;
}

if ($action === 'add_item' && $type === 'document') {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('NewDocumentCreated')];
}

// Theme calls.
$show_learn_path = true;
$lp_theme_css = $learnPath->get_theme();

Display::display_header(null, 'Path');

$suredel = trim(get_lang('AreYouSureToDeleteJS'));
?>
    <script>
        function stripslashes(str) {
            str = str.replace(/\\'/g, '\'');
            str = str.replace(/\\"/g, '"');
            str = str.replace(/\\\\/g, '\\');
            str = str.replace(/\\0/g, '\0');
            return str;
        }

        function confirmation(name) {
            name = stripslashes(name);
            if (confirm("<?php echo $suredel; ?> " + name + " ?")) {
                return true;
            } else {
                return false;
            }
        }

        $(function () {
            jQuery('.scrollbar-inner').scrollbar();

            $('#subtab ').on('click', 'a:first', function () {
                window.location.reload();
            });
            expandColumnToogle('#hide_bar_template', {
                selector: '#lp_sidebar'
            }, {
                selector: '#doc_form'
            });

            $('.lp-btn-associate-forum').on('click', function (e) {
                var associate = confirm('<?php echo get_lang('ConfirmAssociateForumToLPItem'); ?>');

                if (!associate) {
                    e.preventDefault();
                }
            });

            $('.lp-btn-dissociate-forum').on('click', function (e) {
                var dissociate = confirm('<?php echo get_lang('ConfirmDissociateForumToLPItem'); ?>');

                if (!dissociate) {
                    e.preventDefault();
                }
            });

            // hide the current template list for new documment until it tab clicked
            $('#frmModel').hide();
        });

        // document template for new document tab handler
        $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
            var id = e.target.id;
            if (id == 'subtab2') {
                $('#frmModel').show();
            } else {
                $('#frmModel').hide();
            }
        })
    </script>
<?php
$extraField = [];
$form = new FormValidator('configure_homepage_'.$action,
    'post',
    $_SERVER['REQUEST_URI'].'&sub_action=author_view',
    '',
    ['style' => 'margin: 0px;']);

$priceExtraField = ExtraField::getDisplayNameByVariable('AuthorLP');
if ($priceExtraField != null) {
    $extraField['AuthorLP'] = $priceExtraField;
}

echo $learnPath->build_action_menu(false,
    true,
    false,
    true,
    '',
    $extraField);

echo '<div class="row">';
echo '<div id="lp_sidebar" class="col-md-4">';
echo $learnPath->return_new_tree(null, false);
// Second Col
$message = isset($_REQUEST['message']) ? $_REQUEST['message'] : null;

// Show the template list.
if (($type == 'document' || $type == 'step') && !isset($_GET['file'])) {
    // Show the template list.
    echo '<div id="frmModel" class="scrollbar-inner lp-add-item">';
    echo '</div>';
}
echo '</div>';

$form->addHtml('<div id="doc_form" class="col-md-12 row">');
$extraFieldValue = new ExtraFieldValue('lp_item');
$form->addHtml('<h1 class="col-md-12 text-center">'.get_lang('LpByAuthor').'</h1>');
$default = [];
$form->addHtml('<div class="col-xs-12 row" >');
$defaultAuthor = [];
foreach ($_SESSION['oLP']->items as $item) {
    $itemName = $item->name;
    $itemId = $item->iId;
    $extraFieldValues = $extraFieldValue->get_values_by_handler_and_field_variable(
        $itemId,
        'AuthorLPItem'
    );
    if (!empty($extraFieldValues)) {
        $default["itemSelected[$itemId]"] = true;
        if ($extraFieldValues != false) {
            foreach (explode(';', $extraFieldValues['value']) as $author) {
                $defaultAuthor[$author] = $author;
            }
        }
    }
    $form->addCheckBox("itemSelected[$itemId]", null, Display::return_icon('lp_document.png', $itemName).$itemName);
}

$options = ['' => get_lang('SelectAnOption')];
$default["authorItemSelect"] = [];
$form->addHtml('</div>');
foreach ($learnPath->authorsAvaible as $key => $value) {
    $authorId = $value['id'];
    $authorName = $value['complete_name'];
    if (!empty($authorName)) {
        $options[$authorId] = $authorName;
        if (isset($defaultAuthor[$authorId])) {
            $default["authorItemSelect"][$authorId] = $authorId;
        }
    }
}
$form->addSelect('authorItemSelect', get_lang('Authors'), $options, [
    'multiple' => 'multiple',
]);
$form->addHtml('</div>');
$form->addButtonCreate(get_lang('Send'));
$form->setDefaults($default);
$form->display();
echo '</div>';
echo '</div>';
if ($form->validate()) {
    if (isset($_GET['sub_action']) && ($_GET['sub_action'] === 'author_view')) {
        $authors = $_POST['authorItemSelect'];
        $items = $_POST['itemSelected'];
        $saveExtraFieldItem = [];
        foreach ($_SESSION['oLP']->items as $item) {
            $itemName = $item->name;
            $itemId = $item->iId;
            if (isset($items[$itemId])) {
                foreach ($authors as $author) {
                    $saveExtraFieldItem[$itemId][$author] = $author;
                }
            }
        }

        if (count($saveExtraFieldItem) > 0) {
            foreach ($saveExtraFieldItem as $saveItemId => $values) {
                $extraFieldValues = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $saveItemId,
                    'AuthorLPItem'
                );
                $extraFieldValue->save([
                    'variable' => 'AuthorLPItem',
                    'value' => $values,
                    'item_id' => $saveItemId,
                ]);
            }
        }
    }
}
Display::display_footer();
