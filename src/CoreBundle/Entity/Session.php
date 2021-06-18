<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="session",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="name", columns={"name"})
 *     },
 *     indexes={
 *         @ORM\Index(name="idx_id_coach", columns={"id_coach"}),
 *         @ORM\Index(name="idx_id_session_admin_id", columns={"session_admin_id"})
 *     }
 * )
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\SessionListener"})
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\SessionRepository")
 * @UniqueEntity("name")
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
        'post' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
    ],
    itemOperations: [
        'get' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
        'put' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
    ],
    attributes: [
        'security' => "is_granted('ROLE_ADMIN')",
    ],
    denormalizationContext: [
        'groups' => ['session:write'],
    ],
    normalizationContext: [
        'groups' => ['session:read'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial'])]
#[ApiFilter(PropertyFilter::class)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'name'])]

class Session implements ResourceWithAccessUrlInterface
{
    public const VISIBLE = 1;
    public const READ_ONLY = 2;
    public const INVISIBLE = 3;
    public const AVAILABLE = 4;

    public const STUDENT = 0;
    public const DRH = 1;
    public const COACH = 2;

    /**
     * @Groups({"session:read", "session_rel_user:read"})
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected ?int $id = null;

    /**
     * @var Collection|SessionRelCourse[]
     *
     * @ORM\OrderBy({"position"="ASC"})
     * @ORM\OneToMany(targetEntity="SessionRelCourse", mappedBy="session", cascade={"persist"}, orphanRemoval=true)
     */
    protected Collection $courses;

    /**
     * @var Collection|SessionRelUser[]
     *
     * @ORM\OneToMany(targetEntity="SessionRelUser", mappedBy="session", cascade={"persist"}, orphanRemoval=true)
     */
    protected Collection $users;

    /**
     * @Groups({"session:read", "session_rel_user:read"})
     *
     * @var Collection|SessionRelCourseRelUser[]
     *
     * @ORM\OneToMany(
     *     targetEntity="SessionRelCourseRelUser",
     *     mappedBy="session",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    protected Collection $sessionRelCourseRelUsers;

    /**
     * @var Collection|SkillRelCourse[]
     * @ORM\OneToMany(targetEntity="SkillRelCourse", mappedBy="session", cascade={"persist", "remove"})
     */
    protected Collection $skills;

    /**
     * @var Collection|SkillRelUser[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SkillRelUser", mappedBy="session", cascade={"persist"})
     */
    protected Collection $issuedSkills;

    /**
     * @var AccessUrlRelSession[]|Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrlRelSession",
     *     mappedBy="session",
     *     cascade={"persist"}, orphanRemoval=true
     * )
     */
    protected Collection $urls;

    /**
     * @var Collection|ResourceLink[]
     *
     * @ORM\OneToMany(targetEntity="ResourceLink", mappedBy="session", cascade={"remove"}, orphanRemoval=true)
     */
    protected Collection $resourceLinks;

    protected AccessUrl $currentUrl;

    protected Course $currentCourse;

    /**
     * @Groups({"session:read", "session:write", "session_rel_course_rel_user:read", "document:read", "session_rel_user:read"})
     * @ORM\Column(name="name", type="string", length=150)
     */
    #[Assert\NotBlank]
    protected string $name;

    /**
     * @Groups({"session:read", "session:write"})
     *
     * @ORM\Column(name="description", type="text", nullable=true, unique=false)
     */
    protected ?string $description;

    /**
     * @Groups({"session:read", "session:write"})
     * @ORM\Column(name="show_description", type="boolean", nullable=true)
     */
    protected ?bool $showDescription;

    /**
     * @Groups({"session:read", "session:write"})
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    protected ?int $duration = null;

    /**
     * @Groups({"session:read"})
     * @ORM\Column(name="nbr_courses", type="integer", nullable=false, unique=false)
     */
    protected int $nbrCourses;

    /**
     * @Groups({"session:read"})
     * @ORM\Column(name="nbr_users", type="integer", nullable=false, unique=false)
     */
    protected int $nbrUsers;

    /**
     * @Groups({"session:read"})
     * @ORM\Column(name="nbr_classes", type="integer", nullable=false, unique=false)
     */
    protected int $nbrClasses;

    /**
     * @Groups({"session:read", "session:write"})
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="session_admin_id", referencedColumnName="id", nullable=true)
     */
    protected ?User $sessionAdmin = null;

    /**
     * @Assert\NotBlank
     * @Groups({"session:read", "session:write"})
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="sessionsAsGeneralCoach")
     * @ORM\JoinColumn(name="id_coach", referencedColumnName="id")
     */
    protected User $generalCoach;

    /**
     * @Groups({"session:read", "session:write"})
     * @ORM\Column(name="visibility", type="integer")
     */
    protected int $visibility;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Promotion", inversedBy="sessions", cascade={"persist"})
     * @ORM\JoinColumn(name="promotion_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?Promotion $promotion = null;

    /**
     * @Groups({"session:read"})
     * @ORM\Column(name="display_start_date", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $displayStartDate;

    /**
     * @Groups({"session:read"})
     * @ORM\Column(name="display_end_date", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $displayEndDate;

    /**
     * @ORM\Column(name="access_start_date", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $accessStartDate;

    /**
     * @ORM\Column(name="access_end_date", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $accessEndDate;

    /**
     * @ORM\Column(name="coach_access_start_date", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $coachAccessStartDate;

    /**
     * @ORM\Column(name="coach_access_end_date", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $coachAccessEndDate;

    /**
     * @ORM\Column(name="position", type="integer", nullable=false, options={"default":0})
     */
    protected int $position;

    /**
     * @Groups({"session:read"})
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected int $status;

    /**
     * @Groups({"session:read", "session:write"})
     * @ORM\ManyToOne(targetEntity="SessionCategory", inversedBy="sessions")
     * @ORM\JoinColumn(name="session_category_id", referencedColumnName="id")
     */
    protected ?SessionCategory $category = null;

    /**
     * @ORM\Column(name="send_subscription_notification", type="boolean", nullable=false, options={"default":false})
     */
    protected bool $sendSubscriptionNotification;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->issuedSkills = new ArrayCollection();
        $this->resourceLinks = new ArrayCollection();
        $this->courses = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->sessionRelCourseRelUsers = new ArrayCollection();
        $this->urls = new ArrayCollection();

        $this->duration = 0;
        $this->description = '';
        $this->nbrClasses = 0;
        $this->nbrUsers = 0;
        $this->nbrCourses = 0;
        $this->sendSubscriptionNotification = false;
        $now = new DateTime();
        $this->displayStartDate = $now;
        $this->displayEndDate = $now;
        $this->accessStartDate = $now;
        $this->accessEndDate = $now;
        $this->coachAccessStartDate = $now;
        $this->coachAccessEndDate = $now;
        $this->visibility = 1;
        $this->showDescription = false;
        $this->category = null;
        $this->status = 0;
        $this->position = 0;
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getShowDescription(): bool
    {
        return $this->showDescription;
    }

    public function setShowDescription(bool $showDescription): self
    {
        $this->showDescription = $showDescription;

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
     * @return Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    public function setUsers(Collection $users): self
    {
        $this->users = new ArrayCollection();

        foreach ($users as $user) {
            $this->addUser($user);
        }

        return $this;
    }

    public function addUser(SessionRelUser $user): void
    {
        $user->setSession($this);

        if (!$this->hasUser($user)) {
            $this->users[] = $user;
            $this->nbrUsers++;
        }
    }

    public function addUserInSession(int $status, User $user): self
    {
        $sessionRelUser = new SessionRelUser();
        $sessionRelUser->setSession($this);
        $sessionRelUser->setUser($user);
        $sessionRelUser->setRelationType($status);

        $this->addUser($sessionRelUser);

        return $this;
    }

    public function hasUser(SessionRelUser $subscription): bool
    {
        if (0 !== $this->getUsers()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('user', $subscription->getUser())
            )->andWhere(
                Criteria::expr()->eq('session', $subscription->getSession())
            )->andWhere(
                Criteria::expr()->eq('relationType', $subscription->getRelationType())
            );

            $relation = $this->getUsers()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    /**
     * @return Collection
     */
    public function getCourses()
    {
        return $this->courses;
    }

    public function setCourses(ArrayCollection $courses): void
    {
        $this->courses = new ArrayCollection();

        foreach ($courses as $course) {
            $this->addCourses($course);
        }
    }

    public function addCourses(SessionRelCourse $course): void
    {
        $course->setSession($this);
        $this->courses[] = $course;
    }

    public function hasCourse(Course $course): bool
    {
        if (0 !== $this->getCourses()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('course', $course)
            );
            $relation = $this->getCourses()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    /**
     * Remove $course.
     */
    public function removeCourses(SessionRelCourse $course): void
    {
        foreach ($this->courses as $key => $value) {
            if ($value->getId() === $course->getId()) {
                unset($this->courses[$key]);
            }
        }
    }

    /**
     * Remove course subscription for a user.
     * If user status in session is student, then decrease number of course users.
     */
    public function removeUserCourseSubscription(User $user, Course $course): void
    {
        foreach ($this->sessionRelCourseRelUsers as $i => $sessionRelUser) {
            if ($sessionRelUser->getCourse()->getId() === $course->getId() &&
                $sessionRelUser->getUser()->getId() === $user->getId()) {
                if (self::STUDENT === $this->sessionRelCourseRelUsers[$i]->getStatus()) {
                    $sessionCourse = $this->getCourseSubscription($course);
                    $sessionCourse->setNbrUsers($sessionCourse->getNbrUsers() - 1);
                }

                unset($this->sessionRelCourseRelUsers[$i]);
            }
        }
    }

    /**
     * @param int $status if not set it will check if the user is registered
     *                    with any status
     */
    public function hasUserInCourse(User $user, Course $course, int $status = null): bool
    {
        $relation = $this->getUserInCourse($user, $course, $status);

        return $relation->count() > 0;
    }

    public function hasStudentInCourse(User $user, Course $course): bool
    {
        return $this->hasUserInCourse($user, $course, self::STUDENT);
    }

    public function hasCoachInCourseWithStatus(User $user, Course $course = null): bool
    {
        if (null === $course) {
            return false;
        }

        return $this->hasUserInCourse($user, $course, self::COACH);
    }

    public function getUserInCourse(User $user, Course $course, $status = null): Collection
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('course', $course)
            )->andWhere(
                Criteria::expr()->eq('user', $user)
            );

        if (null !== $status) {
            $criteria->andWhere(
                Criteria::expr()->eq('status', $status)
            );
        }

        return $this->getSessionRelCourseRelUsers()->matching($criteria);
    }

    public function getAllUsersFromCourse(int $status): Collection
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('status', $status)
            )
        ;

        return $this->getSessionRelCourseRelUsers()->matching($criteria);
    }

    public function getSessionRelCourseByUser(User $user, $status = null): Collection
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('user', $user)
            )
        ;

        if (null !== $status) {
            $criteria->andWhere(
                Criteria::expr()->eq('status', $status)
            );
        }

        return $this->getSessionRelCourseRelUsers()->matching($criteria);
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setNbrCourses(int $nbrCourses): self
    {
        $this->nbrCourses = $nbrCourses;

        return $this;
    }

    public function getNbrCourses(): int
    {
        return $this->nbrCourses;
    }

    public function setNbrUsers(int $nbrUsers): self
    {
        $this->nbrUsers = $nbrUsers;

        return $this;
    }

    public function getNbrUsers(): int
    {
        return $this->nbrUsers;
    }

    public function setNbrClasses(int $nbrClasses): self
    {
        $this->nbrClasses = $nbrClasses;

        return $this;
    }

    public function getNbrClasses(): int
    {
        return $this->nbrClasses;
    }

    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility.
     *
     * @return int
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    public function getPromotion(): ?Promotion
    {
        return $this->promotion;
    }

    public function setPromotion(?Promotion $promotion): self
    {
        $this->promotion = $promotion;

        return $this;
    }

    public function setDisplayStartDate(?DateTime $displayStartDate): self
    {
        $this->displayStartDate = $displayStartDate;

        return $this;
    }

    /**
     * Get displayStartDate.
     *
     * @return DateTime
     */
    public function getDisplayStartDate()
    {
        return $this->displayStartDate;
    }

    public function setDisplayEndDate(?DateTime $displayEndDate): self
    {
        $this->displayEndDate = $displayEndDate;

        return $this;
    }

    /**
     * Get displayEndDate.
     *
     * @return DateTime
     */
    public function getDisplayEndDate()
    {
        return $this->displayEndDate;
    }

    public function setAccessStartDate(?DateTime $accessStartDate): self
    {
        $this->accessStartDate = $accessStartDate;

        return $this;
    }

    /**
     * Get accessStartDate.
     *
     * @return DateTime
     */
    public function getAccessStartDate()
    {
        return $this->accessStartDate;
    }

    public function setAccessEndDate(?DateTime $accessEndDate): self
    {
        $this->accessEndDate = $accessEndDate;

        return $this;
    }

    /**
     * Get accessEndDate.
     *
     * @return DateTime
     */
    public function getAccessEndDate()
    {
        return $this->accessEndDate;
    }

    public function setCoachAccessStartDate(?DateTime $coachAccessStartDate): self
    {
        $this->coachAccessStartDate = $coachAccessStartDate;

        return $this;
    }

    /**
     * Get coachAccessStartDate.
     *
     * @return DateTime
     */
    public function getCoachAccessStartDate()
    {
        return $this->coachAccessStartDate;
    }

    public function setCoachAccessEndDate(?DateTime $coachAccessEndDate): self
    {
        $this->coachAccessEndDate = $coachAccessEndDate;

        return $this;
    }

    /**
     * Get coachAccessEndDate.
     *
     * @return DateTime
     */
    public function getCoachAccessEndDate()
    {
        return $this->coachAccessEndDate;
    }

    public function getGeneralCoach(): User
    {
        return $this->generalCoach;
    }

    public function setGeneralCoach(User $coach): self
    {
        $this->generalCoach = $coach;

        return $this;
    }

    public function getCategory(): ?SessionCategory
    {
        return $this->category;
    }

    public function setCategory(?SessionCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public static function getStatusList(): array
    {
        return [
            self::VISIBLE => 'status_visible',
            self::READ_ONLY => 'status_read_only',
            self::INVISIBLE => 'status_invisible',
            self::AVAILABLE => 'status_available',
        ];
    }

    /**
     * Check if session is visible.
     */
    public function isActive(): bool
    {
        $now = new Datetime('now');

        return $now > $this->getAccessStartDate();
    }

    public function isActiveForStudent(): bool
    {
        $start = $this->getAccessStartDate();
        $end = $this->getAccessEndDate();

        return $this->compareDates($start, $end);
    }

    public function isActiveForCoach(): bool
    {
        $start = $this->getCoachAccessStartDate();
        $end = $this->getCoachAccessEndDate();

        return $this->compareDates($start, $end);
    }

    /**
     * Compare the current date with start and end access dates.
     * Either missing date is interpreted as no limit.
     *
     * @return bool whether now is between the session access start and end dates
     */
    public function isCurrentlyAccessible(): bool
    {
        $now = new Datetime();

        return
            (null === $this->accessStartDate || $this->accessStartDate < $now) &&
            (null === $this->accessEndDate || $now < $this->accessEndDate);
    }

    public function addCourse(Course $course): void
    {
        $entity = new SessionRelCourse();
        $entity->setCourse($course);
        $this->addCourses($entity);
    }

    /**
     * Removes a course from this session.
     *
     * @param Course $course the course to remove from this session
     *
     * @return bool whether the course was actually found in this session and removed from it
     */
    public function removeCourse(Course $course): bool
    {
        $relCourse = $this->getCourseSubscription($course);
        if (null !== $relCourse) {
            $this->courses->removeElement($relCourse);
            $this->setNbrCourses(\count($this->courses));

            return true;
        }

        return false;
    }

    /**
     * @return SessionRelCourseRelUser[]|ArrayCollection|Collection
     */
    public function getSessionRelCourseRelUsers()
    {
        return $this->sessionRelCourseRelUsers;
    }

    public function setSessionRelCourseRelUsers(Collection $sessionRelCourseRelUsers): self
    {
        $this->sessionRelCourseRelUsers = new ArrayCollection();
        foreach ($sessionRelCourseRelUsers as $item) {
            $this->addSessionRelCourseRelUser($item);
        }

        return $this;
    }

    public function addSessionRelCourseRelUser(SessionRelCourseRelUser $sessionRelCourseRelUser): void
    {
        $sessionRelCourseRelUser->setSession($this);
        if (!$this->hasUserCourseSubscription($sessionRelCourseRelUser)) {
            $this->sessionRelCourseRelUsers->add($sessionRelCourseRelUser);
        }
    }

    /**
     * @return null|SessionRelCourse
     */
    public function getCourseSubscription(Course $course)
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('course', $course)
        );

        return $this->courses->matching($criteria)->current();
    }

    /**
     * Add a user course subscription.
     * If user status in session is student, then increase number of course users.
     */
    public function addUserInCourse(int $status, User $user, Course $course): SessionRelCourseRelUser
    {
        $userRelCourseRelSession =
            (new SessionRelCourseRelUser())
                ->setCourse($course)
                ->setUser($user)
                ->setSession($this)
                ->setStatus($status)
        ;
        $this->addSessionRelCourseRelUser($userRelCourseRelSession);

        if (self::STUDENT === $status) {
            $sessionCourse = $this->getCourseSubscription($course);
            $sessionCourse->setNbrUsers($sessionCourse->getNbrUsers() + 1);
        }

        return $userRelCourseRelSession;
    }

    public function hasUserCourseSubscription(SessionRelCourseRelUser $subscription): bool
    {
        if (0 !== $this->getSessionRelCourseRelUsers()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('user', $subscription->getUser())
            )->andWhere(
                Criteria::expr()->eq('course', $subscription->getCourse())
            )->andWhere(
                Criteria::expr()->eq('session', $subscription->getSession())
            );
            $relation = $this->getSessionRelCourseRelUsers()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    /**
     * currentCourse is set in CourseListener.
     *
     * @return Course
     */
    public function getCurrentCourse()
    {
        return $this->currentCourse;
    }

    /**
     * currentCourse is set in CourseListener.
     */
    public function setCurrentCourse(Course $course): self
    {
        // If the session is registered in the course session list.
        $exists = $this->getCourses()->exists(
            function ($key, $element) use ($course) {
                /** @var SessionRelCourse $element */
                return $course->getId() === $element->getCourse()->getId();
            }
        );

        if ($exists) {
            $this->currentCourse = $course;
        }

        return $this;
    }

    public function setSendSubscriptionNotification(bool $sendNotification): self
    {
        $this->sendSubscriptionNotification = $sendNotification;

        return $this;
    }

    public function getSendSubscriptionNotification(): bool
    {
        return $this->sendSubscriptionNotification;
    }

    /**
     * Get user from course by status.
     *
     * @return ArrayCollection|Collection
     */
    public function getSessionRelCourseRelUsersByStatus(Course $course, int $status)
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('course', $course)
            )
            ->andWhere(
                Criteria::expr()->eq('status', $status)
            )
        ;

        return $this->sessionRelCourseRelUsers->matching($criteria);
    }

    public function getIssuedSkills(): Collection
    {
        return $this->issuedSkills;
    }

    public function setCurrentUrl(AccessUrl $url): self
    {
        $urlList = $this->getUrls();
        foreach ($urlList as $item) {
            if ($item->getUrl()->getId() === $url->getId()) {
                $this->currentUrl = $url;

                break;
            }
        }

        return $this;
    }

    /**
     * @return AccessUrl
     */
    public function getCurrentUrl()
    {
        return $this->currentUrl;
    }

    /**
     * @return Collection
     */
    public function getUrls()
    {
        return $this->urls;
    }

    public function setUrls(Collection $urls): self
    {
        $this->urls = new ArrayCollection();

        foreach ($urls as $url) {
            $this->addUrls($url);
        }

        return $this;
    }

    public function addAccessUrl(AccessUrl $url): self
    {
        $accessUrlRelSession = new AccessUrlRelSession();
        $accessUrlRelSession->setUrl($url);
        $accessUrlRelSession->setSession($this);

        $this->addUrls($accessUrlRelSession);

        return $this;
    }

    public function addUrls(AccessUrlRelSession $url): self
    {
        $url->setSession($this);
        $this->urls->add($url);

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSessionAdmin(): ?User
    {
        return $this->sessionAdmin;
    }

    public function setSessionAdmin(User $sessionAdmin): self
    {
        $this->sessionAdmin = $sessionAdmin;

        return $this;
    }

    /**
     * @return SkillRelCourse[]|Collection
     */
    public function getSkills()
    {
        return $this->skills;
    }

    /**
     * @return ResourceLink[]|Collection
     */
    public function getResourceLinks()
    {
        return $this->resourceLinks;
    }

    public function isUserGeneralCoach(User $user): bool
    {
        $generalCoach = $this->getGeneralCoach();

        return $generalCoach instanceof User && $user->getId() === $generalCoach->getId();
    }

    /**
     * Check if $user is course coach in any course.
     */
    public function hasCoachInCourseList(User $user): bool
    {
        foreach ($this->courses as $sessionCourse) {
            if ($this->hasCoachInCourseWithStatus($user, $sessionCourse->getCourse())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if $user is student in any course.
     */
    public function hasStudentInCourseList(User $user): bool
    {
        foreach ($this->courses as $sessionCourse) {
            if ($this->hasStudentInCourse($user, $sessionCourse->getCourse())) {
                return true;
            }
        }

        return false;
    }

    protected function compareDates(DateTime $start, DateTime $end): bool
    {
        $now = new Datetime('now');

        if (!empty($start) && !empty($end) && ($now >= $start && $now <= $end)) {
            return true;
        }

        if (!empty($start) && $now >= $start) {
            return true;
        }

        return !empty($end) && $now <= $end;
    }
}
