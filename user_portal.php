<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use Doctrine\Common\Collections\Criteria;

/**
 * This is the index file displayed when a user is logged in on Chamilo.
 *
 * It displays:
 * - personal course list
 * - menu bar
 * Search for CONFIGURATION parameters to modify settings
 * @package chamilo.main
 * @todo Shouldn't the CONFVAL_ constant be moved to the config page? Has anybody any idea what the are used for?
 *       If these are really configuration settings then we can add those to the dokeos config settings.
 * @todo check for duplication of functions with index.php (user_portal.php is orginally a copy of index.php)
 * @todo display_digest, shouldn't this be removed and be made into an extension?
 */

/* Flag forcing the 'current course' reset, as we're not inside a course anymore */
$cidReset = true;

// For HTML editor repository.
if (isset($_SESSION['this_section'])) {
    unset($_SESSION['this_section']);
}

/* Included libraries */
require_once './main/inc/global.inc.php';

$this_section = SECTION_COURSES;

api_block_anonymous_users(); // Only users who are logged in can proceed.

$userId = api_get_user_id();

/* Constants and CONFIGURATION parameters */
$load_dirs = api_get_setting('show_documents_preview');
$displayMyCourseViewBySessionLink = api_get_setting('my_courses_view_by_session') === 'true';
$nameTools = get_lang('MyCourses');

// Load course notification by ajax
$loadNotificationsByAjax = api_get_configuration_value('user_portal_load_notification_by_ajax');
if ($loadNotificationsByAjax) {
    $htmlHeadXtra[] = '<script>
    $(function() {
        $(".course_notification").each(function(index) {
            var div = $(this);
            var id = $(this).attr("id");       
            var idList = id.split("_");
            var courseId = idList[1];
            var sessionId = idList[2];
            var status = idList[3];
            $.ajax({			
                type: "GET",
                url: "'.api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=get_notification&course_id="+courseId+"&session_id="+sessionId+"&status="+status,			
                success: function(data) {			    
                    div.append(data);			    
                }
            });
        });
    });
    </script>';
}

/*
    Header
    Include the HTTP, HTML headers plus the top banner.
*/
if ($load_dirs) {
    $url = api_get_path(WEB_AJAX_PATH).'document.ajax.php?a=document_preview';
    $folder_icon = api_get_path(WEB_IMG_PATH).'icons/22/folder.png';
    $close_icon = api_get_path(WEB_IMG_PATH).'loading1.gif';
    $htmlHeadXtra[] = '<script>
	$(document).ready(function() {
		$(".document_preview_container").hide();
		$(".document_preview").click(function() {
			var my_id = this.id;
			var course_id  = my_id.split("_")[2];
			var session_id = my_id.split("_")[3];

			//showing div
			$(".document_preview_container").hide();
			$("#document_result_" +course_id+"_" + session_id).show();

			// Loading
			var image = $("img", this);
			image.attr("src", "'.$close_icon.'");

			$.ajax({
				url: "'.$url.'",
				data: "course_id="+course_id+"&session_id="+session_id,
	            success: function(return_value) {
	            	image.attr("src", "'.$folder_icon.'");
	            	$("#document_result_" +course_id+"_" + session_id).html(return_value);
	            }
	        });

		});
	});
	</script>';
}
if ($displayMyCourseViewBySessionLink) {
    $htmlHeadXtra[] = '
    <script>
        userId = '.$userId.'
        $(document).ready(function() {
            changeMyCoursesView($.cookie("defaultMyCourseView" + userId));
        });
    
        /**
        * Keep in cookie the last teacher view for the My Courses Tab. default view, or view by session
        * @param inView
        */
        function changeMyCoursesView(inView) {
            $.cookie("defaultMyCourseView"+userId, inView, { expires: 365 });
            if (inView == '.IndexManager::VIEW_BY_SESSION.') {
                $("#viewBySession").addClass("btn-primary");
                $("#viewByDefault").removeClass("btn-primary");
            } else {
                $("#viewByDefault").addClass("btn-primary");
                $("#viewBySession").removeClass("btn-primary");
            }
        }
	</script>';
}

$myCourseListAsCategory = api_get_configuration_value('my_courses_list_as_category');

$controller = new IndexManager(get_lang('MyCourses'));

if (!$myCourseListAsCategory) {
    // Main courses and session list
    if (isset($_COOKIE['defaultMyCourseView'.$userId]) &&
        $_COOKIE['defaultMyCourseView'.$userId] == IndexManager::VIEW_BY_SESSION &&
        $displayMyCourseViewBySessionLink
    ) {
        $courseAndSessions = $controller->returnCoursesAndSessionsViewBySession($userId);
        IndexManager::setDefaultMyCourseView(IndexManager::VIEW_BY_SESSION, $userId);
    } else {
        $courseAndSessions = $controller->returnCoursesAndSessions($userId);
        IndexManager::setDefaultMyCourseView(IndexManager::VIEW_BY_DEFAULT, $userId);
    }

    // if teacher, session coach or admin, display the button to change te course view

    if ($displayMyCourseViewBySessionLink
        && (api_is_drh() || api_is_session_general_coach() || api_is_platform_admin() || api_is_session_admin()
            || api_is_teacher())
    ) {
        $courseAndSessions['html'] = "
            <div class='view-by-session-link'>
                <div class='btn-group pull-right'>
                    <a class='btn btn-default' id='viewByDefault' href='user_portal.php'
                        onclick='changeMyCoursesView(\"".IndexManager::VIEW_BY_DEFAULT."\")'>
                        ".get_lang('MyCoursesDefaultView')."
                    </a>
                    <a class='btn btn-default' id='viewBySession' href='user_portal.php'
                        onclick='changeMyCoursesView(\"".IndexManager::VIEW_BY_SESSION."\")'>
                        ".get_lang('MyCoursesSessionView')."
                    </a>
                </div>
            </div>
            <br /><br />
        ".$courseAndSessions['html'];
    }
} else {
    $categoryCode = isset($_GET['category']) ? $_GET['category'] : '';

    if (!$categoryCode) {
        $courseAndSessions = $controller->returnCourseCategoryListFromUser($userId);
    } else {
        $courseAndSessions = $controller->returnCoursesAndSessions(
            $userId,
            false,
            $categoryCode
        );
        $getCategory = CourseCategory::getCategory($categoryCode);
        $controller->tpl->assign('category', $getCategory);
    }
}

// Check if a user is enrolled only in one course for going directly to the course after the login.
if (api_get_setting('go_to_course_after_login') == 'true') {
    $count_of_sessions = $courseAndSessions['session_count'];
    $count_of_courses_no_sessions = $courseAndSessions['course_count'];
    // User is subscribe in 1 session and 0 courses.
    if ($count_of_sessions == 1 && $count_of_courses_no_sessions == 0) {
        $sessions = SessionManager::get_sessions_by_user($userId);

        if (isset($sessions[0])) {
            $sessionInfo = $sessions[0];
            // Session only has 1 course.
            if (isset($sessionInfo['courses']) &&
                count($sessionInfo['courses']) == 1
            ) {
                $courseCode = $sessionInfo['courses'][0]['code'];
                $courseInfo = api_get_course_info_by_id($sessionInfo['courses'][0]['real_id']);
                $courseUrl = $courseInfo['course_public_url'].'?id_session='.$sessionInfo['session_id'];
                header('Location:'.$courseUrl);
                exit;
            }

            // Session has many courses.
            if (isset($sessionInfo['session_id'])) {
                $url = api_get_path(WEB_CODE_PATH).'session/?session_id='.$sessionInfo['session_id'];

                header('Location:'.$url);
                exit;
            }
        }
    }

    // User is subscribed to 1 course.
    if (!isset($_SESSION['coursesAlreadyVisited']) &&
        $count_of_sessions == 0 &&
        $count_of_courses_no_sessions == 1
    ) {
        $courses = CourseManager::get_courses_list_by_user_id(
            $userId
        );

        if (!empty($courses) && isset($courses[0]) && isset($courses[0]['code'])) {
            $courseInfo = api_get_course_info_by_id($courses[0]['real_id']);
            if (!empty($courseInfo)) {
                $courseUrl = $courseInfo['course_public_url'];
                header('Location:'.$courseUrl);
                exit;
            }
        }
    }
}

// Show the chamilo mascot
if (empty($courseAndSessions['html']) && !isset($_GET['history'])) {
    $controller->tpl->assign(
        'welcome_to_course_block',
        $controller->return_welcome_to_course_block()
    );
}

$controller->tpl->assign('content', $courseAndSessions['html']);

// Display the Site Use Cookie Warning Validation
$useCookieValidation = api_get_setting('cookie_warning');
if ($useCookieValidation === 'true') {
    if (isset($_POST['acceptCookies'])) {
        api_set_site_use_cookie_warning_cookie();
    } else {
        if (!api_site_use_cookie_warning_cookie_exist()) {
            if (Template::isToolBarDisplayedForUser()) {
                $controller->tpl->assign('toolBarDisplayed', true);
            } else {
                $controller->tpl->assign('toolBarDisplayed', false);
            }
            $controller->tpl->assign('displayCookieUsageWarning', true);
        }
    }
}

//check for flash and message
$sniff_notification = '';
$some_activex = isset($_SESSION['sniff_check_some_activex']) ? $_SESSION['sniff_check_some_activex'] : null;
$some_plugins = isset($_SESSION['sniff_check_some_plugins']) ? $_SESSION['sniff_check_some_plugins'] : null;

if (!empty($some_activex) || !empty($some_plugins)) {
    if (!preg_match("/flash_yes/", $some_activex) && !preg_match("/flash_yes/", $some_plugins)) {
        $sniff_notification = Display::return_message(get_lang('NoFlash'), 'warning', true);
        //js verification - To annoying of redirecting every time the page
        $controller->tpl->assign('sniff_notification', $sniff_notification);
    }
}

$controller->tpl->assign('profile_block', $controller->return_profile_block());
$controller->tpl->assign('user_image_block', $controller->return_user_image_block());
$controller->tpl->assign('course_block', $controller->return_course_block());
$controller->tpl->assign('navigation_course_links', $controller->return_navigation_links());
$controller->tpl->assign('search_block', $controller->return_search_block());
$controller->tpl->assign('classes_block', $controller->return_classes_block());
$controller->tpl->assign('skills_block', $controller->returnSkillLinks());

$historyClass = '';
if (!empty($_GET['history'])) {
    $historyClass = 'courses-history';
}
$controller->tpl->assign('course_history_page', $historyClass);
if ($myCourseListAsCategory) {
    $controller->tpl->assign('header', get_lang('MyCourses'));
}

$allow = api_get_configuration_value('gradebook_dependency');

if (!empty($courseAndSessions['courses']) && $allow) {
    $courseList = api_get_configuration_value('gradebook_dependency_mandatory_courses');
    $courseList = isset($courseList['courses']) ? $courseList['courses'] : [];
    $mandatoryCourse = [];
    if (!empty($courseList)) {
        foreach ($courseList as $courseId) {
            $courseInfo = api_get_course_info_by_id($courseId);
            $mandatoryCourse[] = $courseInfo['code'];
        }
    }

     // @todo improve calls of course info
    $subscribedCourses = !empty($courseAndSessions['courses']) ? $courseAndSessions['courses'] : [];
    $mainCategoryList = [];
    foreach ($subscribedCourses as $courseInfo) {
        $courseCode = $courseInfo['code'];
        $categories = Category::load(null, null, $courseCode);
        /** @var Category $category */
        $category = !empty($categories[0]) ? $categories[0] : [];
        if (!empty($category)) {
            $mainCategoryList[] = $category;
        }
    }

    $result = [];
    $result20 = 0;
    $result80 = 0;
    $countCoursesPassedNoDependency = 0;
    /** @var Category $category */
    foreach ($mainCategoryList as $category) {
        $userFinished = Category::userFinishedCourse(
            $userId,
            $category,
            true
        );

        if ($userFinished) {
            if (in_array($category->get_course_code(), $mandatoryCourse)) {
                if ($result20 < 20) {
                    $result20 += 10;
                }
            } else {
                $countCoursesPassedNoDependency++;
                if ($result80 < 80) {
                    $result80 += 10;
                }
            }
        }
    }

    $finalResult = $result20 + $result80;

    $gradeBookList = api_get_configuration_value('gradebook_badge_sidebar');
    $gradeBookList = isset($gradeBookList['gradebooks']) ? $gradeBookList['gradebooks'] : [];
    $badgeList = [];
    foreach ($gradeBookList as $id) {
        $categories = Category::load($id);
        /** @var Category $category */
        $category = !empty($categories[0]) ? $categories[0] : [];
        $badgeList[$id]['name'] = $category->get_name();
        $badgeList[$id]['finished'] = false;
        $badgeList[$id]['skills'] = [];
        if (!empty($category)) {
            $minToValidate = $category->getMinimumToValidate();
            $dependencies = $category->getCourseListDependency();
            $countDependenciesPassed = 0;
            foreach ($dependencies as $courseId) {
                $courseInfo = api_get_course_info_by_id($courseId);
                $courseCode = $courseInfo['code'];
                $categories = Category::load(null, null, $courseCode);
                $subCategory = !empty($categories[0]) ? $categories[0] : null;
                if (!empty($subCategory)) {
                    $score = Category::userFinishedCourse(
                        $userId,
                        $subCategory,
                        true
                    );
                    if ($score) {
                        $countDependenciesPassed++;
                    }
                }
            }

            $userFinished =
                $countDependenciesPassed == count($dependencies) &&
                $countCoursesPassedNoDependency >= $minToValidate
            ;

            if ($userFinished) {
                $objSkill = new Skill();
                $skills = $category->get_skills();
                $skillList = [];
                foreach ($skills as $skill) {
                    $skillList[] = $objSkill->get($skill['id']);
                }

                $badgeList[$id]['finished'] = true;
                $badgeList[$id]['skills'] = $skillList;
            }
        }
    }
    /*

    $categoriesNoCourseList = Category::load(null, null, '');
    $courseList = api_get_configuration_value('gradebook_dependency_mandatory_courses');
    $courseList = isset($courseList['courses']) ? $courseList['courses'] : [];
    $mandatoryCourse = [];
    if (!empty($courseList)) {
        foreach ($courseList as $courseId) {
            $courseInfo = api_get_course_info_by_id($courseId);
            $mandatoryCourse[] = $courseInfo['code'];
        }
    }

    // @todo improve calls of course info
    $subscribedCourses = !empty($courseAndSessions['courses']) ? $courseAndSessions['courses'] : [];
    $mainCategoryList = [];
    foreach ($subscribedCourses as $courseInfo) {
        $courseCode = $courseInfo['code'];
        $categories = Category::load(null, null, $courseCode);
        $category = !empty($categories[0]) ? $categories[0] : [];
        if (!empty($category)) {
            $mainCategoryList[] = $category;
        }
    }
    $total = [];
    foreach ($mainCategoryList as $category) {
        $parentScore = Category::getCurrentScore(
            $userId,
            $category,
            true
        );

        $dependencies = $category->getCourseListDependency();
        $children = [];
        $totalScoreWithChildren = 0;
        if (!empty($dependencies)) {
            foreach ($dependencies as $courseId) {
                $courseInfo = api_get_course_info_by_id($courseId);
                $courseCode = $courseInfo['code'];
                $categories = Category::load(null, null, $courseCode);
                $subCategory = !empty($categories[0]) ? $categories[0] : null;
                if (!empty($subCategory)) {
                    $score = Category::getCurrentScore(
                        $userId,
                        $subCategory,
                        true
                    );
                    $totalScoreWithChildren += $score;
                    $children[$subCategory->get_course_code()] = ['score' => $score];
                }
            }
        }
        $totalScoreWithChildren += $parentScore;
        $totalScoreWithChildrenAverage = $parentScore;
        if (!empty($children)) {
            $totalScoreWithChildrenAverage = $totalScoreWithChildren / (1 + count($children));
        }

        $total[$category->get_course_code()] = [
            'score' => $parentScore,
            'total_score_with_children' => api_number_format($totalScoreWithChildrenAverage),
            'children' => $children,
            'min_validated' => $category->getMinimumToValidate()
        ];
    }

    $countTotalGradeBookValidated = 0;
    foreach ($total as $courseCode => $data) {
        $totalScoreWithChildren = $data['total_score_with_children'];
        if ($totalScoreWithChildren == 100) {
            $countTotalGradeBookValidated++;
        }
    }


    $finalScore = 0;
    $customTotalPercentage = 0;
    $maxPercentage = 80;
    $maxCustomPercentageCounter = 0;
    $validatedCoursesPercentage = 0;
    $resultPerCategory = [];

    $mandatoryPercentage = 0;
    $nonMandatoryPercentage = 0;

    foreach ($categoriesNoCourseList as $category) {
        $dependencies = $category->getCourseListDependency();
        $minValidated = $category->getMinimumToValidate();
        $resultPerCategory[$category->get_id()] = '';
        $subTotal = 0;
        if (!empty($dependencies)) {
            foreach ($dependencies as $courseId) {
                $courseInfo = api_get_course_info_by_id($courseId);
                $courseCode = $courseInfo['code'];
                if (in_array($courseCode, $mandatoryCourse)) {
                    if ($mandatoryPercentage <= 20) {
                        $mandatoryPercentage += 10;
                    }
                } else {
                    if ($nonMandatoryPercentage <= 80) {
                        $nonMandatoryPercentage += 10;
                    }
                }
                if (isset($total[$courseCode])) {
                    $subTotal += $total[$courseCode]['total_score_with_children'];
                }
            }

            if ($minValidated < $countTotalGradeBookValidated) {
                $subTotal = 0;
            }
            $resultPerCategory[$category->get_id()] = $subTotal;
        }

        $completed = false;
        if ($mandatoryPercentage == 20 && $nonMandatoryPercentage == 80) {
            $completed = true;
        }
    }*/

    /*
    foreach ($total as $courseCode => $data) {
        $totalScoreWithChildren = $data['total_score_with_children'];
        if ($data['min_validated'] < $countValidated) {
            $totalScoreWithChildren = 0;
        }

        if (in_array($courseCode, $mandatoryCourse)) {
            if ($totalScoreWithChildren == 100) {
                $finalScore = 0;
                $customTotalPercentage += 10;
                $validatedCoursesPercentage += 10;
                break;
            }
        } else {
            $maxCustomPercentageCounter++;
            if ($customTotalPercentage < $maxPercentage &&
                $totalScoreWithChildren == 100
            ) {
                $customTotalPercentage += 10;
            }
        }
        $finalScore += $totalScoreWithChildren;
    }

    $completed = false;
    if ($validatedCoursesPercentage == 20 && $customTotalPercentage == 80) {
        $completed = true;
    }*/

    $controller->tpl->assign(
        'grade_book_sidebar',
        true
    );

    $controller->tpl->assign(
        'grade_book_progress',
        $finalResult
    );
    $controller->tpl->assign('grade_book_badge_list', $badgeList);
    /*if ($finalScore > 0) {
        $finalScore = (int) $finalScore / count($total);
        if ($finalScore == 100) {
            $completed = true;
        }
    }*/
}

$controller->tpl->display_two_col_template();

// Deleting the session_id.
Session::erase('session_id');
Session::erase('studentview');
api_remove_in_gradebook();
