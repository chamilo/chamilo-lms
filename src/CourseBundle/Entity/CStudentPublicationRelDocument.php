<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(security: "object.getPublication() != null and is_granted('VIEW', object.getPublication().resourceNode)"),
        new Delete(security: "(is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')) and object.getPublication() != null and is_granted('EDIT', object.getPublication().resourceNode)"),
        new Post(
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            securityPostDenormalize: "object.getPublication() != null and is_granted('EDIT', object.getPublication().resourceNode) and object.getDocument() != null and object.isDocumentInPublicationCourse()",
        ),
        new GetCollection(security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')"),
    ],
    normalizationContext: ['groups' => ['student_publication_rel_document:read']],
    denormalizationContext: ['groups' => ['student_publication_rel_document:write']]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'publication.iid' => 'exact',
    'document.iid' => 'exact',
])]
#[ORM\Table(name: 'c_student_publication_rel_document')]
#[ORM\Entity]
class CStudentPublicationRelDocument
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['student_publication_rel_document:read'])]
    protected ?int $iid = null;

    #[ORM\ManyToOne(targetEntity: CStudentPublication::class)]
    #[ORM\JoinColumn(name: 'work_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    #[Groups(['student_publication_rel_document:read', 'student_publication_rel_document:write'])]
    protected CStudentPublication $publication;

    #[ORM\ManyToOne(targetEntity: CDocument::class)]
    #[ORM\JoinColumn(name: 'document_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    #[Groups(['student_publication_rel_document:read', 'student_publication_rel_document:write'])]
    protected CDocument $document;

    public function getPublication(): CStudentPublication
    {
        return $this->publication;
    }

    public function setPublication(CStudentPublication $publication): self
    {
        $this->publication = $publication;

        return $this;
    }

    public function getDocument(): CDocument
    {
        return $this->document;
    }

    public function setDocument(CDocument $document): self
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Whether the document belongs to the course and session the assignment is published in.
     * A base-course document is shared with every session of the course; a session document
     * only with its own session.
     */
    public function isDocumentInPublicationCourse(): bool
    {
        $contexts = [];
        foreach ($this->publication->getResourceNode()?->getResourceLinks() ?? [] as $link) {
            if (null !== $link->getCourse()) {
                $contexts[] = [(int) $link->getCourse()->getId(), (int) $link->getSession()?->getId()];
            }
        }

        foreach ($this->document->getResourceNode()?->getResourceLinks() ?? [] as $link) {
            if (null === $link->getCourse()) {
                continue;
            }

            $courseId = (int) $link->getCourse()->getId();
            $sessionId = (int) $link->getSession()?->getId();

            foreach ($contexts as [$publicationCourseId, $publicationSessionId]) {
                if ($courseId === $publicationCourseId && (0 === $sessionId || $sessionId === $publicationSessionId)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }
}
