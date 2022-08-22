<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/../global.inc.php';

$httpRequest = HttpRequest::createFromGlobals();

$action = $httpRequest->query->has('a') ? $httpRequest->query->get('a') : $httpRequest->request->get('a');
$user_id = api_get_user_id();

$em = Database::getManager();

$item = null;

if ($httpRequest->query->has('item')) {
    /** @var Portfolio $item */
    $item = $em->find(
        Portfolio::class,
        $httpRequest->query->getInt('item')
    );
}

$httpResponse = Response::create();

switch ($action) {
    case 'find_template':
        if (!$item
            || !$item->isTemplate()
        ) {
            $httpResponse->setStatusCode(Response::HTTP_NOT_FOUND);
            break;
        }

        $httpResponse = JsonResponse::create(
            [
                'title' => $item->getTitle(),
                'content' => $item->getContent(),
            ]
        );
        break;
}

$httpResponse->send();
