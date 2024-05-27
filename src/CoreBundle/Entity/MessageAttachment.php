<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\Repository\Node\MessageAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    types: ['http://schema.org/MediaObject'],
    operations: [
        new Get(),
    ],
    normalizationContext: [
        'groups' => ['message:read'],
    ],
)]
#[ORM\Table(name: 'message_attachment')]
#[ORM\Entity(repositoryClass: MessageAttachmentRepository::class)]
class MessageAttachment extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    #[ORM\Column(name: 'path', type: 'string', length: 255, nullable: false)]
    protected string $path;

    #[Groups(['message:read', 'message:write'])]
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment = null;

    #[ORM\Column(name: 'size', type: 'integer', nullable: false)]
    protected int $size;

    #[ORM\ManyToOne(targetEntity: Message::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', nullable: false)]
    protected Message $message;

    #[ORM\Column(name: 'filename', type: 'string', length: 255, nullable: false)]
    protected string $filename;

    #[Groups(['message:write'])]
    protected ResourceFile $resourceFileToAttach;

    public function __construct()
    {
        $this->size = 0;
        $this->comment = '';
        $this->path = '';
    }

    public function __toString(): string
    {
        return $this->getFilename();
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getResourceName(): string
    {
        return $this->getFilename();
    }

    public function setResourceName(string $name): self
    {
        return $this->setFilename($name);
    }

    public function getResourceFileToAttach(): ResourceFile
    {
        return $this->resourceFileToAttach;
    }

    public function setResourceFileToAttach(ResourceFile $resourceFileToAttach): self
    {
        $this
            ->setFilename($resourceFileToAttach->getOriginalName())
            ->setSize($resourceFileToAttach->getSize())
            ->setPath($resourceFileToAttach->getTitle())
        ;

        $this->resourceFileToAttach = $resourceFileToAttach;

        return $this;
    }
}
