<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is a learning path editor author.
 *
 * @author Carlos Alvarado
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update
 * @author Julio Montoya  - Improving the list of templates
 */
$this_section = SECTION_COURSES;

api_protect_course_script();
api_protect_admin_script(true);

$isStudentView = isset($_REQUEST['isStudentView']) ? $_REQUEST['isStudentView'] : null;
$lpId = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;
$is_allowed_to_edit = api_is_allowed_to_edit(null, false);

$listUrl = api_get_path(WEB_CODE_PATH).
    'lp/lp_controller.php?action=view&lp_id='.$lpId.'&'.api_get_cidreq().'&isStudentView=true';
if (!$is_allowed_to_edit) {
    header("Location: $listUrl");
    exit;
}

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');

if (empty($learnPath)) {
    api_not_allowed();
}

if ($learnPath->get_lp_session_id() != api_get_session_id()) {
    // You cannot edit an LP from a base course.
    header("Location: $listUrl");
    exit;
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);
$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
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

if ('add_item' === $action && 'document' === $type) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('NewDocumentCreated')];
}

// Theme calls.
$show_learn_path = true;
$lp_theme_css = $learnPath->get_theme();

$extraField = [];
$form = new FormValidator(
    'configure_homepage_'.$action,
    'post',
    api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=author_view&sub_action=author_view',
    '',
    ['style' => 'margin: 0px;']
);
$extraField['backTo'] = api_get_self().'?action=add_item&type=step&lp_id='.$lpId.'&'.api_get_cidreq();
$form->addHtml('<div id="doc_form" class="col-md-12 row">');
$extraFieldValue = new ExtraFieldValue('lp_item');
$form->addHeader(get_lang('LpByAuthor'));
$default = [];
$form->addHtml('<div class="col-xs-12 row" >');
$defaultAuthor = [];
foreach ($_SESSION['oLP']->items as $item) {
    $itemName = $item->name;
    $itemId = $item->iId;
    $extraFieldValues = $extraFieldValue->get_values_by_handler_and_field_variable($itemId, 'authorlpitem');
    $priceItem = $extraFieldValue->get_values_by_handler_and_field_variable($itemId, 'price');
    $authorName = [];
    if (!empty($extraFieldValues)) {
        if (false != $extraFieldValues) {
            $authors = explode(';', $extraFieldValues['value']);
            if (!empty($authors)) {
                foreach ($authors as $author) {
                    if (0 != $author) {
                        $defaultAuthor[$author] = $author;
                        $teacher = api_get_user_info($author);
                        $authorName[] = $teacher['complete_name'];
                    }
                }
            }
        }
    }
    if (0 != count($authorName)) {
        $authorName = " (".implode(', ', $authorName).")";
    } else {
        $authorName = '';
    }
    if (isset($priceItem['value']) && !empty($priceItem['value'])) {
        $authorName .= "<br /><small>".get_lang('Price')." (".$priceItem['value'].")</small>";
    }
    $form->addCheckBox(
        "itemSelected[$itemId]",
        null,
        Display::getMdiIcon('bookshelf', 'ch-tool-icon', null, 22, get_lang('Document')).$itemName.$authorName
    );
    $default['itemSelected'][$itemId] = false;
}

$options = [0 => get_lang('RemoveSelected')];
$default['authorItemSelect'] = [];
$form->addHtml('</div>');
$teachers = [];
$field = new ExtraField('user');
$authorLp = $field->get_handler_field_info_by_field_variable('authorlp');
$extraFieldId = isset($authorLp['id']) ? (int) $authorLp['id'] : 0;
if (0 != $extraFieldId) {
    $extraFieldValueUser = new ExtraFieldValue('user');
    $arrayExtraFieldValueUser = $extraFieldValueUser->get_item_id_from_field_variable_and_field_value(
        'authorlp',
        1,
        true,
        false,
        true
    );

    if (!empty($arrayExtraFieldValueUser)) {
        foreach ($arrayExtraFieldValueUser as $item) {
            $teacher = api_get_user_info($item['item_id']);
            $teachers[] = $teacher;
        }
    }
}

foreach ($teachers as $key => $value) {
    $authorId = $value['id'];
    $authorName = $value['complete_name'];
    if (!empty($authorName)) {
        $options[$authorId] = $authorName;
    }
}

$form->addSelect('authorItemSelect', get_lang('Authors'), $options, ['multiple' => 'multiple']);
$form->addNumeric('price', get_lang('Price'));
$form->addHtml('</div>');
$form->addButtonCreate(get_lang('Send'));
$form->setDefaults($default);

if ($form->validate()) {
    if (isset($_GET['sub_action']) && ('author_view' === $_GET['sub_action'])) {
        $authors = isset($_POST['authorItemSelect']) ? $_POST['authorItemSelect'] : [];
        $items = isset($_POST['itemSelected']) ? $_POST['itemSelected'] : [];
        $price = api_float_val($_POST['price']);
        unset($author);
        $saveExtraFieldItem = [];
        $saveAuthor = [];
        $removeExist = 0;
        foreach ($_SESSION['oLP']->items as $item) {
            $itemName = $item->name;
            $itemId = $item->iId;
            if (isset($items[$itemId])) {
                foreach ($authors as $author) {
                    if (0 == $author || 1 == $removeExist) {
                        $saveExtraFieldItem[$itemId][0] = 0;
                        $removeExist = 1;
                    } else {
                        $saveExtraFieldItem[$itemId][$author] = $author;
                    }
                }
                if ($price > 0) {
                    $extraFieldValues = $extraFieldValue->get_values_by_handler_and_field_variable(
                        $itemId,
                        'price'
                    );
                    $extraFieldValue->save([
                        'variable' => 'price',
                        'value' => $price,
                        'item_id' => $itemId,
                    ]);
                }
            }
        }

        if (count($saveExtraFieldItem) > 0 || $price > 0) {
            $lastEdited = [];
            foreach ($saveExtraFieldItem as $saveItemId => $values) {
                $extraFieldValues = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $saveItemId,
                    'authorlpitem'
                );
                $extraFieldValue->save([
                    'variable' => 'authorlpitem',
                    'value' => $values,
                    'item_id' => $saveItemId,
                ]);
                $lastEdited = $values;
                if (isset($options[$author])) {
                    $saveAuthor[] = $options[$author];
                }
            }
            $saveAuthor = array_unique($saveAuthor);
            $messages = implode(' / ', $saveAuthor);
            $currentUrl = api_request_uri();
            $redirect = false;
            if ($removeExist) {
                Display::addFlash(Display::return_message(get_lang('DeletedAuthors')));
                $redirect = true;
            } elseif ($price > 0) {
                Display::addFlash(Display::return_message(get_lang('PriceUpdated')));
                $redirect = true;
            } elseif (!empty($messages)) {
                Display::addFlash(Display::return_message(get_lang('RegisteredAuthors').' '.$messages));
                $redirect = true;
            }

            if ($redirect) {
                api_location($currentUrl);
            }
        }
    }
}

Display::display_header(null, 'Path');

echo $learnPath->build_action_menu(
    false,
    true,
    false,
    true,
    '',
    $extraField
);

echo '<div class="row">';
echo $learnPath->showBuildSideBar(null, false, $type);
$form->display();
echo '</div>';
echo '</div>';
Display::display_footer();
