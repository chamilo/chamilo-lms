<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for courses
* @author Christian Fasanando <christian1827@gmail.com> - Beeznest
* @package chamilo.auth
*/

// Acces rights: anonymous users can't do anything usefull here.
api_block_anonymous_users();
$stok = Security::get_token();
$courses_without_category = $courses_in_category[0];

?>

<!-- Actions: The menu with the different options in cathe course management -->
<div id="actions" class="actions">
    <?php if ($action != 'subscribe') { ?>
        &nbsp;<a href="<?php echo api_get_self(); ?>?action=subscribe"><?php echo Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('SubscribeToCourse'),'','32'); ?></a>
    <?php } ?>

    <?php if ($action != 'sortmycourses' && isset($action)) { ?>
        &nbsp;<a href="<?php echo api_get_self(); ?>?action=sortmycourses"><?php echo Display::return_icon('course_move.png', get_lang('SortMyCourses'),'','32'); ?></a>
    <?php } ?>

    <?php if ($action != 'createcoursecategory') { ?>
	&nbsp;<a href="<?php echo api_get_self(); ?>?action=createcoursecategory"><?php echo Display::return_icon('new_folder.png', get_lang('CreateCourseCategory'),'','32'); ?></a>
    <?php } ?>
</div>


<table cellpadding="4">

    <?php if (!empty($message)) { Display::display_confirmation_message($message, false); } ?>        

    <?php
        // COURSES WITHOUT CATEGORY
        if (!empty($courses_without_category)) {
            $number_of_courses = count($courses_without_category);
            $key = 0;
            foreach ($courses_without_category as $course) { ?>
                   <tr>

                    <?php if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
                            $icon_title = get_lang('CourseDetails') . ' - ' . $course['title'];
                    ?>
                            <td>                            
                            <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>inc/ajax/course_home.ajax.php?a=show_course_information&code=<?php echo $course['code'] ?>" title="<?php echo $icon_title ?>" rel="gb_page_center[778]"><?php echo Display::return_icon('info.png', $icon_title, '','22'); ?></a>
                            </td>
                    <?php } ?>

                    <td>
                        <a name="course<?php echo $course['code']; ?>"></a>
                        <strong><?php echo $course['title']; ?></strong><br />
                    <?php
                    if (api_get_setting('display_coursecode_in_courselist') == 'true') { echo $course['visual_code']; }
                    if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') { echo " - "; }
                    if (api_get_setting('display_teacher_in_courselist') == 'true') { echo $course['tutor']; }
                    ?>
                    </td>
                    
                    <td valign="top">

                    <!-- display course icons -->
                    <table><tr><td>
                    <?php if ($key > 0) { ?>
                            <a href="courses.php?action=<?php echo $action; ?>&amp;move=up&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                            <?php echo Display::display_icon('up.png', get_lang('Up'),'',22) ?>
                            </a>
                    <?php } ?>
                    </td>
                    <!-- the edit icon OR the edit dropdown list -->
                    <?php if (isset($_GET['edit']) && $course['code'] == $_GET['edit']) {
                            $edit_course = Security::remove_XSS($_GET['edit']);
                    ?>
                            <td rowspan="2" valign="top">
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
                                </form>
                            </td>
                    <?php } else { ?>
                            <td rowspan="2" valign="middle"><a href="courses.php?action=<?php echo $action; ?>&amp;edit=<?php echo $course['code']; ?>&amp;sec_token=<?php echo $stok; ?>">
                            <?php echo Display::display_icon('edit.png', get_lang('Edit'),'',22); ?>
                            </a></td>
                    <?php } ?>

                    <td rowspan="2" valign="top" class="invisible">
                    <?php if ($course['status'] != 1) {
                            if ($course['unsubscr'] == 1) {
                    ?>
                                <!-- changed link to submit to avoid action by the search tool indexer -->
                                <form action="<?php echo api_get_self(); ?>" method="post" onsubmit="javascript: if (!confirm('<?php echo addslashes(api_htmlentities(get_lang("ConfirmUnsubscribeFromCourse"), ENT_QUOTES, api_get_system_encoding())) ?>')) return false;">
                                <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                                <input type="hidden" name="unsubscribe" value="<?php echo $course['code']; ?>" />
                                <input type="image" name="unsub" style="border-color:#fff" src="<?php echo api_get_path(WEB_IMG_PATH).'/icons/22/unsubscribe_course.png'; ?>" title="<?php echo get_lang('_unsubscribe') ?>"  alt="<?php echo get_lang('_unsubscribe'); ?>" /></form>
                      <?php } else {
                                echo get_lang('UnsubscribeNotAllowed');

                            }
                        } else {
                            echo get_lang('CourseAdminUnsubscribeNotAllowed');
                        }
                      ?>
                    
                    </td>
                    </tr><tr><td>
                    <?php if ($key < $number_of_courses - 1) { ?>
                            <a href="courses.php?action=<?php echo $action; ?>&amp;move=down&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                            <?php echo Display::display_icon('down.png', get_lang('Down'),'',22); ?>
                            </a>
                    <?php } ?>
                    </td></tr></table>
                    </td>
                    </tr>
                    <?php $key++;
            }
        } ?>

        <!-- COURSES WITH CATEGORIES -->
        <?php if (!empty($user_course_categories)) {
               foreach ($user_course_categories as $row) {
                   if (isset($_GET['categoryid']) && $_GET['categoryid'] == $row['id']) {
         ?>
                            <!-- We display the edit form for the category -->
                            <tr><td colspan="2" class="user_course_category">
                            <a name="category<?php echo $row['id']; ?>"></a>
                            <form name="edit_course_category" method="post" action="courses.php?action=<?php echo $action; ?>">
                            <input type="hidden" name="edit_course_category" value="<?php echo $row['id']; ?>" />
                            <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                            <input type="text" name="title_course_category" value="<?php echo $row['title']; ?>" />
                            <button class="save" type="submit" name="submit_edit_course_category"><?php echo get_lang('Ok'); ?></button>
                            </form>
                    <?php } else { ?>
                            
                            <tr><td colspan="2" class="user_course_category">
                            <a name="category<?php echo $row['id']; ?>"></a>
                            <?php echo $row['title']; ?>
                    <?php } ?>
                            </td><td class="user_course_category">

                    <!-- display category icons -->
                    <?php $max_category_key = count($user_course_categories);
                    if ($action != 'unsubscribe') { ?>
                            <table>
                            <tr>
                            <td>
                            <?php if ($row['id'] != $user_course_categories[0]['id']) { ?>
                                    <a href="courses.php?action=<?php echo $action ?>&amp;move=up&amp;category=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>">
                                    <?php echo Display::return_icon('up.png', get_lang('Up'),'',22); ?>
                                    </a>
                            <?php } ?>
                            </td>
                            <td rowspan="2">
                            <a href="courses.php?action=sortmycourses&amp;categoryid=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>#category<?php echo $row['id']; ?>">
                            <?php echo Display::display_icon('edit.png', get_lang('Edit'),'',22); ?>
                            </a>
                            </td>
                            <td rowspan=\"2\">
                            <a href="courses.php?action=deletecoursecategory&amp;id=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>">
                            <?php echo Display::display_icon('delete.png', get_lang('Delete'), array('onclick' => "javascript: if (!confirm('".addslashes(api_htmlentities(get_lang("CourseCategoryAbout2bedeleted"), ENT_QUOTES, api_get_system_encoding()))."')) return false;"),22) ?>
                            </a>
                            </td>
                            </tr>
                            <tr>
                            <td>
                            <?php if ($row['id'] != $user_course_categories[$max_category_key - 1]['id']) { ?>
                                    <a href="courses.php?action=<?php echo $action; ?>&amp;move=down&amp;category=<?php echo $row['id']; ?>&amp;sec_token=<?php echo $stok; ?>">
                                    <?php echo Display::return_icon('down.png', get_lang('Down'),'',22); ?>
                                    </a>
                            <?php } ?>
                            </td>
                            </tr>
                            </table>
                    <?php } ?>

                            </td></tr>

                    <!-- Show the courses inside this category -->
                    <?php
                    $number_of_courses = count($courses_in_category[$row['id']]);
                    $key = 0;
                    if (!empty($courses_in_category[$row['id']])) {
                        foreach ($courses_in_category[$row['id']] as $course) {
                    ?>
                            <tr>
                                <?php if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
                                        $icon_title = get_lang('CourseDetails') . ' - ' . $course['title'];
                                ?>
                                        <td>                                        
                                        <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>inc/ajax/course_home.ajax.php?a=show_course_information&code=<?php echo $course['code'] ?>" title="<?php echo $icon_title ?>" rel="gb_page_center[778]"><?php echo Display::return_icon('info.png', $icon_title,'','22') ?></a>
                                        </td>
                                <?php } ?>

                                <td>
                                <a name="course<?php echo $course['code']; ?>"></a>
                                <strong><?php echo $course['title']; ?></strong><br />
                                <?php
                                if (api_get_setting('display_coursecode_in_courselist') == 'true') { echo $course['visual_code']; }
                                if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') { echo " - "; }
                                if (api_get_setting('display_teacher_in_courselist') == 'true') { echo $course['tutor']; }
                                ?>
                                </td>
                                <td valign="top">


                                <!-- display course icons -->
                                <table><tr><td>
                                <?php if ($key > 0) { ?>
                                        <a href="courses.php?action=<?php echo $action; ?>&amp;move=up&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                                        <?php echo Display::display_icon('up.png', get_lang('Up'),'',22); ?>
                                        </a>
                                <?php } ?>
                                </td>
                                
                                <?php if (isset($_GET['edit']) && $course['code'] == $_GET['edit']) {
                                        $edit_course = Security::remove_XSS($_GET['edit']);
                                ?>
                                        <td rowspan="2" valign="top">
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
                                        </td>

                                <?php } else { ?>
                                        <td rowspan="2" valign="middle"><a href="courses.php?action=<?php echo $action; ?>&amp;edit=<?php echo $course['code']; ?>&amp;sec_token=<?php echo $stok; ?>">
                                        <?php echo Display::display_icon('edit.png', get_lang('Edit'),'',22); ?>
                                        </a></td>
                                <?php } ?>
                                <td rowspan="2" valign="top" class="invisible">
                                <?php if ($course['status'] != 1) {
                                        if ($course['unsubscr'] == 1) {
                                ?>
                                                        
                                                        <form action="<?php echo api_get_self(); ?>" method="post" onsubmit="javascript: if (!confirm('<?php echo addslashes(api_htmlentities(get_lang("ConfirmUnsubscribeFromCourse"), ENT_QUOTES, api_get_system_encoding()))?>')) return false">
                                                        <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                                                        <input type="hidden" name="unsubscribe" value="<?php echo $course['code']; ?>" />
                                                        <input type="image" name="unsub" style="border-color:#fff" src="<?php echo api_get_path(WEB_IMG_PATH); ?>icons/22/unsubscribe_course.png" title="<?php echo get_lang('_unsubscribe') ?>"  alt="<?php echo get_lang('_unsubscribe') ?>" /></form>
                                  <?php } else {
                                                echo get_lang('UnsubscribeNotAllowed');
                                        }
                                } else {
                                        echo get_lang('CourseAdminUnsubscribeNotAllowed');
                                }
                                ?>
                                </td>
                                </tr><tr><td>
                                <?php if ($key < $number_of_courses - 1) { ?>
                                        <a href="courses.php?action=<?php echo $action; ?>&amp;move=down&amp;course=<?php echo $course['code']; ?>&amp;category=<?php echo $course['user_course_cat']; ?>&amp;sec_token=<?php echo $stok; ?>">
                                        <?php echo Display::display_icon('down.png', get_lang('Down'),'',22); ?>
                                        </a>
                                <?php } ?>
                                </td></tr></table>
                                </td>
                                </tr>
                          <?php $key++;
                        }
                    }
            }
        }

    ?>

</table>
