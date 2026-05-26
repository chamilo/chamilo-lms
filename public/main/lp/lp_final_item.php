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
$finalItem    = null;

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

    // Resolve GradebookCategory using lp_item.ref when item_type = final_item.
    // We store the gradebook category id in c_lp_item.ref (string).
    $categoryIdFromRef = 0;

    if (!empty($finalItem) && method_exists($finalItem, 'getRef')) {
        try {
            $refRaw = trim((string) $finalItem->getRef());
            if ($refRaw !== '' && $refRaw !== '0') {
                $categoryIdFromRef = (int) $refRaw;
            }
        } catch (\Throwable $e) {
            error_log('[LP_FINAL] Unable to read lp_item.ref for final_item: '.$e->getMessage());
        }
    }

    /** @var GradebookCategory|null $gbCat */
    $gbCat = null;

    // 1) First, try the explicit category id stored in c_lp_item.ref.
    if ($categoryIdFromRef > 0) {
        $gbCat = $gbRepo->find($categoryIdFromRef);

        // Safety check: ensure the referenced category belongs to the same course/session context.
        if ($gbCat && $courseEntity) {
            $catCourse  = $gbCat->getCourse();
            $catSession = $gbCat->getSession();

            // If course does not match, discard this category and let the fallback logic handle it.
            if (!$catCourse || $catCourse->getId() !== $courseEntity->getId()) {
                $gbCat = null;
            } elseif ($sessionEntity) {
                // If we are in a session context, ensure the category session matches.
                if ($catSession && $catSession->getId() !== $sessionEntity->getId()) {
                    $gbCat = null;
                }
            }
        }
    }

    // 2) Fallback: keep legacy behaviour (root course/session category).
    if (!$gbCat && $courseEntity) {
        if ($sessionEntity) {
            $gbCat = $gbRepo->findOneBy([
                'course'  => $courseEntity,
                'session' => $sessionEntity,
            ]);
        }

        if (!$gbCat) {
            $gbCat = $gbRepo->findOneBy([
                'course'  => $courseEntity,
                'session' => null,
            ]);
        }
    }

    if ($gbCat && !api_is_allowed_to_edit() && !api_is_excluded_user_type()) {
        // Use legacy Category business object to generate certificate + skills
        // for this specific gradebook category.
        // NOTE: Category::generateUserCertificate() is expected to know how to
        // work with the Doctrine GradebookCategory entity.
        $certificate = Category::generateUserCertificate($gbCat, $userId);
        if (!empty($certificate)) {
            // Build the HTML panel to replace ((certificate)).
            $downloadBlock = Category::getDownloadCertificateBlock($certificate);
        }

        // Skills: Category::generateUserCertificate() already assigns skills
        // to the user for this course/session/category when enabled.
        // Here we just render the user's skills panel.
        $badgeBlock = generateBadgePanel($userId, $courseId, $sessionId, (int) $gbCat->getId());
    }

    // Replace ((certificate)) and ((skill)) tokens in the final-item document.
    $finalHtml = renderFinalItemDocument($id, $downloadBlock, $badgeBlock);
}

$tpl = new Template(null, false, false);
$tpl->assign('content', $finalHtml);
$tpl->display_blank_template();

/**
 * Returns the user's skills panel HTML for the current final-item category only (empty if none).
 */
function generateBadgePanel(int $userId, int $courseId, int $sessionId = 0, int $gradebookCategoryId = 0): string
{
    $gradebookCategoryId = (int) $gradebookCategoryId;
    if ($gradebookCategoryId <= 0) {
        return '';
    }

    $allowedSkillIds = getSkillIdsForGradebookCategory($gradebookCategoryId);
    if (empty($allowedSkillIds)) {
        return '';
    }

    $em           = Database::getManager();
    $skillRelUser = new SkillRelUserModel();
    $userSkills   = $skillRelUser->getUserSkills($userId, $courseId, $sessionId);

    if (empty($userSkills)) {
        return '';
    }

    $items = '';

    foreach ($userSkills as $row) {
        $rowSkillId = (int) ($row['skill_id'] ?? 0);
        if ($rowSkillId <= 0) {
            continue;
        }

        if (!in_array($rowSkillId, $allowedSkillIds, true)) {
            continue;
        }

        $skill = $em->find(Skill::class, $rowSkillId);
        if (!$skill) {
            continue;
        }

        $skillId = (int) $skill->getId();
        $title   = (string) $skill->getTitle();
        $desc    = (string) $skill->getDescription();
        $iconUrl = (string) SkillModel::getWebIconPath($skill);

        $shareUrl = api_get_path(WEB_PATH)."badge/$skillId/user/$userId";

        // Facebook sharer (https)
        $fbUrl = 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($shareUrl);

        // Twitter/X intent
        $tweetText = sprintf(
            get_lang('I have achieved skill %s on %s'),
            $title,
            api_get_setting('siteName')
        );
        $twUrl = 'https://twitter.com/intent/tweet?text='.rawurlencode($tweetText).'&url='.rawurlencode($shareUrl);

        $safeTitle = Security::remove_XSS($title);
        $safeDesc  = Security::remove_XSS($desc);

        $items .= "
            <div class='py-6 border-b border-gray-20 last:border-b-0'>
                <div class='grid grid-cols-1 sm:grid-cols-12 gap-5 items-start'>

                    <div class='sm:col-span-3 flex justify-center sm:justify-start'>
                        <img
                            src='".htmlspecialchars($iconUrl, ENT_QUOTES)."'
                            alt='".htmlspecialchars($title, ENT_QUOTES)."'
                            loading='lazy'
                            width='140'
                            height='140'
                            style='max-width:140px;height:auto;'
                            class='h-24 w-24 sm:h-28 sm:w-28 object-contain rounded-xl bg-white ring-1 ring-gray-25 shadow-sm'
                        >
                    </div>

                    <div class='sm:col-span-6'>
                        <div class='text-lg font-semibold text-gray-90'>$safeTitle</div>
                        <div class='mt-1 text-sm text-gray-50'>$safeDesc</div>
                    </div>

                    <div class='sm:col-span-3'>
                        <div class='text-sm font-semibold text-gray-90'>".get_lang('Share with your friends')."</div>

                        <div class='mt-3 flex items-center gap-3'>
                            <a
                                href='".htmlspecialchars($fbUrl, ENT_QUOTES)."'
                                target='_blank'
                                rel='noopener noreferrer'
                                class='inline-flex h-10 w-10 items-center justify-center rounded-full ring-1 ring-gray-25 bg-white hover:bg-gray-15'
                                aria-label='Facebook'
                                title='Facebook'
                            >
                                <svg viewBox='0 0 24 24' class='h-5 w-5 text-gray-90' aria-hidden='true'>
                                    <path fill='currentColor' d='M22 12a10 10 0 1 0-11.56 9.87v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.88h-2.34v6.99A10 10 0 0 0 22 12Z'/>
                                </svg>
                            </a>

                            <a
                                href='".htmlspecialchars($twUrl, ENT_QUOTES)."'
                                target='_blank'
                                rel='noopener noreferrer'
                                class='inline-flex h-10 w-10 items-center justify-center rounded-full ring-1 ring-gray-25 bg-white hover:bg-gray-15'
                                aria-label='X'
                                title='X'
                            >
                                <svg viewBox='0 0 24 24' class='h-5 w-5 text-gray-90' aria-hidden='true'>
                                    <path fill='currentColor' d='M18.9 2H22l-6.76 7.73L23 22h-6.2l-4.86-6.4L6.34 22H3.2l7.23-8.26L1 2h6.36l4.4 5.83L18.9 2Zm-1.09 18h1.72L6.42 3.9H4.58L17.81 20Z'/>
                                </svg>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        ";
    }

    if ($items === '') {
        return '';
    }

    return "
        <section class='mx-auto max-w-5xl p-4'>
            <div class='rounded-2xl bg-white ring-1 ring-gray-25 shadow-sm'>
                <div class='px-6 pt-6'>
                    <h3 class='text-center text-xl font-semibold text-gray-90'>
                        ".get_lang('Additionally, you have achieved the following skills')."
                    </h3>
                </div>
                <div class='px-6 pb-6'>
                    $items
                </div>
            </div>
        </section>
    ";
}

/**
 * Returns the skill IDs linked to a gradebook category.
 */
function getSkillIdsForGradebookCategory(int $categoryId): array
{
    if ($categoryId <= 0) {
        return [];
    }

    $ids = [];

    try {
        $gradebook = new Gradebook();
        $rows = $gradebook->getSkillsByGradebook($categoryId);

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (is_array($row) && isset($row['id'])) {
                    $ids[] = (int) $row['id'];
                } elseif (is_scalar($row)) {
                    $ids[] = (int) $row;
                }
            }
        } elseif (is_string($rows) && trim($rows) !== '') {
            $parts = preg_split('/\s*,\s*/', trim($rows)) ?: [];
            foreach ($parts as $p) {
                $ids[] = (int) $p;
            }
        }
    } catch (\Throwable $e) {
        return [];
    }

    return array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $v) => $v > 0)));
}

/**
 * Render the Learning Path final-item document.
 */
function renderFinalItemDocument(int $lpItemOrDocId, string $certificateBlock, string $badgeBlock): string
{
    $docRepo    = Container::getDocumentRepository();
    $lpItemRepo = Container::getLpItemRepository();

    $document = null;

    // First, try to use the id directly as a document iid.
    try {
        $document = $docRepo->find($lpItemOrDocId);
    } catch (\Throwable $e) {
        // Silence here, we will try the LP item fallback below.
    }

    // If not a document iid, try resolving from the LP item path.
    if (!$document) {
        try {
            $lpItem = $lpItemRepo->find($lpItemOrDocId);
            if ($lpItem) {
                // In our case, lp_item.path stores the document iid as string.
                $document = $docRepo->find((int) $lpItem->getPath());
            }
        } catch (\Throwable $e) {
            // As a last resort, fail quietly and return empty content.
        }
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

    if ($hasCert) {
        $content = str_replace('((certificate))', $certificateBlock, $content);
    }
    if ($hasSkill) {
        $content = str_replace('((skill))', $badgeBlock, $content);
    }

    return $content;
}
