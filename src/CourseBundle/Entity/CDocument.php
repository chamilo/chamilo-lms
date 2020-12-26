<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Chamilo\CoreBundle\Controller\Api\CreateResourceNodeFileAction;
use Chamilo\CoreBundle\Controller\Api\UpdateResourceNodeFileAction;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Traits\ShowCourseResourcesInSessionTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *      shortName="Documents",
 *      normalizationContext={"groups"={"document:read", "resource_node:read"}},
 *      denormalizationContext={"groups"={"document:write"}},
 *      itemOperations={
 *          "put" ={
 *              "controller"=UpdateResourceNodeFileAction::class,
 *              "deserialize"=false,
 *              "security" = "is_granted('EDIT', object.resourceNode)",
 *              "validation_groups"={"Default", "media_object_create", "document:write"},
 *          },
 *          "get" = {
 *              "security" = "is_granted('VIEW', object.resourceNode)",
 *          },
 *          "delete" = {
 *              "security" = "is_granted('DELETE', object.resourceNode)",
 *          },
 *     },
 *     collectionOperations={
 *         "post"={
 *             "controller"=CreateResourceNodeFileAction::class,
 *             "deserialize"=false,
 *             "security"="is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_SESSION_COURSE_TEACHER')",
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
 *                                              "type": "object",
 *                                              "properties"={
 *                                                  "visibility"={
 *                                                       "type"="integer",
 *                                                   },
 *                                                  "c_id"={
 *                                                       "type"="integer",
 *                                                   },
 *                                                   "session_id"={
 *                                                       "type"="integer",
 *                                                   },
 *                                              }
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
 *              "security"="is_granted('ROLE_USER')",
 *         },
 *     },
 * )
 * @ApiFilter(SearchFilter::class, properties={"title": "partial", "resourceNode.parent": "exact"})
 * @ApiFilter(PropertyFilter::class)
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={
 *          "id",
 *          "filetype",
 *          "resourceNode.title",
 *          "resourceNode.createdAt",
 *          "resourceNode.resourceFile.size",
 *          "resourceNode.updatedAt"
 *      }
 * )
 *
 * @ORM\Table(
 *  name="c_document",
 *  indexes={
 *      @ORM\Index(name="idx_cdoc_type", columns={"filetype"}),
 *  }
 * )
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\ResourceListener"})
 * @ORM\Entity
 */
class CDocument extends AbstractResource implements ResourceInterface
{
    use ShowCourseResourcesInSessionTrait;

    /**
     * @var int
     * @Groups({"document:read"})
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var string
     * @Assert\NotBlank
     * @Groups({"document:read", "document:write"})
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    protected $title;

    /**
     * @var string
     * @Groups({"document:read", "document:write"})
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var string File type, it can be 'folder' or 'file'
     * @Groups({"document:read", "document:write"})
     * @Assert\Choice({"folder", "file"}, message="Choose a valid filetype.")
     * @ORM\Column(name="filetype", type="string", length=10, nullable=false)
     */
    protected $filetype;

    /**
     * @var bool
     * @ORM\Column(name="readonly", type="boolean", nullable=false)
     */
    protected $readonly;

    /**
     * @var bool
     * @ORM\Column(name="template", type="boolean", nullable=false)
     */
    protected $template;

    /**
     * CDocument constructor.
     */
    public function __construct()
    {
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

    /**
     * Set comment.
     *
     * @param string $comment
     */
    public function setComment($comment): self
    {
        $this->comment = $comment;

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

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Document title.
     */
    public function getTitle(): string
    {
        return (string) $this->title;
    }

    /**
     * Set filetype.
     */
    public function setFiletype(string $filetype): self
    {
        $this->filetype = $filetype;

        return $this;
    }

    /**
     * Get filetype.
     *
     * @return string
     */
    public function getFiletype()
    {
        return $this->filetype;
    }

    /**
     * Set readonly.
     *
     * @param bool $readonly
     *
     * @return CDocument
     */
    public function setReadonly($readonly)
    {
        $this->readonly = $readonly;

        return $this;
    }

    /**
     * Get readonly.
     *
     * @return bool
     */
    public function getReadonly()
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
