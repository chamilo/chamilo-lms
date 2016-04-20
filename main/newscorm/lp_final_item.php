<?php

$_in_course = true;
require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_GRADEBOOK;

api_protect_course_script(true);

$courseCode = api_get_course_id();
$userId = api_get_user_id();
$sessionId = api_get_session_id();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lpId = isset($_GET['lp_id']) ? intval($_GET['lp_id']) : 0;

if (!$id && !$lpId) {
    Display::display_warning_message(get_lang('FileNotFound'));
    exit;
}

$catLoad = Category::load(null, null, $courseCode, null, null, $sessionId, 'ORDER By id');

if (!$catLoad) {
    Display::display_warning_message(get_lang('FileNotFound'));
    exit;
}

$categoryId = $catLoad[0]->get_id();
$link = LinkFactory::load(null, null, $lpId, null, $courseCode, $categoryId);
$downloadCertificateLink = '';
$viewCertificateLink = '';
$badgeLink = '';

if ($link) {
    $cat = new Category();
    $catCourseCode     = CourseManager::get_course_by_category($categoryId);
    $show_message  = $cat->show_message_resource_delete($catCourseCode);

    if ($show_message == '') {
        if (!api_is_allowed_to_edit() && !api_is_excluded_user_type()) {
            $certificate = Category::register_user_certificate(
                $categoryId,
                $userId
            );
            if (isset($certificate['pdf_url']) && isset($certificate['certificate_link']) && isset($certificate['badge_link'])) {
                $downloadCertificateLink .= Display::url(Display::returnFontAwesomeIcon('file-pdf-o') .
                    get_lang('DownloadCertificatePdf'),
                    $certificate['pdf_url'],
                    ['class' => 'btn btn-default']
                );
                $viewCertificateLink .= $certificate['certificate_link'];
                $downloadCertificateLink = "
                    <div class='panel panel-default'>
                        <div class='panel-body'>
                            <h3 class='text-center'>".get_lang('NowDownloadYourCertificateClickHere')."</h3>
                            <div class='text-center'>$downloadCertificateLink $viewCertificateLink</div>
                        </div>
                    </div>
                ";

                $skillRelUser = new SkillRelUser();
                $courseId = api_get_course_int_id();
                $userSkills = $skillRelUser->get_user_skills($userId, $courseId, $sessionId);
                $skillList = '';

                if ($userSkills) {
                    $skill = new Skill();
                    foreach ($userSkills as $userSkill) {
                        $oneSkill = $skill->get($userSkill['skill_id']);
                        $skillList .= "
                            <div class='row'>
                                <div class='col-md-2 col-xs-6'>
                                    <div class='thumbnail'>
                                      <img class='skill-badge-img' src='".$oneSkill['web_icon_path']."' >
                                    </div>
                                </div>
                                <div class='col-md-8 col-xs-6'>
                                    <h5><b>".$oneSkill['name']."</b></h5>
                                    ".$oneSkill['description']."
                                </div>
                                <div class='col-md-2 col-xs-12'>
                                    <h5><b>".get_lang('ShareWithYourFriends')."</b></h5>
                                    <a href='http://www.facebook.com/sharer.php?u=".api_get_path(WEB_PATH)."badge/".$oneSkill['id']."/user/".$userId."' target='_new'>
                                        <em class='fa fa-facebook-square fa-3x text-info' aria-hidden='true'></em>
                                    </a>
                                    <a href='https://twitter.com/home?status=".api_get_path(WEB_PATH)."badge/".$oneSkill['id']."/user/".$userId."' target='_new'>
                                        <em class='fa fa-twitter-square fa-3x text-light' aria-hidden='true'></em>
                                    </a>
                                </div>
                            </div>
                        ";
                    }
                    $badgeLink .= "
                        <div class='panel panel-default'>
                            <div class='panel-body'>
                                <h3 class='text-center'>".get_lang('AdditionallyYouHaveObtainedTheFollowingSkills')."</h3>
                                $skillList
                            </div>
                        </div>
                    ";
                }

                $documentInfo = DocumentManager::get_document_data_by_id(
                    $id,
                    $courseCode,
                    true,
                    $sessionId
                );

                $finalItemTemplate = file_get_contents($documentInfo['absolute_path']);

                $finalItemTemplate = str_replace('((certificate))', $downloadCertificateLink, $finalItemTemplate);
                $finalItemTemplate = str_replace('((skill))', $badgeLink, $finalItemTemplate);
            } else {
                Display::display_warning_message(get_lang('LearnpathPrereqNotCompleted'));
                $finalItemTemplate = '';
            }

            $currentScore = Category::getCurrentScore($userId, $categoryId, $courseCode, $sessionId, true);
            Category::registerCurrentScore($currentScore, $userId, $categoryId);
        }
    }
}

// Instance a new template : No page tittle, No header, No footer
$tpl = new Template(null, false, false);
$tpl->assign('content', $finalItemTemplate);
$tpl->display_one_col_template();

