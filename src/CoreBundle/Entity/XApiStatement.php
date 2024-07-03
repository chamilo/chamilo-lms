<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiStatementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: XApiStatementRepository::class)]
class XApiStatement
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column]
    private ?string $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $created = null;

    #[ORM\Column(nullable: true)]
    private ?int $stored = null;

    #[ORM\Column]
    private ?bool $hasAttachments = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiObject $actor = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiVerb $verb = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiObject $object = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiResult $result = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiObject $authority = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiContext $context = null;

    /**
     * @var Collection<int, XApiAttachment>
     */
    #[ORM\OneToMany(mappedBy: 'statement', targetEntity: XApiAttachment::class)]
    private Collection $attachments;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCreated(): ?int
    {
        return $this->created;
    }

    public function setCreated(?int $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getStored(): ?int
    {
        return $this->stored;
    }

    public function setStored(?int $stored): static
    {
        $this->stored = $stored;

        return $this;
    }

    public function hasAttachments(): ?bool
    {
        return $this->hasAttachments;
    }

    public function setHasAttachments(bool $hasAttachments): static
    {
        $this->hasAttachments = $hasAttachments;

        return $this;
    }

    public function getActor(): ?XApiObject
    {
        return $this->actor;
    }

    public function setActor(?XApiObject $actor): static
    {
        $this->actor = $actor;

        return $this;
    }

    public function getVerb(): ?XApiVerb
    {
        return $this->verb;
    }

    public function setVerb(?XApiVerb $verb): static
    {
        $this->verb = $verb;

        return $this;
    }

    public function getObject(): ?XApiObject
    {
        return $this->object;
    }

    public function setObject(?XApiObject $object): static
    {
        $this->object = $object;

        return $this;
    }

    public function getResult(): ?XApiResult
    {
        return $this->result;
    }

    public function setResult(?XApiResult $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getAuthority(): ?XApiObject
    {
        return $this->authority;
    }

    public function setAuthority(?XApiObject $authority): static
    {
        $this->authority = $authority;

        return $this;
    }

    public function getContext(): ?XApiContext
    {
        return $this->context;
    }

    public function setContext(?XApiContext $context): static
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return Collection<int, XApiAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(XApiAttachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setStatement($this);
        }

        return $this;
    }

    public function removeAttachment(XApiAttachment $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            // set the owning side to null (unless already changed)
            if ($attachment->getStatement() === $this) {
                $attachment->setStatement(null);
            }
        }

        return $this;
    }
}
