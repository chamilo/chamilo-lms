<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Database;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\OptimisticLockException;
use Exception;

/**
 * CLpItem.
 *
 * @ORM\Table(
 *  name="c_lp_item",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="lp_id", columns={"lp_id"}),
 *      @ORM\Index(name="idx_c_lp_item_cid_lp_id", columns={"c_id", "lp_id"})
 *  }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class CLpItem
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
     * @ORM\Column(name="lp_id", type="integer", nullable=false)
     */
    protected $lpId;

    /**
     * @var string
     *
     * @ORM\Column(name="item_type", type="string", length=32, nullable=false)
     */
    protected $itemType;

    /**
     * @var string
     *
     * @ORM\Column(name="ref", type="text", nullable=false)
     */
    protected $ref;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=511, nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=511, nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="text", nullable=false)
     */
    protected $path;

    /**
     * @var float
     *
     * @ORM\Column(name="min_score", type="float", precision=10, scale=0, nullable=false)
     */
    protected $minScore;

    /**
     * @var float
     *
     * @ORM\Column(name="max_score", type="float", precision=10, scale=0, nullable=true, options={"default":"100"})
     */
    protected $maxScore;

    /**
     * @var float
     *
     * @ORM\Column(name="mastery_score", type="float", precision=10, scale=0, nullable=true)
     */
    protected $masteryScore;

    /**
     * @var int
     *
     * @ORM\Column(name="parent_item_id", type="integer", nullable=false)
     */
    protected $parentItemId;

    /**
     * @var int
     *
     * @ORM\Column(name="previous_item_id", type="integer", nullable=false)
     */
    protected $previousItemId;

    /**
     * @var int
     *
     * @ORM\Column(name="next_item_id", type="integer", nullable=false)
     */
    protected $nextItemId;

    /**
     * @var int
     *
     * @ORM\Column(name="display_order", type="integer", nullable=false)
     */
    protected $displayOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="prerequisite", type="text", nullable=true)
     */
    protected $prerequisite;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text", nullable=true)
     */
    protected $parameters;

    /**
     * @var string
     *
     * @ORM\Column(name="launch_data", type="text", nullable=false)
     */
    protected $launchData;

    /**
     * @var string
     *
     * @ORM\Column(name="max_time_allowed", type="string", length=13, nullable=true)
     */
    protected $maxTimeAllowed;

    /**
     * @var string
     *
     * @ORM\Column(name="terms", type="text", nullable=true)
     */
    protected $terms;

    /**
     * @var int
     *
     * @ORM\Column(name="search_did", type="integer", nullable=true)
     */
    protected $searchDid;

    /**
     * @var string
     *
     * @ORM\Column(name="audio", type="string", length=250, nullable=true)
     */
    protected $audio;

    /**
     * @var float
     *
     * @ORM\Column(name="prerequisite_min_score", type="float", precision=10, scale=0, nullable=true)
     */
    protected $prerequisiteMinScore;

    /**
     * @var float
     *
     * @ORM\Column(name="prerequisite_max_score", type="float", precision=10, scale=0, nullable=true)
     */
    protected $prerequisiteMaxScore;

    /**
     * @var CLp
     *
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CourseBundle\Entity\CLp",
     *     inversedBy="items",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(
     *     name="lp_id",
     *     referencedColumnName="iid",
     * )
     */
    protected $learningPath;

    /**
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="learningPathItems")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * CLpItem constructor.
     */
    public function __construct()
    {
        $this->ref = 0;
        $this->minScore = 0;
        $this->maxScore = 100.0;
        $this->parentItemId = 0;
        $this->previousItemId = 0;
        $this->nextItemId = 0;
        $this->displayOrder = 0;
        $this->launchData = '';
        $this->path = '';
    }

    public function __toString()
    {
        return sprintf(
            'item %s (%s "%s") of %s',
            $this->id,
            $this->itemType,
            $this->title,
            $this->learningPath
        );
    }

    /**
     * @return EntityRepository
     */
    public static function getRepository()
    {
        return Database::getManager()->getRepository('ChamiloCourseBundle:CLpItem');
    }

    /**
     * If id is null, copies iid to id.
     * If ref is empty or zero, copies iid to ref.
     * if they still equal zero, computes displayOrder, previousItemId and nextItemId.
     * If pointing to an enabled quiz, disables it and updates max score.
     *
     * @throws OptimisticLockException
     * @throws Exception               on quiz not found
     *
     * @ORM\PostPersist
     */
    public function postPersist()
    {
        if (is_null($this->id)) {
            $this->id = $this->iid;
        }
        if (is_null($this->ref) || empty($this->ref) || 0 == $this->ref) {
            $this->ref = $this->iid;
        }
        if (empty($this->maxTimeAllowed)) {
            $this->maxTimeAllowed = '0';
        }

        if (0 == $this->displayOrder) {
            foreach ($this->getSiblings() as $sibling) {
                if ($this->displayOrder < $sibling->displayOrder) {
                    $this->displayOrder = $sibling->displayOrder;
                    $this->previousItemId = 0;
                }
            }
            if (0 == $this->displayOrder) {
                $this->displayOrder = 1;
            }
        } else {
            foreach ($this->getSiblings() as $sibling) {
                if ($this->displayOrder === $sibling->displayOrder) {
                    $sibling->displayOrder++;
                    Database::getManager()->persist($sibling);
                }
            }
        }
        if (0 == $this->previousItemId) {
            $previousSibling = $this->getPreviousSibling();
            if (!is_null($previousSibling)) {
                $this->previousItemId = $previousSibling->iid;
                $previousSibling->nextItemId = $this->iid;
                Database::getManager()->persist($previousSibling);
            }
        }
        if (0 == $this->nextItemId) {
            $nextSibling = $this->getNextSibling();
            if (!is_null($nextSibling)) {
                $this->nextItemId = $nextSibling->iid;
                $nextSibling->previousItemId = $this->iid;
                Database::getManager()->persist($nextSibling);
            }
        }
        if ('quiz' === $this->itemType) {
            /** @var CQuiz $quiz */
            $quiz = CQuiz::getRepository()->find($this->path);
            if (is_null($quiz)) {
                throw new Exception('no quiz has id '.$this->path);
            }
            $this->setMaxScore($quiz->getMaxScore());
            if ($quiz->getActive()) {
                $quiz->setActive(false);
                Database::getManager()->persist($quiz);
            }
        }
        Database::getManager()->persist($this);
        $this->learningPath->updateFinalItemsPreviousItemId(false);
        Database::getManager()->flush();
    }

    /**
     * Computes next sibling's previousItemId and previous sibling's nextItemId.
     *
     * @throws OptimisticLockException
     *
     * @ORM\PreRemove
     */
    public function preRemove()
    {
        $previousSibling = $this->getPreviousSibling();
        $nextSibling = $this->getNextSibling();
        if (is_null($previousSibling)) {
            if (!is_null($nextSibling)) {
                $nextSibling->previousItemId = 0;
                Database::getManager()->persist($nextSibling);
                Database::getManager()->flush($nextSibling);
            }
        } else {
            if (is_null($nextSibling)) {
                $previousSibling->nextItemId = 0;
                Database::getManager()->persist($previousSibling);
                Database::getManager()->flush($previousSibling);
            } else {
                $previousSibling->nextItemId = $nextSibling->iid;
                Database::getManager()->persist($previousSibling);
                Database::getManager()->flush($previousSibling);
                $nextSibling->previousItemId = $previousSibling->iid;
                Database::getManager()->persist($nextSibling);
                Database::getManager()->flush($nextSibling);
            }
        }
    }

    /**
     * Updates the final item's previous item id.
     *
     * @ORM\PostRemove
     *
     * @throws OptimisticLockException
     */
    public function postRemove()
    {
        $this->learningPath->updateFinalItemsPreviousItemId();
    }

    /**
     * Set lpId.
     *
     * @param int $lpId
     *
     * @return CLpItem
     */
    public function setLpId($lpId)
    {
        $this->lpId = $lpId;

        return $this;
    }

    /**
     * Get lpId.
     *
     * @return int
     */
    public function getLpId()
    {
        return $this->lpId;
    }

    /**
     * Set itemType.
     *
     * @param string $itemType
     *
     * @return CLpItem
     */
    public function setItemType($itemType)
    {
        $this->itemType = $itemType;

        return $this;
    }

    /**
     * Get itemType.
     *
     * @return string
     */
    public function getItemType()
    {
        return $this->itemType;
    }

    /**
     * Set ref.
     *
     * @param string $ref
     *
     * @return CLpItem
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
     * Set title.
     *
     * @param string $title
     *
     * @return CLpItem
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
     * Set description.
     *
     * @param string $description
     *
     * @return CLpItem
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
     * @return CLpItem
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
     * Set minScore.
     *
     * @param float $minScore
     *
     * @return CLpItem
     */
    public function setMinScore($minScore)
    {
        $this->minScore = $minScore;

        return $this;
    }

    /**
     * Get minScore.
     *
     * @return float
     */
    public function getMinScore()
    {
        return $this->minScore;
    }

    /**
     * Set maxScore.
     *
     * @param float $maxScore
     *
     * @return CLpItem
     */
    public function setMaxScore($maxScore)
    {
        $this->maxScore = $maxScore;

        return $this;
    }

    /**
     * Get maxScore.
     *
     * @return float
     */
    public function getMaxScore()
    {
        return $this->maxScore;
    }

    /**
     * Set masteryScore.
     *
     * @param float $masteryScore
     *
     * @return CLpItem
     */
    public function setMasteryScore($masteryScore)
    {
        $this->masteryScore = $masteryScore;

        return $this;
    }

    /**
     * Get masteryScore.
     *
     * @return float
     */
    public function getMasteryScore()
    {
        return $this->masteryScore;
    }

    /**
     * Set parentItemId.
     *
     * @param int $parentItemId
     *
     * @return CLpItem
     */
    public function setParentItemId($parentItemId)
    {
        $this->parentItemId = $parentItemId;

        return $this;
    }

    /**
     * Get parentItemId.
     *
     * @return int
     */
    public function getParentItemId()
    {
        return $this->parentItemId;
    }

    /**
     * Set previousItemId.
     *
     * @param int $previousItemId
     *
     * @return CLpItem
     */
    public function setPreviousItemId($previousItemId)
    {
        $this->previousItemId = $previousItemId;

        return $this;
    }

    /**
     * Get previousItemId.
     *
     * @return int
     */
    public function getPreviousItemId()
    {
        return $this->previousItemId;
    }

    /**
     * Set nextItemId.
     *
     * @param int $nextItemId
     *
     * @return CLpItem
     */
    public function setNextItemId($nextItemId)
    {
        $this->nextItemId = $nextItemId;

        return $this;
    }

    /**
     * Get nextItemId.
     *
     * @return int
     */
    public function getNextItemId()
    {
        return $this->nextItemId;
    }

    /**
     * Set displayOrder.
     *
     * @param int $displayOrder
     *
     * @return CLpItem
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
     * Set prerequisite.
     *
     * @param string $prerequisite
     *
     * @return CLpItem
     */
    public function setPrerequisite($prerequisite)
    {
        $this->prerequisite = $prerequisite;

        return $this;
    }

    /**
     * Get prerequisite.
     *
     * @return string
     */
    public function getPrerequisite()
    {
        return $this->prerequisite;
    }

    /**
     * Set parameters.
     *
     * @param string $parameters
     *
     * @return CLpItem
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get parameters.
     *
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set launchData.
     *
     * @param string $launchData
     *
     * @return CLpItem
     */
    public function setLaunchData($launchData)
    {
        $this->launchData = $launchData;

        return $this;
    }

    /**
     * Get launchData.
     *
     * @return string
     */
    public function getLaunchData()
    {
        return $this->launchData;
    }

    /**
     * Set maxTimeAllowed.
     *
     * @param string $maxTimeAllowed
     *
     * @return CLpItem
     */
    public function setMaxTimeAllowed($maxTimeAllowed)
    {
        $this->maxTimeAllowed = $maxTimeAllowed;

        return $this;
    }

    /**
     * Get maxTimeAllowed.
     *
     * @return string
     */
    public function getMaxTimeAllowed()
    {
        return $this->maxTimeAllowed;
    }

    /**
     * Set terms.
     *
     * @param string $terms
     *
     * @return CLpItem
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;

        return $this;
    }

    /**
     * Get terms.
     *
     * @return string
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * Set searchDid.
     *
     * @param int $searchDid
     *
     * @return CLpItem
     */
    public function setSearchDid($searchDid)
    {
        $this->searchDid = $searchDid;

        return $this;
    }

    /**
     * Get searchDid.
     *
     * @return int
     */
    public function getSearchDid()
    {
        return $this->searchDid;
    }

    /**
     * Set audio.
     *
     * @param string $audio
     *
     * @return CLpItem
     */
    public function setAudio($audio)
    {
        $this->audio = $audio;

        return $this;
    }

    /**
     * Get audio.
     *
     * @return string
     */
    public function getAudio()
    {
        return $this->audio;
    }

    /**
     * Set prerequisiteMinScore.
     *
     * @param float $prerequisiteMinScore
     *
     * @return CLpItem
     */
    public function setPrerequisiteMinScore($prerequisiteMinScore)
    {
        $this->prerequisiteMinScore = $prerequisiteMinScore;

        return $this;
    }

    /**
     * Get prerequisiteMinScore.
     *
     * @return float
     */
    public function getPrerequisiteMinScore()
    {
        return $this->prerequisiteMinScore;
    }

    /**
     * Set prerequisiteMaxScore.
     *
     * @param float $prerequisiteMaxScore
     *
     * @return CLpItem
     */
    public function setPrerequisiteMaxScore($prerequisiteMaxScore)
    {
        $this->prerequisiteMaxScore = $prerequisiteMaxScore;

        return $this;
    }

    /**
     * Get prerequisiteMaxScore.
     *
     * @return float
     */
    public function getPrerequisiteMaxScore()
    {
        return $this->prerequisiteMaxScore;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CLpItem
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
     * @return CLpItem
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
     * Sets learning path AND course (copying the learning path's).
     *
     * @param CLp $clp
     *
     * @return CLpItem
     */
    public function setLearningPath($clp)
    {
        $this->learningPath = $clp;
        $clp->getItems()->add($this);
        $this->setCourse($clp->getCourse());

        return $this;
    }

    /**
     * @return CLp
     */
    public function getLearningPath()
    {
        return $this->learningPath;
    }

    /**
     * @param Course $course
     *
     * @return $this
     */
    public function setCourse($course)
    {
        $this->course = $course;
        $this->course->getLearningPathItems()->add($this);

        return $this;
    }

    /**
     * Retrieves the list of this instance's siblings, that is all the other children of this item's parent.
     *
     * @return static[]
     */
    public function getSiblings()
    {
        $siblings = [];
        foreach (self::getRepository()->findByParentItemId($this->parentItemId) as $candidate) {
            if ($candidate !== $this) {
                $siblings[] = $candidate;
            }
        }

        return $siblings;
    }

    /**
     * Returns the previous sibling according to displayOrders only (not looking at previousItemId).
     *
     * @return static|null
     */
    public function getPreviousSibling()
    {
        $previousSibling = null;
        foreach ($this->getSiblings() as $sibling) {
            if ($sibling->displayOrder < $this->displayOrder) {
                if (is_null($previousSibling)) {
                    $previousSibling = $sibling;
                } elseif ($sibling->displayOrder > $previousSibling->displayOrder) {
                    $previousSibling = $sibling;
                }
            }
        }

        return $previousSibling;
    }

    /**
     * Returns the next sibling according to displayOrders only (not looking at nextItemId).
     *
     * @return static|null
     */
    public function getNextSibling()
    {
        $nextSibling = null;
        foreach ($this->getSiblings() as $sibling) {
            if ($sibling->displayOrder > $this->displayOrder) {
                if (is_null($nextSibling)) {
                    $nextSibling = $sibling;
                } elseif ($sibling->displayOrder < $nextSibling->displayOrder) {
                    $nextSibling = $sibling;
                }
            }
        }

        return $nextSibling;
    }
}
