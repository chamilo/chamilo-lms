<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'justification_document_rel_users')]
class JustificationDocumentRelUsers
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: JustificationDocument::class)]
    #[ORM\JoinColumn(name: 'justification_document_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private JustificationDocument $justificationDocument;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTime $dateValidity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJustificationDocument(): JustificationDocument
    {
        return $this->justificationDocument;
    }

    public function setJustificationDocument(JustificationDocument $justificationDocument): self
    {
        $this->justificationDocument = $justificationDocument;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDateValidity(): ?\DateTime
    {
        return $this->dateValidity;
    }

    public function setDateValidity(?\DateTime $dateValidity): self
    {
        $this->dateValidity = $dateValidity;

        return $this;
    }
}
