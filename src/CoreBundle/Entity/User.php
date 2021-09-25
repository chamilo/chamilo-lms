<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Chamilo\CoreBundle\Traits\UserCreatorTrait;
use Chamilo\CourseBundle\Entity\CGroupRelTutor;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\NilUuid;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use UserManager;

/**
 * EquatableInterface is needed to check if the user needs to be refreshed.
 *
 * @ORM\Table(
 *     name="user",
 *     indexes={
 *         @ORM\Index(name="status", columns={"status"})
 *     }
 * )
 * @UniqueEntity("username")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\Node\UserRepository")
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\UserListener"})
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            'security' => "is_granted('ROLE_USER')", // @todo increase security
        ],
        'post' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
    ],
    iri: 'http://schema.org/Person',
    itemOperations: [
        'get' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
        'put' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
        'delete' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
    ],
    attributes: [
        'security' => 'is_granted("ROLE_USER")',
    ],
    denormalizationContext: [
        'groups' => ['user:write'],
    ],
    normalizationContext: [
        'groups' => ['user:read'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'username' => 'partial',
    'firstname' => 'partial',
    'lastname' => 'partial',
])]
#[ApiFilter(BooleanFilter::class, properties: ['isActive'])]
class User implements UserInterface, EquatableInterface, ResourceInterface, ResourceIllustrationInterface, PasswordAuthenticatedUserInterface, LegacyPasswordAuthenticatedUserInterface
{
    use TimestampableEntity;
    use UserCreatorTrait;

    public const USERNAME_MAX_LENGTH = 100;
    public const ROLE_DEFAULT = 'ROLE_USER';

    public const ANONYMOUS = 6;

    /*public const COURSE_MANAGER = 1;
    public const TEACHER = 1;
    public const SESSION_ADMIN = 3;
    public const DRH = 4;
    public const STUDENT = 5;
    public const ANONYMOUS = 6;*/

    /**
     * @ORM\OneToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\ResourceNode", cascade={"remove"}, orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="resource_node_id", onDelete="CASCADE")
     */
    #[Groups(['user_json:read'])]
    public ?ResourceNode $resourceNode = null;

    /**
     * Resource illustration URL - Property set by ResourceNormalizer.php.
     *
     * @ApiProperty(iri="http://schema.org/contentUrl")
     */
    #[Groups([
        'user:read',
        'resource_node:read',
        'document:read',
        'media_object_read',
        'course:read',
        'course_rel_user:read',
        'user_json:read',
        'message:read',
        'user_rel_user:read',
    ])]
    public ?string $illustrationUrl = null;

    /**
     * @Groups({"user:read", "resource_node:read", "user_json:read"})
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    #[Groups([
        'message:read',
        'user_rel_user:read',
    ])]
    protected ?int $id = null;

    /**
     * @ORM\Column(name="username", type="string", length=100, unique=true)
     */
    #[Assert\NotBlank]
    #[Groups([
        'user:read',
        'user:write',
        'course:read',
        'resource_node:read',
        'user_json:read',
        'message:read',
        'user_rel_user:read',
    ])]
    protected string $username;

    /**
     * @ORM\Column(name="api_token", type="string", unique=true, nullable=true)
     */
    protected ?string $apiToken = null;

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @ORM\Column(name="firstname", type="string", length=64, nullable=true)
     */
    #[Assert\NotBlank]
    #[Groups([
        'user:read',
        'user:write',
        'resource_node:read',
        'user_json:read',
    ])]
    protected ?string $firstname = null;

    /**
     * @ORM\Column(name="lastname", type="string", length=64, nullable=true)
     */
    #[Groups([
        'user:read',
        'user:write',
        'resource_node:read',
        'user_json:read',
    ])]
    protected ?string $lastname = null;

    /**
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="website", type="string", length=255, nullable=true)
     */
    protected ?string $website;

    /**
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="biography", type="text", nullable=true)
     */
    protected ?string $biography;

    /**
     * @Groups({"user:read", "user:write", "user_json:read"})
     * @ORM\Column(name="locale", type="string", length=10)
     */
    protected string $locale;

    /**
     * @Groups({"user:write"})
     */
    protected ?string $plainPassword = null;

    /**
     * @ORM\Column(name="password", type="string", length=255)
     */
    protected string $password;

    /**
     * @ORM\Column(name="username_canonical", type="string", length=180)
     */
    protected string $usernameCanonical;

    /**
     * @Groups({"user:read", "user:write", "user_json:read"})
     * @ORM\Column(name="timezone", type="string", length=64)
     */
    protected string $timezone;

    /**
     * @ORM\Column(name="email_canonical", type="string", length=100)
     */
    protected string $emailCanonical;

    /**
     * @Groups({"user:read", "user:write", "user_json:read"})
     *
     * @ORM\Column(name="email", type="string", length=100)
     */
    #[Assert\NotBlank]
    #[Assert\Email]
    protected string $email;

    /**
     * @ORM\Column(name="locked", type="boolean")
     */
    protected bool $locked;

    /**
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="enabled", type="boolean")
     */
    #[Assert\NotNull]
    protected bool $enabled;

    /**
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="expired", type="boolean")
     */
    protected bool $expired;

    /**
     * @ORM\Column(name="credentials_expired", type="boolean")
     */
    protected bool $credentialsExpired;

    /**
     * @ORM\Column(name="credentials_expire_at", type="datetime", nullable=true)
     */
    protected ?DateTime $credentialsExpireAt;

    /**
     * @ORM\Column(name="date_of_birth", type="datetime", nullable=true)
     */
    protected ?DateTime $dateOfBirth;

    /**
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="expires_at", type="datetime", nullable=true)
     */
    protected ?DateTime $expiresAt;

    /**
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="phone", type="string", length=64, nullable=true)
     */
    protected ?string $phone = null;

    /**
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="address", type="string", length=250, nullable=true)
     */
    protected ?string $address = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected string $salt;

    /**
     * @ORM\Column(name="gender", type="string", length=1, nullable=true)
     */
    protected ?string $gender = null;

    /**
     * @Groups({"user:read"})
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    protected ?DateTime $lastLogin = null;

    /**
     * Random string sent to the user email address in order to verify it.
     *
     * @ORM\Column(name="confirmation_token", type="string", length=255, nullable=true)
     */
    protected ?string $confirmationToken = null;

    /**
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     */
    protected ?DateTime $passwordRequestedAt;

    /**
     * @var Collection<int, CourseRelUser>|CourseRelUser[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\CourseRelUser", mappedBy="user", orphanRemoval=true)
     */
    #[ApiSubresource]
    protected Collection $courses;

    /**
     * @var Collection<int, UsergroupRelUser>|UsergroupRelUser[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\UsergroupRelUser", mappedBy="user")
     */
    protected Collection $classes;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CDropboxPost", mappedBy="user").
     */
    protected Collection $dropBoxReceivedFiles;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CDropboxFile", mappedBy="userSent").
     */
    protected Collection $dropBoxSentFiles;

    /**
     * An array of roles. Example: ROLE_USER, ROLE_TEACHER, ROLE_ADMIN.
     *
     * @Groups({"user:read", "user:write", "user_json:read"})
     * @ORM\Column(type="array")
     *
     * @var mixed[]|string[]
     */
    protected array $roles = [];

    /**
     * @ORM\Column(name="profile_completed", type="boolean", nullable=true)
     */
    protected ?bool $profileCompleted = null;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\JuryMembers", mappedBy="user")
     */
    //protected $jurySubscriptions;

    /**
     * @var Collection|Group[]
     * @ORM\ManyToMany(targetEntity="Chamilo\CoreBundle\Entity\Group", inversedBy="users")
     * @ORM\JoinTable(
     *     name="fos_user_user_group",
     *     joinColumns={
     *         @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="cascade")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     *     }
     * )
     */
    protected Collection $groups;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\CurriculumItemRelUser", mappedBy="user").
     *
     * @var Collection|mixed[]
     */
    protected Collection $curriculumItems;

    /**
     * @var AccessUrlRelUser[]|Collection<int, AccessUrlRelUser>
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrlRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected Collection $portals;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\ResourceNode", mappedBy="creator")
     *
     * @var Collection<int, ResourceNode>|ResourceNode[]
     */
    protected Collection $resourceNodes;

    /**
     * @var Collection<int, SessionRelCourseRelUser>|SessionRelCourseRelUser[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\SessionRelCourseRelUser",
     *     mappedBy="user",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    protected Collection $sessionRelCourseRelUsers;

    /**
     * @var Collection<int, SessionRelUser>|SessionRelUser[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\SessionRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    #[ApiSubresource]
    protected Collection $sessionsRelUser;

    /**
     * @var Collection<int, SkillRelUser>|SkillRelUser[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\SkillRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected Collection $achievedSkills;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\SkillRelUserComment",
     *     mappedBy="feedbackGiver",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     *
     * @var Collection<int, SkillRelUserComment>|SkillRelUserComment[]
     */
    protected Collection $commentedUserSkills;

    /**
     * @var Collection<int, GradebookCategory>|GradebookCategory[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory", mappedBy="user")
     */
    protected Collection $gradeBookCategories;

    /**
     * @var Collection<int, GradebookCertificate>|GradebookCertificate[]
     *
     * @ORM\OneToMany(
     *     targetEntity="GradebookCertificate", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected Collection $gradeBookCertificates;

    /**
     * @var Collection<int, GradebookComment>|GradebookComment[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\GradebookComment", mappedBy="user")
     */
    protected Collection $gradeBookComments;

    /**
     * @ORM\OneToMany(
     *     targetEntity="GradebookEvaluation", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     *
     * @var Collection<int, GradebookEvaluation>|GradebookEvaluation[]
     */
    protected Collection $gradeBookEvaluations;

    /**
     * @ORM\OneToMany(targetEntity="GradebookLink", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var Collection<int, GradebookLink>|GradebookLink[]
     */
    protected Collection $gradeBookLinks;

    /**
     * @ORM\OneToMany(targetEntity="GradebookResult", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @var Collection<int, GradebookResult>|GradebookResult[]
     */
    protected Collection $gradeBookResults;

    /**
     * @ORM\OneToMany(
     *     targetEntity="GradebookResultLog", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     *
     * @var Collection<int, GradebookResultLog>|GradebookResultLog[]
     */
    protected Collection $gradeBookResultLogs;

    /**
     * @ORM\OneToMany(
     *     targetEntity="GradebookScoreLog", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     *
     * @var Collection<int, GradebookScoreLog>|GradebookScoreLog[]
     */
    protected Collection $gradeBookScoreLogs;

    /**
     * @var Collection<int, UserRelUser>|UserRelUser[]
     * @ORM\OneToMany(targetEntity="UserRelUser", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    protected Collection $friends;

    /**
     * @var Collection<int, UserRelUser>|UserRelUser[]
     * @ORM\OneToMany(targetEntity="UserRelUser", mappedBy="friend", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    protected Collection $friendsWithMe;

    /**
     * @var Collection<int, GradebookLinkevalLog>|GradebookLinkevalLog[]
     * @ORM\OneToMany(
     *     targetEntity="GradebookLinkevalLog",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected Collection $gradeBookLinkEvalLogs;

    /**
     * @var Collection<int, SequenceValue>|SequenceValue[]
     * @ORM\OneToMany(targetEntity="SequenceValue", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected Collection $sequenceValues;

    /**
     * @var Collection<int, TrackEExerciseConfirmation>|TrackEExerciseConfirmation[]
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\TrackEExerciseConfirmation",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected Collection $trackEExerciseConfirmations;

    /**
     * @var Collection<int, TrackEAttempt>|TrackEAttempt[]
     * @ORM\OneToMany(
     *     targetEntity="TrackEAccessComplete", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected Collection $trackEAccessCompleteList;

    /**
     * @var Collection<int, Templates>|Templates[]
     * @ORM\OneToMany(targetEntity="Templates", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected Collection $templates;

    /**
     * @var Collection<int, TrackEAttempt>|TrackEAttempt[]
     * @ORM\OneToMany(targetEntity="TrackEAttempt", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected Collection $trackEAttempts;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\TrackECourseAccess",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     *
     * @var Collection<int, TrackECourseAccess>|TrackECourseAccess[]
     */
    protected Collection $trackECourseAccess;

    /**
     * @var Collection<int, UserCourseCategory>|UserCourseCategory[]
     *
     * @ORM\OneToMany(
     *     targetEntity="UserCourseCategory",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected Collection $userCourseCategories;

    /**
     * @var Collection<int, UserRelCourseVote>|UserRelCourseVote[]
     * @ORM\OneToMany(targetEntity="UserRelCourseVote", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected Collection $userRelCourseVotes;

    /**
     * @var Collection<int, UserRelTag>|UserRelTag[]
     * @ORM\OneToMany(targetEntity="UserRelTag", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected Collection $userRelTags;

    /**
     * @var Collection<int, PersonalAgenda>|PersonalAgenda[]
     * @ORM\OneToMany(targetEntity="PersonalAgenda", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected Collection $personalAgendas;

    /**
     * @var CGroupRelUser[]|Collection<int, CGroupRelUser>
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CGroupRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected Collection $courseGroupsAsMember;

    /**
     * @var CGroupRelTutor[]|Collection<int, CGroupRelTutor>
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CGroupRelTutor", mappedBy="user", orphanRemoval=true)
     */
    protected Collection $courseGroupsAsTutor;

    /**
     * @ORM\Column(name="auth_source", type="string", length=50, nullable=true)
     */
    protected ?string $authSource;

    /**
     * @ORM\Column(name="status", type="integer")
     */
    protected int $status;

    /**
     * @ORM\Column(name="official_code", type="string", length=40, nullable=true)
     */
    protected ?string $officialCode = null;

    /**
     * @ORM\Column(name="picture_uri", type="string", length=250, nullable=true)
     */
    protected ?string $pictureUri = null;

    /**
     * @ORM\Column(name="creator_id", type="integer", nullable=true, unique=false)
     */
    protected ?int $creatorId = null;

    /**
     * @ORM\Column(name="competences", type="text", nullable=true, unique=false)
     */
    protected ?string $competences = null;

    /**
     * @ORM\Column(name="diplomas", type="text", nullable=true, unique=false)
     */
    protected ?string $diplomas = null;

    /**
     * @ORM\Column(name="openarea", type="text", nullable=true, unique=false)
     */
    protected ?string $openarea = null;

    /**
     * @ORM\Column(name="teach", type="text", nullable=true, unique=false)
     */
    protected ?string $teach = null;

    /**
     * @ORM\Column(name="productions", type="string", length=250, nullable=true, unique=false)
     */
    protected ?string $productions = null;

    /**
     * @ORM\Column(name="registration_date", type="datetime")
     */
    protected DateTime $registrationDate;

    /**
     * @ORM\Column(name="expiration_date", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $expirationDate = null;

    /**
     * @ORM\Column(name="active", type="boolean")
     */
    protected bool $active;

    /**
     * @ORM\Column(name="openid", type="string", length=255, nullable=true, unique=false)
     */
    protected ?string $openid = null;

    /**
     * @ORM\Column(name="theme", type="string", length=255, nullable=true, unique=false)
     */
    protected ?string $theme = null;

    /**
     * @ORM\Column(name="hr_dept_id", type="smallint", nullable=true, unique=false)
     */
    protected ?int $hrDeptId = null;

    protected AccessUrl $currentUrl;

    /**
     * @var Collection<int, MessageTag>|MessageTag[]
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\MessageTag",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval="true"
     * )
     */
    protected Collection $messageTags;

    /**
     * @var Collection<int, Message>|Message[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Message",
     *     mappedBy="sender",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected Collection $sentMessages;

    /**
     * @var Collection<int, MessageRelUser>|MessageRelUser[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\MessageRelUser",
     *     mappedBy="receiver",
     *     cascade={"persist", "remove"}
     * )
     */
    protected Collection $receivedMessages;

    /**
     * @var Collection<int, CSurveyInvitation>|CSurveyInvitation[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CSurveyInvitation",
     *     mappedBy = "user",
     *     cascade={"remove"}
     * )
     */
    protected Collection $surveyInvitations;

    /**
     * @var Collection<int, TrackELogin>|TrackELogin[]
     *
     * @ORM\OneToMany(
     *     targetEntity="TrackELogin",
     *     mappedBy = "user",
     *     cascade={"remove"}
     * )
     */
    protected Collection $logins;

    /**
     * @ORM\OneToOne(targetEntity="Admin", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected ?Admin $admin = null;

    /**
     * @var null|NilUuid|UuidV4
     *
     * @ORM\Column(type="uuid", unique=true)
     */
    protected $uuid;

    // Property used only during installation.
    protected bool $skipResourceNode = false;

    public function __construct()
    {
        $this->skipResourceNode = false;
        $this->uuid = Uuid::v4();
        $this->apiToken = null;
        $this->biography = '';
        $this->website = '';
        $this->locale = 'en';
        $this->timezone = 'Europe\Paris';
        $this->authSource = 'platform';

        $this->status = CourseRelUser::STUDENT;
        $this->salt = sha1(uniqid('', true));
        $this->active = true;
        $this->enabled = true;
        $this->locked = false;
        $this->expired = false;

        $this->courses = new ArrayCollection();
        $this->classes = new ArrayCollection();
        $this->curriculumItems = new ArrayCollection();
        $this->portals = new ArrayCollection();
        $this->dropBoxSentFiles = new ArrayCollection();
        $this->dropBoxReceivedFiles = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->gradeBookCertificates = new ArrayCollection();
        $this->courseGroupsAsMember = new ArrayCollection();
        $this->courseGroupsAsTutor = new ArrayCollection();
        $this->resourceNodes = new ArrayCollection();
        $this->sessionRelCourseRelUsers = new ArrayCollection();
        $this->achievedSkills = new ArrayCollection();
        $this->commentedUserSkills = new ArrayCollection();
        $this->gradeBookCategories = new ArrayCollection();
        $this->gradeBookComments = new ArrayCollection();
        $this->gradeBookEvaluations = new ArrayCollection();
        $this->gradeBookLinks = new ArrayCollection();
        $this->gradeBookResults = new ArrayCollection();
        $this->gradeBookResultLogs = new ArrayCollection();
        $this->gradeBookScoreLogs = new ArrayCollection();
        $this->friends = new ArrayCollection();
        $this->friendsWithMe = new ArrayCollection();
        $this->gradeBookLinkEvalLogs = new ArrayCollection();
        $this->sequenceValues = new ArrayCollection();
        $this->trackEExerciseConfirmations = new ArrayCollection();
        $this->trackEAccessCompleteList = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->trackEAttempts = new ArrayCollection();
        $this->trackECourseAccess = new ArrayCollection();
        $this->userCourseCategories = new ArrayCollection();
        $this->userRelCourseVotes = new ArrayCollection();
        $this->userRelTags = new ArrayCollection();
        $this->personalAgendas = new ArrayCollection();
        $this->sessionsRelUser = new ArrayCollection();
        $this->sentMessages = new ArrayCollection();
        $this->receivedMessages = new ArrayCollection();
        $this->surveyInvitations = new ArrayCollection();
        $this->logins = new ArrayCollection();

        //$this->extraFields = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->registrationDate = new DateTime();

        $this->roles = [];
        $this->credentialsExpired = false;
        $this->credentialsExpireAt = new DateTime();
        $this->dateOfBirth = new DateTime();
        $this->expiresAt = new DateTime();
        $this->passwordRequestedAt = new DateTime();
    }

    public function __toString(): string
    {
        return $this->username;
    }

    public static function getPasswordConstraints(): array
    {
        return [
            new Assert\Length([
                'min' => 5,
            ]),
            // Alpha numeric + "_" or "-"
            new Assert\Regex(
                [
                    'pattern' => '/^[a-z\-_0-9]+$/i',
                    'htmlPattern' => '/^[a-z\-_0-9]+$/i',
                ]
            ),
            // Min 3 letters - not needed
            /*new Assert\Regex(array(
                    'pattern' => '/[a-z]{3}/i',
                    'htmlPattern' => '/[a-z]{3}/i')
                ),*/
            // Min 2 numbers
            new Assert\Regex(
                [
                    'pattern' => '/[0-9]{2}/',
                    'htmlPattern' => '/[0-9]{2}/',
                ]
            ),
        ];
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        //$metadata->addPropertyConstraint('firstname', new Assert\NotBlank());
        //$metadata->addPropertyConstraint('lastname', new Assert\NotBlank());
        //$metadata->addPropertyConstraint('email', new Assert\Email());
        /*
        $metadata->addPropertyConstraint('password',
            new Assert\Collection(self::getPasswordConstraints())
        );*/

        /*$metadata->addConstraint(new UniqueEntity(array(
            'fields'  => 'username',
            'message' => 'This value is already used.',
        )));*/

        /*$metadata->addPropertyConstraint(
            'username',
            new Assert\Length(array(
                'min'        => 2,
                'max'        => 50,
                'minMessage' => 'This value is too short. It should have {{ limit }} character or more.|This value is too short. It should have {{ limit }} characters or more.',
                'maxMessage' => 'This value is too long. It should have {{ limit }} character or less.|This value is too long. It should have {{ limit }} characters or less.',
            ))
        );*/
    }

    public function getUuid(): UuidV4
    {
        return $this->uuid;
    }

    public function setUuid(UuidV4 $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getResourceNode(): ?ResourceNode
    {
        return $this->resourceNode;
    }

    public function setResourceNode(ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    public function hasResourceNode(): bool
    {
        return $this->resourceNode instanceof ResourceNode;
    }

    public function getResourceNodes(): Collection
    {
        return $this->resourceNodes;
    }

    /**
     * @param Collection<int, ResourceNode>|ResourceNode[] $resourceNodes
     */
    public function setResourceNodes(Collection $resourceNodes): self
    {
        $this->resourceNodes = $resourceNodes;

        return $this;
    }

    public function getDropBoxSentFiles(): Collection
    {
        return $this->dropBoxSentFiles;
    }

    public function setDropBoxSentFiles(Collection $value): self
    {
        $this->dropBoxSentFiles = $value;

        return $this;
    }

    /*public function getDropBoxReceivedFiles()
    {
        return $this->dropBoxReceivedFiles;
    }

    public function setDropBoxReceivedFiles($value): void
    {
        $this->dropBoxReceivedFiles = $value;
    }*/

    public function getCourses(): Collection
    {
        return $this->courses;
    }

    /**
     * @param Collection<int, CourseRelUser>|CourseRelUser[] $courses
     */
    public function setCourses(Collection $courses): self
    {
        $this->courses = $courses;

        return $this;
    }

    public function setPortal(AccessUrlRelUser $portal): self
    {
        $this->portals->add($portal);

        return $this;
    }

    /*public function getCurriculumItems(): Collection
    {
        return $this->curriculumItems;
    }

    public function setCurriculumItems(array $items): self
    {
        $this->curriculumItems = $items;

        return $this;
    }*/

    public function getIsActive(): bool
    {
        return $this->active;
    }

    public function isEnabled(): bool
    {
        return $this->isActive();
    }

    public function setEnabled(bool $boolean): self
    {
        $this->enabled = $boolean;

        return $this;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(string $salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Returns the list of classes for the user.
     */
    public function getCompleteNameWithClasses(): string
    {
        $classSubscription = $this->getClasses();
        $classList = [];
        /** @var UsergroupRelUser $subscription */
        foreach ($classSubscription as $subscription) {
            $class = $subscription->getUsergroup();
            $classList[] = $class->getName();
        }
        $classString = empty($classList) ? null : ' ['.implode(', ', $classList).']';

        return UserManager::formatUserFullName($this).$classString;
    }

    public function getClasses(): Collection
    {
        return $this->classes;
    }

    /**
     * @param Collection<int, UsergroupRelUser>|UsergroupRelUser[] $classes
     */
    public function setClasses(Collection $classes): self
    {
        $this->classes = $classes;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getAuthSource(): ?string
    {
        return $this->authSource;
    }

    public function setAuthSource(string $authSource): self
    {
        $this->authSource = $authSource;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getOfficialCode(): ?string
    {
        return $this->officialCode;
    }

    public function setOfficialCode(?string $officialCode): self
    {
        $this->officialCode = $officialCode;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getCreatorId(): ?int
    {
        return $this->creatorId;
    }

    public function setCreatorId(int $creatorId): self
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    public function getCompetences(): ?string
    {
        return $this->competences;
    }

    public function setCompetences(?string $competences): self
    {
        $this->competences = $competences;

        return $this;
    }

    public function getDiplomas(): ?string
    {
        return $this->diplomas;
    }

    public function setDiplomas(?string $diplomas): self
    {
        $this->diplomas = $diplomas;

        return $this;
    }

    public function getOpenarea(): ?string
    {
        return $this->openarea;
    }

    public function setOpenarea(?string $openarea): self
    {
        $this->openarea = $openarea;

        return $this;
    }

    public function getTeach(): ?string
    {
        return $this->teach;
    }

    public function setTeach(?string $teach): self
    {
        $this->teach = $teach;

        return $this;
    }

    public function getProductions(): ?string
    {
        return $this->productions;
    }

    public function setProductions(?string $productions): self
    {
        $this->productions = $productions;

        return $this;
    }

    public function getRegistrationDate(): DateTime
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(DateTime $registrationDate): self
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    public function getExpirationDate(): ?DateTime
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?DateTime $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function isActive(): bool
    {
        return $this->getIsActive();
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getOpenid(): ?string
    {
        return $this->openid;
    }

    public function setOpenid(string $openid): self
    {
        $this->openid = $openid;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    public function getHrDeptId(): ?int
    {
        return $this->hrDeptId;
    }

    public function setHrDeptId(int $hrDeptId): self
    {
        $this->hrDeptId = $hrDeptId;

        return $this;
    }

    public function getMemberSince(): DateTime
    {
        return $this->registrationDate;
    }

    public function isOnline(): bool
    {
        return false;
    }

    public function getIdentifier(): int
    {
        return $this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIri(): ?string
    {
        if (null === $this->id) {
            return null;
        }

        return '/api/users/'.$this->getId();
    }

    public function getSlug(): string
    {
        return $this->getUsername();
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function setSlug(string $slug): self
    {
        return $this->setUsername($slug);
    }

    public function getLastLogin(): ?DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(DateTime $lastLogin = null): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(string $confirmationToken): self
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function isPasswordRequestNonExpired(int $ttl): bool
    {
        return $this->getPasswordRequestedAt() instanceof DateTime
            && $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }

    public function getPasswordRequestedAt(): ?DateTime
    {
        return $this->passwordRequestedAt;
    }

    public function setPasswordRequestedAt(DateTime $date = null): self
    {
        $this->passwordRequestedAt = $date;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $password): self
    {
        $this->plainPassword = $password;

        // forces the object to look "dirty" to Doctrine. Avoids
        // Doctrine *not* saving this entity, if only plainPassword changes
        $this->password = '';

        return $this;
    }

    /**
     * Returns the expiration date.
     */
    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTime $date): self
    {
        $this->expiresAt = $date;

        return $this;
    }

    /**
     * Returns the credentials expiration date.
     */
    public function getCredentialsExpireAt(): ?DateTime
    {
        return $this->credentialsExpireAt;
    }

    /**
     * Sets the credentials expiration date.
     */
    public function setCredentialsExpireAt(DateTime $date = null): self
    {
        $this->credentialsExpireAt = $date;

        return $this;
    }

    public function getFullname(): string
    {
        return sprintf('%s %s', $this->getFirstname(), $this->getLastname());
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function hasGroup(string $name): bool
    {
        return \in_array($name, $this->getGroupNames(), true);
    }

    public function getGroupNames(): array
    {
        $names = [];
        foreach ($this->getGroups() as $group) {
            $names[] = $group->getName();
        }

        return $names;
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    /**
     * Sets the user groups.
     */
    public function setGroups(Collection $groups): self
    {
        foreach ($groups as $group) {
            $this->addGroup($group);
        }

        return $this;
    }

    public function addGroup(Group $group): self
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    public function removeGroup(Group $group): self
    {
        if ($this->getGroups()->contains($group)) {
            $this->getGroups()->removeElement($group);
        }

        return $this;
    }

    public function isAccountNonExpired(): bool
    {
        /*if (true === $this->expired) {
            return false;
        }

        if (null !== $this->expiresAt && $this->expiresAt->getTimestamp() < time()) {
            return false;
        }*/

        return true;
    }

    public function isAccountNonLocked(): bool
    {
        return true;
        //return !$this->locked;
    }

    public function isCredentialsNonExpired(): bool
    {
        /*if (true === $this->credentialsExpired) {
            return false;
        }

        if (null !== $this->credentialsExpireAt && $this->credentialsExpireAt->getTimestamp() < time()) {
            return false;
        }*/

        return true;
    }

    public function getCredentialsExpired(): bool
    {
        return $this->credentialsExpired;
    }

    public function setCredentialsExpired(bool $boolean): self
    {
        $this->credentialsExpired = $boolean;

        return $this;
    }

    public function getExpired(): bool
    {
        return $this->expired;
    }

    /**
     * Sets this user to expired.
     */
    public function setExpired(bool $boolean): self
    {
        $this->expired = $boolean;

        return $this;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $boolean): self
    {
        $this->locked = $boolean;

        return $this;
    }

    /**
     * Check if the user has the skill.
     *
     * @param Skill $skill The skill
     */
    public function hasSkill(Skill $skill): bool
    {
        $achievedSkills = $this->getAchievedSkills();

        foreach ($achievedSkills as $userSkill) {
            if ($userSkill->getSkill()->getId() !== $skill->getId()) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function getAchievedSkills(): Collection
    {
        return $this->achievedSkills;
    }

    /**
     * @param Collection<int, SkillRelUser>|SkillRelUser[] $value
     */
    public function setAchievedSkills(Collection $value): self
    {
        $this->achievedSkills = $value;

        return $this;
    }

    public function isProfileCompleted(): ?bool
    {
        return $this->profileCompleted;
    }

    public function setProfileCompleted(?bool $profileCompleted): self
    {
        $this->profileCompleted = $profileCompleted;

        return $this;
    }

    public function getCurrentUrl(): AccessUrl
    {
        return $this->currentUrl;
    }

    /**
     * Sets the AccessUrl for the current user in memory.
     */
    public function setCurrentUrl(AccessUrl $url): self
    {
        $urlList = $this->getPortals();
        /** @var AccessUrlRelUser $item */
        foreach ($urlList as $item) {
            if ($item->getUrl()->getId() === $url->getId()) {
                $this->currentUrl = $url;

                break;
            }
        }

        return $this;
    }

    public function getPortals(): Collection
    {
        return $this->portals;
    }

    /**
     * @param AccessUrlRelUser[]|Collection<int, AccessUrlRelUser> $value
     */
    public function setPortals(Collection $value): void
    {
        $this->portals = $value;
    }

    public function getSessionsAsGeneralCoach(): array
    {
        return $this->getSessions(Session::SESSION_COACH);
    }

    public function getSessionsAsAdmin(): array
    {
        return $this->getSessions(Session::SESSION_ADMIN);
    }

    public function getCommentedUserSkills(): Collection
    {
        return $this->commentedUserSkills;
    }

    /**
     * @param Collection<int, SkillRelUserComment>|SkillRelUserComment[] $commentedUserSkills
     */
    public function setCommentedUserSkills(Collection $commentedUserSkills): self
    {
        $this->commentedUserSkills = $commentedUserSkills;

        return $this;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUserIdentifier()) {
            return false;
        }

        return true;
    }

    public function getSentMessages(): Collection
    {
        return $this->sentMessages;
    }

    public function getReceivedMessages(): Collection
    {
        return $this->receivedMessages;
    }

    public function getCourseGroupsAsMember(): Collection
    {
        return $this->courseGroupsAsMember;
    }

    public function getCourseGroupsAsTutor(): Collection
    {
        return $this->courseGroupsAsTutor;
    }

    public function getCourseGroupsAsMemberFromCourse(Course $course): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(
            Criteria::expr()->eq('cId', $course)
        );

        return $this->courseGroupsAsMember->matching($criteria);
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('ROLE_SUPER_ADMIN');
    }

    public function setRoleFromStatus(int $status): void
    {
        $role = match ($status) {
            COURSEMANAGER => 'ROLE_TEACHER',
            STUDENT => 'ROLE_STUDENT',
            DRH => 'ROLE_RRHH',
            SESSIONADMIN => 'ROLE_SESSION_MANAGER',
            STUDENT_BOSS => 'ROLE_STUDENT_BOSS',
            INVITEE => 'ROLE_INVITEE',
            default => 'ROLE_USER',
        };

        $this->addRole($role);
    }

    public function hasRole(string $role): bool
    {
        return \in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * Returns the user roles.
     *
     * @return array The roles
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        foreach ($this->getGroups() as $group) {
            $roles = array_merge($roles, $group->getRoles());
        }

        // we need to make sure to have at least one role
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    public function addRole(string $role): self
    {
        $role = strtoupper($role);
        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!\in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole(string $role): self
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function getUsernameCanonical(): string
    {
        return $this->usernameCanonical;
    }

    public function setUsernameCanonical(string $usernameCanonical): self
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    public function getEmailCanonical(): string
    {
        return $this->emailCanonical;
    }

    public function setEmailCanonical(string $emailCanonical): self
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(string $apiToken): self
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(string $biography): self
    {
        $this->biography = $biography;

        return $this;
    }

    public function getDateOfBirth(): ?DateTime
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(DateTime $dateOfBirth = null): self
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getProfileUrl(): string
    {
        return '/main/social/profile.php?u='.$this->id;
    }

    public function getIconStatus(): string
    {
        $hasCertificates = $this->getGradeBookCertificates()->count() > 0;
        $urlImg = '/img/';

        if ($this->isStudent()) {
            $iconStatus = $urlImg.'icons/svg/identifier_student.svg';
            if ($hasCertificates) {
                $iconStatus = $urlImg.'icons/svg/identifier_graduated.svg';
            }

            return $iconStatus;
        }

        if ($this->isTeacher()) {
            $iconStatus = $urlImg.'icons/svg/identifier_teacher.svg';
            if ($this->isAdmin()) {
                $iconStatus = $urlImg.'icons/svg/identifier_admin.svg';
            }

            return $iconStatus;
        }

        if ($this->isStudentBoss()) {
            return $urlImg.'icons/svg/identifier_teacher.svg';
        }

        return '';
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

    public function getPictureUri(): ?string
    {
        return $this->pictureUri;
    }

    public function getGradeBookCertificates(): Collection
    {
        return $this->gradeBookCertificates;
    }

    /**
     * @param Collection<int, GradebookCertificate>|GradebookCertificate[] $gradeBookCertificates
     */
    public function setGradeBookCertificates(Collection $gradeBookCertificates): self
    {
        $this->gradeBookCertificates = $gradeBookCertificates;

        return $this;
    }

    public function isStudent(): bool
    {
        return $this->hasRole('ROLE_STUDENT');
    }

    public function isStudentBoss(): bool
    {
        return $this->hasRole('ROLE_STUDENT_BOSS');
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('ROLE_TEACHER');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    /**
     * @return GradebookCategory[]|Collection
     */
    public function getGradeBookCategories()
    {
        return $this->gradeBookCategories;
    }

    /**
     * @return GradebookComment[]|Collection
     */
    public function getGradeBookComments()
    {
        return $this->gradeBookComments;
    }

    /**
     * @return GradebookEvaluation[]|Collection
     */
    public function getGradeBookEvaluations()
    {
        return $this->gradeBookEvaluations;
    }

    /**
     * @return GradebookLink[]|Collection
     */
    public function getGradeBookLinks()
    {
        return $this->gradeBookLinks;
    }

    /**
     * @return GradebookResult[]|Collection
     */
    public function getGradeBookResults()
    {
        return $this->gradeBookResults;
    }

    /**
     * @return GradebookResultLog[]|Collection
     */
    public function getGradeBookResultLogs()
    {
        return $this->gradeBookResultLogs;
    }

    /**
     * @return GradebookScoreLog[]|Collection
     */
    public function getGradeBookScoreLogs()
    {
        return $this->gradeBookScoreLogs;
    }

    /**
     * @return GradebookLinkevalLog[]|Collection
     */
    public function getGradeBookLinkEvalLogs()
    {
        return $this->gradeBookLinkEvalLogs;
    }

    /**
     * @return UserRelCourseVote[]|Collection
     */
    public function getUserRelCourseVotes()
    {
        return $this->userRelCourseVotes;
    }

    /**
     * @return UserRelTag[]|Collection
     */
    public function getUserRelTags()
    {
        return $this->userRelTags;
    }

    /**
     * @return PersonalAgenda[]|Collection
     */
    public function getPersonalAgendas()
    {
        return $this->personalAgendas;
    }

    /**
     * @return Collection|mixed[]
     */
    public function getCurriculumItems()
    {
        return $this->curriculumItems;
    }

    /**
     * @return UserRelUser[]|Collection
     */
    public function getFriends()
    {
        return $this->friends;
    }

    /**
     * @param int $relationType Example: UserRelUser::USER_RELATION_TYPE_BOSS
     *
     * @return UserRelUser[]|Collection
     */
    public function getFriendsByRelationType(int $relationType)
    {
        return $this->friends->filter(function (UserRelUser $userRelUser) use ($relationType) {
            return $relationType === $userRelUser->getRelationType();
        });
    }

    /**
     * @return UserRelUser[]|Collection
     */
    public function getFriendsWithMe()
    {
        return $this->friendsWithMe;
    }

    public function addFriend(self $friend): self
    {
        return $this->addUserRelUser($friend, UserRelUser::USER_RELATION_TYPE_FRIEND);
    }

    public function addUserRelUser(self $friend, int $relationType): self
    {
        $userRelUser = (new UserRelUser())
            ->setUser($this)
            ->setFriend($friend)
            ->setRelationType($relationType)
        ;
        $this->friends->add($userRelUser);

        return $this;
    }

    /**
     * @return Templates[]|Collection
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getDropBoxReceivedFiles()
    {
        return $this->dropBoxReceivedFiles;
    }

    /**
     * @return SequenceValue[]|Collection
     */
    public function getSequenceValues()
    {
        return $this->sequenceValues;
    }

    /**
     * @return TrackEExerciseConfirmation[]|Collection
     */
    public function getTrackEExerciseConfirmations()
    {
        return $this->trackEExerciseConfirmations;
    }

    /**
     * @return TrackEAttempt[]|Collection
     */
    public function getTrackEAccessCompleteList()
    {
        return $this->trackEAccessCompleteList;
    }

    /**
     * @return TrackEAttempt[]|Collection
     */
    public function getTrackEAttempts()
    {
        return $this->trackEAttempts;
    }

    /**
     * @return TrackECourseAccess[]|Collection
     */
    public function getTrackECourseAccess()
    {
        return $this->trackECourseAccess;
    }

    /**
     * @return UserCourseCategory[]|Collection
     */
    public function getUserCourseCategories()
    {
        return $this->userCourseCategories;
    }

    public function getCourseGroupsAsTutorFromCourse(Course $course): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(
            Criteria::expr()->eq('cId', $course->getId())
        );

        return $this->courseGroupsAsTutor->matching($criteria);
    }

    /**
     * Retrieves this user's related student sessions.
     *
     * @return Session[]
     */
    public function getStudentSessions(): array
    {
        return $this->getSessions(Session::STUDENT);
    }

    /**
     * @return SessionRelUser[]|Collection
     */
    public function getSessionsRelUser()
    {
        return $this->sessionsRelUser;
    }

    public function isSkipResourceNode(): bool
    {
        return $this->skipResourceNode;
    }

    public function setSkipResourceNode(bool $skipResourceNode): self
    {
        $this->skipResourceNode = $skipResourceNode;

        return $this;
    }

    /**
     * Retrieves this user's related sessions.
     *
     * @param int $relationType \Chamilo\CoreBundle\Entity\SessionRelUser::relationTypeList key
     *
     * @return Session[]
     */
    public function getSessions(int $relationType): array
    {
        $sessions = [];
        foreach ($this->getSessionsRelUser() as $sessionRelUser) {
            if ($sessionRelUser->getRelationType() === $relationType) {
                $sessions[] = $sessionRelUser->getSession();
            }
        }

        return $sessions;
    }

    /**
     * Retrieves this user's related DRH sessions.
     *
     * @return Session[]
     */
    public function getDRHSessions(): array
    {
        return $this->getSessions(Session::DRH);
    }

    /**
     * Get this user's related accessible sessions of a type, student by default.
     *
     * @param int $relationType \Chamilo\CoreBundle\Entity\SessionRelUser::relationTypeList key
     *
     * @return Session[]
     */
    public function getCurrentlyAccessibleSessions(int $relationType = Session::STUDENT): array
    {
        $sessions = [];
        foreach ($this->getSessions($relationType) as $session) {
            if ($session->isCurrentlyAccessible()) {
                $sessions[] = $session;
            }
        }

        return $sessions;
    }

    public function getResourceIdentifier(): int
    {
        return $this->id;
    }

    public function getResourceName(): string
    {
        return $this->getUsername();
    }

    public function setResourceName(string $name): void
    {
        $this->setUsername($name);
    }

    public function setParent(AbstractResource $parent): void
    {
    }

    public function getDefaultIllustration(int $size): string
    {
        $size = empty($size) ? 32 : $size;

        return sprintf('/img/icons/%s/unknown.png', $size);
    }

    public function getAdmin(): ?Admin
    {
        return $this->admin;
    }

    public function setAdmin(?Admin $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function addUserAsAdmin(): self
    {
        if (null === $this->admin) {
            $admin = new Admin();
            $admin->setUser($this);
            $this->setAdmin($admin);
            $this->addRole('ROLE_ADMIN');
        }

        return $this;
    }

    public function getSessionsByStatusInCourseSubscription(int $status): Collection
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('status', $status)
        );

        return $this
            ->getSessionRelCourseRelUsers()
            ->matching($criteria)
            ->map(fn(SessionRelCourseRelUser $sessionRelCourseRelUser) => $sessionRelCourseRelUser->getSession());
    }

    /**
     * @return SessionRelCourseRelUser[]|Collection
     */
    public function getSessionRelCourseRelUsers()
    {
        return $this->sessionRelCourseRelUsers;
    }

    /**
     * @param SessionRelCourseRelUser[]|Collection $sessionRelCourseRelUsers
     */
    public function setSessionRelCourseRelUsers($sessionRelCourseRelUsers): self
    {
        $this->sessionRelCourseRelUsers = $sessionRelCourseRelUsers;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return CSurveyInvitation[]|Collection
     */
    public function getSurveyInvitations()
    {
        return $this->surveyInvitations;
    }

    public function setSurveyInvitations(Collection $surveyInvitations): self
    {
        $this->surveyInvitations = $surveyInvitations;

        return $this;
    }

    /**
     * @return TrackELogin[]|Collection
     */
    public function getLogins()
    {
        return $this->logins;
    }

    public function setLogins(Collection $logins): self
    {
        $this->logins = $logins;

        return $this;
    }

    /**
     * @return MessageTag[]|Collection
     */
    public function getMessageTags()
    {
        return $this->messageTags;
    }

    /**
     * @param MessageTag[]|Collection $messageTags
     */
    public function setMessageTags($messageTags): self
    {
        $this->messageTags = $messageTags;

        return $this;
    }

    /**
     * @todo move in a repo
     * Find the largest sort value in a given UserCourseCategory
     * This method is used when we are moving a course to a different category
     * and also when a user subscribes to courses (the new course is added at the end of the main category).
     *
     * Used to be implemented in global function \api_max_sort_value.
     * Reimplemented using the ORM cache.
     *
     * @param null|UserCourseCategory $userCourseCategory the user_course_category
     */
    public function getMaxSortValue(?UserCourseCategory $userCourseCategory = null): int
    {
        $categoryCourses = $this->courses->matching(
            Criteria::create()
                ->where(Criteria::expr()->neq('relationType', COURSE_RELATION_TYPE_RRHH))
                ->andWhere(Criteria::expr()->eq('userCourseCat', $userCourseCategory))
        );

        return $categoryCourses->isEmpty()
            ? 0
            : max(
                $categoryCourses->map(
                    function ($courseRelUser) {
                        return $courseRelUser->getSort();
                    }
                )->toArray()
            );
    }
}
