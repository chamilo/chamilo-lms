<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      iri="http://schema.org/Person",
 *      normalizationContext={"groups"={"user:read"}},
 *      denormalizationContext={"groups"={"user:write"}},
 *      collectionOperations={"get"},
 *      itemOperations={
 *          "get"={},
 *          "put"={},
 *          "delete"={},
 *     }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"username": "partial", "firstname" : "partial"})
 * @ApiFilter(BooleanFilter::class, properties={"isActive"})
 *
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *  name="user",
 *  indexes={
 *      @ORM\Index(name="status", columns={"status"})
 *  }
 * )
 * @UniqueEntity("username")
 * @ORM\Entity
 */
class User implements UserInterface, EquatableInterface, ResourceInterface, ResourceIllustrationInterface
{
    use TimestampableEntity;

    public const ROLE_DEFAULT = 'ROLE_USER';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const COURSE_MANAGER = 1;
    public const TEACHER = 1;
    public const SESSION_ADMIN = 3;
    public const DRH = 4;
    public const STUDENT = 5;
    public const ANONYMOUS = 6;

    /**
     * @Groups({"user:read", "resource_node:read"})
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\Column(name="api_token", type="string", unique=true, nullable=true)
     */
    protected $apiToken;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"user:read", "user:write", "resource_node:read"})
     * @ORM\Column(name="firstname", type="string", length=64, nullable=true, unique=false)
     */
    protected $firstname;

    /**
     * @var string
     * @Groups({"user:read", "user:write", "resource_node:read"})
     * @ORM\Column(name="lastname", type="string", length=64, nullable=true, unique=false)
     */
    protected $lastname;

    /**
     * @var string
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="website", type="string", length=255, nullable=true)
     */
    protected $website;

    /**
     * @var string
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="biography", type="text", nullable=true)
     */
    protected $biography;

    /**
     * @var string
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="locale", type="string", length=8, nullable=true, unique=false)
     */
    protected $locale;

    /**
     * @var string
     * @Groups({"user:read", "user:write", "course:read", "resource_node:read"})
     * @Assert\NotBlank()
     * @ORM\Column(name="username", type="string", length=100, nullable=false, unique=true)
     */
    protected $username;

    /**
     * @var string|null
     */
    protected $plainPassword;

    /**
     * @var string
     * @ORM\Column(name="password", type="string", length=255, nullable=false, unique=false)
     */
    protected $password;

    /**
     * @var string
     *
     * @ORM\Column(name="username_canonical", type="string", length=180, nullable=false)
     */
    protected $usernameCanonical;

    /**
     * @var string
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="timezone", type="string", length=64)
     */
    protected $timezone;

    /**
     * @var string
     * @ORM\Column(name="email_canonical", type="string", length=100, nullable=false, unique=false)
     */
    protected $emailCanonical;

    /**
     * @var string
     * @var string
     * @Groups({"user:read", "user:write"})
     * @Assert\NotBlank()
     * @Assert\Email()
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false, unique=false)
     */
    protected $email;

    /**
     * @var bool
     *
     * @ORM\Column(name="locked", type="boolean")
     */
    protected $locked;

    /**
     * @var bool
     * @Assert\NotBlank()
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="enabled", type="boolean")
     */
    protected $enabled;

    /**
     * @var bool
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="expired", type="boolean")
     */
    protected $expired;

    /**
     * @var bool
     *
     * @ORM\Column(name="credentials_expired", type="boolean")
     */
    protected $credentialsExpired;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="credentials_expire_at", type="datetime", nullable=true, unique=false)
     */
    protected $credentialsExpireAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_birth", type="datetime", nullable=true)
     */
    protected $dateOfBirth;

    /**
     * @var \DateTime
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="expires_at", type="datetime", nullable=true, unique=false)
     */
    protected $expiresAt;

    /**
     * @var string
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="phone", type="string", length=64, nullable=true, unique=false)
     */
    protected $phone;

    /**
     * @var string
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="address", type="string", length=250, nullable=true, unique=false)
     */
    protected $address;

    /**
     * @var AccessUrl
     */
    protected $currentUrl;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $salt;

    /**
     * @var \DateTime
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="last_login", type="datetime", nullable=true, unique=false)
     */
    protected $lastLogin;

    /**
     * Random string sent to the user email address in order to verify it.
     *
     * @var string
     * @ORM\Column(name="confirmation_token", type="string", length=255, nullable=true)
     */
    protected $confirmationToken;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true, unique=false)
     */
    protected $passwordRequestedAt;

    /**
     * @var CourseRelUser[]|ArrayCollection
     *
     * @ApiSubresource()
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\CourseRelUser", mappedBy="user", orphanRemoval=true)
     */
    protected $courses;

    /**
     * @var UsergroupRelUser[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\UsergroupRelUser", mappedBy="user")
     */
    protected $classes;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CDropboxPost", mappedBy="user").
     */
    protected $dropBoxReceivedFiles;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CDropboxFile", mappedBy="userSent").
     */
    protected $dropBoxSentFiles;

    /**
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(type="array")
     */
    protected $roles;

    /**
     * @var bool
     *
     * @ORM\Column(name="profile_completed", type="boolean", nullable=true)
     */
    protected $profileCompleted;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\JuryMembers", mappedBy="user")
     */
    //protected $jurySubscriptions;

    /**
     * @var Group[]
     * @ORM\ManyToMany(targetEntity="Chamilo\CoreBundle\Entity\Group", inversedBy="users")
     * @ORM\JoinTable(
     *      name="fos_user_user_group",
     *      joinColumns={
     *          @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="cascade")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     *      }
     * )
     */
    protected $groups;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\CurriculumItemRelUser", mappedBy="user").
     */
    protected $curriculumItems;

    /**
     * @var AccessUrlRelUser[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrlRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $portals;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Session", mappedBy="generalCoach")
     */
    protected $sessionAsGeneralCoach;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\ResourceNode", cascade={"remove"}, orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="resource_node_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $resourceNode;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\ResourceNode", mappedBy="creator")
     */
    protected $resourceNodes;

    /**
     * @ApiSubresource()
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\SessionRelCourseRelUser",
     *     mappedBy="user",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    protected $sessionCourseSubscriptions;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\SkillRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $achievedSkills;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\SkillRelUserComment",
     *     mappedBy="feedbackGiver",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $commentedUserSkills;

    /**
     * @var ArrayCollection|GradebookCategory[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory", mappedBy="user")
     */
    protected $gradeBookCategories;

    /**
     * @var ArrayCollection|GradebookCertificate[]
     *
     * @ORM\OneToMany(
     *  targetEntity="GradebookCertificate", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected $gradeBookCertificates;

    /**
     * @var ArrayCollection|GradebookComment[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\GradebookComment", mappedBy="user")
     */
    protected $gradeBookComments;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="GradebookEvaluation", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected $gradeBookEvaluations;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="GradebookLink", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $gradeBookLinks;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="GradebookResult", mappedBy="user", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $gradeBookResults;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="GradebookResultLog", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected $gradeBookResultLogs;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="GradebookScoreLog", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected $gradeBookScoreLogs;

    /**
     * @var ArrayCollection|UserRelUser[]
     * @ORM\OneToMany(targetEntity="UserRelUser", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $userRelUsers;

    /**
     * @var ArrayCollection|GradebookLinkevalLog[]
     * @ORM\OneToMany(
     *     targetEntity="GradebookLinkevalLog",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $gradeBookLinkEvalLogs;

    /**
     * @var ArrayCollection|SequenceValue[]
     * @ORM\OneToMany(targetEntity="SequenceValue", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $sequenceValues;

    /**
     * @var ArrayCollection|TrackEExerciseConfirmation[]
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\TrackEExerciseConfirmation",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $trackEExerciseConfirmations;

    /**
     * @var ArrayCollection|TrackEAttempt[]
     * @ORM\OneToMany(
     *     targetEntity="TrackEAccessComplete", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected $trackEAccessCompleteList;

    /**
     * @var ArrayCollection|Templates[]
     * @ORM\OneToMany(targetEntity="Templates", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $templates;

    /**
     * @var ArrayCollection|TrackEAttempt[]
     * @ORM\OneToMany(targetEntity="TrackEAttempt", mappedBy="user", cascade={"persist", "remove"},orphanRemoval=true)
     */
    protected $trackEAttempts;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\TrackECourseAccess",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $trackECourseAccess;

    /**
     * @var ArrayCollection|UserCourseCategory[]
     *
     * @ORM\OneToMany(
     *     targetEntity="UserCourseCategory",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $userCourseCategories;

    /**
     * @var ArrayCollection|UserRelCourseVote[]
     * @ORM\OneToMany(targetEntity="UserRelCourseVote", mappedBy="user",cascade={"persist","remove"},orphanRemoval=true)
     */
    protected $userRelCourseVotes;

    /**
     * @var ArrayCollection|UserRelTag[]
     * @ORM\OneToMany(targetEntity="UserRelTag", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $userRelTags;

    /**
     * @var ArrayCollection|PersonalAgenda[]
     * @ORM\OneToMany(targetEntity="PersonalAgenda",mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $personalAgendas;

    /**
     * @var Session[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\SessionRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $sessions;

    /**
     * @var CGroupRelUser[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CGroupRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $courseGroupsAsMember;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CGroupRelTutor", mappedBy="user", orphanRemoval=true)
     */
    protected $courseGroupsAsTutor;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_source", type="string", length=50, nullable=true, unique=false)
     */
    protected $authSource;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected $status;

    /**
     * @var string
     *
     * @ORM\Column(name="official_code", type="string", length=40, nullable=true, unique=false)
     */
    protected $officialCode;

    /**
     * @var string
     *
     * @ORM\Column(name="picture_uri", type="string", length=250, nullable=true, unique=false)
     */
    protected $pictureUri;

    /**
     * @var int
     *
     * @ORM\Column(name="creator_id", type="integer", nullable=true, unique=false)
     */
    protected $creatorId;

    /**
     * @var string
     *
     * @ORM\Column(name="competences", type="text", nullable=true, unique=false)
     */
    protected $competences;

    /**
     * @var string
     *
     * @ORM\Column(name="diplomas", type="text", nullable=true, unique=false)
     */
    protected $diplomas;

    /**
     * @var string
     *
     * @ORM\Column(name="openarea", type="text", nullable=true, unique=false)
     */
    protected $openarea;

    /**
     * @var string
     *
     * @ORM\Column(name="teach", type="text", nullable=true, unique=false)
     */
    protected $teach;

    /**
     * @var string
     *
     * @ORM\Column(name="productions", type="string", length=250, nullable=true, unique=false)
     */
    protected $productions;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=40, nullable=true, unique=false)
     */
    protected $language;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registration_date", type="datetime", nullable=false, unique=false)
     */
    protected $registrationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration_date", type="datetime", nullable=true, unique=false)
     */
    protected $expirationDate;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false, unique=false)
     */
    protected $active;

    /**
     * @var string
     *
     * @ORM\Column(name="openid", type="string", length=255, nullable=true, unique=false)
     */
    protected $openid;

    /**
     * @var string
     *
     * @ORM\Column(name="theme", type="string", length=255, nullable=true, unique=false)
     */
    protected $theme;

    /**
     * @var int
     *
     * @ORM\Column(name="hr_dept_id", type="smallint", nullable=true, unique=false)
     */
    protected $hrDeptId;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\Message",
     *     mappedBy="userSender",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $sentMessages;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\Message",
     *     mappedBy="userReceiver",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $receivedMessages;

    /**
     * @var Admin
     *
     * @ORM\OneToOne(targetEntity="Admin", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $admin;

    /**
     * @ORM\Column(type="uuid", unique=true)
     */
    protected $uuid;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->apiToken = null;
        $this->status = self::STUDENT;
        $this->salt = sha1(uniqid(null, true));
        $this->active = true;
        $this->registrationDate = new \DateTime();
        $this->authSource = 'platform';
        $this->courses = new ArrayCollection();
        //$this->items = new ArrayCollection();
        $this->classes = new ArrayCollection();
        $this->curriculumItems = new ArrayCollection();
        $this->portals = new ArrayCollection();
        $this->dropBoxSentFiles = new ArrayCollection();
        $this->dropBoxReceivedFiles = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->gradeBookCertificates = new ArrayCollection();
        $this->courseGroupsAsMember = new ArrayCollection();
        $this->courseGroupsAsTutor = new ArrayCollection();
        //$this->extraFields = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();

        $this->enabled = false;
        $this->locked = false;
        $this->expired = false;
        $this->roles = [];
        $this->credentialsExpired = false;
    }

    /**
     * @return array
     */
    public static function getPasswordConstraints()
    {
        return
            [
                new Assert\Length(['min' => 5]),
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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
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

    public function __toString(): string
    {
        return (string) $this->username;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid(UuidV4 $uuid): User
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getResourceNode(): ResourceNode
    {
        return $this->resourceNode;
    }

    public function setResourceNode(ResourceNode $resourceNode): ResourceInterface
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    public function hasResourceNode(): bool
    {
        return $this->resourceNode instanceof ResourceNode;
    }

    /**
     * @return ArrayCollection|ResourceNode[]
     */
    public function getResourceNodes()
    {
        return $this->resourceNodes;
    }

    /**
     * @return User
     */
    public function setResourceNodes($resourceNodes)
    {
        $this->resourceNodes = $resourceNodes;

        return $this;
    }

    /**
     * @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        /*$user = $args->getEntity();
        */
    }

    /**
     * @return ArrayCollection
     */
    public function getDropBoxSentFiles()
    {
        return $this->dropBoxSentFiles;
    }

    /**
     * @param ArrayCollection $value
     */
    public function setDropBoxSentFiles($value)
    {
        $this->dropBoxSentFiles = $value;
    }

    /**
     * @return ArrayCollection
     */
    public function getDropBoxReceivedFiles()
    {
        return $this->dropBoxReceivedFiles;
    }

    /**
     * @param ArrayCollection $value
     */
    public function setDropBoxReceivedFiles($value)
    {
        $this->dropBoxReceivedFiles = $value;
    }

    public function getCourses()
    {
        return $this->courses;
    }

    /**
     * @param ArrayCollection $courses
     */
    public function setCourses($courses): self
    {
        $this->courses = $courses;

        return $this;
    }

    /**
     * @param $portal
     */
    public function setPortal($portal)
    {
        $this->portals->add($portal);
    }

    /**
     * @return ArrayCollection
     */
    public function getCurriculumItems()
    {
        return $this->curriculumItems;
    }

    public function setCurriculumItems(array $items): self
    {
        $this->curriculumItems = $items;

        return $this;
    }

    public function getIsActive(): bool
    {
        return true === $this->active;
    }

    public function isEnabled()
    {
        return $this->isActive();
    }

    /**
     * @param $boolean
     */
    public function setEnabled($boolean): self
    {
        $this->enabled = (bool) $boolean;

        return $this;
    }

    /**
     * Get salt.
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set salt.
     *
     * @param string $salt
     *
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    public function getLps()
    {
        //return $this->lps;
        /*$criteria = Criteria::create()
            ->where(Criteria::expr()->eq("id", "666"))
            //->orderBy(array("username" => "ASC"))
            //->setFirstResult(0)
            //->setMaxResults(20)
        ;
        $lps = $this->lps->matching($criteria);*/
        /*return $this->lps->filter(
            function($entry) use ($idsToFilter) {
                return $entry->getId() == 1;
        });*/
    }

    /**
     * Returns the list of classes for the user.
     *
     * @return string
     */
    public function getCompleteNameWithClasses()
    {
        $classSubscription = $this->getClasses();
        $classList = [];
        /** @var UsergroupRelUser $subscription */
        foreach ($classSubscription as $subscription) {
            $class = $subscription->getUsergroup();
            $classList[] = $class->getName();
        }
        $classString = !empty($classList) ? ' ['.implode(', ', $classList).']' : null;

        return \UserManager::formatUserFullName($this).$classString;
    }

    /**
     * @return ArrayCollection
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @param ArrayCollection $classes
     *
     * @return $this
     */
    public function setClasses($classes)
    {
        $this->classes = $classes;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get authSource.
     *
     * @return string
     */
    public function getAuthSource()
    {
        return $this->authSource;
    }

    /**
     * Set authSource.
     *
     * @param string $authSource
     *
     * @return User
     */
    public function setAuthSource($authSource)
    {
        $this->authSource = $authSource;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get officialCode.
     *
     * @return string
     */
    public function getOfficialCode()
    {
        return $this->officialCode;
    }

    /**
     * Set officialCode.
     *
     * @param string $officialCode
     *
     * @return User
     */
    public function setOfficialCode($officialCode)
    {
        $this->officialCode = $officialCode;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set phone.
     *
     * @param string $phone
     *
     * @return User
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set address.
     *
     * @param string $address
     *
     * @return User
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get creatorId.
     *
     * @return int
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * Set creatorId.
     *
     * @param int $creatorId
     *
     * @return User
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    /**
     * Get competences.
     *
     * @return string
     */
    public function getCompetences()
    {
        return $this->competences;
    }

    /**
     * Set competences.
     *
     * @param string $competences
     *
     * @return User
     */
    public function setCompetences($competences)
    {
        $this->competences = $competences;

        return $this;
    }

    /**
     * Get diplomas.
     *
     * @return string
     */
    public function getDiplomas()
    {
        return $this->diplomas;
    }

    /**
     * Set diplomas.
     *
     * @param string $diplomas
     *
     * @return User
     */
    public function setDiplomas($diplomas)
    {
        $this->diplomas = $diplomas;

        return $this;
    }

    /**
     * Get openarea.
     *
     * @return string
     */
    public function getOpenarea()
    {
        return $this->openarea;
    }

    /**
     * Set openarea.
     *
     * @param string $openarea
     *
     * @return User
     */
    public function setOpenarea($openarea)
    {
        $this->openarea = $openarea;

        return $this;
    }

    /**
     * Get teach.
     *
     * @return string
     */
    public function getTeach()
    {
        return $this->teach;
    }

    /**
     * Set teach.
     *
     * @param string $teach
     *
     * @return User
     */
    public function setTeach($teach)
    {
        $this->teach = $teach;

        return $this;
    }

    /**
     * Get productions.
     *
     * @return string
     */
    public function getProductions()
    {
        return $this->productions;
    }

    /**
     * Set productions.
     *
     * @param string $productions
     *
     * @return User
     */
    public function setProductions($productions)
    {
        $this->productions = $productions;

        return $this;
    }

    /**
     * Get language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set language.
     */
    public function setLanguage(string $language): User
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get registrationDate.
     *
     * @return \DateTime
     */
    public function getRegistrationDate()
    {
        return $this->registrationDate;
    }

    /**
     * Set registrationDate.
     *
     * @param \DateTime $registrationDate
     *
     * @return User
     */
    public function setRegistrationDate($registrationDate)
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    /**
     * Get expirationDate.
     *
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * Set expirationDate.
     *
     * @param \DateTime $expirationDate
     *
     * @return User
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * Get active.
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    public function isActive(): bool
    {
        return $this->getIsActive();
    }

    /**
     * Set active.
     *
     * @param bool $active
     *
     * @return User
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get openid.
     *
     * @return string
     */
    public function getOpenid()
    {
        return $this->openid;
    }

    /**
     * Set openid.
     *
     * @param string $openid
     *
     * @return User
     */
    public function setOpenid($openid)
    {
        $this->openid = $openid;

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
     * Set theme.
     *
     * @param string $theme
     *
     * @return User
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get hrDeptId.
     *
     * @return int
     */
    public function getHrDeptId()
    {
        return $this->hrDeptId;
    }

    /**
     * Set hrDeptId.
     *
     * @param int $hrDeptId
     *
     * @return User
     */
    public function setHrDeptId($hrDeptId)
    {
        $this->hrDeptId = $hrDeptId;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getMemberSince()
    {
        return $this->registrationDate;
    }

    /**
     * @return bool
     */
    public function isOnline()
    {
        return false;
    }

    /**
     * @return int
     */
    public function getIdentifier()
    {
        return $this->getId();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $userId
     */
    public function setId($userId)
    {
        $this->id = $userId;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->getUsername();
    }

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername($username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @param string $slug
     *
     * @return User
     */
    public function setSlug($slug)
    {
        return $this->setUsername($slug);
    }

    /**
     * Get lastLogin.
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set lastLogin.
     *
     * @param \DateTime $lastLogin
     */
    public function setLastLogin(\DateTime $lastLogin = null): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get sessionCourseSubscription.
     *
     * @return ArrayCollection
     */
    public function getSessionCourseSubscriptions()
    {
        return $this->sessionCourseSubscriptions;
    }

    public function setSessionCourseSubscriptions(array $value): self
    {
        $this->sessionCourseSubscriptions = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * @param string $confirmationToken
     */
    public function setConfirmationToken($confirmationToken): self
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function isPasswordRequestNonExpired($ttl)
    {
        return $this->getPasswordRequestedAt() instanceof \DateTime &&
            $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }

    /**
     * @return \DateTime
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    public function setPasswordRequestedAt(\DateTime $date = null)
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
        $this->password = null;

        return $this;
    }

    /**
     * Returns the expiration date.
     *
     * @return \DateTime|null
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $date): self
    {
        $this->expiresAt = $date;

        return $this;
    }

    /**
     * Returns the credentials expiration date.
     *
     * @return \DateTime
     */
    public function getCredentialsExpireAt()
    {
        return $this->credentialsExpireAt;
    }

    /**
     * Sets the credentials expiration date.
     */
    public function setCredentialsExpireAt(\DateTime $date = null): self
    {
        $this->credentialsExpireAt = $date;

        return $this;
    }

    public function getFullname(): string
    {
        return sprintf('%s %s', $this->getFirstname(), $this->getLastname());
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set firstname.
     *
     * @return User
     */
    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set lastname.
     *
     * @return User
     */
    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @param string $name
     */
    public function hasGroup($name): bool
    {
        return in_array($name, $this->getGroupNames());
    }

    public function getGroupNames(): array
    {
        $names = [];
        foreach ($this->getGroups() as $group) {
            $names[] = $group->getName();
        }

        return $names;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Sets the user groups.
     *
     * @param array $groups
     */
    public function setGroups($groups): self
    {
        foreach ($groups as $group) {
            $this->addGroup($group);
        }

        return $this;
    }

    public function addGroup($group): self
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    public function removeGroup($group): self
    {
        if ($this->getGroups()->contains($group)) {
            $this->getGroups()->removeElement($group);
        }

        return $this;
    }

    public function isAccountNonExpired()
    {
        /*if (true === $this->expired) {
            return false;
        }

        if (null !== $this->expiresAt && $this->expiresAt->getTimestamp() < time()) {
            return false;
        }*/

        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
        //return !$this->locked;
    }

    public function isCredentialsNonExpired()
    {
        /*if (true === $this->credentialsExpired) {
            return false;
        }

        if (null !== $this->credentialsExpireAt && $this->credentialsExpireAt->getTimestamp() < time()) {
            return false;
        }*/

        return true;
    }

    /**
     * @return bool
     */
    public function getCredentialsExpired()
    {
        return $this->credentialsExpired;
    }

    /**
     * @param bool $boolean
     */
    public function setCredentialsExpired($boolean): self
    {
        $this->credentialsExpired = $boolean;

        return $this;
    }

    /**
     * @return bool
     */
    public function getExpired()
    {
        return $this->expired;
    }

    /**
     * Sets this user to expired.
     *
     * @param bool $boolean
     */
    public function setExpired($boolean): self
    {
        $this->expired = (bool) $boolean;

        return $this;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param $boolean
     */
    public function setLocked($boolean): self
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
    }

    /**
     * Get achievedSkills.
     *
     * @return ArrayCollection
     */
    public function getAchievedSkills()
    {
        return $this->achievedSkills;
    }

    /**
     * @param string[] $value
     */
    public function setAchievedSkills(array $value): self
    {
        $this->achievedSkills = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isProfileCompleted()
    {
        return $this->profileCompleted;
    }

    public function setProfileCompleted($profileCompleted): self
    {
        $this->profileCompleted = $profileCompleted;

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

    /**
     * @return ArrayCollection
     */
    public function getPortals()
    {
        return $this->portals;
    }

    public function setPortals(array $value)
    {
        $this->portals = $value;
    }

    /**
     * Get sessionAsGeneralCoach.
     *
     * @return ArrayCollection
     */
    public function getSessionAsGeneralCoach()
    {
        return $this->sessionAsGeneralCoach;
    }

    /**
     * Get sessionAsGeneralCoach.
     *
     * @param ArrayCollection $value
     */
    public function setSessionAsGeneralCoach($value): self
    {
        $this->sessionAsGeneralCoach = $value;

        return $this;
    }

    public function getCommentedUserSkills()
    {
        return $this->commentedUserSkills;
    }

    /**
     * @return User
     */
    public function setCommentedUserSkills(array $commentedUserSkills): self
    {
        $this->commentedUserSkills = $commentedUserSkills;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEqualTo(UserInterface $user)
    {
        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    /**
     * Get sentMessages.
     *
     * @return ArrayCollection
     */
    public function getSentMessages()
    {
        return $this->sentMessages;
    }

    /**
     * Get receivedMessages.
     *
     * @return ArrayCollection
     */
    public function getReceivedMessages()
    {
        return $this->receivedMessages;
    }

    /**
     * @param int $lastId Optional. The ID of the last received message
     */
    public function getUnreadReceivedMessages($lastId = 0): ArrayCollection
    {
        $criteria = Criteria::create();
        $criteria->where(
            Criteria::expr()->eq('msgStatus', MESSAGE_STATUS_UNREAD)
        );

        if ($lastId > 0) {
            $criteria->andWhere(
                Criteria::expr()->gt('id', (int) $lastId)
            );
        }

        $criteria->orderBy(['sendDate' => Criteria::DESC]);

        return $this->receivedMessages->matching($criteria);
    }

    public function getCourseGroupsAsMember(): Collection
    {
        return $this->courseGroupsAsMember;
    }

    public function getCourseGroupsAsTutor(): Collection
    {
        return $this->courseGroupsAsTutor;
    }

    public function getCourseGroupsAsMemberFromCourse(Course $course): ArrayCollection
    {
        $criteria = Criteria::create();
        $criteria->where(
            Criteria::expr()->eq('cId', $course)
        );

        return $this->courseGroupsAsMember->matching($criteria);
    }

    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('ROLE_SUPER_ADMIN');
    }

    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * Returns the user roles.
     *
     * @return array The roles
     */
    public function getRoles()
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

    /**
     * @param string $role
     */
    public function addRole($role): self
    {
        $role = strtoupper($role);
        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function isUser(UserInterface $user = null)
    {
        return null !== $user && $this->getId() === $user->getId();
    }

    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function getUsernameCanonical()
    {
        return $this->usernameCanonical;
    }

    public function setUsernameCanonical($usernameCanonical)
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    public function getEmailCanonical()
    {
        return $this->emailCanonical;
    }

    public function setEmailCanonical($emailCanonical): self
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     *
     * @return User
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return User
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiToken()
    {
        return $this->apiToken;
    }

    /**
     * @param string $apiToken
     *
     * @return User
     */
    public function setApiToken($apiToken)
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

    /**
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * @param \DateTime $dateOfBirth
     */
    public function setDateOfBirth($dateOfBirth): self
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
        $status = $this->getStatus();
        $hasCertificates = $this->getGradeBookCertificates()->count() > 0;
        $urlImg = '/img/';
        $iconStatus = '';
        switch ($status) {
            case STUDENT:
                if ($hasCertificates) {
                    $iconStatus = $urlImg.'icons/svg/identifier_graduated.svg';
                } else {
                    $iconStatus = $urlImg.'icons/svg/identifier_student.svg';
                }
                break;
            case COURSEMANAGER:
                if ($this->isAdmin()) {
                    $iconStatus = $urlImg.'icons/svg/identifier_admin.svg';
                } else {
                    $iconStatus = $urlImg.'icons/svg/identifier_teacher.svg';
                }
                break;
            case STUDENT_BOSS:
                $iconStatus = $urlImg.'icons/svg/identifier_teacher.svg';
                break;
        }

        return $iconStatus;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return (int) $this->status;
    }

    /**
     * Set status.
     *
     * @return User
     */
    public function setStatus(int $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return GradebookCertificate[]|ArrayCollection
     */
    public function getGradeBookCertificates()
    {
        return $this->gradeBookCertificates;
    }

    /**
     * @param GradebookCertificate[]|ArrayCollection $gradeBookCertificates
     */
    public function setGradeBookCertificates($gradeBookCertificates): self
    {
        $this->gradeBookCertificates = $gradeBookCertificates;

        return $this;
    }

    public function isAdmin()
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function getCourseGroupsAsTutorFromCourse(Course $course): ArrayCollection
    {
        $criteria = Criteria::create();
        $criteria->where(
            Criteria::expr()->eq('cId', $course->getId())
        );

        return $this->courseGroupsAsTutor->matching($criteria);
    }

    /**
     * Retreives this user's related student sessions.
     *
     * @return Session[]
     */
    public function getStudentSessions()
    {
        return $this->getSessions(0);
    }

    /**
     * Retreives this user's related sessions.
     *
     * @param int $relationType \Chamilo\CoreBundle\Entity\SessionRelUser::relationTypeList key
     *
     * @return Session[]
     */
    public function getSessions($relationType)
    {
        $sessions = [];
        foreach ($this->sessions as $sessionRelUser) {
            if ($sessionRelUser->getRelationType() == $relationType) {
                $sessions[] = $sessionRelUser->getSession();
            }
        }

        return $sessions;
    }

    /**
     * Retreives this user's related DRH sessions.
     *
     * @return Session[]
     */
    public function getDRHSessions()
    {
        return $this->getSessions(1);
    }

    /**
     * Get this user's related accessible sessions of a type, student by default.
     *
     * @param int $relationType \Chamilo\CoreBundle\Entity\SessionRelUser::relationTypeList key
     *
     * @return Session[]
     */
    public function getCurrentlyAccessibleSessions($relationType = 0)
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

    public function setResourceName(string $name)
    {
        $this->setUsername($name);
    }

    public function setParent(AbstractResource $parent)
    {
    }

    public function getDefaultIllustration($size): string
    {
        $size = empty($size) ? 32 : (int) $size;

        return "/img/icons/$size/unknown.png";
    }

    /**
     * Find the largest sort value in a given UserCourseCategory
     * This method is used when we are moving a course to a different category
     * and also when a user subscribes to courses (the new course is added at the end of the main category).
     *
     * Used to be implemented in global function \api_max_sort_value.
     * Reimplemented using the ORM cache.
     *
     * @param UserCourseCategory|null $userCourseCategory the user_course_category
     *
     * @return int|mixed
     */
    public function getMaxSortValue($userCourseCategory = null)
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
                    /** @var CourseRelUser $courseRelUser */
                    function ($courseRelUser) {
                        return $courseRelUser->getSort();
                    }
                )->toArray()
            );
    }
}
