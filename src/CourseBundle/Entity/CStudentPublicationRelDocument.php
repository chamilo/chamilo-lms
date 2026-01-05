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
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(),
        new Delete(),
        new Post(),
        new GetCollection(),
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

    public function getIid(): ?int
    {
        return $this->iid;
    }
}
