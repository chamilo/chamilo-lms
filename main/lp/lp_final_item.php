<?php
/* For licensing terms, see /license.txt */

/**
 * Print a learning path finish page with details.
 *
 * @author Jose Loguercio <jose.loguercio@beeznest.com>
 *
 * @package chamilo.learnpath
 */
$_in_course = true;

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_GRADEBOOK;

// Make sure no anonymous user gets here without permission
api_protect_course_script(true);

// Get environment variables
$courseCode = api_get_course_id();
$courseId = api_get_course_int_id();
$userId = api_get_user_id();
$sessionId = api_get_session_id();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lpId = isset($_GET['lp_id']) ? intval($_GET['lp_id']) : 0;

// This page can only be shown from inside a learning path
if (!$id && !$lpId) {
    Display::return_message(get_lang('FileNotFound'), 'warning');
    exit;
}

// Certificate and Skills Premium with Service check
$plugin = BuyCoursesPlugin::create();
$checker = $plugin->isEnabled() && $plugin->get('include_services');

if ($checker) {
    $userServiceSale = $plugin->getServiceSales(
        $userId,
        BuyCoursesPlugin::SERVICE_STATUS_COMPLETED,
        BuyCoursesPlugin::SERVICE_TYPE_LP_FINAL_ITEM,
        $lpId
    );

    if (empty($userServiceSale)) {
        // Instance a new template : No page tittle, No header, No footer
        $tpl = new Template(null, false, false);
        $url = api_get_path(WEB_PLUGIN_PATH).'buycourses/src/service_catalog.php';
        $content = sprintf(
            Display::return_message(
                get_lang('IfYouWantToGetTheCertificateAndOrSkillsAsociatedToThisCourseYouNeedToBuyTheCertificateServiceYouCanGoToServiceCatalogClickingHere'),
                'normal',
                false
            ),
            '<a href="'.$url.'">'.$url.'</a>'
        );
        $tpl->assign('content', $content);
        $tpl->display_blank_template();
        exit;
    }
}

// Initialize variables required for the template
$downloadCertificateLink = '';
$viewCertificateLink = '';
$badgeLink = '';
$finalItemTemplate = '';

// Check prerequisites and total completion of the learning path
$lp = new Learnpath($courseCode, $lpId, $userId);
$count = $lp->getTotalItemsCountWithoutDirs();
$completed = $lp->get_complete_items_count(true);
$currentItemId = $lp->get_current_item_id();
$currentItem = $lp->items[$currentItemId];
$currentItemStatus = $currentItem->get_status();
$accessGranted = false;

if (($count - $completed == 0) ||
    ($count - $completed == 1 && ($currentItemStatus == 'incomplete') || ($currentItemStatus == 'not attempted'))
) {
    if ($lp->prerequisites_match($currentItemId)) {
        $accessGranted = true;
    }
}
// Update the progress in DB from the items completed
$lp->save_last();

// unset the (heavy) lp object to free memory - we don't need it anymore
unset($lp);
unset($currentItem);

// If for some reason we consider the requirements haven't been completed yet,
// show a prerequisites warning
if ($accessGranted == false) {
    echo Display::return_message(
        get_lang('LearnpathPrereqNotCompleted'),
        'warning'
    );
    $finalItemTemplate = '';
} else {
    $catLoad = Category::load(
        null,
        null,
        $courseCode,
        null,
        null,
        $sessionId,
        'ORDER By id'
    );
    // If not gradebook has been defined
    if (empty($catLoad)) {
        $finalItemTemplate = generateLPFinalItemTemplate(
            $id,
            $courseCode,
            $sessionId,
            $downloadCertificateLink,
            $badgeLink
        );
    } else {
        // A gradebook was found, proceed...
        /** @var Category $category */
        $category = $catLoad[0];
        $categoryId = $category->get_id();
        $link = LinkFactory::load(
            null,
            null,
            $lpId,
            null,
            $courseCode,
            $categoryId
        );

        if ($link) {
            $cat = new Category();
            $catCourseCode = CourseManager::get_course_by_category($categoryId);
            $show_message = $cat->show_message_resource_delete($catCourseCode);

            if (false === $show_message && !api_is_allowed_to_edit() && !api_is_excluded_user_type()) {
                $certificate = Category::generateUserCertificate(
                    $categoryId,
                    $userId
                );

                if (!empty($certificate['pdf_url']) ||
                    !empty($certificate['badge_link'])
                ) {
                    if (is_array($certificate)) {
                        $downloadCertificateLink = Category::getDownloadCertificateBlock($certificate);
                    }

                    if (is_array($certificate) &&
                        isset($certificate['badge_link'])
                    ) {
                        $courseId = api_get_course_int_id();
                        $badgeLink = generateLPFinalItemTemplateBadgeLinks(
                            $userId,
                            $courseId,
                            $sessionId
                        );
                    }
                }

                $currentScore = Category::getCurrentScore(
                    $userId,
                    $category,
                    true
                );
                Category::registerCurrentScore(
                    $currentScore,
                    $userId,
                    $categoryId
                );
            }
        }

        $finalItemTemplate = generateLPFinalItemTemplate(
            $id,
            $courseCode,
            $sessionId,
            $downloadCertificateLink,
            $badgeLink
        );

        if (!$finalItemTemplate) {
            echo Display::return_message(get_lang('FileNotFound'), 'warning');
        }
    }
}

// Instance a new template : No page tittle, No header, No footer
$tpl = new Template(null, false, false);
$tpl->assign('content', $finalItemTemplate);
$tpl->display_blank_template();

// A few functions used only here...

/**
 * Return a HTML string to show as final document in learning path.
 *
 * @param int    $lpItemId
 * @param string $courseCode
 * @param int    $sessionId
 * @param string $downloadCertificateLink
 * @param string $badgeLink
 *
 * @return mixed|string
 */
function generateLPFinalItemTemplate(
    $lpItemId,
    $courseCode,
    $sessionId = 0,
    $downloadCertificateLink = '',
    $badgeLink = ''
) {
    $documentInfo = DocumentManager::get_document_data_by_id(
        $lpItemId,
        $courseCode,
        true,
        $sessionId
    );

    $finalItemTemplate = file_get_contents($documentInfo['absolute_path']);
    $finalItemTemplate = str_replace('((certificate))', $downloadCertificateLink, $finalItemTemplate);
    $finalItemTemplate = str_replace('((skill))', $badgeLink, $finalItemTemplate);

    return $finalItemTemplate;
}

/**
 * Return HTML string with badges list.
 *
 * @param int $userId
 * @param int $courseId
 * @param int $sessionId
 *
 * @return string HTML string for badges
 */
function generateLPFinalItemTemplateBadgeLinks($userId, $courseId, $sessionId = 0)
{
    $em = Database::getManager();
    $skillRelUser = new SkillRelUser();
    $userSkills = $skillRelUser->getUserSkills($userId, $courseId, $sessionId);
    $skillList = '';
    $badgeLink = '';

    if ($userSkills) {
        foreach ($userSkills as $userSkill) {
            $skill = $em->find('ChamiloCoreBundle:Skill', $userSkill['skill_id']);
            if (!$skill) {
                continue;
            }
            $skillList .= "
                <div class='row'>
                    <div class='col-md-2 col-xs-4'>
                        <div class='thumbnail'>
                          <img class='skill-badge-img' src='".Skill::getWebIconPath($skill)."' >
                        </div>
                    </div>
                    <div class='col-md-8 col-xs-8'>
                        <h5><b>".$skill->getName()."</b></h5>
                        ".$skill->getDescription()."
                    </div>
                    <div class='col-md-2 col-xs-12'>
                        <h5><b>".get_lang('ShareWithYourFriends')."</b></h5>
                        <a href='http://www.facebook.com/sharer.php?u=".api_get_path(WEB_PATH)."badge/".$skill->getId()."/user/".$userId."' target='_new'>
                            <em class='fa fa-facebook-square fa-3x text-info' aria-hidden='true'></em>
                        </a>
                        <a
                            href='https://twitter.com/home?status=".sprintf(get_lang('IHaveObtainedSkillXOnY'), '"'.$skill->getName().'"', api_get_setting('siteName')).' - '.api_get_path(WEB_PATH).'badge/'.$skill->getId().'/user/'.$userId."' target='_new'>
                            <em class='fa fa-twitter-square fa-3x text-light' aria-hidden='true'></em>
                        </a>
                    </div>
                </div>
            ";
        }

        if (!empty($skillList)) {
            $badgeLink .= "
                <div class='panel panel-default'>
                    <div class='panel-body'>
                        <h3 class='text-center'>".get_lang('AdditionallyYouHaveObtainedTheFollowingSkills')."</h3>
                        $skillList
                    </div>
                </div>
            ";
        }
    }

    return $badgeLink;
}
