<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use Chamilo\CoreBundle\Entity\Listener\SessionListener;
use Chamilo\CoreBundle\Repository\SessionRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: [
                'groups' => ['session:read', 'session:item:read'],
            ],
            security: "is_granted('ROLE_ADMIN') or is_granted('VIEW', object)"
        ),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['session:read']],
    denormalizationContext: ['groups' => ['session:write']],
    security: "is_granted('ROLE_ADMIN')"
)]
#[ORM\Table(name: 'session')]
#[ORM\UniqueConstraint(name: 'name', columns: ['name'])]
#[ORM\EntityListeners([SessionListener::class])]
#[ORM\Entity(repositoryClass: SessionRepository::class)]
#[UniqueEntity('name')]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['name' => 'partial'])]
#[ApiFilter(filterClass: PropertyFilter::class)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['id', 'name'])]
class Session implements ResourceWithAccessUrlInterface, Stringable
{
    public const VISIBLE = 1;
    public const READ_ONLY = 2;
    public const INVISIBLE = 3;
    public const AVAILABLE = 4;
    public const STUDENT = 0;
    public const DRH = 1;
    public const COURSE_COACH = 2;
    public const GENERAL_COACH = 3;
    public const SESSION_ADMIN = 4;

    #[Groups([
        'session:read',
        'session_rel_user:read',
        'session_rel_course_rel_user:read',
        'course:read',
        'track_e_exercise:read',
    ])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @var Collection<int, SessionRelCourse>
     */
    #[Groups([
        'session:read',
        'session_rel_user:read',
        'session_rel_course_rel_user:read',
    ])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[ORM\OneToMany(
        mappedBy: 'session',
        targetEntity: SessionRelCourse::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    protected Collection $courses;

    /**
     * @var Collection<int, SessionRelUser>
     */
    #[Groups([
        'session:read',
    ])]
    #[ORM\OneToMany(
        mappedBy: 'session',
        targetEntity: SessionRelUser::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $users;

    /**
     * @var Collection<int, SessionRelCourseRelUser>
     */
    #[Groups([
        'session:read',
        'session_rel_course_rel_user:read',
    ])]
    #[ORM\OneToMany(
        mappedBy: 'session',
        targetEntity: SessionRelCourseRelUser::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    protected Collection $sessionRelCourseRelUsers;

    /**
     * @var Collection<int, SkillRelCourse>
     */
    #[ORM\OneToMany(
        mappedBy: 'session',
        targetEntity: SkillRelCourse::class,
        cascade: ['persist', 'remove']
    )]
    protected Collection $skills;

    /**
     * @var Collection<int, SkillRelUser>
     */
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: SkillRelUser::class, cascade: ['persist'])]
    protected Collection $issuedSkills;

    /**
     * @var Collection<int, AccessUrlRelSession>
     */
    #[ORM\OneToMany(
        mappedBy: 'session',
        targetEntity: AccessUrlRelSession::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    protected Collection $urls;

    /**
     * @var Collection<int, ResourceLink>
     */
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: ResourceLink::class, cascade: ['remove'], orphanRemoval: true)]
    protected Collection $resourceLinks;

    protected AccessUrl $currentUrl;

    protected ?Course $currentCourse = null;

    #[Assert\NotBlank]
    #[Groups([
        'session:read',
        'session:write',
        'session_rel_course_rel_user:read',
        'document:read',
        'session_rel_user:read',
        'course:read',
    ])]
    #[ORM\Column(name: 'name', type: 'string', length: 150)]
    protected string $name;

    #[Groups([
        'session:read',
        'session:write',
    ])]
    #[ORM\Column(name: 'description', type: 'text', unique: false, nullable: true)]
    protected ?string $description;

    #[Groups([
        'session:read',
        'session:write',
    ])]
    #[ORM\Column(name: 'show_description', type: 'boolean', nullable: true)]
    protected ?bool $showDescription;

    #[Groups(['session:read', 'session:write'])]
    #[ORM\Column(name: 'duration', type: 'integer', nullable: true)]
    protected ?int $duration = null;

    #[Groups(['session:read'])]
    #[ORM\Column(name: 'nbr_courses', type: 'integer', unique: false, nullable: false)]
    protected int $nbrCourses;

    #[Groups(['session:read'])]
    #[ORM\Column(name: 'nbr_users', type: 'integer', unique: false, nullable: false)]
    protected int $nbrUsers;

    #[Groups(['session:read'])]
    #[ORM\Column(name: 'nbr_classes', type: 'integer', unique: false, nullable: false)]
    protected int $nbrClasses;

    #[Groups([
        'session:read',
        'session:write',
    ])]
    #[ORM\Column(name: 'visibility', type: 'integer')]
    protected int $visibility;

    #[ORM\ManyToOne(targetEntity: Promotion::class, cascade: ['persist'], inversedBy: 'sessions')]
    #[ORM\JoinColumn(name: 'promotion_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Promotion $promotion = null;

    #[Groups([
        'session:read',
        'session_rel_user:read',
        'session_rel_course_rel_user:read',
    ])]
    #[ORM\Column(name: 'display_start_date', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $displayStartDate;

    #[Groups([
        'session:read',
        'session_rel_user:read',
        'session_rel_course_rel_user:read',
    ])]
    #[ORM\Column(name: 'display_end_date', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $displayEndDate;

    #[Groups([
        'session:read',
        'session_rel_user:read',
        'session_rel_course_rel_user:read',
    ])]
    #[ORM\Column(name: 'access_start_date', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $accessStartDate;

    #[Groups([
        'session:read',
        'session_rel_user:read',
        'session_rel_course_rel_user:read',
    ])]
    #[ORM\Column(name: 'access_end_date', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $accessEndDate;

    #[Groups([
        'session:read',
        'session_rel_user:read',
        'session_rel_course_rel_user:read',
    ])]
    #[ORM\Column(name: 'coach_access_start_date', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $coachAccessStartDate;

    #[Groups([
        'session:read',
        'session_rel_user:read',
        'session_rel_course_rel_user:read',
    ])]
    #[ORM\Column(name: 'coach_access_end_date', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $coachAccessEndDate;

    #[ORM\Column(name: 'position', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $position;

    #[Groups(['session:read'])]
    #[ORM\Column(name: 'status', type: 'integer', nullable: false)]
    protected int $status;

    #[Groups(['session:read', 'session:write', 'session_rel_user:read'])]
    #[ORM\ManyToOne(targetEntity: SessionCategory::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(name: 'session_category_id', referencedColumnName: 'id')]
    protected ?SessionCategory $category = null;

    #[ORM\Column(
        name: 'send_subscription_notification',
        type: 'boolean',
        nullable: false,
        options: ['default' => false]
    )]
    protected bool $sendSubscriptionNotification;

    /**
     * Image illustrating the session (was extra field 'image' in 1.11).
     */
    #[ORM\ManyToOne(targetEntity: Asset::class, cascade: ['remove'])]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Asset $image = null;

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
        return $this->getName();
    }

    public static function getRelationTypeList(): array
    {
        return [self::STUDENT, self::DRH, self::COURSE_COACH, self::GENERAL_COACH, self::SESSION_ADMIN];
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDuration(): ?int
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
     * @return Collection<int, SessionRelUser>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function setUsers(Collection $users): self
    {
        $this->users = new ArrayCollection();
        foreach ($users as $user) {
            $this->addUserSubscription($user);
        }

        return $this;
    }

    public function addUserSubscription(SessionRelUser $subscription): void
    {
        $subscription->setSession($this);
        if (!$this->hasUser($subscription)) {
            $this->users->add($subscription);
            $this->nbrUsers++;
        }
    }

    public function hasUser(SessionRelUser $subscription): bool
    {
        if (0 !== $this->getUsers()->count()) {
            $criteria = Criteria::create()
                ->where(
                    Criteria::expr()->eq('user', $subscription->getUser())
                )
                ->andWhere(
                    Criteria::expr()->eq('session', $subscription->getSession())
                )
                ->andWhere(
                    Criteria::expr()->eq('relationType', $subscription->getRelationType())
                )
            ;
            $relation = $this->getUsers()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    public function hasCourse(Course $course): bool
    {
        if (0 !== $this->getCourses()->count()) {
            $criteria = Criteria::create()->where(Criteria::expr()->eq('course', $course));
            $relation = $this->getCourses()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    public function getCourses(): Collection
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
        $this->courses->add($course);
    }

    public function removeCourses(SessionRelCourse $course): void
    {
        foreach ($this->courses as $key => $value) {
            if ($value->getId() === $course->getId()) {
                unset($this->courses[$key]);
            }
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Remove course subscription for a user.
     * If user status in session is student, then decrease number of course users.
     */
    public function removeUserCourseSubscription(User $user, Course $course): void
    {
        foreach ($this->sessionRelCourseRelUsers as $i => $sessionRelUser) {
            if ($sessionRelUser->getCourse()->getId() === $course->getId()
                && $sessionRelUser->getUser()->getId() === $user->getId()
            ) {
                if (self::STUDENT === $this->sessionRelCourseRelUsers[$i]->getStatus()) {
                    $sessionCourse = $this->getCourseSubscription($course);
                    $sessionCourse->setNbrUsers($sessionCourse->getNbrUsers() - 1);
                }

                unset($this->sessionRelCourseRelUsers[$i]);
            }
        }
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

    public function getCourseSubscription(Course $course): ?SessionRelCourse
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('course', $course));

        return $this->courses->matching($criteria)->current();
    }

    public function getNbrUsers(): int
    {
        return $this->nbrUsers;
    }

    public function setNbrUsers(int $nbrUsers): self
    {
        $this->nbrUsers = $nbrUsers;

        return $this;
    }

    public function getAllUsersFromCourse(int $status): Collection
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('status', $status));

        return $this->getSessionRelCourseRelUsers()->matching($criteria);
    }

    public function getSessionRelCourseRelUsers(): Collection
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

    public function hasUserCourseSubscription(SessionRelCourseRelUser $subscription): bool
    {
        if (0 !== $this->getSessionRelCourseRelUsers()->count()) {
            $criteria = Criteria::create()
                ->where(
                    Criteria::expr()->eq('user', $subscription->getUser())
                )
                ->andWhere(
                    Criteria::expr()->eq('course', $subscription->getCourse())
                )
                ->andWhere(
                    Criteria::expr()->eq('session', $subscription->getSession())
                )
            ;
            $relation = $this->getSessionRelCourseRelUsers()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    public function getSessionRelCourseByUser(User $user, ?int $status = null): Collection
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('user', $user));
        if (null !== $status) {
            $criteria->andWhere(Criteria::expr()->eq('status', $status));
        }

        return $this->sessionRelCourseRelUsers->matching($criteria);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getNbrCourses(): int
    {
        return $this->nbrCourses;
    }

    public function setNbrCourses(int $nbrCourses): self
    {
        $this->nbrCourses = $nbrCourses;

        return $this;
    }

    public function getNbrClasses(): int
    {
        return $this->nbrClasses;
    }

    public function setNbrClasses(int $nbrClasses): self
    {
        $this->nbrClasses = $nbrClasses;

        return $this;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
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

    public function getDisplayStartDate(): ?DateTime
    {
        return $this->displayStartDate;
    }

    public function setDisplayStartDate(?DateTime $displayStartDate): self
    {
        $this->displayStartDate = $displayStartDate;

        return $this;
    }

    public function getDisplayEndDate(): ?DateTime
    {
        return $this->displayEndDate;
    }

    public function setDisplayEndDate(?DateTime $displayEndDate): self
    {
        $this->displayEndDate = $displayEndDate;

        return $this;
    }

    public function getGeneralCoaches(): ReadableCollection
    {
        return $this->getGeneralCoachesSubscriptions()
            ->map(fn (SessionRelUser $subscription) => $subscription->getUser())
        ;
    }

    public function getGeneralCoachesSubscriptions(): Collection
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('relationType', self::GENERAL_COACH));

        return $this->users->matching($criteria);
    }

    public function hasUserAsGeneralCoach(User $user): bool
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('relationType', self::GENERAL_COACH)
            )
            ->andWhere(
                Criteria::expr()->eq('user', $user)
            )
        ;

        return $this->users->matching($criteria)->count() > 0;
    }

    public function addGeneralCoach(User $coach): self
    {
        return $this->addUserInSession(self::GENERAL_COACH, $coach);
    }

    public function addUserInSession(int $relationType, User $user): self
    {
        $sessionRelUser = (new SessionRelUser())->setUser($user)->setRelationType($relationType);
        $this->addUserSubscription($sessionRelUser);

        return $this;
    }

    public function removeGeneralCoach(User $user): self
    {
        $this->removeUserInSession(self::GENERAL_COACH, $user);

        return $this;
    }

    public function removeUserInSession(int $relationType, User $user): self
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('relationType', $relationType)
            )
            ->andWhere(
                Criteria::expr()->eq('user', $user)
            )
        ;
        $subscriptions = $this->users->matching($criteria);

        foreach ($subscriptions as $subscription) {
            $this->removeUserSubscription($subscription);
        }

        return $this;
    }

    public function removeUserSubscription(SessionRelUser $subscription): self
    {
        if ($this->hasUser($subscription)) {
            $subscription->setSession(null);
            $this->users->removeElement($subscription);
            $this->nbrUsers--;
        }

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

    /**
     * Check if session is visible.
     */
    public function isActive(): bool
    {
        $now = new Datetime('now');

        return $now > $this->getAccessStartDate();
    }

    public function getAccessStartDate(): ?DateTime
    {
        return $this->accessStartDate;
    }

    public function setAccessStartDate(?DateTime $accessStartDate): self
    {
        $this->accessStartDate = $accessStartDate;

        return $this;
    }

    public function isActiveForStudent(): bool
    {
        $start = $this->getAccessStartDate();
        $end = $this->getAccessEndDate();

        return $this->compareDates($start, $end);
    }

    public function getAccessEndDate(): ?DateTime
    {
        return $this->accessEndDate;
    }

    public function setAccessEndDate(?DateTime $accessEndDate): self
    {
        $this->accessEndDate = $accessEndDate;

        return $this;
    }

    public function isActiveForCoach(): bool
    {
        $start = $this->getCoachAccessStartDate();
        $end = $this->getCoachAccessEndDate();

        return $this->compareDates($start, $end);
    }

    public function getCoachAccessStartDate(): ?DateTime
    {
        return $this->coachAccessStartDate;
    }

    public function setCoachAccessStartDate(?DateTime $coachAccessStartDate): self
    {
        $this->coachAccessStartDate = $coachAccessStartDate;

        return $this;
    }

    public function getCoachAccessEndDate(): ?DateTime
    {
        return $this->coachAccessEndDate;
    }

    public function setCoachAccessEndDate(?DateTime $coachAccessEndDate): self
    {
        $this->coachAccessEndDate = $coachAccessEndDate;

        return $this;
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

        return (null === $this->accessStartDate || $this->accessStartDate < $now)
            && (null === $this->accessEndDate || $now < $this->accessEndDate);
    }

    public function addCourse(Course $course): self
    {
        $sessionRelCourse = (new SessionRelCourse())->setCourse($course);
        $this->addCourses($sessionRelCourse);

        return $this;
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
     * Add a user course subscription.
     * If user status in session is student, then increase number of course users.
     * Status example: Session::STUDENT.
     */
    public function addUserInCourse(int $status, User $user, Course $course): SessionRelCourseRelUser
    {
        $userRelCourseRelSession = (new SessionRelCourseRelUser())
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

    /**
     * currentCourse is set in CourseListener.
     */
    public function getCurrentCourse(): ?Course
    {
        return $this->currentCourse;
    }

    /**
     * currentCourse is set in CourseListener.
     */
    public function setCurrentCourse(Course $course): self
    {
        // If the session is registered in the course session list.
        $exists = $this->getCourses()
            ->exists(
                fn ($key, $element) => $course->getId() === $element->getCourse()->getId()
            )
        ;

        if ($exists) {
            $this->currentCourse = $course;
        }

        return $this;
    }

    public function getSendSubscriptionNotification(): bool
    {
        return $this->sendSubscriptionNotification;
    }

    public function setSendSubscriptionNotification(bool $sendNotification): self
    {
        $this->sendSubscriptionNotification = $sendNotification;

        return $this;
    }

    /**
     * Get user from course by status.
     */
    public function getSessionRelCourseRelUsersByStatus(Course $course, int $status): Collection
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

    public function getCurrentUrl(): AccessUrl
    {
        return $this->currentUrl;
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

    public function getUrls(): Collection
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

    public function addUrls(AccessUrlRelSession $url): self
    {
        $url->setSession($this);
        $this->urls->add($url);

        return $this;
    }

    public function addAccessUrl(?AccessUrl $url): self
    {
        $accessUrlRelSession = new AccessUrlRelSession();
        $accessUrlRelSession->setUrl($url);
        $accessUrlRelSession->setSession($this);
        $this->addUrls($accessUrlRelSession);

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getSessionAdmins(): ReadableCollection
    {
        return $this->getGeneralAdminsSubscriptions()
            ->map(fn (SessionRelUser $subscription) => $subscription->getUser())
        ;
    }

    public function getGeneralAdminsSubscriptions(): Collection
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('relationType', self::SESSION_ADMIN));

        return $this->users->matching($criteria);
    }

    public function hasUserAsSessionAdmin(User $user): bool
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('relationType', self::SESSION_ADMIN)
            )
            ->andWhere(
                Criteria::expr()->eq('user', $user)
            )
        ;

        return $this->users->matching($criteria)->count() > 0;
    }

    public function addSessionAdmin(User $sessionAdmin): self
    {
        return $this->addUserInSession(self::SESSION_ADMIN, $sessionAdmin);
    }

    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function getResourceLinks(): Collection
    {
        return $this->resourceLinks;
    }

    public function getImage(): ?Asset
    {
        return $this->image;
    }

    public function setImage(?Asset $asset): self
    {
        $this->image = $asset;

        return $this;
    }

    public function hasImage(): bool
    {
        return null !== $this->image;
    }

    /**
     * Check if $user is course coach in any course.
     */
    public function hasCoachInCourseList(User $user): bool
    {
        foreach ($this->courses as $sessionCourse) {
            if ($this->hasCourseCoachInCourse($user, $sessionCourse->getCourse())) {
                return true;
            }
        }

        return false;
    }

    public function hasCourseCoachInCourse(User $user, Course $course = null): bool
    {
        if (null === $course) {
            return false;
        }

        return $this->hasUserInCourse($user, $course, self::COURSE_COACH);
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

    public function getUserInCourse(User $user, Course $course, ?int $status = null): Collection
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('course', $course)
            )
            ->andWhere(
                Criteria::expr()->eq('user', $user)
            )
        ;

        if (null !== $status) {
            $criteria->andWhere(Criteria::expr()->eq('status', $status));
        }

        return $this->getSessionRelCourseRelUsers()->matching($criteria);
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

    public function hasStudentInCourse(User $user, Course $course): bool
    {
        return $this->hasUserInCourse($user, $course, self::STUDENT);
    }

    protected function compareDates(?DateTime $start, ?DateTime $end = null): bool
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
