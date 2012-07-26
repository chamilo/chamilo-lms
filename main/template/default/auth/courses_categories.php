<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for courses categories
* @author Christian Fasanando <christian1827@gmail.com> - Beeznest
* @package chamilo.auth
*/
$stok = Security::get_token();
?>
<script type="text/javascript">
    $(document).ready( function() {
        $('.star-rating li a').live('click', function(event) {
        var id = $(this).parents('ul').attr('id');
        $('#vote_label2_' + id).html("<?php echo get_lang('Loading'); ?>");
            $.ajax({
                url: $(this).attr('data-link'),
                success: function(data) {
                    $("#rating_wrapper_"+id).html(data);
                    if(data == 'added') {
                        //$('#vote_label2_' + id).html("{'Saved'|get_lang}");
                    }
                    if(data == 'updated') {
                        //$('#vote_label2_' + id).html("{'Saved'|get_lang}");
                    }
                }
            });
        });
    });
</script>

<?php if (intval($_GET['hidden_links']) != 1) { ?>

<div class="actions">
    <form class="form-search" method="post" action="<?php echo api_get_self(); ?>?action=subscribe&amp;hidden_links=0">
        <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
        <input type="hidden" name="search_course" value="1" />
        <input type="text" name="search_term" value="<?php echo (empty($_POST['search_term']) ? '' : api_htmlentities(Security::remove_XSS($_POST['search_term']))); ?>" />
        &nbsp;<button class="search" type="submit"><?php echo get_lang('SearchCourse'); ?></button>
    </form>
</div>
<?php
    $hidden_links = 0;
} else {
    $hidden_links = 1;
}
?>
<div class="row">
    <div class="span3">
        <div id="course_category_well" class="well">
            <ul class="nav nav-list">
            <?php
            if (!empty($browse_course_categories)) {
                echo '<a class="btn" href="'.api_get_self().'?action=display_random_courses">'.get_lang('RandomPick').'</a><br /><br />';
                
                echo '<li class="nav-header">'.get_lang('CourseCategories').'</li>';

                // level 1
                foreach ($browse_course_categories[0] as $category) {
                    $category_name = $category['name'];
                    $category_code = $category['code'];
                    $count_courses_lv1 = $category['count_courses'];

                    if ($code == $category_code) {
                        $category_link = '<strong>'.$category_name.' ('.$count_courses_lv1.')</strong>';
                    } else {
                        if (!empty($count_courses_lv1)) {
                            $category_link = '<a href="'. api_get_self().'?action=display_courses&amp;category_code='.$category_code.'&amp;hidden_links='.$hidden_links.'">'.$category_name.' ('.$count_courses_lv1.') </a>';
                        } else {
                            $category_link = ''.$category_name.' ('.$count_courses_lv1.')';
                        }
                    }
                    echo '<li>'.$category_link.'</li>';

                    // level 2
                    if (!empty($browse_course_categories[$category_code])) {
                        foreach ($browse_course_categories[$category_code] as $subcategory1) {
                            $subcategory1_name = $subcategory1['name'];
                            $subcategory1_code = $subcategory1['code'];
                            $count_courses_lv2 = $subcategory1['count_courses'];
                            if ($code == $subcategory1_code) {
                                $subcategory1_link = '<strong>'.$subcategory1_name.' ('.$count_courses_lv2.')</strong>';
                            } else {
                                $subcategory1_link = '<a href="'. api_get_self().'?action=display_courses&amp;category_code='.$subcategory1_code.'&amp;hidden_links='.$hidden_links.'">'.$subcategory1_name.' ('.$count_courses_lv2.') </a> ';
                            }
                            echo '<li style="margin-left:20px;">'.$subcategory1_link.'</li>';
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
                                    $subcategory2_link = '<a href="'. api_get_self().'?action=display_courses&amp;category_code='.$subcategory2_code.'&amp;hidden_links='.$hidden_links.'">'.$subcategory2_name.'</a> ('.$count_courses_lv3.')';
                                }
                                echo '<li style="margin-left:40px;">'.$subcategory2_link.'</li>';
                            }
                        }
                    }
                }
            }
            ?>
        </div>
    </div>

    <div class="span9">
        <?php
        if (!empty($message)) { Display::display_confirmation_message($message, false); }
        if (!empty($error)) { Display::display_error_message($error, false); }

        if (!empty($search_term)) {
            echo "<p><strong>".get_lang('SearchResultsFor')." ".Security::remove_XSS($_POST['search_term'])."</strong><br />";
        }

        $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';
         

        if (!empty($browse_courses_in_category)) {

            foreach ($browse_courses_in_category as $course) {
                // if course is closed, don't show it.
                if ($course['visibility'] == COURSE_VISIBILITY_CLOSED) {
                    continue;
                }
                // course isn't closed
                $title      = cut($course['title'], 70);
                $tutor_name = $course['tutor'];

                $creation_date = substr($course['creation_date'],0,10);
                $count_connections = $course['count_connections'];

                $course_path = api_get_path(SYS_COURSE_PATH).$course['directory'];   // course path

                if (file_exists($course_path.'/course-pic85x85.png')) {
                    $course_medium_image = api_get_path(WEB_COURSE_PATH).$course['directory'].'/course-pic85x85.png'; // redimensioned image 85x85
                } else {
                    $course_medium_image = api_get_path(WEB_IMG_PATH).'without_picture.png'; // without picture
                }

                $rating = Display::return_rating_system('star_'.$course['real_id'], $ajax_url.'&amp;course_id='.$course['real_id'], $course['point_info']);

                echo '<div class="well_border"><div class="row">';
                    echo '<div class="span2">';
                        echo '<div class="thumbnail">';
                        if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
                            echo '<a class="ajax" href="'.api_get_path(WEB_CODE_PATH).'inc/ajax/course_home.ajax.php?a=show_course_information&amp;code='.$course['code'].'" title="'.$icon_title.'" rel="gb_page_center[778]">';
                            echo '<img src="'.$course_medium_image.'" />';
                            echo '</a>';
                        } else {
                            echo '<img src="'.$course_medium_image.'" />';
                        }
                        echo '</div>';//thumb
                    echo '</div>';

                    echo '<div class="span4">';
                    $teachers = CourseManager::get_teacher_list_from_course_code_to_string($course['code']);
                    $teachers = '<h5>'.$teachers.'</h5>';
                    echo '<div class="categories-course-description"><h3>'.cut($title, 60).'</h3>'.$teachers.$rating.'</div>';

                    echo '<p>';
                    // we display the icon to subscribe or the text already subscribed
                    echo '<div class="btn-toolbar">';

                    if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
                        echo '<a class="ajax btn" href="'.api_get_path(WEB_CODE_PATH).'inc/ajax/course_home.ajax.php?a=show_course_information&amp;code='.$course['code'].'" title="'.$icon_title.'" class="thickbox">'.get_lang('Description').'</a>';
                    }

                    $sub_btn = '';
                    // Register button
                    if (!api_is_anonymous() // Subscribe button
                            && ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD || $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                            && $course['subscribe'] == SUBSCRIBE_ALLOWED
                            && (!in_array($course['code'], $user_coursecodes) || empty($user_coursecodes))) {
                        $sub_btn = ' <a class="btn btn-primary" href="'. api_get_self().'?action=subscribe_course&amp;sec_token='.$stok.'&amp;subscribe_course='.$course['code'].'&amp;search_term='.$search_term.'&amp;category_code='.$code.'">'.get_lang('Subscribe').'</a>';
                    }
                    // Go To Course button (only if admin, if course public or if student already subscribed)
                    if (empty($sub_btn) && (api_is_platform_admin() 
			|| $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD 
			|| (api_user_is_login() && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
			|| in_array($course['code'], $user_coursecodes && $course['visibility'] != COURSE_VISIBILITY_CLOSED   )
                    )) {
                        echo ' <a class="btn btn-primary" href="'.  api_get_course_url($course['code']).'">'.get_lang('GoToCourse').'</a>';
                    }
                    // Print register button (if any)
                    echo $sub_btn;

                    // If user is already subscribed to the course
                    if (!api_is_anonymous() && in_array($course['code'], $user_coursecodes)) {
			if ($course['unsubscribe'] == UNSUBSCRIBE_ALLOWED) {
                            echo ' <a class="btn btn-primary" href="'. api_get_self().'?action=unsubscribe&amp;sec_token='.$stok.'&amp;unsubscribe='.$course['code'].'&amp;search_term='.$search_term.'&amp;category_code='.$code.'">'.get_lang('Unsubscribe').'</a>';
                        }
                        echo '<br />';
                        echo '<br />';
                        echo Display::label(get_lang("AlreadyRegisteredToCourse"), "info");
                    }
                    echo '</div>';

                    echo '</p>';
                    echo '</div>';

                    echo '<div class="span2">';
                        echo '<div class="course-block-popularity"><span>'.get_lang('ConnectionsLastMonth').'</span><div class="course-block-popularity-score">'.$count_connections.'</div></div>';
                    echo '</div>';
                echo '</div></div>';
            }
        } else {
            echo Display::display_warning_message(get_lang('ThereAreNoCoursesInThisCategory'));
        }
        ?>
    </div>
</div>
