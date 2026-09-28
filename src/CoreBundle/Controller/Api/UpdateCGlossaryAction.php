<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Entity\CGlossaryCategory;
use Chamilo\CourseBundle\Repository\CGlossaryCategoryRepository;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UpdateCGlossaryAction extends BaseResourceFileAction
{
    public function __construct(
        private readonly AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    public function __invoke(
        CGlossary $glossary,
        Request $request,
        CGlossaryRepository $repo,
        CGlossaryCategoryRepository $categoryRepository,
        EntityManager $em,
    ): CGlossary {
        $data = json_decode($request->getContent(), true);

        $title = (string) ($data['title'] ?? '');
        $description = (string) ($data['description'] ?? '');
        $parentResourceNodeId = $data['parentResourceNodeId'] ?? null;
        $resourceLinkList = json_decode((string) ($data['resourceLinkList'] ?? '[]'), true);

        $sid = isset($data['sid']) ? (int) $data['sid'] : 0;
        $cid = (int) ($data['cid'] ?? 0);
        $categoryId = (int) ($data['categoryId'] ?? 0);

        $course = $cid ? $em->getRepository(Course::class)->find($cid) : null;
        $session = $sid ? $em->getRepository(Session::class)->find($sid) : null;

        // Check duplicates
        $qb = $repo->getResourcesByCourse($course, $session)
            ->andWhere('resource.title = :name')
            ->setParameter('name', $title)
        ;

        $existing = $qb->getQuery()->getOneOrNullResult();
        if (null !== $existing && $existing->getIid() !== $glossary->getIid()) {
            throw new BadRequestHttpException('The glossary term already exists.');
        }

        $category = null;
        if ($categoryId > 0) {
            $category = $course instanceof Course
                ? $categoryRepository->findInCourseContext($categoryId, $course, $session)
                : null;
            if (!$category instanceof CGlossaryCategory) {
                throw new BadRequestHttpException('Glossary category not found in the current course/session context.');
            }
        }

        $glossary->setTitle($title);
        $glossary->setDescription($description);
        $glossary->setCategory($category);

        if (!empty($parentResourceNodeId)) {
            $glossary->setParentResourceNode($parentResourceNodeId);
        }

        if (!empty($resourceLinkList)) {
            $glossary->setResourceLinkArray($resourceLinkList);
        }

        $this->applyResourceLanguageFromRequest($glossary, $request, $em);

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
