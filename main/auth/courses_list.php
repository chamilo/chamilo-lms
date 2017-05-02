<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for courses
 * @todo fix this. use twig templates
* @author Christian Fasanando <christian1827@gmail.com> - Beeznest
* @package chamilo.auth
*/

// Access rights: anonymous users can't do anything usefull here.
api_block_anonymous_users();
$stok = Security::get_token();
$courses_without_category = isset($courses_in_category[0]) ? $courses_in_category[0] : null;
?>
<!-- Actions: The menu with the different options in cathe course management -->
<div id="actions" class="actions">
    <?php if ($action != 'createcoursecategory') { ?>
	<a href="<?php echo api_get_self(); ?>?action=createcoursecategory">
        <?php echo Display::return_icon('new_folder.png', get_lang('CreateCourseCategory'),'','32'); ?></a>
    <?php } ?>
</div>
<?php
if (!empty($message)) {
    Display::display_confirmation_message($message, false);
}

// COURSES WITH CATEGORIES
if (!empty($user_course_categories)) {
    $counter = 0;
    $last = end($user_course_categories);
    foreach ($user_course_categories as $row) {
        echo Display::page_subheader($row['title']);
        echo '<a name="category'.$row['id'].'"></a>';
        if (isset($_GET['categoryid']) && $_GET['categoryid'] == $row['id']) { ?>
        <!-- We display the edit form for the category -->
        <form name="edit_course_category" method="post" action="courses.php?action=<?php echo $action; ?>">
            <input type="hidden" name="edit_course_category" value="<?php echo $row['id']; ?>" />
            <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
            <input type="text" name="title_course_category" value="<?php echo $row['title']; ?>" />
            <button class="save" type="submit" name="submit_edit_course_category"><?php echo get_lang('Ok'); ?></button>
        </form>
        <?php
        }
        $max_category_key = count($user_course_categories);
        if ($action != 'unsubscribe') { ?>
            <a href="courses.php?action=sortmycourses&amp;categoryid=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>#category<?php echo $row['id']; ?>">
            <?php echo Display::display_icon('edit.png', get_lang('Edit'),'',22); ?>
            </a>
            <?php if (0 != $counter) { ?>
                    <a href="courses.php?action=<?php echo $action ?>&amp;move=up&amp;category=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>">
                    <?php echo Display::return_icon('up.png', get_lang('Up'),'',22); ?>
                    </a>
            <?php } else { ?>
                <?php echo Display::return_icon('up_na.png', get_lang('Up'),'',22); ?>
           <?php } ?>

            <?php if ($row['id'] != $last['id']) { ?>
                <a href="courses.php?action=<?php echo $action; ?>&amp;move=down&amp;category=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>">
                <?php echo Display::return_icon('down.png', get_lang('Down'),'',22); ?>
                </a>
            <?php } else { ?>
                <?php echo Display::return_icon('down_na.png', get_lang('Down'),'',22); ?>
            <?php } ?>
            <a href="courses.php?action=deletecoursecategory&amp;id=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>">
            <?php echo Display::display_icon('delete.png', get_lang('Delete'), array('onclick' => "javascript: if (!confirm('".addslashes(api_htmlentities(get_lang("CourseCategoryAbout2bedeleted"), ENT_QUOTES, api_get_system_encoding()))."')) return false;"),22) ?>
            </a>
        <?php }
        $counter++;
        echo '<br /><br />';
        // Show the courses inside this category
        echo '<table class="data_table">';
        $number_of_courses = isset($courses_in_category[$row['id']]) ? count($courses_in_category[$row['id']]) : 0;
        $key = 0;
        if (!empty($courses_in_category[$row['id']])) {
            foreach ($courses_in_category[$row['id']] as $course) {
        ?>
            <tr>
                <td>
                <a name="course<?php echo $course['code']; ?>"></a>
                <strong><?php echo $course['title']; ?></strong><br />
                <?php
                if (api_get_setting('display_coursecode_in_courselist') === 'true') {
                    echo $course['visual_code'];
                }

                if (api_get_setting('display_coursecode_in_courselist') === 'true' &&
                    api_get_setting('display_teacher_in_courselist') === 'true'
                ) {
                    echo " - ";
                }

                if (api_get_setting('display_teacher_in_courselist') === 'true') {
                    echo $course['tutor'];
                }
                ?>
                </td>
                <td valign="top">
                <!-- edit -->
                <?php
                if (isset($_GET['edit']) && $course['code'] == $_GET['edit']) {
                    $edit_course = Security::remove_XSS($_GET['edit']); ?>

                    <form name="edit_course_category" method="post" action="courses.php?action=<?php echo $action; ?>">
                    <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                    <input type="hidden" name="course_2_edit_category" value="<?php echo $edit_course; ?>" />
                    <select name="course_categories">
                    <option value="0"><?php echo get_lang("NoCourseCategory"); ?></option>
                    <?php foreach ($user_course_categories as $row) { ?>
                        <option value="<?php echo $row['id'] ?>"><?php echo $row['title']; ?></option>
                    <?php } ?>
                    </select>
                    <button class="save" type="submit" name="submit_change_course_category"><?php echo get_lang('Ok'); ?></button>
                    </form>
                <?php }  ?>
                <div style="float:left;width:110px;">
                <?php
                    if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
                        $icon_title = get_lang('CourseDetails') . ' - ' . $course['title'];
                ?>
                <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>inc/ajax/course_home.ajax.php?a=show_course_information&code=<?php echo $course['code'] ?>" data-title="<?php echo $icon_title ?>" title="<?php echo $icon_title ?>" class="ajax">
                    <?php echo Display::return_icon('info.png', $icon_title,'','22') ?>
                   <?php } ?>
                </a>

                <?php if (isset($_GET['edit']) && $course['code'] == $_GET['edit']) { ?>
                      <?php echo Display::display_icon('edit_na.png', get_lang('Edit'),'',22); ?>
                <?php } else { ?>
                    <a href="courses.php?action=<?php echo $action; ?>&amp;edit=<?php echo $course['code']; ?>&amp;sec_token=<?php echo $stok; ?>">
                        <?php echo Display::display_icon('edit.png', get_lang('Edit'),'',22); ?>
                        </a>
                <?php } ?>

                <?php if ($key > 0) { ?>
                    <a href="courses.php?action=<?php echo $action; ?>&amp;move=up&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                    <?php echo Display::display_icon('up.png', get_lang('Up'),'',22); ?>
                    </a>
                <?php } else { ?>
                    <?php echo Display::display_icon('up_na.png', get_lang('Up'),'',22); ?>
                <?php } ?>

                <?php if ($key < $number_of_courses - 1) { ?>
                    <a href="courses.php?action=<?php echo $action; ?>&amp;move=down&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                    <?php echo Display::display_icon('down.png', get_lang('Down'),'',22); ?>
                    </a>
                <?php } else { ?>
                    <?php echo Display::display_icon('down_na.png', get_lang('Down'),'',22); ?>
                <?php } ?>

              </div>
              <div style="float:left; margin-right:10px;">
                <?php
                    if ($course['status'] != 1) {
                        if ($course['unsubscr'] == 1) {
                ?>

                <form action="<?php echo api_get_self(); ?>" method="post" onsubmit="javascript: if (!confirm('<?php echo addslashes(api_htmlentities(get_lang("ConfirmUnsubscribeFromCourse"), ENT_QUOTES, api_get_system_encoding()))?>')) return false">
                    <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                    <input type="hidden" name="unsubscribe" value="<?php echo $course['code']; ?>" />
                     <button class="btn btn-default" value="<?php echo get_lang('Unsubscribe'); ?>" name="unsub">
                    <?php echo get_lang('Unsubscribe'); ?>
                    </button>
                </form>
              </div>
                  <?php }
                }
                $key++;
            }
            echo '</table>';
        }
    }
}

echo Display::page_subheader(get_lang('NoCourseCategory'));

echo '<table class="data_table">';
// COURSES WITHOUT CATEGORY
if (!empty($courses_without_category)) {
    $number_of_courses = count($courses_without_category);
    $key = 0;
    foreach ($courses_without_category as $course) {
        echo '<tr>';
        ?>
        <td>
            <a name="course<?php echo $course['code']; ?>"></a>
            <strong><?php echo $course['title']; ?></strong><br />
            <?php
            if (api_get_setting('display_coursecode_in_courselist') === 'true') { echo $course['visual_code']; }
            if (api_get_setting('display_coursecode_in_courselist') === 'true' && api_get_setting('display_teacher_in_courselist') === 'true') { echo " - "; }
            if (api_get_setting('display_teacher_in_courselist') === 'true') { echo $course['tutor']; }
            ?>
        </td>
        <td valign="top">
            <!-- the edit icon OR the edit dropdown list -->
            <?php if (isset($_GET['edit']) && $course['code'] == $_GET['edit']) {
                $edit_course = Security::remove_XSS($_GET['edit']);
            ?>
            <div style="float:left;">
            <form name="edit_course_category" method="post" action="courses.php?action=<?php echo $action; ?>">
                <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                <input type="hidden" name="course_2_edit_category" value="<?php echo $edit_course; ?>" />
                <select name="course_categories">
                    <option value="0"><?php echo get_lang("NoCourseCategory"); ?></option>
                    <?php foreach ($user_course_categories as $row) { ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['title']; ?></option>
                    <?php } ?>
                </select>
                <button class="save" type="submit" name="submit_change_course_category"><?php echo get_lang('Ok') ?></button>
            </form><br />
            </div>
            <?php } ?>

            <div style="float:left; width:110px">
            <?php
            if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
            $icon_title = get_lang('CourseDetails') . ' - ' . $course['title'];
            ?>
            <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>inc/ajax/course_home.ajax.php?a=show_course_information&code=<?php echo $course['code'] ?>" data-title="<?php echo $icon_title ?>" title="<?php echo $icon_title ?>" class="ajax">
                <?php echo Display::return_icon('info.png', $icon_title, '','22'); ?>
            </a>
            <?php } ?>

             <?php if (isset($_GET['edit']) && $course['code'] == $_GET['edit']) {   ?>
                    <?php echo Display::display_icon('edit_na.png', get_lang('Edit'),'',22); ?>
                  <?php } else { ?>
                    <a href="courses.php?action=<?php echo $action; ?>&amp;edit=<?php echo $course['code']; ?>&amp;sec_token=<?php echo $stok; ?>">
                    <?php echo Display::display_icon('edit.png', get_lang('Edit'),'',22); ?>
                    </a>
             <?php } ?>

            <!-- up /down icons-->
            <?php if ($key > 0) { ?>
                    <a href="courses.php?action=<?php echo $action; ?>&amp;move=up&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                    <?php echo Display::display_icon('up.png', get_lang('Up'),'',22) ?>
                    </a>
            <?php } else {
                    echo Display::display_icon('up_na.png', get_lang('Up'),'',22);
                    }

                    if ($key < $number_of_courses - 1) { ?>
                    <a href="courses.php?action=<?php echo $action; ?>&amp;move=down&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                    <?php echo Display::display_icon('down.png', get_lang('Down'),'',22); ?>
                    </a>
            <?php } else {
                    echo Display::display_icon('down_na.png', get_lang('Down'),'',22);
                    }?>
                </div>
                 <div style="float:left; margin-right:10px;">
                  <!-- cancel subscrioption-->
            <?php if ($course['status'] != 1) {
                    if ($course['unsubscr'] == 1) {
            ?>
                    <!-- changed link to submit to avoid action by the search tool indexer -->
                    <form action="<?php echo api_get_self(); ?>" method="post" onsubmit="javascript: if (!confirm('<?php echo addslashes(api_htmlentities(get_lang("ConfirmUnsubscribeFromCourse"), ENT_QUOTES, api_get_system_encoding())) ?>')) return false;">
                        <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                        <input type="hidden" name="unsubscribe" value="<?php echo $course['code']; ?>" />
                        <button class="btn btn-default" value="<?php echo get_lang('Unsubscribe'); ?>" name="unsub">
                            <?php echo get_lang('Unsubscribe'); ?>
                        </button>
                    </form>
                    </div>
              <?php }
                }
                ?>
            </td>
            </tr>
            <?php
            $key++;
        }
    }
?>
</table>
