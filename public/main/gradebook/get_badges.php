<?php
/* For licensing terms, see /license.txt */

/**
 * Show the achieved badges by an user.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
use Chamilo\CoreBundle\Entity\SkillRelUser;

require_once __DIR__.'/../inc/global.inc.php';

$userId = isset($_GET['user']) ? (int) $_GET['user'] : 0;

if (empty($userId)) {
    api_not_allowed(true);
}

SkillModel::isAllowed($userId);

$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

$criteria = [
    'user' => $userId,
    'status' => SkillRelUser::STATUS_ACQUIRED,
];
if ($courseId > 0) {
    $criteria['course'] = $courseId;
    $criteria['session'] = $sessionId > 0 ? $sessionId : null;
}

$issues = Database::getManager()->getRepository(SkillRelUser::class)->findBy($criteria, ['id' => 'ASC']);
$assertions = [];
foreach ($issues as $issue) {
    if (!$issue instanceof SkillRelUser
        || null === $issue->getId()
        || null === $issue->getSkill()?->getId()
        || $issue->getSkill()->getAccessUrlId() !== api_get_current_access_url_id()
    ) {
        continue;
    }

    $assertions[] = $issue->getAssertionUrl();
}

if ([] === $assertions) {
    api_not_allowed(true);
}

$backpack = 'https://backpack.openbadges.org/';

$configBackpack = api_get_setting('skill.openbadges_backpack');
if (0 !== strcmp($backpack, $configBackpack)) {
    $backpack = $configBackpack;
    if ('/' !== substr($backpack, -1)) {
        $backpack .= '/';
    }
}

$htmlHeadXtra[] = '<script src="'.$backpack.'issuer.js"></script>';

$tpl = new Template(get_lang('Badges'), false, false);

// Hosted assertion URLs remain usable when the configured backpack is unavailable.
$links = '<ul>';
foreach ($assertions as $index => $assertionUrl) {
    $safeUrl = htmlspecialchars($assertionUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $links .= '<li><a href="'.$safeUrl.'" target="_blank" rel="noopener noreferrer">'
        .htmlspecialchars(get_lang('Badge'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').' '.($index + 1).'</a></li>';
}
$links .= '</ul>';

$tpl->assign(
    'content',
    $links.'<script>
    $(function() {
        if (window.OpenBadges && typeof window.OpenBadges.issue_no_modal === "function") {
            window.OpenBadges.issue_no_modal('.json_encode($assertions, JSON_THROW_ON_ERROR).');
        }
    });
    </script>'
);

$tpl->display_one_col_template();
