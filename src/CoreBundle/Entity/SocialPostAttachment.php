<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\Controller\Api\CreateSocialPostAttachmentAction;
use Chamilo\CoreBundle\Repository\Node\SocialPostAttachmentRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ApiResource(
    types: ['http://schema.org/MediaObject'],
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(
            controller: CreateSocialPostAttachmentAction::class,
            openapi: new Operation(
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                    'messageId' => [
                                        'type' => 'integer',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                ),
            ),
            security: 'is_granted(\'ROLE_USER\')',
            validationContext: [
                'groups' => [
                    'Default',
                    'message_attachment:create',
                ],
            ],
            deserialize: false
        ),
    ],
    normalizationContext: [
        'groups' => ['message:read'],
    ],
)]
#[ORM\Table(name: 'social_post_attachments')]
#[ORM\Entity(repositoryClass: SocialPostAttachmentRepository::class)]
class SocialPostAttachment extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SocialPost::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'social_post_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected SocialPost $socialPost;

    #[ORM\Column(name: 'path', type: 'string', length: 255, nullable: false)]
    protected string $path;

    #[ORM\Column(name: 'filename', type: 'text', nullable: false)]
    protected string $filename;

    #[ORM\Column(name: 'size', type: 'integer')]
    protected int $size;

    #[ORM\Column(name: 'sys_insert_user_id', type: 'integer')]
    protected int $insertUserId;

    #[ORM\Column(name: 'sys_insert_datetime', type: 'datetime')]
    protected DateTime $insertDateTime;

    #[ORM\Column(name: 'sys_lastedit_user_id', type: 'integer', nullable: true, unique: false)]
    protected ?int $lastEditUserId = null;

    #[ORM\Column(name: 'sys_lastedit_datetime', type: 'datetime', nullable: true, unique: false)]
    protected ?DateTime $lastEditDateTime = null;

    public function __toString(): string
    {
        return $this->getFilename();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
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
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getSocialPost(): SocialPost
    {
        return $this->socialPost;
    }

    public function setSocialPost(SocialPost $socialPost): self
    {
        $this->socialPost = $socialPost;

        return $this;
    }

    public function getResourceName(): string
    {
        return $this->getFilename();
    }

    public function setResourceName(string $name)
    {
        return $this->setFilename($name);
    }

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    public function setInsertUserId(int $insertUserId): self
    {
        $this->insertUserId = $insertUserId;

        return $this;
    }

    public function setInsertDateTime(DateTime $insertDateTime): self
    {
        $this->insertDateTime = $insertDateTime;

        return $this;
    }
}
