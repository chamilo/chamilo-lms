<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CDocument;
use ChamiloSession as Session;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

$lpId = (int) ($_GET['lp_id'] ?? 0);
$lpItemId = (int) ($_GET['lp_item_id'] ?? 0);
$autostart = 'true';

if (empty($lpId) || empty($lpItemId)) {
    api_not_allowed();
}

/** @var learnpath $oLP */
$oLP = Session::read('oLP');

$lpItem = $oLP->items[$lpItemId] ?? null;

if (!$lpItem) {
    echo get_lang('Invalid learning path item');
    exit;
}

$documentIid = (int) $lpItem->get_path();

if (empty($documentIid)) {
    echo get_lang('The file was not found');
    exit;
}

$em = Database::getManager();
$document = $em->getRepository(CDocument::class)
    ->find($documentIid);

if (!$document instanceof CDocument) {
    echo get_lang('The file was not found');
    exit;
}

$html = $oLP->getVideoPlayer($document, $autostart);

echo $html;
