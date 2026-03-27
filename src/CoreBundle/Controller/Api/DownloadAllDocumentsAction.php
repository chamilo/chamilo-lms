<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\ResourceFileHelper;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\GroupVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceFileVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class DownloadAllDocumentsAction
{
    public const CONTENT_TYPE = 'application/zip';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly ResourceFileHelper $resourceFileHelper,
        private readonly Security $security,
        private readonly CidReqHelper $cidReqHelper,
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(Request $request): Response
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');

        $data = json_decode($request->getContent(), true) ?: [];

        $rootNodeId = (int) ($data['rootNodeId'] ?? 0);
        if ($rootNodeId <= 0) {
            return new Response('Missing root node.', Response::HTTP_BAD_REQUEST);
        }

        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();
        $group = $this->cidReqHelper->getGroupEntity();

        if (!$course || !$this->security->isGranted(CourseVoter::VIEW, $course)) {
            throw new NotAllowedException("You're not allowed in this course");
        }

        if ($session && !$this->security->isGranted(SessionVoter::VIEW, $session)) {
            throw new NotAllowedException("You're not allowed in this session");
        }

        if ($group && !$this->security->isGranted(GroupVoter::VIEW, $group)) {
            throw new NotAllowedException("You're not allowed in this group");
        }

        $query = $request->query->all();
        $cid = (int) ($query['cid'] ?? 0);
        $sid = (int) ($query['sid'] ?? 0);
        $gid = (int) ($query['gid'] ?? 0);

        $requestedFiletypes = ['file', 'folder', 'video'];
        $zipName = 'all_documents.zip';

        $response = new StreamedResponse(
            function () use ($rootNodeId, $cid, $sid, $gid, $requestedFiletypes, $zipName): void {
                $options = new Archive();
                $options->setSendHttpHeaders(false);
                $options->setContentType(self::CONTENT_TYPE);

                $zip = new ZipStream($zipName, $options);
                $hasEntries = false;

                $visibleItems = $this->findVisibleChildren($rootNodeId, $cid, $sid, $gid, $requestedFiletypes);

                foreach ($visibleItems as $document) {
                    $node = $document->getResourceNode();
                    if (!$node instanceof ResourceNode) {
                        continue;
                    }

                    $this->addVisibleNodeToZip(
                        $zip,
                        $document,
                        $node,
                        $cid,
                        $sid,
                        $gid,
                        $requestedFiletypes,
                        '',
                        $hasEntries
                    );
                }

                if (!$hasEntries) {
                    $zip->addFile('.empty', '');
                }

                $zip->finish();
            },
            Response::HTTP_CREATED
        );

        $safeZipName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $zipName);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $safeZipName
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', self::CONTENT_TYPE);

        return $response;
    }

    /**
     * @return array<CDocument>
     */
    private function findVisibleChildren(
        int $parentNodeId,
        int $cid,
        int $sid,
        int $gid,
        array $requestedFiletypes
    ): array {
        $qb = $this->entityManager
            ->getRepository(CDocument::class)
            ->createQueryBuilder('d')
            ->innerJoin('d.resourceNode', 'rn')
            ->addSelect('rn')
            ->leftJoin('rn.resourceType', 'rt')
            ->addSelect('rt')
            ->leftJoin('rt.tool', 'tool')
            ->addSelect('tool')
            ->leftJoin('rn.creator', 'creator')
            ->addSelect('creator')
        ;

        $hasContext = $cid > 0 || $sid > 0 || $gid > 0;

        $includeBaseContent = $sid > 0
            && is_a(CDocument::class, ResourceShowCourseResourcesInSessionInterface::class, true);

        $effectiveFiletypes = $requestedFiletypes;
        if (\in_array('file', $effectiveFiletypes, true) && !\in_array('html', $effectiveFiletypes, true)) {
            $effectiveFiletypes[] = 'html';
        }

        $systemFolderTypes = ['user_folder', 'user_folder_ses', 'media_folder', 'chat_folder', 'cert_folder'];

        if (\in_array('folder', $effectiveFiletypes, true)) {
            $effectiveFiletypes = array_values(array_unique(array_merge($effectiveFiletypes, $systemFolderTypes)));
        }

        if (!empty($effectiveFiletypes)) {
            $qb
                ->andWhere($qb->expr()->in('d.filetype', ':filetypes'))
                ->setParameter('filetypes', $effectiveFiletypes)
            ;
        }

        $showUsersFolders = 'true' === (string) ($this->settingsManager->getSetting('document.show_users_folders', true) ?? '');
        $showDefaultFolders = 'false' !== (string) ($this->settingsManager->getSetting('document.show_default_folders', true) ?? '');
        $showChatFolder = 'true' === (string) ($this->settingsManager->getSetting('chat.show_chat_folder', true) ?? '');

        $hiddenSystemTypes = [];

        if (!$showUsersFolders) {
            $hiddenSystemTypes[] = 'user_folder';
            $hiddenSystemTypes[] = 'user_folder_ses';
        } elseif ($sid <= 0) {
            $hiddenSystemTypes[] = 'user_folder_ses';
        }

        if (!$showDefaultFolders) {
            $hiddenSystemTypes[] = 'media_folder';
        }

        if (!$showChatFolder) {
            $hiddenSystemTypes[] = 'chat_folder';
        }

        $hiddenSystemTypes[] = 'cert_folder';
        $hiddenSystemTypes = array_values(array_unique(array_diff($hiddenSystemTypes, $requestedFiletypes)));

        if (!empty($hiddenSystemTypes)) {
            $qb
                ->andWhere($qb->expr()->notIn('d.filetype', ':hiddenFiletypes'))
                ->setParameter('hiddenFiletypes', $hiddenSystemTypes)
            ;
        }

        $meta = $this->entityManager->getClassMetadata(ResourceNode::class);
        if ($meta->hasField('path')) {
            $qb
                ->andWhere('rn.path IS NULL OR rn.path NOT LIKE :certificatesPath')
                ->setParameter('certificatesPath', '%/certificates-%')
            ;
        }

        if ($hasContext) {
            $qb->innerJoin('rn.resourceLinks', 'rl')->addSelect('rl');

            if ($cid > 0) {
                $qb->andWhere('IDENTITY(rl.course) = :cid')->setParameter('cid', $cid);
            }

            if ($sid > 0) {
                if ($includeBaseContent) {
                    $qb
                        ->andWhere(
                            $qb->expr()->orX(
                                'IDENTITY(rl.session) = :sid',
                                'rl.session IS NULL',
                                'IDENTITY(rl.session) = 0'
                            )
                        )
                        ->setParameter('sid', $sid)
                    ;
                } else {
                    $qb
                        ->andWhere('IDENTITY(rl.session) = :sid')
                        ->setParameter('sid', $sid)
                    ;
                }
            } else {
                $qb->andWhere(
                    $qb->expr()->orX(
                        'rl.session IS NULL',
                        'IDENTITY(rl.session) = 0'
                    )
                );
            }

            if ($gid > 0) {
                $qb->andWhere('IDENTITY(rl.group) = :gid')->setParameter('gid', $gid);
            } else {
                $qb->andWhere('rl.group IS NULL');
            }

            /** @var ResourceLinkRepository $linkRepo */
            $linkRepo = $this->entityManager->getRepository(ResourceLink::class);

            $folderNode = $this->entityManager->getRepository(ResourceNode::class)->find($parentNodeId);
            if (null === $folderNode) {
                return [];
            }

            $courseEntity = $cid > 0 ? $this->entityManager->getRepository(Course::class)->find($cid) : null;
            $sessionEntity = $sid > 0 ? $this->entityManager->getRepository(Session::class)->find($sid) : null;
            $groupEntity = $gid > 0 ? $this->entityManager->getRepository(CGroup::class)->find($gid) : null;

            $parentLinkIds = [];

            $sessionParentLink = $linkRepo->findParentLinkForContext(
                $folderNode,
                $courseEntity,
                $sessionEntity,
                $groupEntity,
                null,
                null
            );

            if (null !== $sessionParentLink) {
                $parentLinkIds[] = (int) $sessionParentLink->getId();
            }

            if ($sid > 0 && $includeBaseContent && null !== $courseEntity) {
                $baseParentLink = $linkRepo->findParentLinkForContext(
                    $folderNode,
                    $courseEntity,
                    null,
                    $groupEntity,
                    null,
                    null
                );

                if (null !== $baseParentLink) {
                    $parentLinkIds[] = (int) $baseParentLink->getId();
                }
            }

            $parentLinkIds = array_values(array_unique($parentLinkIds));

            if (!empty($parentLinkIds)) {
                $qb
                    ->andWhere(
                        $qb->expr()->orX(
                            'IDENTITY(rl.parent) IN (:parentLinkIds)',
                            $qb->expr()->andX(
                                'IDENTITY(rn.parent) = :parentNodeId',
                                'rl.parent IS NULL'
                            )
                        )
                    )
                    ->setParameter('parentLinkIds', $parentLinkIds)
                    ->setParameter('parentNodeId', $parentNodeId)
                ;
            } else {
                $qb
                    ->andWhere('IDENTITY(rn.parent) = :parentNodeId')
                    ->setParameter('parentNodeId', $parentNodeId)
                ;
            }

            $qb->distinct();
        } else {
            $qb->leftJoin('rn.resourceLinks', 'rl')->addSelect('rl');
            $qb
                ->andWhere('IDENTITY(rn.parent) = :parentNodeId')
                ->setParameter('parentNodeId', $parentNodeId)
            ;
        }

        $qb->orderBy('rn.title', 'ASC');

        return $qb->getQuery()->getResult();
    }

    private function addVisibleNodeToZip(
        ZipStream $zip,
        CDocument $document,
        ResourceNode $node,
        int $cid,
        int $sid,
        int $gid,
        array $requestedFiletypes,
        string $currentPath,
        bool &$hasEntries
    ): void {
        $filetype = $document->getFiletype();

        if ('folder' === $filetype
            || \in_array($filetype, ['user_folder', 'user_folder_ses', 'media_folder', 'chat_folder', 'cert_folder'], true)
        ) {
            $relativePath = $currentPath.$node->getTitle().'/';
            $zip->addFile($relativePath, '');
            $hasEntries = true;

            $children = $this->findVisibleChildren((int) $node->getId(), $cid, $sid, $gid, $requestedFiletypes);

            foreach ($children as $childDocument) {
                $childNode = $childDocument->getResourceNode();
                if (!$childNode instanceof ResourceNode) {
                    continue;
                }

                $this->addVisibleNodeToZip(
                    $zip,
                    $childDocument,
                    $childNode,
                    $cid,
                    $sid,
                    $gid,
                    $requestedFiletypes,
                    $relativePath,
                    $hasEntries
                );
            }

            return;
        }

        $resourceFile = $this->resourceFileHelper->resolveResourceFileByAccessUrl($node);

        if (!$resourceFile) {
            return;
        }

        if (!$this->security->isGranted(ResourceFileVoter::DOWNLOAD, $resourceFile)) {
            return;
        }

        $fileName = $currentPath.$resourceFile->getOriginalName();

        try {
            $stream = $this->resourceNodeRepository->getResourceNodeFileStream($node, $resourceFile);

            if (!\is_resource($stream)) {
                error_log('[Documents] Invalid file stream for resource node '.$node->getId().' and file '.$fileName);

                return;
            }

            $zip->addFileFromStream($fileName, $stream);
            $hasEntries = true;
        } catch (Throwable $e) {
            error_log(
                '[Documents] Skipping missing or unreadable file during ZIP export. '
                .'node_id='.$node->getId()
                .' file='.$fileName
                .' error='.$e->getMessage()
            );
        }
    }
}
