<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Database;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\OptimisticLockException;
use Exception;

/**
 * CLp.
 *
 * @ORM\Table(
 *  name="c_lp",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *     @ORM\Index(name="session", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class CLp
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
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="lp_type", type="integer", nullable=false)
     */
    protected $lpType;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="ref", type="text", nullable=true)
     */
    protected $ref;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="text", nullable=false)
     */
    protected $path;

    /**
     * @var bool
     *
     * @ORM\Column(name="force_commit", type="boolean", nullable=false)
     */
    protected $forceCommit;

    /**
     * @var string
     *
     * @ORM\Column(name="default_view_mod", type="string", length=32, nullable=false, options={"default":"embedded"})
     */
    protected $defaultViewMod;

    /**
     * @var string
     *
     * @ORM\Column(name="default_encoding", type="string", length=32, nullable=false, options={"default":"UTF-8"})
     */
    protected $defaultEncoding;

    /**
     * @var int
     *
     * @ORM\Column(name="display_order", type="integer", nullable=false, options={"default":"0"})
     */
    protected $displayOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="content_maker", type="text", nullable=false)
     */
    protected $contentMaker;

    /**
     * @var string
     *
     * @ORM\Column(name="content_local", type="string", length=32, nullable=false, options={"default":"local"})
     */
    protected $contentLocal;

    /**
     * @var string
     *
     * @ORM\Column(name="content_license", type="text", nullable=false)
     */
    protected $contentLicense;

    /**
     * @var bool
     *
     * @ORM\Column(name="prevent_reinit", type="boolean", nullable=false, options={"default":"1"})
     */
    protected $preventReinit;

    /**
     * @var string
     *
     * @ORM\Column(name="js_lib", type="text", nullable=false)
     */
    protected $jsLib;

    /**
     * @var bool
     *
     * @ORM\Column(name="debug", type="boolean", nullable=false)
     */
    protected $debug;

    /**
     * @var string
     *
     * @ORM\Column(name="theme", type="string", length=255, nullable=false)
     */
    protected $theme;

    /**
     * @var string
     *
     * @ORM\Column(name="preview_image", type="string", length=255, nullable=false)
     */
    protected $previewImage;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=255, nullable=false)
     */
    protected $author;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="prerequisite", type="integer", nullable=false)
     */
    protected $prerequisite;

    /**
     * @var bool
     *
     * @ORM\Column(name="hide_toc_frame", type="boolean", nullable=false)
     */
    protected $hideTocFrame;

    /**
     * @var bool
     *
     * @ORM\Column(name="seriousgame_mode", type="boolean", nullable=false)
     */
    protected $seriousgameMode;

    /**
     * @var int
     *
     * @ORM\Column(name="use_max_score", type="integer", nullable=false, options={"default":"1"})
     */
    protected $useMaxScore;

    /**
     * @var int
     *
     * @ORM\Column(name="autolaunch", type="integer", nullable=false)
     */
    protected $autolaunch;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    protected $categoryId;

    /**
     * @var int
     *
     * @ORM\Column(name="max_attempts", type="integer", nullable=false)
     */
    protected $maxAttempts;

    /**
     * @var int
     *
     * @ORM\Column(name="subscribe_users", type="integer", nullable=false)
     */
    protected $subscribeUsers;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_on", type="datetime", nullable=false)
     */
    protected $createdOn;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="modified_on", type="datetime", nullable=false)
     */
    protected $modifiedOn;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="publicated_on", type="datetime", nullable=true)
     */
    protected $publicatedOn;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="expired_on", type="datetime", nullable=true)
     */
    protected $expiredOn;

    /**
     * @var string
     *
     * @ORM\Column(name="accumulate_scorm_time", type="integer", nullable=false, options={"default":1})
     */
    protected $accumulateScormTime;

    /**
     * @var Course
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\Course",
     *     inversedBy="learningPaths",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * @var Session
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="learningPaths")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    /*protected $session;*/

    /**
     * @var CLpCategory
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLpCategory", inversedBy="learningPaths")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="iid")
     */
    /*protected $category;*/

    /**
     * @var ArrayCollection|CLpItem[]
     *
     * @ORM\OneToMany(
     *     targetEntity="CLpItem",
     *     mappedBy="learningPath",
     *     orphanRemoval=true
     * )
     */
    protected $items;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->sessionId = api_get_session_id();
        $this->categoryId = 0;
        $this->defaultViewMod = 'embedded';
        $this->defaultEncoding = 'UTF-8';
        $this->displayOrder = 0;
        $this->contentLocal = 'local';
        $this->preventReinit = true;
        $this->useMaxScore = 1;
        $this->lpType = 1;
        $this->path = '';
        $this->forceCommit = false;
        $this->contentMaker = 'Chamilo';
        $this->contentLicense = '';
        $this->jsLib = '';
        $this->debug = false;
        $this->theme = '';
        $this->previewImage = '';
        $this->author = '';
        $this->prerequisite = 0;
        $this->hideTocFrame = false;
        $this->seriousgameMode = false;
        $this->autolaunch = 0;
        $this->maxAttempts = 0;
        $this->subscribeUsers = 0;
        $this->createdOn = new DateTime('now', new DateTimeZone('utc'));
        $this->modifiedOn = new DateTime('now', new DateTimeZone('utc'));
        $this->accumulateScormTime = 1;
        $this->items = new ArrayCollection();
    }

    public function __toString()
    {
        return sprintf('learning path %s ("%s") of %s', $this->id, $this->name, $this->course->__toString());
    }

    /**
     * @return EntityRepository
     */
    public static function getRepository()
    {
        return Database::getManager()->getRepository('ChamiloCourseBundle:CLp');
    }

    /**
     * If course is not yet set, take the current course.
     * Appends a number to name if it is already taken by another learning path in the same course.
     * Computes displayOrder if still zéro.
     *
     * @ORM\PrePersist
     *
     * @throws Exception
     */
    public function prePersist()
    {
        if (is_null($this->course)) {
            $this->course = api_get_course_entity();
            if (is_null($this->course)) {
                throw new Exception('cannot persist a leaning path without course');
            }
        }

        $coursesOtherLearningPaths = $this->course->getLearningPaths()->filter(function ($lp) {
            return $this !== $lp;
        });

        $originalName = $this->name;
        $counter = 0;
        while ($coursesOtherLearningPaths->exists(function ($key, $lp) {
            return $lp->name === $this->name;
        })) {
            $counter++;
            $this->name = sprintf('%s - %d', $originalName, $counter);
        }

        if (0 == $this->displayOrder) {
            $this->displayOrder = $coursesOtherLearningPaths->isEmpty()
                ? 1
                : (
                    1 + max(
                        $coursesOtherLearningPaths->map(
                            function ($lp) {
                                return $lp->displayOrder;
                            }
                        )->toArray()
                    )
                );
        }
    }

    /**
     * If id is null, copies iid to id and writes again.
     * Updates item properties.
     *
     * @throws OptimisticLockException
     *
     * @ORM\PostPersist
     */
    public function postPersist()
    {
        if (is_null($this->id)) {
            $this->id = $this->iid;
            Database::getManager()->persist($this);
            Database::getManager()->flush($this);
        }
        $courseInfo = api_get_course_info_by_id($this->course->getId());
        $userId = api_get_user_id();
        api_item_property_update(
            $courseInfo,
            TOOL_LEARNPATH,
            $this->getId(),
            'LearnpathAdded',
            $userId
        );
        api_set_default_visibility(
            $this->getId(),
            TOOL_LEARNPATH,
            0,
            $courseInfo,
            $this->getSessionId(),
            $userId
        );
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
        $this->course->getLearningPaths()->add($this);

        return $this;
    }

    /**
     * @return Session
     */
    /*public function getSession()
    {
        return $this->session;
    }*/

    /**
     * @param Session $session
     *
     * @return $this
     */
    /*public function setSession(Session $session)
    {
        $this->session = $session;
        if (!is_null($session)) {
            $this->session->getLearningPaths()->add($this);
        }

        return $this;
    }*/

    /**
     * @return CLpCategory
     */
    /*public function getCategory()
    {
        return $this->category;
    }*/

    /**
     * @param CLpCategory $category
     *
     * @return $this
     */
    /*public function setCategory(CLpCategory $category)
    {
        $this->category = $category;
        if (!is_null($category)) {
            $this->category->getLearningPaths()->add($this);
        }

        return $this;
    }*/

    /**
     * Set lpType.
     *
     * @param int $lpType
     *
     * @return CLp
     */
    public function setLpType($lpType)
    {
        $this->lpType = $lpType;

        return $this;
    }

    /**
     * Get lpType.
     *
     * @return int
     */
    public function getLpType()
    {
        return $this->lpType;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return CLp
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set ref.
     *
     * @param string $ref
     *
     * @return CLp
     */
    public function setRef($ref)
    {
        $this->ref = $ref;

        return $this;
    }

    /**
     * Get ref.
     *
     * @return string
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CLp
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return CLp
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
     * Set forceCommit.
     *
     * @param bool $forceCommit
     *
     * @return CLp
     */
    public function setForceCommit($forceCommit)
    {
        $this->forceCommit = $forceCommit;

        return $this;
    }

    /**
     * Get forceCommit.
     *
     * @return bool
     */
    public function getForceCommit()
    {
        return $this->forceCommit;
    }

    /**
     * Set defaultViewMod.
     *
     * @param string $defaultViewMod
     *
     * @return CLp
     */
    public function setDefaultViewMod($defaultViewMod)
    {
        $this->defaultViewMod = $defaultViewMod;

        return $this;
    }

    /**
     * Get defaultViewMod.
     *
     * @return string
     */
    public function getDefaultViewMod()
    {
        return $this->defaultViewMod;
    }

    /**
     * Set defaultEncoding.
     *
     * @param string $defaultEncoding
     *
     * @return CLp
     */
    public function setDefaultEncoding($defaultEncoding)
    {
        $this->defaultEncoding = $defaultEncoding;

        return $this;
    }

    /**
     * Get defaultEncoding.
     *
     * @return string
     */
    public function getDefaultEncoding()
    {
        return $this->defaultEncoding;
    }

    /**
     * Set displayOrder.
     *
     * @param int $displayOrder
     *
     * @return CLp
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder.
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set contentMaker.
     *
     * @param string $contentMaker
     *
     * @return CLp
     */
    public function setContentMaker($contentMaker)
    {
        $this->contentMaker = $contentMaker;

        return $this;
    }

    /**
     * Get contentMaker.
     *
     * @return string
     */
    public function getContentMaker()
    {
        return $this->contentMaker;
    }

    /**
     * Set contentLocal.
     *
     * @param string $contentLocal
     *
     * @return CLp
     */
    public function setContentLocal($contentLocal)
    {
        $this->contentLocal = $contentLocal;

        return $this;
    }

    /**
     * Get contentLocal.
     *
     * @return string
     */
    public function getContentLocal()
    {
        return $this->contentLocal;
    }

    /**
     * Set contentLicense.
     *
     * @param string $contentLicense
     *
     * @return CLp
     */
    public function setContentLicense($contentLicense)
    {
        $this->contentLicense = $contentLicense;

        return $this;
    }

    /**
     * Get contentLicense.
     *
     * @return string
     */
    public function getContentLicense()
    {
        return $this->contentLicense;
    }

    /**
     * Set preventReinit.
     *
     * @param bool $preventReinit
     *
     * @return CLp
     */
    public function setPreventReinit($preventReinit)
    {
        $this->preventReinit = $preventReinit;

        return $this;
    }

    /**
     * Get preventReinit.
     *
     * @return bool
     */
    public function getPreventReinit()
    {
        return $this->preventReinit;
    }

    /**
     * Set jsLib.
     *
     * @param string $jsLib
     *
     * @return CLp
     */
    public function setJsLib($jsLib)
    {
        $this->jsLib = $jsLib;

        return $this;
    }

    /**
     * Get jsLib.
     *
     * @return string
     */
    public function getJsLib()
    {
        return $this->jsLib;
    }

    /**
     * Set debug.
     *
     * @param bool $debug
     *
     * @return CLp
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Get debug.
     *
     * @return bool
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Set theme.
     *
     * @param string $theme
     *
     * @return CLp
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get theme.
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set previewImage.
     *
     * @param string $previewImage
     *
     * @return CLp
     */
    public function setPreviewImage($previewImage)
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    /**
     * Get previewImage.
     *
     * @return string
     */
    public function getPreviewImage()
    {
        return $this->previewImage;
    }

    /**
     * Set author.
     *
     * @param string $author
     *
     * @return CLp
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author.
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CLp
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
     * Set prerequisite.
     *
     * @param int $prerequisite
     *
     * @return CLp
     */
    public function setPrerequisite($prerequisite)
    {
        $this->prerequisite = $prerequisite;

        return $this;
    }

    /**
     * Get prerequisite.
     *
     * @return int
     */
    public function getPrerequisite()
    {
        return $this->prerequisite;
    }

    /**
     * Set hideTocFrame.
     *
     * @param bool $hideTocFrame
     *
     * @return CLp
     */
    public function setHideTocFrame($hideTocFrame)
    {
        $this->hideTocFrame = $hideTocFrame;

        return $this;
    }

    /**
     * Get hideTocFrame.
     *
     * @return bool
     */
    public function getHideTocFrame()
    {
        return $this->hideTocFrame;
    }

    /**
     * Set seriousgameMode.
     *
     * @param bool $seriousgameMode
     *
     * @return CLp
     */
    public function setSeriousgameMode($seriousgameMode)
    {
        $this->seriousgameMode = $seriousgameMode;

        return $this;
    }

    /**
     * Get seriousgameMode.
     *
     * @return bool
     */
    public function getSeriousgameMode()
    {
        return $this->seriousgameMode;
    }

    /**
     * Set useMaxScore.
     *
     * @param int $useMaxScore
     *
     * @return CLp
     */
    public function setUseMaxScore($useMaxScore)
    {
        $this->useMaxScore = $useMaxScore;

        return $this;
    }

    /**
     * Get useMaxScore.
     *
     * @return int
     */
    public function getUseMaxScore()
    {
        return $this->useMaxScore;
    }

    /**
     * Set autolaunch.
     *
     * @param int $autolaunch
     *
     * @return CLp
     */
    public function setAutolaunch($autolaunch)
    {
        $this->autolaunch = $autolaunch;

        return $this;
    }

    /**
     * Get autolaunch.
     *
     * @return int
     */
    public function getAutolaunch()
    {
        return $this->autolaunch;
    }

    /**
     * Set createdOn.
     *
     * @param DateTime $createdOn
     *
     * @return CLp
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get createdOn.
     *
     * @return DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set modifiedOn.
     *
     * @param DateTime $modifiedOn
     *
     * @return CLp
     */
    public function setModifiedOn($modifiedOn)
    {
        $this->modifiedOn = $modifiedOn;

        return $this;
    }

    /**
     * Get modifiedOn.
     *
     * @return DateTime
     */
    public function getModifiedOn()
    {
        return $this->modifiedOn;
    }

    /**
     * Set publicatedOn.
     *
     * @param DateTime $publicatedOn
     *
     * @return CLp
     */
    public function setPublicatedOn($publicatedOn)
    {
        $this->publicatedOn = $publicatedOn;

        return $this;
    }

    /**
     * Get publicatedOn.
     *
     * @return DateTime
     */
    public function getPublicatedOn()
    {
        return $this->publicatedOn;
    }

    /**
     * Set expiredOn.
     *
     * @param DateTime $expiredOn
     *
     * @return CLp
     */
    public function setExpiredOn($expiredOn)
    {
        $this->expiredOn = $expiredOn;

        return $this;
    }

    /**
     * Get expiredOn.
     *
     * @return DateTime
     */
    public function getExpiredOn()
    {
        return $this->expiredOn;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CLp
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
     * @return CLp
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
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     *
     * @return CLp
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccumulateScormTime()
    {
        return $this->accumulateScormTime;
    }

    /**
     * @param string $accumulateScormTime
     *
     * @return CLp
     */
    public function setAccumulateScormTime($accumulateScormTime)
    {
        $this->accumulateScormTime = $accumulateScormTime;

        return $this;
    }

    /**
     * Get iid.
     *
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Get subscribeUsers.
     *
     * @return int
     */
    public function getSubscribeUsers()
    {
        return $this->subscribeUsers;
    }

    /**
     * @return ArrayCollection|CLpItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Returns this learning path's final item.
     *
     * @return CLpItem|null the final item
     */
    public function getFinalItem()
    {
        foreach ($this->items as $item) {
            if ($item->getItemType() == TOOL_LP_FINAL_ITEM) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Returns this learning path's last item in first level that is not the final item.
     *
     * @return CLpItem|null the last item
     */
    public function getLastItemInFirstLevel()
    {
        $last = null;
        foreach ($this->items as $item) {
            if (0 == $item->getParentItemId() && $item->getItemType() != TOOL_LP_FINAL_ITEM) {
                if (is_null($last) || $last->getDisplayOrder() < $item->getDisplayOrder()) {
                    $last = $item;
                }
            }
        }

        return $last;
    }

    /**
     * Updates this learning path's final item previous item id.
     * Sets it to the last item in first level.
     *
     * @param bool $andFlush flush after persist
     *
     * @throws OptimisticLockException
     */
    public function updateFinalItemsPreviousItemId($andFlush = true)
    {
        $finalItem = $this->getFinalItem();
        if (!is_null($finalItem)) {
            $last = $this->getLastItemInFirstLevel();
            if (!is_null($last)) {
                $finalItem->setPreviousItemId($last->getId());
                Database::getManager()->persist($finalItem);
                if ($andFlush) {
                    Database::getManager()->flush($finalItem);
                }
            }
        }
    }
}
