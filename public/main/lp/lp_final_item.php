<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Framework\Container;

/**
 * LP Final Item: muestra certificado y skills al completar el LP.
 */
$_in_course = true;

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_GRADEBOOK;
api_protect_course_script(true);

$courseCode = api_get_course_id();
$courseId   = api_get_course_int_id();
$userId     = api_get_user_id();
$sessionId  = api_get_session_id();
$id         = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$lpId       = isset($_GET['lp_id']) ? (int) $_GET['lp_id'] : 0;

// This page can only be shown from inside a learning path
if (!$id && !$lpId) {
    echo Display::return_message(get_lang('The file was not found'));
    exit;
}

// Certificate and Skills Premium with Service check
$plugin  = BuyCoursesPlugin::create();
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
        $url = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_catalog.php';
        $content = sprintf(
            Display::return_message(
                get_lang('If you want to get the certificate and/or skills associated with this course, you need to buy the certificate service. You can go to the services catalog by clicking this link: %s'),
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

$lpEntity = api_get_lp_entity($lpId);
$lp       = new Learnpath($lpEntity, [], $userId);

$count              = $lp->getTotalItemsCountWithoutDirs();
$completed          = $lp->get_complete_items_count(true);
$currentItemId      = $lp->get_current_item_id();
$currentItem        = $lp->items[$currentItemId] ?? null;
$currentItemStatus  = $currentItem ? $currentItem->get_status() : 'not attempted';

$lpItemRepo   = Container::getLpItemRepository();
$isFinalThere = false;
$isFinalDone  = false;
try {
    $finalItem = $lpItemRepo->findOneBy(['lp' => $lpEntity, 'itemType' => TOOL_LP_FINAL_ITEM]);
    if ($finalItem) {
        $isFinalThere = true;
        $fid = $finalItem->getIid();
        if (isset($lp->items[$fid])) {
            $st = $lp->items[$fid]->get_status();
            $isFinalDone = in_array($st, ['completed','passed','succeeded'], true);
        }
    }
} catch (\Throwable $e) {
    error_log('[LP_FINAL] final_item lookup error: '.$e->getMessage());
}
$countAdj     = max(0, $count    - ($isFinalThere ? 1 : 0));
$completedAdj = max(0, $completed - ($isFinalDone  ? 1 : 0));
$diff         = $countAdj - $completedAdj;
$accessGranted = false;
if ($diff === 0 || ($diff === 1 && (('incomplete' === $currentItemStatus) || ('not attempted' === $currentItemStatus)))) {
    if ($lp->prerequisites_match($currentItemId)) {
        $accessGranted = true;
    }
}
$lp->save_last();
unset($lp, $currentItem);

if (!$accessGranted) {
    echo Display::return_message(
        get_lang('This learning object cannot display because the course prerequisites are not completed. This happens when a course imposes that you follow it step by step or get a minimum score in tests before you reach the next steps.'),
        'warning'
    );
    $finalHtml = '';
} else {
    $downloadBlock = '';
    $badgeBlock    = '';
    $gbRepo        = Container::getGradeBookCategoryRepository();
    $courseEntity  = api_get_course_entity();
    $sessionEntity = api_get_session_entity();

    /* @var GradebookCategory $gbCat */
    $gbCat = $gbRepo->findOneBy(['course' => $courseEntity, 'session' => $sessionEntity]);

    if (!$gbCat) {
        $gbCat = $gbRepo->findOneBy(['course' => $courseEntity, 'session' => null]);
    }

    if ($gbCat && !api_is_allowed_to_edit() && !api_is_excluded_user_type()) {
        $cert = safeGenerateCertificateForCategory($gbCat, $userId);
        $downloadBlock = buildCertificateBlock($cert);
        $badgeBlock = generateBadgePanel($userId, $courseId, $sessionId);
    }

    $finalHtml = renderFinalItemDocument($id, $downloadBlock, $badgeBlock);
}

$tpl = new Template(null, false, false);
$tpl->assign('content', $finalHtml);
$tpl->display_blank_template();

/**
 * Generates/ensures the certificate via Doctrine repositories and returns minimal link data.
 */
function safeGenerateCertificateForCategory(GradebookCategory $category, int $userId): array
{
    $course   = $category->getCourse();
    $session  = $category->getSession();
    $courseId = $course ? $course->getId() : 0;
    $sessId   = $session ? $session->getId() : 0;
    $catId    = (int) $category->getId();

    // Build certificate content & score
    $gb    = GradebookUtils::get_user_certificate_content($userId, $courseId, $sessId);
    $html  = (is_array($gb) && isset($gb['content'])) ? $gb['content'] : '';
    $score = isset($gb['score']) ? (float) $gb['score'] : 100.0;

    $certRepo = Container::getGradeBookCertificateRepository();

    $htmlUrl = '';
    $pdfUrl  = '';

    try {
        // Store/refresh as Resource (controlled access; not shown in "My personal files")
        $cert = $certRepo->upsertCertificateResource($catId, $userId, $score, $html);

        // (Optional) keep metadata (created_at/score). Filename is not required anymore.
        $certRepo->registerUserInfoAboutCertificate($catId, $userId, $score);

        // Build URLs from the Resource layer
        // View URL (first resource file assigned to the node – here the HTML we just uploaded)
        $htmlUrl = $certRepo->getResourceFileUrl($cert);
    } catch (\Throwable $e) {
        error_log('[LP_FINAL] register cert error: '.$e->getMessage());
    }

    return [
        'path_certificate' => (string) ($cert->getPathCertificate() ?? ''),
        'html_url'         => $htmlUrl,
        'pdf_url'          => $pdfUrl,
    ];
}

/**
 * Builds the certificate download/view HTML block (if available).
 */
function buildCertificateBlock(array $cert): string
{
    $htmlUrl = $cert['html_url'] ?? '';
    $pdfUrl  = $cert['pdf_url']  ?? '';
    if (!$htmlUrl && !$pdfUrl) {
        return '';
    }

    $downloadBtn = $pdfUrl
        ? Display::toolbarButton(get_lang('Download certificate in PDF'), $pdfUrl, 'file-pdf-box')
        : '';

    $viewBtn = $htmlUrl
        ? Display::url(get_lang('View certificate'), $htmlUrl, ['class' => 'btn btn-default'])
        : '';

    return "
        <div class='panel panel-default'>
            <div class='panel-body'>
                <h3 class='text-center'>".get_lang('You can now download your certificate by clicking here')."</h3>
                <div class='text-center'>{$downloadBtn} {$viewBtn}</div>
            </div>
        </div>
    ";
}

/**
 * Returns the user's skills panel HTML (empty if none).
 */
function generateBadgePanel(int $userId, int $courseId, int $sessionId = 0): string
{
    $em           = Database::getManager();
    $skillRelUser = new SkillRelUserModel();
    $userSkills   = $skillRelUser->getUserSkills($userId, $courseId, $sessionId);
    if (!$userSkills) {
        return '';
    }

    $items = '';
    foreach ($userSkills as $row) {
        $skill = $em->find(Skill::class, $row['skill_id']);
        if (!$skill) {
            continue;
        }
        $items .= "
            <div class='row'>
                <div class='col-md-2 col-xs-4'>
                    <div class='thumbnail'>
                        <img class='skill-badge-img' src='".SkillModel::getWebIconPath($skill)."' >
                    </div>
                </div>
                <div class='col-md-8 col-xs-8'>
                    <h5><b>".$skill->getTitle()."</b></h5>
                    ".$skill->getDescription()."
                </div>
                <div class='col-md-2 col-xs-12'>
                    <h5><b>".get_lang('Share with your friends')."</b></h5>
                    <a href='http://www.facebook.com/sharer.php?u=".api_get_path(WEB_PATH)."badge/".$skill->getId()."/user/".$userId."' target='_new'>
                        <em class='fa fa-facebook-square fa-3x text-info' aria-hidden='true'></em>
                    </a>
                    <a href='https://twitter.com/home?status=".sprintf(get_lang('I have achieved skill %s on %s'), '"'.$skill->getTitle().'"', api_get_setting('siteName')).' - '.api_get_path(WEB_PATH).'badge/'.$skill->getId().'/user/'.$userId."' target='_new'>
                        <em class='fa fa-twitter-square fa-3x text-light' aria-hidden='true'></em>
                    </a>
                </div>
            </div>";
    }

    if (!$items) {
        return '';
    }

    return "
        <div class='panel panel-default'>
            <div class='panel-body'>
                <h3 class='text-center'>".get_lang('Additionally, you have achieved the following skills')."</h3>
                {$items}
            </div>
        </div>
    ";
}

/**
 * Render the Learning Path final-item document.
 */
function renderFinalItemDocument(int $lpItemOrDocId, string $certificateBlock, string $badgeBlock): string
{
    $docRepo    = Container::getDocumentRepository();
    $lpItemRepo = Container::getLpItemRepository();

    $document = null;
    try { $document = $docRepo->find($lpItemOrDocId); } catch (\Throwable $e) {}
    if (!$document) {
        try {
            $lpItem = $lpItemRepo->find($lpItemOrDocId);
            if ($lpItem) {
                $document = $docRepo->find((int) $lpItem->getPath());
            }
        } catch (\Throwable $e) {}
    }

    if (!$document) {
        return '';
    }

    try {
        $content = $docRepo->getResourceFileContent($document);
    } catch (\Throwable $e) {
        error_log('[LP_FINAL] read doc error: '.$e->getMessage());
        return '';
    }

    $hasCert  = str_contains($content, '((certificate))');
    $hasSkill = str_contains($content, '((skill))');

    if ($hasCert)  { $content = str_replace('((certificate))', $certificateBlock, $content); }
    if ($hasSkill) { $content = str_replace('((skill))', $badgeBlock, $content); }

    return $content;
}
