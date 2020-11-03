<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is a learning path creation and player tool in Chamilo - previously
 * learnpath_handler.php.
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
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
/*
$priceExtraField = ExtraField::getDisplayNameByVariable('price');
if($priceExtraField != null)
{
    $extraField['price']=$priceExtraField;
}
*/
$form = new FormValidator('configure_homepage_'.$action,
    'post',
    $_SERVER['REQUEST_URI'].'&sub_action=author_view',
    '',
    ['style' => 'margin: 0px;']);;

$priceExtraField = ExtraField::getDisplayNameByVariable('IsAuthor');
if ($priceExtraField != null) {
    $extraField['IsAuthor'] = $priceExtraField;
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
// Segunda columna
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
foreach ($learnPath->authorsAvaible as $key => $value) {
    $authorId = $value['id'];
    //add border line bottom
    $form->addHtml('<div class="col-xs-12 row" style="border-bottom: 1px solid #dddddd;">');
    //add name of author
    $form->addHtml('<div class="col-xs-12 label-price">'.$value['complete_name'].'</div>');
    foreach ($_SESSION['oLP']->items as $item) {
        $itemId = $item->db_id;
        $extraFieldValues = $extraFieldValue->get_values_by_handler_and_field_variable(
            $itemId,
            'IsAuthorItem'
        );
        $itemName = $item->name;
        $labelNameField = 'authorItemSelect['.$authorId.']['.$itemId.']';
        //add item name inline
        $form->addHtml('<div class="col-xs-12 col-md-4 col-lg-4 row" style=" display: flex; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;">');
        $form->addCheckBox($labelNameField, null, Display::return_icon('lp_document.png', $itemName).$itemName);
        $form->addHtml('</div>');
        // search by my value
        if ($extraFieldValues != false) {
            if (is_numeric(array_search($authorId, explode(';', $extraFieldValues['value'])))) {
                $default[$labelNameField] = true;
            }
        }

    }
    $form->addHtml('</div>');
}
$form->addHtml('</div>');

$form->addButtonCreate(get_lang('Send'));
$form->setDefaults($default);
$form->display();
echo '</div>';
echo '</div>';
if ($form->validate()) {
    if (isset($_GET['sub_action']) && ($_GET['sub_action'] === 'author_view')) {
        $items = $_POST['authorItemSelect'];
        $saveExtraFieldItem = [];
        foreach ($learnPath->authorsAvaible as $key => $value) {
            $authorId = $value['id'];
            foreach ($_SESSION['oLP']->items as $item) {
                $itemId = $item->db_id;
                if (isset($items[$authorId])) {
                    if (isset($items[$authorId][$itemId])) {
                        $saveExtraFieldItem[$itemId][$authorId] = $authorId;
                    }
                }
            }
        }
        if (count($saveExtraFieldItem) > 0) {
            foreach ($saveExtraFieldItem as $saveItemId => $values) {
                $extraFieldValues = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $saveItemId,
                    'IsAuthorItem'
                );
                $extraFieldValue->save([
                    'variable' => 'IsAuthorItem',
                    'value' => $values,
                    'item_id' => $saveItemId,
                ]);
            }
        }

    }
}
Display::display_footer();
