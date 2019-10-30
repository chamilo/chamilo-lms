<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Resource\ResourceRight;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CDocument;
use Gaufrette\Exception\FileNotFound;

/**
 * Class CDocumentRepository.
 */
final class CDocumentRepository extends ResourceRepository
{
    /**
     * @param int $id
     *
     * @return string
     */
    public function getDocumentContent($id): string
    {
        try {
            /** @var CDocument $document */
            $document = $this->find($id);
            $resourceNode = $document->getResourceNode();
            $resourceFile = $resourceNode->getResourceFile();
            $fileName = $resourceFile->getFile()->getPathname();

            return $this->fs->read($fileName);
        } catch (\Throwable $exception) {
            throw new FileNotFound($id);
        }
    }

    /**
     * @param CDocument $document
     * @param string    $content
     *
     * @return bool
     */
    public function updateDocumentContent(CDocument $document, $content)
    {
        try {
            $resourceNode = $document->getResourceNode();
            $resourceFile = $resourceNode->getResourceFile();
            $fileName = $resourceFile->getFile()->getPathname();

            $this->fs->update($fileName, $content);
            $size = $this->fs->getSize($fileName);
            $document->setSize($size);
            $this->entityManager->persist($document);

            return true;
        } catch (\Throwable $exception) {
        }
    }

    /**
     * @param CDocument $document
     *
     * @return CDocument|null
     */
    public function getParent(CDocument $document)
    {
        $resourceParent = $document->getResourceNode()->getParent();

        if ($resourceParent !== null) {
            $resourceParentId = $resourceParent->getId();
            $criteria = [
                'resourceNode' => $resourceParentId,
            ];

            return $this->findOneBy($criteria);
        }

        return null;
    }

    /**
     * @param int    $courseId
     * @param string $path
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return mixed
     */
    public function getFolderSize($courseId, $path)
    {
        $path = str_replace('_', '\_', $path);
        $addedSlash = $path === '/' ? '' : '/';

        $repo = $this->getRepository();
        $qb = $repo->createQueryBuilder('d');
        $query = $qb
            ->select('SUM(d.size)')
            ->innerJoin('d.resourceNode', 'r')
            ->innerJoin('r.resourceLinks', 'l')
            ->where('d.path LIKE :path')
            ->andWhere('d.path NOT LIKE :deleted')
            ->andWhere('d.path NOT LIKE :extra_path ')
            ->andWhere('l.visibility <> :visibility')
            ->andWhere('d.course = :course')
            ->setParameters([
                'path' => $path.$addedSlash.'%',
                'extra_path' => $path.$addedSlash.'%/%',
                'course' => $courseId,
                'deleted' => '%_DELETED_%',
                'visibility' => ResourceLink::VISIBILITY_DELETED,
            ])
            ->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * @param int $courseId
     * @param int $groupId
     * @param int $sessionId
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return mixed
     */
    public function getTotalSpace($courseId, $groupId = null, $sessionId = null)
    {
        $repo = $this->getRepository();
        $groupId = empty($groupId) ? null : $groupId;
        $sessionId = empty($sessionId) ? null : $sessionId;

        $qb = $repo->createQueryBuilder('d');
        $query = $qb
            ->select('SUM(d.size)')
            ->innerJoin('d.resourceNode', 'r')
            ->innerJoin('r.resourceLinks', 'l')
            ->where('l.course = :course')
            ->andWhere('l.group = :group')
            ->andWhere('l.session = :session')
            ->andWhere('l.visibility <> :visibility')
            ->setParameters([
                'course' => $courseId,
                'group' => $groupId,
                'session' => $sessionId,
                'visibility' => ResourceLink::VISIBILITY_DELETED,
            ])
            ->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * Changes current document link visibility.
     *
     * @param CDocument $document
     * @param int       $visibility
     *
     * @return bool
     */
    public function updateVisibility($document, $visibility): bool
    {
        if (empty($document)) {
            return false;
        }

        $em = $this->getEntityManager();
        $link = $document->getCourseSessionResourceLink();
        $link->setVisibility($visibility);

        if ($visibility === ResourceLink::VISIBILITY_DRAFT) {
            $editorMask = ResourceNodeVoter::getEditorMask();
            $rights = [];
            $resourceRight = new ResourceRight();
            $resourceRight
                ->setMask($editorMask)
                ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)
                ->setResourceLink($link)
            ;
            $rights[] = $resourceRight;

            if (!empty($rights)) {
                $link->setResourceRight($rights);
            }
        } else {
            $link->setResourceRight([]);
        }
        $em->persist($link);
        $em->flush();

        return true;
    }

    /**
     * Change all links visibility to DELETED.
     *
     * @param CDocument $document
     */
    public function softDelete($document)
    {
        $this->setLinkVisibility($document, ResourceLink::VISIBILITY_DELETED);
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public function getAllDocumentsByAuthor($userId)
    {
        $repo = $this->repository;

        $qb = $repo->createQueryBuilder('d');
        $query = $qb
            ->innerJoin('d.resourceNode', 'r')
            ->innerJoin('r.resourceLinks', 'l')
            ->where('l.user = :user')
            ->andWhere('l.visibility <> :visibility')
            ->setParameters([
                'user' => $userId,
                'visibility' => ResourceLink::VISIBILITY_DELETED,
            ])
            ->getQuery();

        return $query->getResult();
    }

    /**
     * @param CDocument $document
     * @param int       $visibility
     * @param bool      $recursive
     */
    private function setLinkVisibility($document, $visibility, $recursive = true)
    {
        $resourceNode = $document->getResourceNode();
        $children = $resourceNode->getChildren();
        $em = $this->getEntityManager();

        if ($recursive) {
            if (!empty($children)) {
                /** @var ResourceNode $child */
                foreach ($children as $child) {
                    $criteria = ['resourceNode' => $child];
                    $childDocument = $this->repository->findOneBy($criteria);
                    if ($childDocument) {
                        $this->setLinkVisibility($childDocument, $visibility);
                    }
                }
            }
        }

        $links = $resourceNode->getResourceLinks();

        if (!empty($links)) {
            /** @var ResourceLink $link */
            foreach ($links as $link) {
                $link->setVisibility($visibility);

                if ($visibility === ResourceLink::VISIBILITY_DRAFT) {
                    $editorMask = ResourceNodeVoter::getEditorMask();
                    $rights = [];
                    $resourceRight = new ResourceRight();
                    $resourceRight
                        ->setMask($editorMask)
                        ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)
                        ->setResourceLink($link)
                    ;
                    $rights[] = $resourceRight;

                    if (!empty($rights)) {
                        $link->setResourceRight($rights);
                    }
                } else {
                    $link->setResourceRight([]);
                }
                $em->merge($link);
            }
        }
        $em->flush();
    }
}
