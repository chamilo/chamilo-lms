<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Database;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Exception;

/**
 * CDocument.
 *
 * @ORM\Table(
 *  name="c_document",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class CDocument
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
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

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
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="documents")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    public function __construct()
    {
        $this->size = 0;
        $this->readonly = false;
        $this->sessionId = 0;
    }

    /**
     * @return EntityRepository
     */
    public static function getRepository()
    {
        return Database::getManager()->getRepository('ChamiloCourseBundle:CDocument');
    }

    /**
     * Instantiates a new CDocument by copying a file to the course.
     *
     * @param string $filePath     the source file to be copied to the course directory
     * @param Course $course       the course for which the document is being created
     * @param string $documentPath the future document's relative path
     * @param string $title        a title for the document
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws Exception
     *
     * @return CDocument
     */
    public static function fromFile($filePath, $course, $documentPath, $title)
    {
        $instance = (new static())
            ->setCourse($course)
            ->setPath($documentPath)
            ->setTitle($title);
        $absolutePath = $instance->getAbsolutePath();
        if (!copy($filePath, $absolutePath)) {
            throw new Exception(sprintf('Could not copy course document file %s to %s', $filePath, $absolutePath));
        }

        return $instance;
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param Course $course
     *
     * @return $this
     */
    public function setCourse($course)
    {
        $this->course = $course;
        $this->course->getDocuments()->add($this);

        return $this;
    }

    /**
     * Builds the document's absolute path from its course's own path and its (relative) path.
     *
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws ORMException
     * @throws Exception
     *
     * @return string the document's absolute path
     */
    public function getAbsolutePath()
    {
        if (is_null($this->course) && $this->cId) {
            $this->course = api_get_course_entity($this->cId);
        }
        if (is_null($this->course)) {
            throw new Exception('this document does not have a course yet');
        }

        return sprintf(
            '%s/document/%s',
            $this->course->getAbsolutePath(),
            $this->path
        );
    }

    /**
     * Makes sure the actual file exists.
     * Records the file type.
     * Computes document file size if needed.
     *
     * @ORM\PrePersist
     *
     * @throws Exception
     */
    public function prePersist()
    {
        $absolutePath = $this->getAbsolutePath();
        if (!file_exists($absolutePath)) {
            throw new Exception('Cannot persist a document without an existing file');
        }
        if (empty($this->filetype)) {
            $type = filetype($absolutePath);
            switch ($type) {
                case 'dir':
                    $this->filetype = 'folder';
                    break;
                case 'file':
                case 'link':
                    $this->filetype = $type;
                    break;
                default:
                    throw new Exception('unsupported file type: '.$type);
            }
        }
        if (0 === $this->size && 'file' == $this->filetype) {
            $this->size = filesize($absolutePath);
        }
    }

    /**
     * If id is null, copies iid to id and writes again.
     *
     * @ORM\PostPersist
     *
     * @throws Exception
     */
    public function postPersist()
    {
        if (is_null($this->id)) { // keep this test to avoid recursion
            $this->id = $this->iid;
            Database::getManager()->persist($this);
            Database::getManager()->flush($this);
        }
    }

    /**
     * Removes the actual file, folder or link.
     *
     * @ORM\PostRemove
     */
    public function postRemove()
    {
        $absolutePath = '';
        try {
            $absolutePath = $this->getAbsolutePath();
        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
        if (!empty($absolutePath) && file_exists($absolutePath)) {
            if ('folder' === $this->filetype && is_dir($absolutePath)) {
                rmdir($absolutePath);
            } elseif ('file' === $this->filetype && is_file($absolutePath)) {
                unlink($absolutePath);
            } elseif ('link' === $this->filetype && is_link($absolutePath)) {
                unlink($absolutePath);
            }
        }
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
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CDocument
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
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
     * Set cId.
     *
     * @deprecated use setCourse wherever possible
     *
     * @param int $cId
     *
     * @return CDocument
     */
    public function setCId($cId)
    {
        $this->cId = $cId;
        $this->setCourse(api_get_course_entity($cId));

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * @param int $iid
     *
     * @return CDocument
     */
    public function setIid($iid)
    {
        $this->iid = $iid;

        return $this;
    }
}
