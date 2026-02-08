<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<CDocument>
 */
final class DocumentCollectionStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<CDocument>|CDocument|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        $qb = $this->entityManager
            ->getRepository(CDocument::class)
            ->createQueryBuilder('d')
            ->innerJoin('d.resourceNode', 'rn')
            ->addSelect('rn')
        ;

        $query = $request->query->all();
        $cid = (int) ($query['cid'] ?? 0);
        $sid = (int) ($query['sid'] ?? 0);
        $gid = (int) ($query['gid'] ?? 0);

        $hasContext = $cid > 0 || $sid > 0 || $gid > 0;

        // By default, documents must be visible in session context including base course content,
        // because CDocument implements ResourceShowCourseResourcesInSessionInterface.
        $includeBaseContent = $sid > 0
            && is_a(CDocument::class, ResourceShowCourseResourcesInSessionInterface::class, true);

        // Allow API clients to override behavior (withBaseContent=0/1).
        if (\array_key_exists('withBaseContent', $query)) {
            $includeBaseContent = 1 === (int) $query['withBaseContent'];
        }

        // Gradebook mode (e.g. gradebook=1)
        $isGradebook = !empty($query['gradebook']) && 1 === (int) $query['gradebook'];
        $showUsersFolders = 'true' === (string) ($this->settingsManager->getSetting('document.show_users_folders', true) ?? '');
        $showDefaultFolders = 'false' !== (string) ($this->settingsManager->getSetting('document.show_default_folders', true) ?? '');
        $showChatFolder = 'true' === (string) ($this->settingsManager->getSetting('chat.show_chat_folder', true) ?? '');

        $rawFiletype = $query['filetype'] ?? null;
        $filetypes = [];

        if (\is_array($rawFiletype)) {
            $filetypes = $rawFiletype;
        } elseif (null !== $rawFiletype && '' !== (string) $rawFiletype) {
            $filetypes = [(string) $rawFiletype];
        }

        // Normalize & unique
        $requestedFiletypes = array_values(array_unique(array_filter(array_map('strval', $filetypes))));

        // Compatibility: treat "html" as a subtype of "file"
        $effectiveFiletypes = $requestedFiletypes;
        if (\in_array('file', $effectiveFiletypes, true) && !\in_array('html', $effectiveFiletypes, true)) {
            $effectiveFiletypes[] = 'html';
        }

        // System folder subtypes
        $systemFolderTypes = ['user_folder', 'user_folder_ses', 'media_folder', 'chat_folder', 'cert_folder'];

        // If the client asks for "folder", include system folder subtypes as well.
        // This keeps folder browsing working after migration (system folders have custom filetypes).
        if (\in_array('folder', $effectiveFiletypes, true)) {
            $effectiveFiletypes = array_values(array_unique(array_merge($effectiveFiletypes, $systemFolderTypes)));
        }

        if (!empty($effectiveFiletypes)) {
            $qb
                ->andWhere($qb->expr()->in('d.filetype', ':filetypes'))
                ->setParameter('filetypes', $effectiveFiletypes)
            ;
        }

        // NOTE: this is for "certificate" filetype lists, not the certificates folder filetype.
        $wantsCertificateList = \in_array('certificate', $effectiveFiletypes, true);
        $showSystemCertificates = !empty($query['showSystemCertificates']) && 1 === (int) $query['showSystemCertificates'];

        if (!$showSystemCertificates && !($isGradebook && $wantsCertificateList)) {
            $meta = $this->entityManager->getClassMetadata(ResourceNode::class);
            if ($meta->hasField('path')) {
                $qb
                    ->andWhere('rn.path IS NULL OR rn.path NOT LIKE :certificatesPath')
                    ->setParameter('certificatesPath', '%/certificates-%')
                ;
            }
        }

        // Hide system folders depending on settings.
        $explicitSystemTypes = array_values(array_intersect($requestedFiletypes, $systemFolderTypes));
        $hiddenSystemTypes = [];

        // User folders
        if (!$showUsersFolders) {
            $hiddenSystemTypes[] = 'user_folder';
            $hiddenSystemTypes[] = 'user_folder_ses';
        } else {
            // When enabled, never show session-scoped shared folders in base course context.
            if ($sid <= 0) {
                $hiddenSystemTypes[] = 'user_folder_ses';
            }
        }

        // Default media folders
        if (!$showDefaultFolders) {
            $hiddenSystemTypes[] = 'media_folder';
        }

        // Chat history folder (controlled by chat.show_chat_folder)
        if (!$showChatFolder) {
            $hiddenSystemTypes[] = 'chat_folder';
        }

        // Certificates folder: hide unless explicitly requested.
        if (!$showSystemCertificates && !($isGradebook && $wantsCertificateList)) {
            $hiddenSystemTypes[] = 'cert_folder';
        }

        $hiddenSystemTypes = array_values(array_unique(array_diff($hiddenSystemTypes, $explicitSystemTypes)));

        if (!empty($hiddenSystemTypes)) {
            $qb
                ->andWhere($qb->expr()->notIn('d.filetype', ':hiddenFiletypes'))
                ->setParameter('hiddenFiletypes', $hiddenSystemTypes)
            ;
        }

        $loadNode = !empty($query['loadNode']) && 1 === (int) $query['loadNode'];

        $parentNodeId = 0;
        if (isset($query['resourceNode.parent'])) {
            $parentNodeId = (int) $query['resourceNode.parent'];
        } elseif (isset($query['resourceNode_parent'])) {
            $parentNodeId = (int) $query['resourceNode_parent'];
        }

        if ($hasContext) {
            // Contextual hierarchy based on ResourceLink.parent
            $qb->innerJoin('rn.resourceLinks', 'rl');

            if ($cid > 0) {
                $qb->andWhere('IDENTITY(rl.course) = :cid')->setParameter('cid', $cid);
            }

            if ($sid > 0) {
                if ($includeBaseContent) {
                    // Include both session content and base course content.
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

            if ($loadNode) {
                if ($parentNodeId > 0) {
                    $folderNode = $this->entityManager->getRepository(ResourceNode::class)->find($parentNodeId);
                    if (null === $folderNode) {
                        // Folder not found -> nothing to list
                        return [];
                    }

                    /** @var ResourceLinkRepository $linkRepo */
                    $linkRepo = $this->entityManager->getRepository(ResourceLink::class);

                    $courseEntity = $cid > 0 ? $this->entityManager->getRepository(Course::class)->find($cid) : null;
                    $sessionEntity = $sid > 0 ? $this->entityManager->getRepository(Session::class)->find($sid) : null;
                    $groupEntity = $gid > 0 ? $this->entityManager->getRepository(CGroup::class)->find($gid) : null;

                    // Resolve possible folder links:
                    // - session link (sid=current session)
                    // - base link (sid=NULL), when base content is enabled in session view
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
                        // If contextual parent links exist, prioritize rl.parent.
                        // The rn.parent fallback must only include items without contextual hierarchy (rl.parent IS NULL),
                        // otherwise moved items might appear in old folders due to rn.parent not changing.
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
                        // No parent link resolved -> fallback to legacy tree
                        $qb
                            ->andWhere('IDENTITY(rn.parent) = :parentNodeId')
                            ->setParameter('parentNodeId', $parentNodeId)
                        ;
                    }
                } else {
                    // Context root
                    $qb->andWhere('rl.parent IS NULL');
                }
            }

            $qb->distinct();
        } else {
            // No context: legacy behavior
            if ($parentNodeId > 0) {
                $qb
                    ->andWhere('IDENTITY(rn.parent) = :parentId')
                    ->setParameter('parentId', $parentNodeId)
                ;
            }
        }

        $qb->orderBy('rn.title', 'ASC');

        $page = isset($query['page']) ? (int) $query['page'] : 1;
        $itemsPerPage = isset($query['itemsPerPage']) ? (int) $query['itemsPerPage'] : 20;

        if ($page < 1) {
            $page = 1;
        }

        // Prevent extreme values (frontend sometimes sends huge numbers)
        if ($itemsPerPage <= 0) {
            $itemsPerPage = 20;
        }
        if ($itemsPerPage > 5000) {
            $itemsPerPage = 5000;
        }

        $qb
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;

        return $qb->getQuery()->getResult();
    }
}
