<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Component\Resource\Settings;
use Chamilo\CoreBundle\Entity\Course;
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

/**
 * Class CDocumentRepository.
 */
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

    public function saveUpload(UploadedFile $file)
    {
        $resource = new CDocument();
        $resource
            ->setFiletype('file')
            //->setSize($file->getSize())
            ->setTitle($file->getClientOriginalName())
        ;

        return $resource;
    }

    public function setResourceProperties(FormInterface $form, $course, $session, $fileType)
    {
        $newResource = $form->getData();
        $newResource
            //->setCourse($course)
            //->setSession($session)
            ->setFiletype($fileType)
            //->setTitle($title) // already added in $form->getData()
            ->setReadonly(false)
        ;

        return $newResource;
    }

    /**
     * @return string
     */
    public function getDocumentUrl(CDocument $document, $courseId, $sessionId)
    {
        // There are no URL for folders.
        if ('folder' === $document->getFiletype()) {
            return '';
        }

        $file = $document->getResourceNode()->getResourceFile();

        if (null === $file) {
            return '';
        }

        $params = [
            'cid' => $courseId,
            'sid' => $sessionId,
            'id' => $document->getResourceNode()->getId(),
            'tool' => 'document',
            'type' => $document->getResourceNode()->getResourceType()->getName(),
        ];

        return $this->getRouter()->generate('chamilo_core_resource_view', $params);
    }

    /**
     * @return CDocument|null
     */
    public function getParent(CDocument $document)
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

    public function getResourceFormType(): string
    {
        return CDocumentType::class;
    }
}
