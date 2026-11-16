<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\ResourceHelper;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class UpdateDocumentFileAction extends BaseResourceFileAction
{
    public function __construct(
        private readonly ResourceHelper $trackHelper,
        private readonly AiDisclosureHelper $aiDisclosureHelper,
        private readonly Security $security,
    ) {}

    public function __invoke(
        CDocument $document,
        Request $request,
        CDocumentRepository $repo,
        EntityManager $em,
        CidReqHelper $cidReqHelper,
    ): CDocument {
        $this->handleUpdateRequest(
            $document,
            $repo,
            $request,
            $em,
            function (array $links) use ($cidReqHelper): void {
                // The creator of a node keeps EDIT on it in every course it is shared with.
                if (!$this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
                    && !$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                ) {
                    throw new AccessDeniedHttpException('Only a teacher of the current course can change its resource links.');
                }

                $this->assertResourceLinksInCurrentContext($links, $cidReqHelper);
            },
        );
        $this->applyResourceLanguageFromRequest($document, $request, $em);

        $raw = $request->request->get('ai_assisted_raw', null);
        if (null !== $raw) {
            $enabled = $this->normalizeBoolean($raw);

            $docId = (int) ($document->getIid() ?? 0);
            if ($docId > 0) {
                $this->aiDisclosureHelper->markAiAssistedExtraField('document', $docId, $enabled);
            }
        }

        $node = $document->getResourceNode();
        if ($node) {
            $this->trackHelper->createAndSaveResourceEvent($node, 'edition');
        }

        return $document;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        // Accept checkbox-like values: 1/0, true/false, on/off, yes/no
        $v = strtolower(trim((string) $value));
        if ('' === $v) {
            return false;
        }

        return \in_array($v, ['1', 'true', 'yes', 'on'], true);
    }
}
