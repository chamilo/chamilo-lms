<?php

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Controller\Api\CreateDropboxFileAction;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Repository\CDropboxFileRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Table(name: 'c_dropbox_file', options: ['row_format' => 'DYNAMIC'])]
#[ORM\Index(columns: ['c_id'], name: 'course')]
#[ORM\Index(columns: ['session_id'], name: 'session_id')]
#[ORM\UniqueConstraint(name: 'UN_filename', columns: ['filename'])]
#[ORM\Entity(repositoryClass: CDropboxFileRepository::class)]
#[ApiResource(operations: [
    new Post(
        uriTemplate: '/c_dropbox_files/upload',
        controller: CreateDropboxFileAction::class,
        security: "is_granted('IS_AUTHENTICATED_REMEMBERED')",
        validationContext: ['groups' => ['Default']],
        output: false,
        deserialize: false
    ),
])]
class CDropboxFile extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\Column(name: 'c_id', type: 'integer', options: ['default' => 0])]
    protected int $cId = 0;

    #[ORM\Column(name: 'uploader_id', type: 'integer', nullable: false)]
    protected int $uploaderId;

    #[ORM\Column(name: 'filename', type: 'string', length: 190, nullable: false)]
    protected string $filename;

    #[ORM\Column(name: 'filesize', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $filesize = 0;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'description', type: 'string', length: 250, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'author', type: 'string', length: 250, nullable: true)]
    protected ?string $author = null;

    #[ORM\Column(name: 'upload_date', type: 'datetime', nullable: false)]
    protected DateTime $uploadDate;

    #[ORM\Column(name: 'last_upload_date', type: 'datetime', nullable: false)]
    protected DateTime $lastUploadDate;

    #[ORM\Column(name: 'cat_id', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $catId = 0;

    #[ORM\Column(name: 'session_id', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $sessionId = 0;

    protected string $filetype = 'file';

    public function __construct()
    {
        // Keep dates always set
        $now = new DateTime();
        $this->uploadDate = $now;
        $this->lastUploadDate = $now;
    }

    /**
     * Required by ResourceInterface.
     */
    public function getResourceIdentifier(): int
    {
        return (int) $this->getIid();
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    // --- Accessors ---

    public function setUploaderId(int $uploaderId): self
    {
        $this->uploaderId = $uploaderId;

        return $this;
    }

    public function getUploaderId(): int
    {
        return $this->uploaderId;
    }

    public function setFilename(string $filename): self
    {
        // Important: filename should be unique as per DB constraint.
        $this->filename = $filename;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilesize(int $filesize): self
    {
        $this->filesize = $filesize;

        return $this;
    }

    public function getFileTypes(): array
    {
        return ['file', 'folder'];
    }

    public function getFiletype(): string
    {
        return $this->filetype;
    }

    public function setFiletype(string $filetype): self
    {
        $this->filetype = $filetype;

        return $this;
    }

    public function getFilesize(): int
    {
        return $this->filesize;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        // Keep ResourceNode title in sync at processors/services level.
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setUploadDate(DateTime $uploadDate): self
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    public function getUploadDate(): DateTime
    {
        return $this->uploadDate;
    }

    public function setLastUploadDate(DateTime $lastUploadDate): self
    {
        $this->lastUploadDate = $lastUploadDate;

        return $this;
    }

    public function getLastUploadDate(): DateTime
    {
        return $this->lastUploadDate;
    }

    public function setCatId(int $catId): self
    {
        $this->catId = $catId;

        return $this;
    }

    public function getCatId(): int
    {
        return $this->catId;
    }

    public function setSessionId(int $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getSessionId(): int
    {
        return $this->sessionId;
    }

    public function setCId(int $cId): self
    {
        $this->cId = $cId;

        return $this;
    }

    public function getCId(): int
    {
        return $this->cId;
    }
}
