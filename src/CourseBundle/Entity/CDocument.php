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
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Traits\ShowCourseResourcesInSessionTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

//*      attributes={"security"="is_granted('ROLE_ADMIN')"},
/**
 * @ApiResource(
 *      shortName="Documents",
 *      normalizationContext={"groups"={"document:read", "resource_node:read"}},
 *      denormalizationContext={"groups"={"document:write"}},
 *     itemOperations={
 *     "put" ={
 *             "controller"=UpdateResourceNodeFileAction::class,
 *             "deserialize"=false,
 *             "security"="is_granted('ROLE_USER')",
 *             "validation_groups"={"Default", "media_object_create", "document:write"},
 *         },
 *     "get",
 *     "delete"
 *     },
 *      collectionOperations={
 *         "post"={
 *             "controller"=CreateResourceNodeFileAction::class,
 *             "deserialize"=false,
 *             "security"="is_granted('ROLE_USER')",
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
 *         "get",
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
 *      @ORM\Index(name="idx_cdoc_size", columns={"size"}),
 *      @ORM\Index(name="idx_cdoc_type", columns={"filetype"}),
 *  }
 * )
 * GRID\Source(columns="iid, title, resourceNode.createdAt", filterable=false, groups={"resource"})
 * GRID\Source(columns="iid, title", filterable=false, groups={"editor"})
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
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    protected $path;

    /**
     * @var string
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
     * @var int
     *
     * @ORM\Column(name="size", type="integer", nullable=false)
     */
    protected $size;

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
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", onDelete="CASCADE" )
     */
    protected $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", onDelete="CASCADE" )
     */
    protected $session;

    /**
     * CDocument constructor.
     */
    public function __construct()
    {
        $this->id = 0;
        $this->size = 0;
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
     * Set path.
     *
     * @param string $path
     *
     * @return CDocument
     */
    public function setPath($path)
    {
        $this->path = $path;

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

    /**
     * Set comment.
     *
     * @param string $comment
     *
     * @return CDocument
     */
    public function setComment($comment)
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

    /**
     * Set title.
     *
     * @return CDocument
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
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
     * Set size.
     *
     * @return CDocument
     */
    public function setSize(int $size)
    {
        $this->size = $size ?: 0;

        return $this;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
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

    public function getCourse(): Course
    {
        return $this->course;
    }

    /**
     * @param Course $course
     *
     * @return CDocument
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param Session $session
     *
     * @return CDocument
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }
}
