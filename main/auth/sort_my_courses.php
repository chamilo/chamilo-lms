<?php
/* For licensing terms, see /license.txt */

$cidReset = true; // Flag forcing the 'current course' reset

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$auth = new Auth();
$user_course_categories = CourseManager::get_user_course_categories(api_get_user_id());
$courses_in_category = $auth->get_courses_in_category();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$currentUrl = api_get_self();

$interbreadcrumb[] = [
    'url' => api_get_self(),
    'name' => get_lang('Sort courses'),
];

// We are moving the course of the user to a different user defined course category (=Sort My Courses).
if (isset($_POST['submit_change_course_category'])) {
    $result = $auth->updateCourseCategory($_POST['course_2_edit_category'], $_POST['course_categories']);
    if ($result) {
        Display::addFlash(
            Display::return_message(get_lang('The course has been added to the category'))
        );
    }
    header('Location: '.api_get_self());
    exit;
}

// We edit course category
if (isset($_POST['submit_edit_course_category']) &&
    isset($_POST['title_course_category'])
) {
    $result = $auth->store_edit_course_category($_POST['title_course_category'], $_POST['category_id']);
    if ($result) {
        Display::addFlash(
            Display::return_message(get_lang('Category updated'))
        );
    }

    header('Location: '.api_get_self());
    exit;
}

// We are creating a new user defined course category (= Create Course Category).
if (isset($_POST['create_course_category']) &&
    isset($_POST['title_course_category']) &&
    strlen(trim($_POST['title_course_category'])) > 0
) {
    $result = $auth->store_course_category($_POST['title_course_category']);
    if ($result) {
        Display::addFlash(
            Display::return_message(get_lang('Course category is created'))
        );
    } else {
        Display::addFlash(
            Display::return_message(
                get_lang('A course category with the same name already exists.'),
                'error'
            )
        );
    }
    header('Location: '.api_get_self());
    exit;
}

// We are moving a course or category of the user up/down the list (=Sort My Courses).
if (isset($_GET['move'])) {
    if (isset($_GET['course'])) {
        $result = $auth->move_course($_GET['move'], $_GET['course'], $_GET['category']);
        if ($result) {
            Display::addFlash(
                Display::return_message(get_lang('Courses sorted'))
            );
        }
    }
    if (isset($_GET['category']) && !isset($_GET['course'])) {
        $result = $auth->move_category($_GET['move'], $_GET['category']);
        if ($result) {
            Display::addFlash(
                Display::return_message(get_lang('Category sorting done'))
            );
        }
    }
    header('Location: '.api_get_self());
    exit;
}

switch ($action) {
    case 'edit_category':
        $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
        $categoryInfo = $auth->getUserCourseCategory($categoryId);
        if ($categoryInfo) {
            $categoryName = $categoryInfo['title'];
            $form = new FormValidator(
                'edit_course_category',
                'post',
                $currentUrl.'?action=edit_category'
            );
            $form->addText('title_course_category', get_lang('Name'));
            $form->addHidden('category_id', $categoryId);
            $form->addButtonSave(get_lang('Edit'), 'submit_edit_course_category');
            $form->setDefaults(['title_course_category' => $categoryName]);
            $form->display();
        }
        exit;
        break;
    case 'edit_course_category':
        $edit_course = (int) $_GET['course_id'];
        $defaultCategoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
        $courseInfo = api_get_course_info_by_id($edit_course);

        if (empty($courseInfo)) {
            exit;
        }

        $form = new FormValidator(
            'edit_course_category',
            'post',
            $currentUrl.'?action=edit_course_category'
        );

        $form->addHeader($courseInfo['title']);

        $options = [];
        foreach ($user_course_categories as $row) {
            $options[$row['id']] = $row['title'];
        }
        asort($options);

        $form->addSelect(
            'course_categories',
            get_lang('Categories'),
            $options,
            ['disable_js' => true, 'placeholder' => get_lang('Please select an option')]
        );
        $form->addHidden('course_2_edit_category', $edit_course);

        if (!empty($defaultCategoryId)) {
            $form->setDefaults(['course_categories' => $defaultCategoryId]);
        }
        $form->addButtonSave(get_lang('Save'), 'submit_change_course_category');
        $form->display();
        exit;
        break;
    case 'deletecoursecategory':
        // we are deleting a course category
        if (isset($_GET['id'])) {
            if (Security::check_token('get')) {
                $result = $auth->delete_course_category($_GET['id']);
                if ($result) {
                    Display::addFlash(
                        Display::return_message(get_lang('The category was deleted'))
                    );
                }
            }
        }
        header('Location: '.api_get_self());
        exit;
        break;
    case 'createcoursecategory':
        $form = new FormValidator(
            'create_course_category',
            'post',
            $currentUrl.'?action=createcoursecategory'
        );
        $form->addText('title_course_category', get_lang('Name'));
        $form->addButtonSave(get_lang('Add category'), 'create_course_category');
        $form->display();
        exit;
        break;
    case 'set_collapsable':
        if (!api_get_configuration_value('allow_user_course_category_collapsable')) {
            api_not_allowed(true);
        }

        $userId = api_get_user_id();
        $categoryId = isset($_REQUEST['categoryid']) ? (int) $_REQUEST['categoryid'] : 0;
        $option = isset($_REQUEST['option']) ? (int) $_REQUEST['option'] : 0;
        $redirect = isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : 0;

        if (empty($userId) || empty($categoryId)) {
            api_not_allowed(true);
        }

        $table = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "UPDATE $table 
                SET collapsed = $option
                WHERE user_id = $userId AND id = $categoryId";
        Database::query($sql);
        Display::addFlash(Display::return_message(get_lang('Update successful')));

        if ($redirect === 'home') {
            $url = api_get_path(WEB_PATH).'user_portal.php';
            header('Location: '.$url);
            exit;
        }

        $url = api_get_self();
        header('Location: '.$url);
        exit;
        break;
}

Display::display_header();

$stok = Security::get_token();
$courses_without_category = isset($courses_in_category[0]) ? $courses_in_category[0] : null;
echo '<div id="actions" class="actions">';
if ($action != 'createcoursecategory') {
    echo '<a class="ajax" href="'.$currentUrl.'?action=createcoursecategory">';
    echo Display::return_icon('new_folder.png', get_lang('Create a personal courses category'), '', '32');
    echo '</a>';
}
echo '</div>';

if (!empty($message)) {
    echo Display::return_message($message, 'confirm', false);
}

$allowCollapsable = api_get_configuration_value('allow_user_course_category_collapsable');
$teachersIcon = Display::return_icon('teacher.png', get_lang('Trainers'), null, ICON_SIZE_TINY);

// COURSES WITH CATEGORIES
if (!empty($user_course_categories)) {
    $counter = 0;
    $last = end($user_course_categories);
    foreach ($user_course_categories as $row) {
        echo Display::page_subheader($row['title']);
        echo '<a name="category'.$row['id'].'"></a>';
        $url = $currentUrl.'?categoryid='.$row['id'].'&sec_token='.$stok;
        if ($allowCollapsable) {
            if (isset($row['collapsed']) && $row['collapsed'] == 0) {
                echo Display::url(
                    '<i class="fa fa-folder-open"></i>',
                    $url.'&action=set_collapsable&option=1'
                );
            } else {
                echo Display::url(
                    '<i class="fa fa-folder"></i>',
                    $url.'&action=set_collapsable&option=0'
                );
            }
        }

        echo Display::url(
            Display::return_icon('edit.png', get_lang('Edit'), '', 22),
            $currentUrl.'?action=edit_category&category_id='.$row['id'].'&sec_token='.$stok,
            ['class' => 'ajax']
        );

        if (0 != $counter) {
            echo Display::url(
                Display::return_icon('up.png', get_lang('Up'), '', 22),
                $currentUrl.'?move=up&category='.$row['id'].'&sec_token='.$stok
            );
        } else {
            echo Display::return_icon('up_na.png', get_lang('Up'), '', 22);
        }
        if ($row['id'] != $last['id']) {
            echo Display::url(
                Display::return_icon('down.png', get_lang('down'), '', 22),
                $currentUrl.'?move=down&category='.$row['id'].'&sec_token='.$stok
            );
        } else {
            echo Display::return_icon('down_na.png', get_lang('down'), '', 22);
        }

        echo Display::url(
            Display::return_icon(
                'delete.png',
                get_lang('Delete'),
                [
                    'onclick' => "javascript: if (!confirm('".addslashes(
                            api_htmlentities(
                                get_lang('Are you sure you want to delete this courses category? Courses inside this category will be moved outside the categories'),
                                ENT_QUOTES,
                                api_get_system_encoding()
                            )
                        )."')) return false;",
                ],
                22
            ),
            $currentUrl.'?action=deletecoursecategory&id='.$row['id'].'&sec_token='.$stok
        );

        $counter++;
        echo '<br /><br />';
        // Show the courses inside this category
        echo '<table class="data_table">';
        $number_of_courses = isset($courses_in_category[$row['id']]) ? count($courses_in_category[$row['id']]) : 0;
        $key = 0;
        if (!empty($courses_in_category[$row['id']])) {
            foreach ($courses_in_category[$row['id']] as $course) {
                echo '<tr><td>';
                echo '<a name="course'.$course['code'].'"></a>';
                echo '<strong>'.$course['title'].'</strong>';
                echo ' ('.$course['visual_code'].')';
                echo '<br />';
                echo $teachersIcon;
                echo '&nbsp;';
                echo CourseManager::getTeacherListFromCourseCodeToString($course['code']);
                echo '<br />';

                if (api_get_setting('display_teacher_in_courselist') === 'true') {
                    echo $course['tutor'];
                }
                echo '</td><td valign="top">'; ?>
                <div style="float:left;width:110px;">
                <?php
                    if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
                        $icon_title = get_lang('Course description').' - '.$course['title']; ?>
                <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>inc/ajax/course_home.ajax.php?a=show_course_information&code=<?php echo $course['code']; ?>" data-title="<?php echo $icon_title; ?>" title="<?php echo $icon_title; ?>" class="ajax">
                    <?php
                    echo Display::return_icon('info.png', $icon_title, '', '22');
                    } ?>
                </a>
                <?php
                    echo Display::url(
                        Display::return_icon('edit.png', get_lang('Edit'), '', 22),
                        $currentUrl.'?action=edit_course_category&category_id='.$row['id'].'&course_id='.$course['real_id'].'&sec_token='.$stok,
                        ['class' => 'ajax']
                    );

                if ($key > 0) {
                    ?>
                    <a href="<?php echo $currentUrl; ?>?action=<?php echo $action; ?>&amp;move=up&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                    <?php echo Display::display_icon('up.png', get_lang('Up'), '', 22); ?>
                    </a>
                <?php
                } else {
                    echo Display::display_icon('up_na.png', get_lang('Up'), '', 22);
                }
                if ($key < $number_of_courses - 1) {
                    ?>
                    <a href="<?php echo $currentUrl; ?>?action=<?php echo $action; ?>&amp;move=down&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                    <?php echo Display::return_icon('down.png', get_lang('down'), '', 22); ?>
                    </a>
                <?php
                } else {
                    echo Display::return_icon('down_na.png', get_lang('down'), '', 22);
                } ?>
              </div>
              <div style="float:left; margin-right:10px;">
                <?php
                    if ($course['status'] != 1) {
                        if ($course['unsubscr'] == 1) {
                            ?>

                <form action="<?php echo api_get_self(); ?>" method="post" onsubmit="javascript: if (!confirm('<?php echo addslashes(api_htmlentities(get_lang("Are you sure you want to unsubscribe?"), ENT_QUOTES, api_get_system_encoding())); ?>')) return false">
                    <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                    <input type="hidden" name="unsubscribe" value="<?php echo $course['code']; ?>" />
                     <button class="btn btn-default" value="<?php echo get_lang('Unsubscribe'); ?>" name="unsub">
                    <?php echo get_lang('Unsubscribe'); ?>
                    </button>
                </form>
              </div>
                  <?php
                        }
                    }
                $key++;
            }
            echo '</table>';
        }
    }
}

echo Display::page_subheader(get_lang('No courses category'));
echo '<table class="data_table">';
// COURSES WITHOUT CATEGORY
if (!empty($courses_without_category)) {
    $number_of_courses = count($courses_without_category);
    $key = 0;
    foreach ($courses_without_category as $course) {
        echo '<tr><td>';
        echo '<a name="course'.$course['code'].'"></a>';
        echo '<strong>'.$course['title'].'</strong>';
        echo ' ('.$course['visual_code'].')';
        echo '<br />';
        echo $teachersIcon;
        echo '&nbsp;';
        echo CourseManager::getTeacherListFromCourseCodeToString($course['code']);
        echo '<br />';

        if (api_get_setting('display_teacher_in_courselist') === 'true') {
            echo $course['tutor'];
        }
        echo '</td><td valign="top">'; ?>
            <div style="float:left; width:110px">
            <?php
            if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
                $icon_title = get_lang('Course description').' - '.$course['title']; ?>
            <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>inc/ajax/course_home.ajax.php?a=show_course_information&code=<?php echo $course['code']; ?>" data-title="<?php echo $icon_title; ?>" title="<?php echo $icon_title; ?>" class="ajax">
                <?php echo Display::return_icon('info.png', $icon_title, '', '22'); ?>
            </a>
            <?php
            }
        echo '';
        if (isset($_GET['edit']) && $course['code'] == $_GET['edit']) {
            echo Display::return_icon('edit_na.png', get_lang('Edit'), '', 22);
        } else {
            echo Display::url(
                Display::return_icon('edit.png', get_lang('Edit'), '', 22),
                $currentUrl.'?action=edit_course_category&course_id='.$course['real_id'].'&'.$stok,
                ['class' => 'ajax']
            );
        }
        if ($key > 0) {
            ?>
                <a href="<?php echo $currentUrl; ?>?action=<?php echo $action; ?>&amp;move=up&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                <?php echo Display::display_icon('up.png', get_lang('Up'), '', 22); ?>
                </a>
            <?php
        } else {
            echo Display::return_icon('up_na.png', get_lang('Up'), '', 22);
        }
        if ($key < $number_of_courses - 1) {
            ?>
                <a href="<?php echo $currentUrl; ?>?action=<?php echo $action; ?>&amp;move=down&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                <?php echo Display::display_icon('down.png', get_lang('down'), '', 22); ?>
                </a>
            <?php
        } else {
            echo Display::return_icon('down_na.png', get_lang('down'), '', 22);
        } ?>
                </div>
                 <div style="float:left; margin-right:10px;">
            <?php
                if ($course['status'] != 1) {
                    if ($course['unsubscr'] == 1) {
                        ?>
                <!-- changed link to submit to avoid action by the search tool indexer -->
                <form action="<?php echo api_get_self(); ?>" method="post" onsubmit="javascript: if (!confirm('<?php echo addslashes(api_htmlentities(get_lang("Are you sure you want to unsubscribe?"), ENT_QUOTES, api_get_system_encoding())); ?>')) return false;">
                    <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                    <input type="hidden" name="unsubscribe" value="<?php echo $course['code']; ?>" />
                    <button class="btn btn-default" value="<?php echo get_lang('Unsubscribe'); ?>" name="unsub">
                        <?php echo get_lang('Unsubscribe'); ?>
                    </button>
                </form>
                </div>
              <?php
                    }
                } ?>
            </td>
            </tr>
            <?php
            $key++;
    }
}
?>
</table>
<?php
Display::display_footer();
