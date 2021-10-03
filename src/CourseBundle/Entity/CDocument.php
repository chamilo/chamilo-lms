<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Chamilo\CoreBundle\Controller\Api\CreateDocumentFileAction;
use Chamilo\CoreBundle\Controller\Api\UpdateDocumentFileAction;
use Chamilo\CoreBundle\Controller\Api\UpdateVisibilityDocument;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     shortName="Documents",
 *     normalizationContext={"groups"={"document:read", "resource_node:read"}},
 *     denormalizationContext={"groups"={"document:write"}},
 *     itemOperations={
 *         "put" ={
 *             "controller"=UpdateDocumentFileAction::class,
 *             "deserialize"=false,
 *             "security" = "is_granted('EDIT', object.resourceNode)",
 *             "validation_groups"={"media_object_create", "document:write"},
 *         },
 *         "put_toggle_visibility" = {
 *             "method" = "PUT",
 *             "path"="/documents/{iid}/toggle_visibility",
 *             "controller"=UpdateVisibilityDocument::class,
 *         },
 *         "get" = {
 *             "security" = "is_granted('VIEW', object.resourceNode)",
 *         },
 *         "delete" = {
 *             "security" = "is_granted('DELETE', object.resourceNode)",
 *         },
 *
 *     },
 *     collectionOperations={
 *         "post"={
 *             "controller"=CreateDocumentFileAction::class,
 *             "deserialize"=false,
 *             "security"="is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
 *             "validation_groups"={"Default", "media_object_create", "document:write"},
 *             "openapi_context"={
 *                 "requestBody"={
 *                     "content"={
 *                         "multipart/form-data"={
 *                             "schema"={
 *                                 "type"="object",
 *                                 "properties"={
 *                                     "title"={
 *                                         "type"="string",
 *                                     },
 *                                     "filetype"={
 *                                         "type"="string",
 *                                         "enum"={"folder", "file"},
 *                                     },
 *                                     "comment"={
 *                                         "type"="string",
 *                                     },
 *                                     "contentFile"={
 *                                         "type"="string",
 *                                     },
 *                                     "uploadFile"={
 *                                         "type"="string",
 *                                         "format"="binary"
 *                                     },
 *                                     "parentResourceNodeId"={
 *                                         "type"="integer",
 *                                     },
 *                                     "resourceLinkList"={
 *                                         "type"="array",
 *                                         "items": {
 *                                             "type": "object",
 *                                             "properties"={
 *                                                 "visibility"={
 *                                                     "type"="integer",
 *                                                 },
 *                                                 "cid"={
 *                                                     "type"="integer",
 *                                                 },
 *                                                 "gid"={
 *                                                     "type"="integer",
 *                                                 },
 *                                                 "sid"={
 *                                                     "type"="integer",
 *                                                 }
 *                                             }
 *                                         }
 *                                     },
 *                                 }
 *                             }
 *                         }
 *                     }
 *                 }
 *             }
 *         },
 *         "get" = {
 *             "openapi_context" = {
 *                 "parameters" = {
 *                     {
 *                         "name" = "resourceNode.parent",
 *                         "in" = "query",
 *                         "required" = true,
 *                         "description" = "Resource node Parent",
 *                         "schema" = {
 *                             "type" = "integer"
 *                         }
 *                     },
 *                     {
 *                         "name" = "cid",
 *                         "in" = "query",
 *                         "required" = true,
 *                         "description" = "Course id",
 *                         "schema" = {
 *                             "type" = "integer"
 *                         }
 *                     },
 *                     {
 *                         "name" = "sid",
 *                         "in" = "query",
 *                         "required" = false,
 *                         "description" = "Session id",
 *                         "schema" = {
 *                             "type" = "integer"
 *                         }
 *                     }
 *                 }
 *             }
 *         }
 *     },
 * )
 *
 * @ORM\Table(
 *     name="c_document",
 *     indexes={
 *         @ORM\Index(name="idx_cdoc_type", columns={"filetype"}),
 *     }
 * )
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\ResourceListener"})
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Repository\CDocumentRepository")
 */
#[ApiFilter(PropertyFilter::class)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'resourceNode.parent' => 'exact',
])]
//resourceNode.resourceLinks.course can be used but instead cid/sid/gid is used
#[ApiFilter(OrderFilter::class, properties: [
    'iid',
    'filetype',
    'resourceNode.title',
    'resourceNode.createdAt',
    'resourceNode.resourceFile.size',
    'resourceNode.updatedAt',
])]
class CDocument extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[ApiProperty(identifier: true)]
    #[Groups(['document:read'])]
    protected int $iid;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    #[Groups(['document:read', 'document:write', 'document:browse'])]
    #[Assert\NotBlank]
    protected string $title;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    #[Groups(['document:read', 'document:write'])]
    protected ?string $comment;

    /**
     * @Assert\Choice({"folder", "file"}, message="Choose a valid filetype.")
     * @ORM\Column(name="filetype", type="string", length=10, nullable=false)
     */
    #[Groups(['document:read', 'document:write'])]
    protected string $filetype;

    /**
     * @ORM\Column(name="readonly", type="boolean", nullable=false)
     */
    protected bool $readonly;

    /**
     * @ORM\Column(name="template", type="boolean", nullable=false)
     */
    protected bool $template;

    public function __construct()
    {
        $this->comment = '';
        $this->filetype = 'folder';
        $this->readonly = false;
        $this->template = false;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function isTemplate(): bool
    {
        return $this->template;
    }

    public function setTemplate(bool $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setFiletype(string $filetype): self
    {
        $this->filetype = $filetype;

        return $this;
    }

    public function getFiletype(): string
    {
        return $this->filetype;
    }

    public function setReadonly(bool $readonly): self
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function getReadonly(): bool
    {
        return $this->readonly;
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
