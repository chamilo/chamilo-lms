<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\Controller\Api\CreateUserOnAccessUrlAction;
use Chamilo\CoreBundle\Controller\Api\GetStatsAction;
use Chamilo\CoreBundle\Controller\Api\UserSkillsController;
use Chamilo\CoreBundle\Dto\CreateUserOnAccessUrlInput;
use Chamilo\CoreBundle\Entity\Listener\UserListener;
use Chamilo\CoreBundle\Filter\PartialSearchOrFilter;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Traits\UserCreatorTrait;
use Chamilo\CourseBundle\Entity\CGroupRelTutor;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use UserManager;

#[ApiResource(
    types: ['http://schema.org/Person'],
    operations: [
        new Get(
            openapi: new Operation(
                summary: 'Get details of one specific user, including name, e-mail and role.'
            ),
            security: "is_granted('VIEW', object)",
        ),
        new Get(
            uriTemplate: '/users/{id}/courses/{courseId}/stats/{metric}',
            requirements: [
                'id' => '\d+',
                'courseId' => '\d+',
                'metric' => 'avg-lp-progress|certificates|gradebook-global',
            ],
            controller: GetStatsAction::class,
            openapi: new Operation(
                summary: 'User-course statistics, switch by {metric}',
                parameters: [
                    new Parameter(
                        name: 'courseId',
                        in: 'path',
                        description: 'Course ID',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'metric',
                        in: 'path',
                        description: 'Metric selector',
                        required: true,
                        schema: [
                            'type' => 'string',
                            'enum' => ['avg-lp-progress', 'certificates', 'gradebook-global'],
                        ],
                    ),
                    new Parameter(
                        name: 'sessionId',
                        in: 'query',
                        description: 'Optional Session ID',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
            security: "is_granted('ROLE_USER')",
            output: false,
            read: false,
            name: 'stats_user_course_metric'
        ),
        new Put(security: "is_granted('EDIT', object)"),
        new Delete(security: "is_granted('DELETE', object)"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new GetCollection(
            uriTemplate: '/users/{id}/skills',
            controller: UserSkillsController::class,
            normalizationContext: ['groups' => ['user_skills:read']],
            name: 'get_user_skills'
        ),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    security: 'is_granted("ROLE_USER")'
)]
#[ApiResource(
    uriTemplate: '/access_urls/{id}/user',
    operations: [
        new Post(
            controller: CreateUserOnAccessUrlAction::class,
        ),
    ],
    uriVariables: [
        'id' => new Link(
            description: 'AccessUrl identifier',
        ),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    input: CreateUserOnAccessUrlInput::class,
    output: User::class,
    security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_SESSION_MANAGER')",
)]
#[ORM\Table(name: 'user')]
#[ORM\Index(columns: ['status'], name: 'status')]
#[UniqueEntity('username')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\EntityListeners([UserListener::class])]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'username' => 'partial',
        'firstname' => 'partial',
        'lastname' => 'partial',
    ]
)]
#[ApiFilter(PartialSearchOrFilter::class, properties: ['username', 'firstname', 'lastname'])]
#[ApiFilter(filterClass: BooleanFilter::class, properties: ['isActive'])]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['username', 'firstname', 'lastname'])]
class User implements UserInterface, EquatableInterface, ResourceInterface, ResourceIllustrationInterface, PasswordAuthenticatedUserInterface, LegacyPasswordAuthenticatedUserInterface, ExtraFieldItemInterface, Stringable
{
    use TimestampableEntity;
    use UserCreatorTrait;

    public const USERNAME_MAX_LENGTH = 100;
    public const ROLE_DEFAULT = 'ROLE_USER';
    public const ANONYMOUS = 6;

    /**
     * Global status for the fallback user.
     * This special status is used for a system user that acts as a placeholder
     * or fallback for content ownership when regular users are deleted.
     * This ensures data integrity and prevents orphaned content within the system.
     */
    public const ROLE_FALLBACK = 99;

    // User active field constants
    public const ACTIVE = 1;
    public const INACTIVE = 0;
    public const INACTIVE_AUTOMATIC = -1;
    public const SOFT_DELETED = -2;

    /**
     * Context roles must NEVER be persisted.
     * They are computed per-request from course/session/group context.
     *
     * @var string[]
     */
    private array $temporaryRoles = [];

    /**
     * List of all context roles used by the platform security layer.
     * These roles must not be stored in the DB.
     */
    public const CONTEXT_ROLES = [
        'ROLE_CURRENT_COURSE_TEACHER',
        'ROLE_CURRENT_COURSE_STUDENT',
        'ROLE_CURRENT_COURSE_GROUP_TEACHER',
        'ROLE_CURRENT_COURSE_GROUP_STUDENT',
        'ROLE_CURRENT_COURSE_SESSION_TEACHER',
        'ROLE_CURRENT_COURSE_SESSION_STUDENT',
    ];

    #[Groups(['user_json:read'])]
    #[ORM\OneToOne(targetEntity: ResourceNode::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'resource_node_id', onDelete: 'CASCADE')]
    public ?ResourceNode $resourceNode = null;

    /**
     * Resource illustration URL - Property set by ResourceNormalizer.php.
     */
    #[ApiProperty(iris: ['http://schema.org/contentUrl'])]
    #[Groups([
        'user_export',
        'user:read',
        'resource_node:read',
        'document:read',
        'media_object_read',
        'course:read',
        'course_rel_user:read',
        'user_json:read',
        'message:read',
        'user_rel_user:read',
        'social_post:read',
        'user_subscriptions:sessions',
    ])]
    public ?string $illustrationUrl = null;

    #[Groups([
        'user:read',
        'course:read',
        'resource_node:read',
        'user_json:read',
        'message:read',
        'user_rel_user:read',
        'session:item:read',
    ])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups([
        'user_export',
        'user:read',
        'user:write',
        'course:read',
        'course_rel_user:read',
        'resource_node:read',
        'user_json:read',
        'message:read',
        'page:read',
        'user_rel_user:read',
        'social_post:read',
        'track_e_exercise:read',
        'user_subscriptions:sessions',
        'student_publication_rel_user:read',
    ])]
    #[ORM\Column(name: 'username', type: 'string', length: 100, unique: true)]
    protected string $username;

    #[ORM\Column(name: 'api_token', type: 'string', unique: true, nullable: true)]
    protected ?string $apiToken = null;

    #[ApiProperty(iris: ['http://schema.org/name'])]
    #[Groups(['user:read', 'user:write', 'resource_node:read', 'user_json:read', 'track_e_exercise:read', 'user_rel_user:read', 'user_subscriptions:sessions', 'student_publication_rel_user:read'])]
    #[ORM\Column(name: 'firstname', type: 'string', length: 64, nullable: true)]
    protected ?string $firstname = null;

    #[Groups(['user:read', 'user:write', 'resource_node:read', 'user_json:read', 'track_e_exercise:read', 'user_rel_user:read', 'user_subscriptions:sessions', 'student_publication_rel_user:read'])]
    #[ORM\Column(name: 'lastname', type: 'string', length: 64, nullable: true)]
    protected ?string $lastname = null;

    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(name: 'website', type: 'string', length: 255, nullable: true)]
    protected ?string $website;

    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(name: 'biography', type: 'text', nullable: true)]
    protected ?string $biography;

    #[Groups(['user:read', 'user:write', 'user_json:read'])]
    #[ORM\Column(name: 'locale', type: 'string', length: 10)]
    protected string $locale;

    #[Groups(['user:write'])]
    protected ?string $plainPassword = null;

    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    protected string $password = '';

    #[ORM\Column(name: 'username_canonical', type: 'string', length: 180)]
    protected string $usernameCanonical;

    #[Groups(['user:read', 'user:write', 'user_json:read'])]
    #[ORM\Column(name: 'timezone', type: 'string', length: 64)]
    protected string $timezone;

    #[ORM\Column(name: 'email_canonical', type: 'string', length: 100)]
    protected string $emailCanonical;

    #[Groups(['user:read', 'user:write', 'user_json:read'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[ORM\Column(name: 'email', type: 'string', length: 100)]
    protected string $email;

    #[ORM\Column(name: 'locked', type: 'boolean')]
    protected bool $locked;

    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(name: 'expired', type: 'boolean')]
    protected bool $expired;

    #[ORM\Column(name: 'credentials_expired', type: 'boolean')]
    protected bool $credentialsExpired;

    #[ORM\Column(name: 'credentials_expire_at', type: 'datetime', nullable: true)]
    protected ?DateTime $credentialsExpireAt;

    #[ORM\Column(name: 'date_of_birth', type: 'datetime', nullable: true)]
    protected ?DateTime $dateOfBirth = null;

    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(name: 'expires_at', type: 'datetime', nullable: true)]
    protected ?DateTime $expiresAt;

    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(name: 'phone', type: 'string', length: 64, nullable: true)]
    protected ?string $phone = null;

    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(name: 'address', type: 'string', length: 250, nullable: true)]
    protected ?string $address = null;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $salt;

    #[ORM\Column(name: 'gender', type: 'string', length: 1, nullable: true)]
    protected ?string $gender = null;

    #[Groups(['user:read'])]
    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    protected ?DateTime $lastLogin = null;

    /**
     * Random string sent to the user email address in order to verify it.
     */
    #[ORM\Column(name: 'confirmation_token', type: 'string', length: 255, nullable: true)]
    protected ?string $confirmationToken = null;

    #[ORM\Column(name: 'password_requested_at', type: 'datetime', nullable: true)]
    protected ?DateTime $passwordRequestedAt;

    /**
     * @var Collection<int, CourseRelUser>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CourseRelUser::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $courses;

    /**
     * @var Collection<int, UsergroupRelUser>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UsergroupRelUser::class)]
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
     */
    #[Groups(['user:read', 'user:write', 'user_json:read'])]
    #[ORM\Column(type: 'array')]
    protected array $roles = [];

    #[ORM\Column(name: 'profile_completed', type: 'boolean', nullable: true)]
    protected ?bool $profileCompleted = null;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\JuryMembers", mappedBy="user").
     */
    // protected $jurySubscriptions;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\JoinTable(name: 'fos_user_user_group')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'group_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'users')]
    protected Collection $groups;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\CurriculumItemRelUser", mappedBy="user").
     */
    protected Collection $curriculumItems;

    /**
     * @var Collection<int, AccessUrlRelUser>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: AccessUrlRelUser::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $portals;

    /**
     * @var Collection<int, ResourceNode>
     */
    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: ResourceNode::class, cascade: ['persist', 'remove'])]
    protected Collection $resourceNodes;

    /**
     * @var Collection<int, SessionRelCourseRelUser>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: SessionRelCourseRelUser::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    protected Collection $sessionRelCourseRelUsers;

    /**
     * @var Collection<int, SessionRelUser>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: SessionRelUser::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $sessionsRelUser;

    /**
     * @var Collection<int, SkillRelUser>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: SkillRelUser::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $achievedSkills;

    /**
     * @var Collection<int, SkillRelUserComment>
     */
    #[ORM\OneToMany(
        mappedBy: 'feedbackGiver',
        targetEntity: SkillRelUserComment::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $commentedUserSkills;

    /**
     * @var Collection<int, GradebookCategory>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: GradebookCategory::class)]
    protected Collection $gradeBookCategories;

    /**
     * @var Collection<int, GradebookCertificate>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: GradebookCertificate::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $gradeBookCertificates;

    /**
     * @var Collection<int, GradebookComment>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: GradebookComment::class)]
    protected Collection $gradeBookComments;

    /**
     * @var Collection<int, GradebookResult>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: GradebookResult::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $gradeBookResults;

    /**
     * @var Collection<int, GradebookResultLog>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: GradebookResultLog::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $gradeBookResultLogs;

    /**
     * @var Collection<int, GradebookScoreLog>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: GradebookScoreLog::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $gradeBookScoreLogs;

    /**
     * @var Collection<int, UserRelUser>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: UserRelUser::class,
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    protected Collection $friends;

    /**
     * @var Collection<int, UserRelUser>
     */
    #[ORM\OneToMany(
        mappedBy: 'friend',
        targetEntity: UserRelUser::class,
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    protected Collection $friendsWithMe;

    /**
     * @var Collection<int, GradebookLinkevalLog>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: GradebookLinkevalLog::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $gradeBookLinkEvalLogs;

    /**
     * @var Collection<int, SequenceValue>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: SequenceValue::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $sequenceValues;

    /**
     * @var Collection<int, TrackEExerciseConfirmation>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: TrackEExerciseConfirmation::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $trackEExerciseConfirmations;

    /**
     * @var Collection<int, TrackEAttempt>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: TrackEAccessComplete::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true
    )]
    protected Collection $trackEAccessCompleteList;

    /**
     * @var Collection<int, Templates>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: Templates::class,
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    protected Collection $templates;

    /**
     * @var Collection<int, TrackEAttempt>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: TrackEAttempt::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $trackEAttempts;

    /**
     * @var Collection<int, TrackECourseAccess>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: TrackECourseAccess::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $trackECourseAccess;

    /**
     * @var Collection<int, UserCourseCategory>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: UserCourseCategory::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $userCourseCategories;

    /**
     * @var Collection<int, UserRelCourseVote>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: UserRelCourseVote::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $userRelCourseVotes;

    /**
     * @var Collection<int, UserRelTag>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: UserRelTag::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $userRelTags;

    /**
     * @var Collection<int, CGroupRelUser>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: CGroupRelUser::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $courseGroupsAsMember;

    /**
     * @var Collection<int, CGroupRelTutor>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CGroupRelTutor::class, orphanRemoval: true)]
    protected Collection $courseGroupsAsTutor;

    #[ORM\Column(name: 'status', type: 'integer')]
    protected int $status;

    #[ORM\Column(name: 'official_code', type: 'string', length: 40, nullable: true)]
    protected ?string $officialCode = null;

    #[ORM\Column(name: 'picture_uri', type: 'string', length: 250, nullable: true)]
    protected ?string $pictureUri = null;

    #[ORM\Column(name: 'creator_id', type: 'integer', unique: false, nullable: true)]
    protected ?int $creatorId = null;

    #[ORM\Column(name: 'competences', type: 'text', unique: false, nullable: true)]
    protected ?string $competences = null;

    #[ORM\Column(name: 'diplomas', type: 'text', unique: false, nullable: true)]
    protected ?string $diplomas = null;

    #[ORM\Column(name: 'openarea', type: 'text', unique: false, nullable: true)]
    protected ?string $openarea = null;

    #[ORM\Column(name: 'teach', type: 'text', unique: false, nullable: true)]
    protected ?string $teach = null;

    #[ORM\Column(name: 'productions', type: 'string', length: 250, unique: false, nullable: true)]
    protected ?string $productions = null;

    #[ORM\Column(name: 'expiration_date', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $expirationDate = null;

    #[Groups(['user:read', 'user_json:read'])]
    #[ORM\Column(name: 'active', type: 'integer')]
    protected int $active;

    #[ORM\Column(name: 'openid', type: 'string', length: 255, unique: false, nullable: true)]
    protected ?string $openid = null;

    #[ORM\Column(name: 'theme', type: 'string', length: 255, unique: false, nullable: true)]
    protected ?string $theme = null;

    #[ORM\Column(name: 'hr_dept_id', type: 'smallint', unique: false, nullable: true)]
    protected ?int $hrDeptId = null;

    #[Groups(['user:write'])]
    protected ?AccessUrl $currentUrl = null;

    /**
     * @var Collection<int, MessageTag>
     */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: MessageTag::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $messageTags;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(
        mappedBy: 'sender',
        targetEntity: Message::class,
        cascade: ['persist']
    )]
    protected Collection $sentMessages;

    /**
     * @var Collection<int, MessageRelUser>
     */
    #[ORM\OneToMany(mappedBy: 'receiver', targetEntity: MessageRelUser::class, cascade: ['persist', 'remove'])]
    protected Collection $receivedMessages;

    /**
     * @var Collection<int, CSurveyInvitation>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CSurveyInvitation::class, cascade: ['persist', 'remove'])]
    protected Collection $surveyInvitations;

    /**
     * @var Collection<int, TrackELogin>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: TrackELogin::class, cascade: ['persist', 'remove'])]
    protected Collection $logins;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Admin::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected ?Admin $admin = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    protected Uuid $uuid;

    // Property used only during installation.
    protected bool $skipResourceNode = false;

    #[Groups([
        'user:read',
        'user_json:read',
        'social_post:read',
        'course:read',
        'course_rel_user:read',
        'message:read',
        'user_subscriptions:sessions',
        'student_publication_rel_user:read',
        'student_publication:read',
        'student_publication_comment:read',
    ])]
    protected string $fullName;

    #[ORM\OneToMany(mappedBy: 'sender', targetEntity: SocialPost::class, orphanRemoval: true)]
    private Collection $sentSocialPosts;

    #[ORM\OneToMany(mappedBy: 'userReceiver', targetEntity: SocialPost::class)]
    private Collection $receivedSocialPosts;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: SocialPostFeedback::class, orphanRemoval: true)]
    private Collection $socialPostsFeedbacks;

    #[ORM\Column(name: 'mfa_enabled', type: 'boolean', options: ['default' => false])]
    protected bool $mfaEnabled = false;

    #[ORM\Column(name: 'mfa_service', type: 'string', length: 255, nullable: true)]
    protected ?string $mfaService = null;

    #[ORM\Column(name: 'mfa_secret', type: 'string', length: 255, nullable: true)]
    protected ?string $mfaSecret = null;

    #[ORM\Column(name: 'mfa_backup_codes', type: 'text', nullable: true)]
    protected ?string $mfaBackupCodes = null;

    #[ORM\Column(name: 'mfa_last_used', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $mfaLastUsed = null;

    /**
     * @var Collection<int, UserAuthSource>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserAuthSource::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $authSources;

    #[Groups(['user:read', 'user:write'])]
    #[ORM\Column(name: 'password_updated_at', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $passwordUpdatedAt = null;

    public function __construct()
    {
        $this->skipResourceNode = false;
        $this->uuid = Uuid::v4();
        $this->apiToken = null;
        $this->biography = '';
        $this->website = '';
        $this->locale = 'en_US';
        $this->timezone = 'Europe/Paris';
        $this->status = CourseRelUser::STUDENT;
        $this->salt = sha1(uniqid('', true));
        $this->active = 1;
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
        $this->gradeBookResults = new ArrayCollection();
        $this->gradeBookResultLogs = new ArrayCollection();
        $this->gradeBookScoreLogs = new ArrayCollection();
        $this->friends = new ArrayCollection();
        $this->friendsWithMe = new ArrayCollection();
        $this->gradeBookLinkEvalLogs = new ArrayCollection();
        $this->messageTags = new ArrayCollection();
        $this->sequenceValues = new ArrayCollection();
        $this->trackEExerciseConfirmations = new ArrayCollection();
        $this->trackEAccessCompleteList = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->trackEAttempts = new ArrayCollection();
        $this->trackECourseAccess = new ArrayCollection();
        $this->userCourseCategories = new ArrayCollection();
        $this->userRelCourseVotes = new ArrayCollection();
        $this->userRelTags = new ArrayCollection();
        $this->sessionsRelUser = new ArrayCollection();
        $this->sentMessages = new ArrayCollection();
        $this->receivedMessages = new ArrayCollection();
        $this->surveyInvitations = new ArrayCollection();
        $this->logins = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->roles = [];
        $this->credentialsExpired = false;
        $this->credentialsExpireAt = new DateTime();
        $this->dateOfBirth = null;
        $this->expiresAt = new DateTime();
        $this->passwordRequestedAt = new DateTime();
        $this->sentSocialPosts = new ArrayCollection();
        $this->receivedSocialPosts = new ArrayCollection();
        $this->socialPostsFeedbacks = new ArrayCollection();
        $this->authSources = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->username;
    }

    public static function getPasswordConstraints(): array
    {
        return [
            new Assert\Length(['min' => 5]),
            new Assert\Regex(['pattern' => '/^[a-z\-_0-9]+$/i', 'htmlPattern' => '/^[a-z\-_0-9]+$/i']),
            new Assert\Regex(['pattern' => '/[0-9]{2}/', 'htmlPattern' => '/[0-9]{2}/']),
        ];
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void {}

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): self
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

    public function addResourceNode(ResourceNode $resourceNode): static
    {
        if (!$this->resourceNodes->contains($resourceNode)) {
            $this->resourceNodes->add($resourceNode);
            $resourceNode->setCreator($this);
        }

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

    /**
     * @return Collection<int, CourseRelUser>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    /**
     * @param Collection<int, CourseRelUser> $courses
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

    /**
     * Get a bool on whether the user is active or not. Active can be "-1" which means pre-deleted, and is returned as false (not active).
     *
     * @return bool True if active = 1, false in any other case (0 = inactive, -1 = predeleted)
     */
    public function getIsActive(): bool
    {
        return self::ACTIVE === $this->active;
    }

    public function isSoftDeleted(): bool
    {
        return self::SOFT_DELETED === $this->active;
    }

    public function isEnabled(): bool
    {
        return $this->isActive();
    }

    /**
     * Returns the list of classes for the user.
     */
    public function getFullNameWithClasses(): string
    {
        $classSubscription = $this->getClasses();
        $classList = [];

        /** @var UsergroupRelUser $subscription */
        foreach ($classSubscription as $subscription) {
            $class = $subscription->getUsergroup();
            $classList[] = $class->getTitle();
        }
        $classString = empty($classList) ? null : ' ['.implode(', ', $classList).']';

        return UserManager::formatUserFullName($this).$classString;
    }

    public function getClasses(): Collection
    {
        return $this->classes;
    }

    /**
     * @param Collection<int, UsergroupRelUser> $classes
     */
    public function setClasses(Collection $classes): self
    {
        $this->classes = $classes;

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

    public function getExpirationDate(): ?DateTime
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?DateTime $expirationDate): self
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function isActive(): bool
    {
        return $this->getIsActive();
    }

    public function setActive(int $active): self
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

    public function setLastLogin(?DateTime $lastLogin = null): self
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
        return $this->getPasswordRequestedAt() instanceof DateTime && $this->getPasswordRequestedAt()->getTimestamp(
        ) + $ttl > time();
    }

    public function getPasswordRequestedAt(): ?DateTime
    {
        return $this->passwordRequestedAt;
    }

    public function setPasswordRequestedAt(?DateTime $date = null): self
    {
        $this->passwordRequestedAt = $date;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $password): self
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
    public function setCredentialsExpireAt(?DateTime $date = null): self
    {
        $this->credentialsExpireAt = $date;

        return $this;
    }

    public function getFullName(): string
    {
        if (empty($this->fullName)) {
            return \sprintf('%s %s', $this->getFirstname(), $this->getLastname());
        }

        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
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
            $names[] = $group->getTitle();
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
        return true;
    }

    public function isAccountNonLocked(): bool
    {
        return true;
    }

    public function isCredentialsNonExpired(): bool
    {
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

    /**
     * @return Collection<int, SkillRelUser>
     */
    public function getAchievedSkills(): Collection
    {
        return $this->achievedSkills;
    }

    /**
     * @param Collection<int, SkillRelUser> $value
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

    public function getCurrentUrl(): ?AccessUrl
    {
        return $this->currentUrl;
    }

    public function setCurrentUrl(AccessUrl $url): self
    {
        $accessUrlRelUser = (new AccessUrlRelUser())->setUrl($url)->setUser($this);
        $this->getPortals()->add($accessUrlRelUser);

        return $this;
    }

    /**
     * @return Collection<int, AccessUrlRelUser>
     */
    public function getPortals(): Collection
    {
        return $this->portals;
    }

    /**
     * @param Collection<int, AccessUrlRelUser> $value
     */
    public function setPortals(Collection $value): void
    {
        $this->portals = $value;
    }

    /**
     * @return array<int, Session>
     */
    public function getSessionsAsGeneralCoach(): array
    {
        return $this->getSessions(Session::GENERAL_COACH);
    }

    /**
     * Retrieves this user's related sessions.
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
     * @return Collection<int, SessionRelUser>
     */
    public function getSessionsRelUser(): Collection
    {
        return $this->sessionsRelUser;
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
     * @param Collection<int, SkillRelUserComment> $commentedUserSkills
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

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

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * @return Collection<int, Message>
     */
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
        $criteria->where(Criteria::expr()->eq('cId', $course));

        return $this->courseGroupsAsMember->matching($criteria);
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * Returns whether a user can be admin of all multi-URL portals in the case of a multi-URL install.
     */
    public function isSuperAdmin(): bool
    {
        // Treat "global admin" as super-admin
        return $this->hasRole('ROLE_GLOBAL_ADMIN');
    }

    public function hasRole(string $role): bool
    {
        return \in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * Returns the user roles (persisted + temporary + group roles).
     */
    public function getRoles(): array
    {
        // Never trust persisted roles to be free of context roles (backward compat safety).
        $persisted = array_map('strtoupper', $this->roles);
        $persisted = array_values(array_diff($persisted, self::CONTEXT_ROLES));

        $temporary = array_map('strtoupper', $this->temporaryRoles);

        $roles = array_merge($persisted, $temporary);

        foreach ($this->getGroups() as $group) {
            $roles = array_merge($roles, $group->getRoles());
        }

        // Ensure baseline role.
        $roles[] = 'ROLE_USER';

        $roles = array_map('strtoupper', $roles);

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): self
    {
        $this->roles = [];
        foreach ($roles as $role) {
            $this->addRole((string) $role);
        }

        return $this;
    }

    public function setRoleFromStatus(int $status): void
    {
        $role = self::getRoleFromStatus($status);
        $this->addRole($role);
    }

    public static function getRoleFromStatus(int $status): string
    {
        return match ($status) {
            COURSEMANAGER => 'ROLE_TEACHER',
            STUDENT => 'ROLE_STUDENT',
            DRH => 'ROLE_HR',
            SESSIONADMIN => 'ROLE_SESSION_MANAGER',
            STUDENT_BOSS => 'ROLE_STUDENT_BOSS',
            INVITEE => 'ROLE_INVITEE',
            default => 'ROLE_USER',
        };
    }

    public function addRole(string $role): self
    {
        $role = strtoupper(trim($role));

        if ('' === $role || self::ROLE_DEFAULT === $role || 'ROLE_USER' === $role) {
            return $this;
        }

        // Context roles must never be persisted.
        if (\in_array($role, self::CONTEXT_ROLES, true)) {
            return $this->addTemporaryRole($role);
        }

        if (!\in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole(string $role): self
    {
        $role = strtoupper(trim($role));

        // If it's a context role, remove it from temporary roles.
        if (\in_array($role, self::CONTEXT_ROLES, true)) {
            return $this->removeTemporaryRole($role);
        }

        if (false !== ($key = array_search($role, $this->roles, true))) {
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

    public function setDateOfBirth(?DateTime $dateOfBirth = null): self
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

    public function getGradeBookCertificates(): Collection
    {
        return $this->gradeBookCertificates;
    }

    /**
     * @param Collection<int, GradebookCertificate> $gradeBookCertificates
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

    public function isTeacher(): bool
    {
        return $this->hasRole('ROLE_TEACHER');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function isStudentBoss(): bool
    {
        return $this->hasRole('ROLE_STUDENT_BOSS');
    }

    public function isSessionAdmin(): bool
    {
        return $this->hasRole('ROLE_SESSION_MANAGER');
    }

    public function isInvitee(): bool
    {
        return $this->hasRole('ROLE_INVITEE');
    }

    public function isHRM(): bool
    {
        return $this->hasRole('ROLE_HR');
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

    /**
     * @return Collection<int, GradebookCategory>
     */
    public function getGradeBookCategories(): Collection
    {
        return $this->gradeBookCategories;
    }

    /**
     * @return Collection<int, GradebookComment>
     */
    public function getGradeBookComments(): Collection
    {
        return $this->gradeBookComments;
    }

    /**
     * @return Collection<int, GradebookResult>
     */
    public function getGradeBookResults(): Collection
    {
        return $this->gradeBookResults;
    }

    /**
     * @return Collection<int, GradebookResultLog>
     */
    public function getGradeBookResultLogs(): Collection
    {
        return $this->gradeBookResultLogs;
    }

    /**
     * @return Collection<int, GradebookScoreLog>
     */
    public function getGradeBookScoreLogs(): Collection
    {
        return $this->gradeBookScoreLogs;
    }

    /**
     * @return Collection<int, GradebookLinkevalLog>
     */
    public function getGradeBookLinkEvalLogs(): Collection
    {
        return $this->gradeBookLinkEvalLogs;
    }

    /**
     * @return Collection<int, UserRelCourseVote>
     */
    public function getUserRelCourseVotes(): Collection
    {
        return $this->userRelCourseVotes;
    }

    /**
     * @return Collection<int, UserRelTag>
     */
    public function getUserRelTags(): Collection
    {
        return $this->userRelTags;
    }

    public function getCurriculumItems(): Collection
    {
        return $this->curriculumItems;
    }

    /**
     * @return Collection<int, UserRelUser>
     */
    public function getFriends(): Collection
    {
        return $this->friends;
    }

    /**
     * @return Collection<int, UserRelUser>
     */
    public function getFriendsWithMe(): Collection
    {
        return $this->friendsWithMe;
    }

    public function addFriend(self $friend): self
    {
        return $this->addUserRelUser($friend, UserRelUser::USER_RELATION_TYPE_FRIEND);
    }

    public function addUserRelUser(self $friend, int $relationType): self
    {
        $userRelUser = (new UserRelUser())->setUser($this)->setFriend($friend)->setRelationType($relationType);
        $this->friends->add($userRelUser);

        return $this;
    }

    /**
     * @return Collection<int, Templates>
     */
    public function getTemplates(): Collection
    {
        return $this->templates;
    }

    public function getDropBoxReceivedFiles(): Collection
    {
        return $this->dropBoxReceivedFiles;
    }

    /**
     * @return Collection<int, SequenceValue>
     */
    public function getSequenceValues(): Collection
    {
        return $this->sequenceValues;
    }

    /**
     * @return Collection<int, TrackEExerciseConfirmation>
     */
    public function getTrackEExerciseConfirmations(): Collection
    {
        return $this->trackEExerciseConfirmations;
    }

    /**
     * @return Collection<int, TrackEAttempt>
     */
    public function getTrackEAccessCompleteList(): Collection
    {
        return $this->trackEAccessCompleteList;
    }

    /**
     * @return Collection<int, TrackEAttempt>
     */
    public function getTrackEAttempts(): Collection
    {
        return $this->trackEAttempts;
    }

    /**
     * @return Collection<int, TrackECourseAccess>
     */
    public function getTrackECourseAccess(): Collection
    {
        return $this->trackECourseAccess;
    }

    /**
     * @return Collection<int, UserCourseCategory>
     */
    public function getUserCourseCategories(): Collection
    {
        return $this->userCourseCategories;
    }

    public function getCourseGroupsAsTutorFromCourse(Course $course): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('cId', $course->getId()));

        return $this->courseGroupsAsTutor->matching($criteria);
    }

    /**
     * Retrieves this user's related student sessions.
     *
     * @return Session[]
     */
    public function getSessionsAsStudent(): array
    {
        return $this->getSessions(Session::STUDENT);
    }

    public function addSessionRelUser(SessionRelUser $sessionSubscription): static
    {
        $this->sessionsRelUser->add($sessionSubscription);

        return $this;
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

    public function setParent(AbstractResource $parent): void {}

    public function getDefaultIllustration(int $size): string
    {
        $size = empty($size) ? 32 : $size;

        return \sprintf('/img/icons/%s/unknown.png', $size);
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

    public function removeUserAsAdmin(): self
    {
        $this->admin->setUser(null);
        $this->admin = null;
        $this->removeRole('ROLE_ADMIN');

        return $this;
    }

    public function getSessionsByStatusInCourseSubscription(int $status): ReadableCollection
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('status', $status));

        /** @var ArrayCollection $subscriptions */
        $subscriptions = $this->getSessionRelCourseRelUsers();

        return $subscriptions->matching($criteria)->map(
            fn (SessionRelCourseRelUser $sessionRelCourseRelUser) => $sessionRelCourseRelUser->getSession()
        );
    }

    /**
     * @return Collection<int, SessionRelCourseRelUser>
     */
    public function getSessionRelCourseRelUsers(): Collection
    {
        return $this->sessionRelCourseRelUsers;
    }

    /**
     * @param Collection<int, SessionRelCourseRelUser> $sessionRelCourseRelUsers
     */
    public function setSessionRelCourseRelUsers(Collection $sessionRelCourseRelUsers): self
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
     * @return Collection<int, CSurveyInvitation>
     */
    public function getSurveyInvitations(): Collection
    {
        return $this->surveyInvitations;
    }

    public function setSurveyInvitations(Collection $surveyInvitations): self
    {
        $this->surveyInvitations = $surveyInvitations;

        return $this;
    }

    public function getLogin(): string
    {
        return $this->username;
    }

    public function setLogin(string $login): self
    {
        $this->username = $login;

        return $this;
    }

    /**
     * @return Collection<int, TrackELogin>
     */
    public function getLogins(): Collection
    {
        return $this->logins;
    }

    public function setLogins(Collection $logins): self
    {
        $this->logins = $logins;

        return $this;
    }

    /**
     * @return Collection<int, MessageTag>
     */
    public function getMessageTags(): Collection
    {
        return $this->messageTags;
    }

    /**
     * @param Collection<int, MessageTag> $messageTags
     */
    public function setMessageTags(Collection $messageTags): self
    {
        $this->messageTags = $messageTags;

        return $this;
    }

    /**
     * @param null|UserCourseCategory $userCourseCategory the user_course_category
     *
     * @todo move in a repo
     * Find the largest sort value in a given UserCourseCategory
     * This method is used when we are moving a course to a different category
     * and also when a user subscribes to courses (the new course is added at the end of the main category).
     *
     * Used to be implemented in global function \api_max_sort_value.
     * Reimplemented using the ORM cache.
     */
    public function getMaxSortValue(?UserCourseCategory $userCourseCategory = null): int
    {
        $categoryCourses = $this->courses->matching(
            Criteria::create()->where(Criteria::expr()->neq('relationType', COURSE_RELATION_TYPE_RRHH))->andWhere(
                Criteria::expr()->eq('userCourseCat', $userCourseCategory)
            )
        );

        return $categoryCourses->isEmpty() ? 0 : max(
            $categoryCourses->map(fn ($courseRelUser) => $courseRelUser->getSort())->toArray()
        );
    }

    public function hasFriendWithRelationType(self $friend, int $relationType): bool
    {
        $friends = $this->getFriendsByRelationType($relationType);

        return $friends->exists(fn (int $index, UserRelUser $userRelUser) => $userRelUser->getFriend() === $friend);
    }

    public function isFriendWithMeByRelationType(self $friend, int $relationType): bool
    {
        return $this
            ->getFriendsWithMeByRelationType($relationType)
            ->exists(fn (int $index, UserRelUser $userRelUser) => $userRelUser->getUser() === $friend)
        ;
    }

    /**
     * @param int $relationType Example: UserRelUser::USER_RELATION_TYPE_BOSS
     *
     * @return Collection<int, UserRelUser>
     */
    public function getFriendsByRelationType(int $relationType): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('relationType', $relationType));

        return $this->friends->matching($criteria);
    }

    public function getFriendsWithMeByRelationType(int $relationType): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('relationType', $relationType));

        return $this->friendsWithMe->matching($criteria);
    }

    public function getFriendsOfFriends(): array
    {
        $friendsOfFriends = [];
        foreach ($this->getFriends() as $friendRelation) {
            foreach ($friendRelation->getFriend()->getFriends() as $friendOfFriendRelation) {
                $friendsOfFriends[] = $friendOfFriendRelation->getFriend();
            }
        }

        return $friendsOfFriends;
    }

    /**
     * @return Collection<int, SocialPost>
     */
    public function getSentSocialPosts(): Collection
    {
        return $this->sentSocialPosts;
    }

    public function addSentSocialPost(SocialPost $sentSocialPost): self
    {
        if (!$this->sentSocialPosts->contains($sentSocialPost)) {
            $this->sentSocialPosts[] = $sentSocialPost;
            $sentSocialPost->setSender($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, SocialPost>
     */
    public function getReceivedSocialPosts(): Collection
    {
        return $this->receivedSocialPosts;
    }

    public function addReceivedSocialPost(SocialPost $receivedSocialPost): self
    {
        if (!$this->receivedSocialPosts->contains($receivedSocialPost)) {
            $this->receivedSocialPosts[] = $receivedSocialPost;
            $receivedSocialPost->setUserReceiver($this);
        }

        return $this;
    }

    public function getSocialPostFeedbackBySocialPost(SocialPost $post): ?SocialPostFeedback
    {
        $filtered = $this->getSocialPostsFeedbacks()->filter(
            fn (SocialPostFeedback $postFeedback) => $postFeedback->getSocialPost() === $post
        );
        if ($filtered->count() > 0) {
            return $filtered->first();
        }

        return null;
    }

    /**
     * @return Collection<int, SocialPostFeedback>
     */
    public function getSocialPostsFeedbacks(): Collection
    {
        return $this->socialPostsFeedbacks;
    }

    public function addSocialPostFeedback(SocialPostFeedback $socialPostFeedback): self
    {
        if (!$this->socialPostsFeedbacks->contains($socialPostFeedback)) {
            $this->socialPostsFeedbacks[] = $socialPostFeedback;
            $socialPostFeedback->setUser($this);
        }

        return $this;
    }

    public function getSubscriptionToSession(Session $session): ?SessionRelUser
    {
        $criteria = Criteria::create();
        $criteria->where(
            Criteria::expr()->eq('session', $session)
        );

        $match = $this->sessionsRelUser->matching($criteria);

        if ($match->count() > 0) {
            return $match->first();
        }

        return null;
    }

    public function getFirstAccessToSession(Session $session): ?TrackECourseAccess
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('sessionId', $session->getId())
            )
        ;

        $match = $this->trackECourseAccess->matching($criteria);

        return $match->count() > 0 ? $match->first() : null;
    }

    public function isCourseTutor(?Course $course = null, ?Session $session = null): bool
    {
        return $session?->hasCoachInCourseList($this) || $course?->getSubscriptionByUser($this)?->isTutor();
    }

    /**
     * @return Collection<int, UserAuthSource>
     */
    public function getAuthSources(): Collection
    {
        return $this->authSources;
    }

    /**
     * @return Collection<int, UserAuthSource>
     */
    public function getAuthSourcesByUrl(AccessUrl $url): Collection
    {
        $criteria = Criteria::create();
        $criteria->where(
            Criteria::expr()->eq('url', $url)
        );

        return $this->authSources->matching($criteria);
    }

    /**
     * @return array<int, string>
     */
    public function getAuthSourcesAuthentications(?AccessUrl $url = null): array
    {
        $authSources = $this->getAuthSourcesByUrl($url);

        if ($authSources->count() <= 0) {
            return [];
        }

        return $authSources->map(fn (UserAuthSource $authSource) => $authSource->getAuthentication())->toArray();
    }

    public function addAuthSource(UserAuthSource $authSource): static
    {
        if (!$this->authSources->contains($authSource)) {
            $this->authSources->add($authSource);
            $authSource->setUser($this);
        }

        return $this;
    }

    public function addAuthSourceByAuthentication(string $authentication, AccessUrl $url): static
    {
        $authSource = $this->getAuthSourceByAuthentication($authentication, $url);

        if (!$authSource) {
            $authSource = (new UserAuthSource())
                ->setAuthentication($authentication)
                ->setUrl($url)
            ;

            $this->addAuthSource($authSource);
        }

        return $this;
    }

    public function hasAuthSourceByAuthentication(string $authentication): bool
    {
        return $this->authSources->exists(
            fn ($key, $authSource) => $authSource instanceof UserAuthSource
                && $authSource->getAuthentication() === $authentication
        );
    }

    public function getAuthSourceByAuthentication(string $authentication, AccessUrl $accessUrl): ?UserAuthSource
    {
        return $this->authSources->findFirst(
            fn (int $index, UserAuthSource $authSource) => $authSource->getAuthentication() === $authentication
                && $authSource->getUrl()->getId() === $accessUrl->getId()
        );
    }

    public function removeAuthSources(): static
    {
        foreach ($this->authSources as $authSource) {
            $authSource->setUser(null);
        }

        $this->authSources = new ArrayCollection();

        return $this;
    }

    public function removeAuthSource(UserAuthSource $authSource): static
    {
        if ($this->authSources->removeElement($authSource)) {
            // set the owning side to null (unless already changed)
            if ($authSource->getUser() === $this) {
                $authSource->setUser(null);
            }
        }

        return $this;
    }

    public function getMfaEnabled(): bool
    {
        return $this->mfaEnabled;
    }

    public function setMfaEnabled(bool $mfaEnabled): self
    {
        $this->mfaEnabled = $mfaEnabled;

        return $this;
    }

    public function getMfaService(): ?string
    {
        return $this->mfaService;
    }

    public function setMfaService(?string $mfaService): self
    {
        $this->mfaService = $mfaService;

        return $this;
    }

    public function getMfaSecret(): ?string
    {
        return $this->mfaSecret;
    }

    public function setMfaSecret(?string $mfaSecret): self
    {
        $this->mfaSecret = $mfaSecret;

        return $this;
    }

    public function getMfaBackupCodes(): ?string
    {
        return $this->mfaBackupCodes;
    }

    public function setMfaBackupCodes(?string $mfaBackupCodes): self
    {
        $this->mfaBackupCodes = $mfaBackupCodes;

        return $this;
    }

    public function getMfaLastUsed(): ?DateTimeInterface
    {
        return $this->mfaLastUsed;
    }

    public function setMfaLastUsed(?DateTimeInterface $mfaLastUsed): self
    {
        $this->mfaLastUsed = $mfaLastUsed;

        return $this;
    }

    public function getPasswordUpdatedAt(): ?DateTimeInterface
    {
        return $this->passwordUpdatedAt;
    }

    /**
     * @return $this
     */
    public function setPasswordUpdatedAt(?DateTimeInterface $date): self
    {
        $this->passwordUpdatedAt = $date;

        return $this;
    }

    public function getFullNameWithUsername(): string
    {
        return $this->getFullName().' ('.$this->getUsername().')';
    }

    /**
     * Clears any context roles (temporary) and also removes them from persisted roles
     * for backward compatibility (in case they were stored by mistake in older versions).
     */
    public function resetContextRoles(): self
    {
        $this->clearTemporaryRoles();

        // Backward-compat: remove from persisted roles if they were stored by mistake.
        foreach (self::CONTEXT_ROLES as $role) {
            $this->removeRole($role);
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTemporaryRoles(): array
    {
        return $this->temporaryRoles;
    }

    public function addTemporaryRole(string $role): self
    {
        $role = strtoupper(trim($role));

        if ('' === $role || self::ROLE_DEFAULT === $role || 'ROLE_USER' === $role) {
            return $this;
        }

        if (!\in_array($role, $this->temporaryRoles, true)) {
            $this->temporaryRoles[] = $role;
        }

        return $this;
    }

    public function removeTemporaryRole(string $role): self
    {
        $role = strtoupper(trim($role));

        if (false !== ($key = array_search($role, $this->temporaryRoles, true))) {
            unset($this->temporaryRoles[$key]);
            $this->temporaryRoles = array_values($this->temporaryRoles);
        }

        return $this;
    }

    public function clearTemporaryRoles(): self
    {
        $this->temporaryRoles = [];

        return $this;
    }
}
