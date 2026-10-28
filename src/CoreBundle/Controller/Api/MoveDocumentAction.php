<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\Document\DocumentMoveService;
use Chamilo\CourseBundle\Entity\CDocument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class MoveDocumentAction
{
    public function __construct(
        private readonly DocumentMoveService $documentMoveService
    ) {}

    public function __invoke(CDocument $document, Request $request): CDocument
    {
        $payload = json_decode((string) $request->getContent(), true);
        if (!\is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON body.');
        }

        $destinationId = $this->normalizeNodeId($payload['parentResourceNodeId'] ?? null);
        if (null === $destinationId) {
            throw new BadRequestHttpException('Invalid "parentResourceNodeId".');
        }

        $this->documentMoveService->move(
            [$document],
            $destinationId,
            $request->query->getInt('cid'),
            $request->query->getInt('sid'),
            $request->query->getInt('gid'),
        );

        return $document;
    }

    private function normalizeNodeId(mixed $value): ?int
    {
        if (\is_int($value) && $value > 0) {
            return $value;
        }

        if (\is_string($value)) {
            if (ctype_digit($value) && (int) $value > 0) {
                return (int) $value;
            }

            if (preg_match('#^/api/resource_nodes/(\d+)$#', $value, $matches) && (int) $matches[1] > 0) {
                return (int) $matches[1];
            }
        }

        return null;
    }
}
