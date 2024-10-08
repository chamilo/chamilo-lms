<?php

/* For license terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLpItem;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

require_once __DIR__.'/../../main/inc/global.inc.php';

$httpRequest = HttpRequest::createFromGlobals();
$httpResponse = HttpResponse::create();

$plugin = Text2SpeechPlugin::create();

$isAllowedToEdit = api_is_allowed_to_edit(false, true);

$em = Database::getManager();

try {
    if (!$plugin->isEnabled(true)
        || !$isAllowedToEdit
    ) {
        throw new Exception();
    }

    $textToConvert = '';

    if ($httpRequest->query->has('text')) {
        $textToConvert = $httpRequest->query->get('text');
    } elseif ($httpRequest->query->has('item_id')) {
        $itemId = $httpRequest->query->getInt('item_id');

        $item = $em->find(CLpItem::class, $itemId);

        if (!$item) {
            throw new Exception();
        }

        $course = api_get_course_entity($item->getCId());
        $documentRepo = $em->getRepository(CDocument::class);

        $document = $documentRepo->findOneBy([
            'cId' => $course->getId(),
            'iid' => $item->getPath(),
        ]);

        if (!$document) {
            throw new Exception();
        }

        $textToConvert = file_get_contents(
            api_get_path(SYS_COURSE_PATH).$course->getDirectory().'/document/'.$document->getPath()
        );
        $textToConvert = strip_tags($textToConvert);
    }

    if (empty($textToConvert)) {
        throw new Exception();
    }

    $path = $plugin->convert($textToConvert);

    $httpResponse->setContent($path);
} catch (Exception $exception) {
    $httpResponse->setStatusCode(HttpResponse::HTTP_BAD_REQUEST);
}

$httpResponse->send();
