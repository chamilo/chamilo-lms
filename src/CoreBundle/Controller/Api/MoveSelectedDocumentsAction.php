<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\Document\DocumentMoveService;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class MoveSelectedDocumentsAction
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DocumentMoveService $documentMoveService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        if (!\is_array($payload) || !isset($payload['ids']) || !\is_array($payload['ids'])) {
            throw new BadRequestHttpException('Invalid document selection.');
        }

        if (0 === \count($payload['ids']) || \count($payload['ids']) > 200) {
            throw new BadRequestHttpException('Select between 1 and 200 documents.');
        }

        $ids = [];
        foreach ($payload['ids'] as $id) {
            if ((!\is_int($id) && (!\is_string($id) || !ctype_digit($id))) || (int) $id <= 0) {
                throw new BadRequestHttpException('Invalid document ID.');
            }
            $ids[(int) $id] = (int) $id;
        }
        $ids = array_values($ids);

        $rawDestination = $payload['parentResourceNodeId'] ?? null;
        if ((!\is_int($rawDestination) && (!\is_string($rawDestination) || !ctype_digit($rawDestination)))
            || (int) $rawDestination <= 0) {
            throw new BadRequestHttpException('Invalid destination node ID.');
        }

        $documents = $this->em->getRepository(CDocument::class)->findBy(['iid' => $ids]);
        if (\count($documents) !== \count($ids)) {
            throw new BadRequestHttpException('One or more selected documents were not found.');
        }

        $moved = $this->documentMoveService->move(
            $documents,
            (int) $rawDestination,
            $request->query->getInt('cid'),
            $request->query->getInt('sid'),
            $request->query->getInt('gid'),
        );

        return new JsonResponse(['moved' => $moved]);
    }
}
