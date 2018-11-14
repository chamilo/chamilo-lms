<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as GRID;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceInterface;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

/**
 * CDocument.
 *
 * @ORM\Table(
 *  name="c_document",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @GRID\Source(columns="iid, id, title, filetype", filterable=false)
 *
 * @ORM\Entity
 */
class CDocument extends AbstractResource implements ResourceInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=false)
     */
    protected $path;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
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
     *
     * @ORM\Column(name="readonly", type="boolean", nullable=false)
     */
    protected $readonly;

    /**
     * @var Course|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    protected $session;

    /**
     * CDocument constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->title;
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
     * @param string $title
     *
     * @return CDocument
     */
    public function setTitle($title)
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
        return $this->title;
    }

    /**
     * Set filetype.
     *
     * @param string $filetype
     *
     * @return CDocument
     */
    public function setFiletype($filetype)
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
     * @param int $size
     *
     * @return CDocument
     */
    public function setSize($size)
    {
        $this->size = $size;

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

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CDocument
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    /**
     * @return Course
     */
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
     * @return ResourceLink
     */
    public function getCourseSessionResourceLink()
    {
        $criteria = Criteria::create();
        $criteria
            ->where(Criteria::expr()->eq('course', $this->getCourse()))
            ->andWhere(
                Criteria::expr()->eq('session', $this->getSession())
            );
        $resourceNode = $this->getResourceNode();

        $result = null;
        if ($resourceNode && $resourceNode->getResourceLinks()) {
            $result = $resourceNode->getResourceLinks()->matching($criteria)->first();
        }

        return $result;
    }

    /**
     * This is a "soft delete" only changes visibility, doesn't delete the record.
     */
    public function setSoftDelete()
    {
        $this->getCourseSessionResourceLink()->setVisibility(ResourceLink::VISIBILITY_DELETED);
    }

    /**
     * @ORM\PostPersist()
     *
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        // Update id with iid value
        $em = $args->getEntityManager();
        $this->setId($this->iid);
        $em->persist($this);
        $em->flush($this);
    }

    /**
     * Resource identifier.
     *
     * @return int
     */
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    /**
     * @return string
     */
    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    /**
     * @return string
     */
    public function getToolName(): string
    {
        return 'document';
    }
}
