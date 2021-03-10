<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Component\Resource\Settings;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\Resource\CDocumentType;
use Chamilo\CoreBundle\Repository\GridInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\UploadInterface;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class CDocumentRepository extends ResourceRepository implements GridInterface, UploadInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CDocument::class);
    }

    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }

    public function getResourceSettings(): Settings
    {
        $settings = parent::getResourceSettings();

        $settings
            ->setAllowNodeCreation(true)
            ->setAllowResourceCreation(true)
            ->setAllowResourceUpload(true)
            ->setAllowDownloadAll(true)
            ->setAllowDiskSpace(true)
            ->setAllowToSaveEditorToResourceFile(true)
        ;

        return $settings;
    }

    public function saveUpload(UploadedFile $file): ResourceInterface
    {
        $resource = new CDocument();
        $resource
            ->setFiletype('file')
            //->setSize($file->getSize())
            ->setTitle($file->getClientOriginalName())
        ;

        return $resource;
    }

    public function setResourceProperties(FormInterface $form, Course $course, Session $session, string $fileType): void
    {
        $newResource = $form->getData();
        $newResource
            //->setCourse($course)
            //->setSession($session)
            ->setFiletype($fileType)
            //->setTitle($title) // already added in $form->getData()
            ->setReadonly(false)
        ;

        //return $newResource;
    }

    public function getParent(CDocument $document): ?CDocument
    {
        $resourceParent = $document->getResourceNode()->getParent();

        if (null !== $resourceParent) {
            $resourceParentId = $resourceParent->getId();
            $criteria = [
                'resourceNode' => $resourceParentId,
            ];

            return $this->findOneBy($criteria);
        }

        return null;
    }

    public function getFolderSize(ResourceNode $resourceNode, Course $course, Session $session = null): int
    {
        return $this->getResourceNodeRepository()->getSize($resourceNode, $this->getResourceType(), $course, $session);
    }

    /**
     * @return CDocument[]
     */
    public function findDocumentsByAuthor(int $userId)
    {
        $repo = $this->repository;

        $qb = $repo->createQueryBuilder('d');
        $query = $qb
            ->innerJoin('d.resourceNode', 'node')
            ->innerJoin('r.resourceLinks', 'l')
            ->where('l.user = :user')
            ->andWhere('l.visibility <> :visibility')
            ->setParameters([
                'user' => $userId,
                'visibility' => ResourceLink::VISIBILITY_DELETED,
            ])
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function countUserDocuments(User $user, Course $course, Session $session = null, CGroup $group = null)
    {
        $qb = $this->getResourcesByCourseLinkedToUser($user, $course, $session, $group);

        // Add "not deleted" filters.
        $qb->select('count(resource)');

        $this->addFileTypeQueryBuilder('file', $qb);

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getResourceFormType(): string
    {
        return CDocumentType::class;
    }

    protected function addFileTypeQueryBuilder(string $fileType, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        $qb
            ->andWhere('resource.fileType :type')
            ->setParameter('type', $fileType)
        ;

        return $qb;
    }
}
