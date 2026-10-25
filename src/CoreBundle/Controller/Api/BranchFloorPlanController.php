<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Branch;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\ResourceFileHelper;
use Chamilo\CoreBundle\Helpers\RoomAccessUrlHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Security\FloorPlanSvgValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use const IMAGETYPE_JPEG;
use const IMAGETYPE_PNG;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class BranchFloorPlanController extends AbstractController
{
    private const array ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/svg+xml'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RoomAccessUrlHelper $roomAccessUrlHelper,
        private readonly ResourceFileHelper $resourceFileHelper,
        private readonly ResourceNodeRepository $nodes,
        private readonly IsAllowedToEditHelper $allowedToEdit,
        private readonly FloorPlanSvgValidator $svgValidator,
    ) {}

    // Direct branch image access is administrative, or restricted to an editable
    // course context. Learners use the course/attendance-scoped image endpoints.
    #[Route('/api/branches/{id}/floor-plan', name: 'branch_floor_plan_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, Request $request): Response
    {
        $branch = $this->branch($id);
        $this->assertCanViewPlan($branch, $request);
        $node = $branch->getFloorPlan();
        if (null === $node) {
            throw $this->createNotFoundException('No floor plan.');
        }
        $file = $this->imageFile($node, $branch);
        $stream = $this->nodes->getResourceNodeFileStream($node, $file);
        if (!\is_resource($stream)) {
            throw $this->createNotFoundException('Floor plan file not found.');
        }
        $response = new StreamedResponse(static function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        });
        $response->headers->set('Content-Type', (string) $file->getMimeType());
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Disposition', 'inline');
        if ('image/svg+xml' === $file->getMimeType()) {
            $response->headers->set('Content-Security-Policy', "sandbox; default-src 'none'; style-src 'none'");
        }

        return $response;
    }

    #[Route('/api/branches/{id}/floor-plan/options', name: 'branch_floor_plan_options', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function options(int $id, Request $request): JsonResponse
    {
        $branch = $this->branch($id);
        $query = trim((string) $request->query->get('q', ''));
        if (mb_strlen($query) > 80) {
            throw new BadRequestHttpException('Search query is too long.');
        }

        // Only list Document images. Do not expose unrelated resource nodes
        // (avatars, private attachments, etc.) as floor-plan candidates.
        $qb = $this->em->createQueryBuilder()
            ->select('DISTINCT node.id')
            ->from(ResourceNode::class, 'node')
            ->innerJoin('node.resourceType', 'resourceType')
            ->innerJoin('node.resourceFiles', 'file')
            ->andWhere('resourceType.title = :resourceType')
            ->andWhere('file.mimeType IN (:imageTypes)')
            ->andWhere('file.size > 0')
            ->andWhere('file.size <= :maxSize')
            ->setParameter('resourceType', 'files')
            ->setParameter('imageTypes', self::ALLOWED_TYPES)
            ->setParameter('maxSize', 10485760)
            ->orderBy('node.id', 'DESC')
            ->setMaxResults(100)
        ;

        if ('' !== $query) {
            $qb->andWhere('LOWER(node.title) LIKE :query')
                ->setParameter('query', '%'.mb_strtolower($query).'%')
            ;
        }

        $items = [];
        foreach ($qb->getQuery()->getScalarResult() as $row) {
            $node = $this->em->find(ResourceNode::class, (int) $row['id']);
            if (!$node instanceof ResourceNode) {
                continue;
            }

            try {
                $this->imageFile($node, $branch);
            } catch (AccessDeniedHttpException|BadRequestHttpException|FileNotFoundException) {
                continue;
            }

            $items[] = ['id' => $node->getId(), 'name' => $node->getTitle()];
        }

        // Keep the current selection visible even if the document was renamed,
        // filtered out by the search, or is not among the latest 100 entries.
        $current = $branch->getFloorPlan();
        if ($current instanceof ResourceNode
            && !\in_array($current->getId(), array_column($items, 'id'), true)) {
            try {
                $this->imageFile($current, $branch);
                $items[] = ['id' => $current->getId(), 'name' => $current->getTitle()];
            } catch (AccessDeniedHttpException|BadRequestHttpException|FileNotFoundException) {
                // The existing link must not bypass resource visibility rules.
            }
        }

        // Distinguish duplicate filenames without requiring anyone to type an ID.
        $counts = array_count_values(array_column($items, 'name'));
        foreach ($items as &$item) {
            if (($counts[$item['name']] ?? 0) > 1) {
                $item['name'] .= ' (#'.$item['id'].')';
            }
        }
        unset($item);

        return $this->json(['items' => $items]);
    }

    #[Route('/api/branches/{id}/floor-plan', name: 'branch_floor_plan_attach', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function attach(int $id, Request $request): JsonResponse
    {
        $branch = $this->branch($id);

        try {
            $body = $request->toArray();
        } catch (Throwable) {
            throw new BadRequestHttpException('Invalid JSON.');
        }
        $nodeId = $body['resourceNodeId'] ?? null;
        if (!\is_int($nodeId) || $nodeId <= 0) {
            throw new BadRequestHttpException('resourceNodeId must be a positive integer.');
        }
        $node = $this->em->find(ResourceNode::class, $nodeId);
        if (!$node instanceof ResourceNode) {
            throw new BadRequestHttpException('Invalid image resource node.');
        }
        $this->imageFile($node, $branch);
        $branch->setFloorPlan($node);
        $this->em->flush();

        return $this->json(['floorPlanId' => $nodeId]);
    }

    #[Route('/api/branches/{id}/floor-plan/upload', name: 'branch_floor_plan_upload', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function upload(int $id, Request $request): JsonResponse
    {
        $branch = $this->branch($id);
        if (!$this->isGranted('EDIT', $branch)) {
            throw new AccessDeniedHttpException();
        }
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('A valid image upload is required.');
        }
        $size = $file->getSize();
        if (null === $size || $size < 1 || $size > 10485760) {
            throw new BadRequestHttpException('The floor plan must not exceed 10 MB.');
        }
        // Verify content, not the extension provided by the client. Only static SVG
        // graphics are allowed; reject active XML before persisting any bytes.
        $svg = 'svg' === strtolower($file->getClientOriginalExtension());
        if ($svg) {
            $contents = file_get_contents($file->getPathname());
            if (false === $contents || !$this->svgValidator->isSafe($contents)) {
                throw new BadRequestHttpException('The SVG must contain only static floor-plan graphics.');
            }
        } else {
            $imageInfo = @getimagesize($file->getPathname());
            if (false === $imageInfo
                || !\in_array($imageInfo[2] ?? null, [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)
                || ($imageInfo[0] ?? 0) * ($imageInfo[1] ?? 0) > 50000000
            ) {
                throw new BadRequestHttpException('Only PNG, JPEG and safe static SVG floor plans are supported.');
            }
        }
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }
        $type = $this->em->getRepository(ResourceType::class)->findOneBy(['title' => 'files']);
        if (!$type instanceof ResourceType) {
            throw new BadRequestHttpException('The file resource type is not configured.');
        }
        $accessUrl = $branch->getUrl();
        if (null === $accessUrl) {
            throw new BadRequestHttpException('The branch has no access URL.');
        }
        $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $originalName = mb_substr($originalName, 0, 180);
        if ('' === $originalName) {
            $originalName = 'floor-plan.png';
        }
        $node = (new ResourceNode())
            ->setTitle('Floor plan - '.$branch->getId().' - '.bin2hex(random_bytes(6)))
            ->setResourceType($type)
            ->setCreator($user)
            ->setPublic(false)
        ;
        // A floor plan is a standalone private node; the branch relation and scoped
        // read endpoints gate it to this access URL. ResourceFile has no access-URL
        // variant here, so ResourceFileHelper can resolve it with both variants settings.
        $resourceFile = (new ResourceFile())
            ->setTitle($originalName)
            ->setFile($file)
            ->setResourceNode($node)
        ;
        if ($svg) {
            $resourceFile->setMimeType('image/svg+xml');
        }
        $node->addResourceFile($resourceFile);
        $this->em->persist($node);
        $this->em->persist($resourceFile);
        $branch->setFloorPlan($node);
        // The Vich resources mapping persists the bytes via the existing Flysystem storage.
        // Replacing a plan never deletes the old resource: it may be reused elsewhere.
        $this->em->flush();

        return $this->json(['floorPlanId' => $node->getId(), 'name' => $originalName], Response::HTTP_CREATED);
    }

    #[Route('/api/branches/{id}/floor-plan', name: 'branch_floor_plan_detach', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function detach(int $id): JsonResponse
    {
        $branch = $this->branch($id);
        $branch->setFloorPlan(null);
        $this->em->flush();

        return $this->json(['floorPlanId' => null]);
    }

    /**
     * A teacher may view a plan only while editing one of their courses.
     * Admins retain direct branch access; learners use course/attendance image URLs.
     */
    private function assertCanViewPlan(Branch $branch, Request $request): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        $courseId = $request->query->getInt('cid');
        $course = $courseId > 0 ? $this->em->find(Course::class, $courseId) : null;
        if (!$course instanceof Course) {
            throw new AccessDeniedHttpException();
        }

        $portalId = $branch->getUrl()?->getId();
        $belongsToPortal = false;
        foreach ($course->getUrls() as $relation) {
            if ($relation->getUrl()?->getId() === $portalId) {
                $belongsToPortal = true;

                break;
            }
        }
        if (!$belongsToPortal) {
            throw new AccessDeniedHttpException();
        }

        if ($this->isGranted('EDIT', $course)) {
            return;
        }
        $sessionId = $request->query->getInt('sid');
        $session = $sessionId > 0 ? $this->em->find(Session::class, $sessionId) : null;
        if (!$session instanceof Session || !$session->hasCourse($course)
            || !$this->allowedToEdit->check(coach: true, course: $course, session: $session)
        ) {
            throw new AccessDeniedHttpException();
        }
    }

    private function branch(int $id): Branch
    {
        $branch = $this->em->find(Branch::class, $id);
        if (!$branch instanceof Branch) {
            throw $this->createNotFoundException('Branch not found.');
        }
        $this->roomAccessUrlHelper->assertBranchAllowed($branch);
        if (!$this->isGranted('VIEW', $branch)) {
            throw new AccessDeniedHttpException();
        }

        return $branch;
    }

    private function imageFile(ResourceNode $node, Branch $branch): ResourceFile
    {
        if (!$this->isGranted('VIEW', $node)) {
            throw new AccessDeniedHttpException('No access to image.');
        }
        $file = $this->resourceFileHelper->resolveResourceFileByAccessUrl($node);
        if (null === $file) {
            throw new BadRequestHttpException('Resource has no accessible image file.');
        }
        if (null !== $file->getAccessUrl()
            && $file->getAccessUrl()->getId() !== $branch->getUrl()?->getId()) {
            throw new AccessDeniedHttpException('Image belongs to another access URL.');
        }
        // Files with no explicit access URL can be shared across portals. Only
        // accept a course document when it is linked to a course in this branch's
        // portal. Keep existing directly uploaded plans usable: those private
        // nodes have no ResourceLink and are already associated with this branch.
        if (null === $file->getAccessUrl()
            && $branch->getFloorPlan()?->getId() !== $node->getId()
            && !$this->isCourseDocumentFromBranchPortal($node, $branch)
        ) {
            throw new AccessDeniedHttpException('Image is not linked to this access URL.');
        }
        if (!\in_array($file->getMimeType(), self::ALLOWED_TYPES, true)
            || null === $file->getSize() || $file->getSize() > 10485760) {
            throw new BadRequestHttpException('Only PNG/JPEG and safe SVG images up to 10MB are supported.');
        }
        if ('image/svg+xml' === $file->getMimeType()) {
            $stream = $this->nodes->getResourceNodeFileStream($node, $file);
            if (!\is_resource($stream)) {
                throw new BadRequestHttpException('SVG file unavailable.');
            }

            try {
                $contents = stream_get_contents($stream, 10485761);
            } finally {
                fclose($stream);
            }
            if (false === $contents || !$this->svgValidator->isSafe($contents)) {
                throw new BadRequestHttpException('SVG file is not safe for display.');
            }
        }

        return $file;
    }
    private function isCourseDocumentFromBranchPortal(ResourceNode $node, Branch $branch): bool
    {
        $portalId = $branch->getUrl()?->getId();
        if (null === $portalId) {
            return false;
        }

        foreach ($node->getResourceLinks() as $link) {
            // Only a non-deleted, course-wide document establishes its portal.
            // A user/group/session-specific attachment must not be promoted to
            // a branch floor plan merely because the course is in this portal.
            if (null !== $link->getDeletedAt()
                || null !== $link->getUser()
                || null !== $link->getUserGroup()
                || null !== $link->getGroup()
                || null !== $link->getSession()
            ) {
                continue;
            }

            $course = $link->getCourse();
            if (null === $course) {
                continue;
            }
            foreach ($course->getUrls() as $courseUrl) {
                if ($courseUrl->getUrl()?->getId() === $portalId) {
                    return true;
                }
            }
        }

        return false;
    }
}
