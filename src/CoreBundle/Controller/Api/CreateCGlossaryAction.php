<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CreateCGlossaryAction extends BaseResourceFileAction
{
    public function __construct(
        private readonly AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    public function __invoke(Request $request, CGlossaryRepository $repo, EntityManager $em, CidReqHelper $cidReqHelper): CGlossary
    {
        $data = json_decode($request->getContent(), true);
        $title = (string) ($data['title'] ?? '');
        $description = (string) ($data['description'] ?? '');
        $parentResourceNodeId = $data['parentResourceNodeId'] ?? null;
        $bodyResourceLinkList = json_decode((string) ($data['resourceLinkList'] ?? '[]'), true);
        $resourceLinkList = $this->buildResourceLinkListFromContext(
            $cidReqHelper,
            \is_array($bodyResourceLinkList) ? $bodyResourceLinkList : [],
        );

        // The course and session come from the context that authorized the request, never from the body.
        $course = $cidReqHelper->requireDoctrineCourseEntity();
        $session = $cidReqHelper->getDoctrineSessionEntity();

        // Check duplicates
        $qb = $repo->getResourcesByCourse($course, $session)
            ->andWhere('resource.title = :title')
            ->setParameter('title', $title)
        ;

        $existing = $qb->getQuery()->getOneOrNullResult();
        if (null !== $existing) {
            throw new BadRequestHttpException('The glossary term already exists.');
        }

        $glossary = (new CGlossary())
            ->setTitle($title)
            ->setDescription($description)
        ;

        if (!empty($parentResourceNodeId)) {
            $this->assertParentNodeIsInCurrentCourse($cidReqHelper, (int) $parentResourceNodeId, $em);
            $glossary->setParentResourceNode($parentResourceNodeId);
        }

        $glossary->setResourceLinkArray($resourceLinkList);

        // Persist early so we can store ExtraField values using iid.
        $em->persist($glossary);
        $em->flush();

        $this->applyResourceLanguageFromRequest($glossary, $request, $em);
        $em->flush();

        if (\array_key_exists('ai_assisted_raw', $data)) {
            $enabled = $this->normalizeBoolean($data['ai_assisted_raw']);
            $iid = (int) ($glossary->getIid() ?? 0);

            if ($iid > 0) {
                $this->aiDisclosureHelper->markAiAssistedExtraField('glossary', $iid, $enabled);
            }
        }

        return $glossary;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        $v = strtolower(trim((string) $value));
        if ('' === $v) {
            return false;
        }

        return \in_array($v, ['1', 'true', 'yes', 'on'], true);
    }
}
