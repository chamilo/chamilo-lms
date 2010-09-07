<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for courses categories
* @author Christian Fasanando <christian1827@gmail.com> - Beeznest
* @package chamilo.auth
*/

$stok = Security::get_token();

?>
<!-- Actions: The menu with the different options in cathe course management -->

<?php if(intval($_GET['hidden_links']) != 1) { ?>

<div id="actions" class="actions">
    <?php if ($action != 'sortmycourses' && isset($action)) { ?>
            &nbsp;&nbsp;<a href="<?php echo api_get_self(); ?>?action=sortmycourses"><?php echo Display::return_icon('deplacer_fichier.gif', get_lang('SortMyCourses')).' '.get_lang('SortMyCourses'); ?></a>&nbsp;
    <?php } else { ?>
            &nbsp;&nbsp;<strong><?php echo Display::return_icon('deplacer_fichier.gif', get_lang('SortMyCourses')).' '.get_lang('SortMyCourses'); ?></strong>&nbsp;
    <?php } ?>
    &nbsp;

    <?php if ($action != 'createcoursecategory') { ?>
	&nbsp;&nbsp;<a href="<?php echo api_get_self(); ?>?action=createcoursecategory"><?php echo Display::return_icon('folder_new.gif', get_lang('CreateCourseCategory')).' '.get_lang('CreateCourseCategory'); ?></a>&nbsp;
    <?php } else { ?>
        &nbsp;&nbsp;<strong><?php echo Display::return_icon('folder_new.gif', get_lang('CreateCourseCategory')).' '.get_lang('CreateCourseCategory'); ?></strong>&nbsp;
    <?php } ?>
    &nbsp;

    <?php if ($action != 'subscribe') { ?>
        &nbsp;&nbsp;<a href="<?php echo api_get_self(); ?>?action=subscribe"><?php echo Display::return_icon('view_more_stats.gif', get_lang('SubscribeToCourse')).' '.get_lang('SubscribeToCourse'); ?></a>&nbsp;
    <?php } else { ?>
        &nbsp;&nbsp;<strong><?php echo Display::return_icon('view_more_stats.gif', get_lang('SubscribeToCourse')).' '.get_lang('SubscribeToCourse'); ?></strong>&nbsp;
    <?php } ?>
</div>

<?php } ?>

<div id="categories-content" >

    <div id="categories-content-first">

        <div id="categories-search">

            <p><strong><?php echo get_lang('SearchCourse'); ?></strong><br />
            <form class="course_list" method="post" action="<?php echo api_get_self(); ?>?action=subscribe&hidden_links=1">
                <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                <input type="hidden" name="search_course" value="1" />
                <input type="text" size="12" name="search_term" value="<?php echo (empty($_POST['search_term']) ? '' : Security::remove_XSS($_POST['search_term'])); ?>" />
                &nbsp;<button class="search" type="submit"><?php echo get_lang('_search'); ?></button>
            </form>
	
        </div>

        <div id="categories-list">

            <?php if (!empty($browse_course_categories)) {

                    // level 1
                    foreach ($browse_course_categories[0] as $category) {
                        $category_name = $category['name'];
                        $category_code = $category['code'];
                        $count_courses_lv1 = $category['count_courses'];

                        if ($code == $category_code) {
                            $category_link = '<strong>'.$category_name.' ('.$count_courses_lv1.')</strong>';
                        } else {
                            $category_link = '<a href="'. api_get_self().'?action=display_courses&category_code='.$category_code.'&hidden_links=1">'.$category_name.'</a> ('.$count_courses_lv1.')';
                        }

                        echo '<div>'.$category_link.'</div>';
                        // level 2
                        if (!empty($browse_course_categories[$category_code])) {
                            foreach ($browse_course_categories[$category_code] as $subcategory1) {
                                $subcategory1_name = $subcategory1['name'];
                                $subcategory1_code = $subcategory1['code'];
                                $count_courses_lv2 = $subcategory1['count_courses'];
                                if ($code == $subcategory1_code) {
                                    $subcategory1_link = '<strong>'.$subcategory1_name.' ('.$count_courses_lv2.')</strong>';
                                } else {
                                    $subcategory1_link = '<a href="'. api_get_self().'?action=display_courses&category_code='.$subcategory1_code.'&hidden_links=1">'.$subcategory1_name.'</a> ('.$count_courses_lv2.')';
                                }
                                echo '<div style="margin-left:20px;">'.$subcategory1_link.'</div>';
                            }
                            // level 3
                            if (!empty($browse_course_categories[$subcategory1_code])) {
                                foreach ($browse_course_categories[$subcategory1_code] as $subcategory2) {
                                    $subcategory2_name = $subcategory2['name'];
                                    $subcategory2_code = $subcategory2['code'];
                                    $count_courses_lv3 = $subcategory2['count_courses'];
                                    if ($code == $subcategory2_code) {
                                        $subcategory2_link = '<strong>'.$subcategory2_name.' ('.$count_courses_lv3.')</strong>';
                                    } else {
                                        $subcategory2_link = '<a href="'. api_get_self().'?action=display_courses&category_code='.$subcategory2_code.'&hidden_links=1">'.$subcategory2_name.'</a> ('.$count_courses_lv3.')';
                                    }
                                    echo '<div style="margin-left:40px;">'.$subcategory2_link.'</div>';
                                }
                            }
                        }


                    }
            ?>


            <?php } ?>


        </div>

    </div>

    <div id="categories-content-second">

        <?php if (!empty($message)) { Display::display_confirmation_message($message, false); } ?>
        <?php if (!empty($error)) { Display::display_error_message($error, false); } ?>
        <?php

        if (!empty($search_term)) {

            echo "<p><strong>".get_lang('SearchResultsFor')." ".api_htmlentities($_POST['search_term'], ENT_QUOTES, api_get_system_encoding())."</strong><br />";

        }

        if (!empty($browse_courses_in_category)) {

            foreach ($browse_courses_in_category as $course) {

                $title = $course['title'];
                $tutor_name = $course['tutor'];
                $creation_date = $course['creation_date'];
                $count_connections = $course['count_connections'];


                $course_path = api_get_path(SYS_COURSE_PATH).$course['directory'];   // course path

                if (file_exists($course_path.'/course-pic85x85.png')) {
                    $course_web_path = api_get_path(WEB_COURSE_PATH).$course['directory'];   // course web path
                    $course_medium_image = $course_web_path.'/course-pic85x85.png'; // redimensioned image 85x85
                } else {
                    $course_medium_image = api_get_path(WEB_IMG_PATH).'without_picture.png'; // without picture
                }

                echo '<div class="categories-block-course">
                        <div class="categories-content-course">

                            <div class="categories-course-picture">
                                <img src="'.$course_medium_image.'" />
                            </div>
                            <div class="categories-course-description">
                                <div class="course-block-text" style="text-align:center;"><strong>'.strtoupper($title).'</strong></div>
                                <div class="course-block-text"><strong>'.get_lang('TutorName').':</strong> <br />'.$tutor_name.'</div>
                                <div class="course-block-text"><strong>'.get_lang('CreationDate').':</strong><br />'.$creation_date.'</div>
                                <div class="course-block-text"><strong>'.get_lang('ConexionsLastMonth').':</strong>'.$count_connections.'</div>
                            </div>

                        </div>
                        <div style="clear:both;"></div>
                        <div class="categories-course-links">';

                        if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
                            echo '<span class="course-link-desc"><a href="'.api_get_path(WEB_CODE_PATH).'inc/ajax/course_home.ajax.php?a=show_course_information&code='.$course['code'].'" title="'.$icon_title.'" rel="gb_page_center[778]">'.get_lang('CourseDetails').'</a></span>';
                        }

                        // we display the icon to subscribe or the text already subscribed
                        if (!in_array($course['code'], $user_coursecodes)) {
                                if ($course['subscribe'] == SUBSCRIBE_ALLOWED) {
                                        echo '<span class="course-link-desc"><a href="'. api_get_self().'?action=subscribe_course&sec_token='.$stok.'&subscribe_course='.$course['code'].'&search_term='.$search_term.'&category_code='.$code.'">'.get_lang('Subscribe').'</a></span>';
                                }
                        }
                     echo  '</div>
                    </div>';
            }

        } else {
            echo '<div id="course-message">'.get_lang('ThereAreNoCoursesInThisCategory').'</div>';
        }


        ?>

        <div class="clear"></div>
    </div>

</div>