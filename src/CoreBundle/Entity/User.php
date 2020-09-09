<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
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
class User implements UserInterface, EquatableInterface
{
    public const ROLE_DEFAULT = 'ROLE_USER';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const COURSE_MANAGER = 1;
    public const TEACHER = 1;
    public const SESSION_ADMIN = 3;
    public const DRH = 4;
    public const STUDENT = 5;
    public const ANONYMOUS = 6;

    /**
     * @var int
     * @Groups({"user:read", "resource_node:read"})
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var UuidInterface|null
     *
     * @ORM\Column(type="uuid", unique=true)
     */
    protected $uuid;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
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
     * @ORM\Column(name="website", type="string", length=64, nullable=true)
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
     * @ApiSubresource()
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\CourseRelUser", mappedBy="user", orphanRemoval=true)
     */
    protected $courses;

    /**
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
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrlRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $portals;

    /**
     * @var Admin
     * @ORM\OneToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\Admin",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $admin;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\GradebookCertificate",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $gradebookCertificates;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\GradebookEvaluation",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $gradebookEvaluations;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\GradebookLink",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $gradebookLinks;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\GradebookResult",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $gradebookResults;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\GradebookResultLog",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $gradebookResultLogs;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\GradebookScoreLog",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $gradebookScoreLogs;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\SequenceValue",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $sequenceValues;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\TrackEExerciseConfirmation",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $trackEExerciseConfirmations;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\Templates",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $templates;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\TrackEAttempt",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
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
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\UserCourseCategory",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $userCourseCategorys;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\UserRelCourseVote",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $userRelCourseVotes;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\UserRelTag",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $userRelTags;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\GradebookLinkevalLog",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $gradebookLinkevalLogs;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\UserRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $userRelationships;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CAttendanceResult",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cAttendanceResults;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CAttendanceSheet",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cAttendanceSheets;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CBlogRating",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cBlogRatings;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CBlogRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cBlogRelUsers;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CBlogTaskRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cBlogTaskRelUsers;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CChatConnected",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cChatConnected;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CDropboxCategory",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cDropboxCategorys;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CDropboxPerson",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cDropboxPersons;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CForumMailcue",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cForumMailcues;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CForumNotification",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cForumNotifications;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CForumThreadQualify",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cForumThreadQualifys;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CForumThreadQualifyLog",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cForumThreadQualifyLogs;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CLpView",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cLpViews;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CNotebook",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cNotebooks;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\COnlineConnected",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cOnlineConnected;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CRoleUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cRoleUsers;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CStudentPublication",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cStudentPublications;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CStudentPublicationComment",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cStudentPublicationComments;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CStudentPublicationRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $CStudentPublicationRelUser;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CWiki",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cWiki;
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CWikiMailcue",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $cWikiMailcues;

    /**
     * @var TrackEAccessComplete
     * @ORM\OneToOne (
     *     targetEntity="Chamilo\CoreBundle\Entity\TrackEAccessComplete",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $trackEAccessComplete;

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
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory", mappedBy="user")
     */
    protected $gradeBookCategories;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\SessionRelUser",
     *     mappedBy="user",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $sessions;

    /**
     * @var Collection
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
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=true, unique=false)
     */
    protected $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="updated_at", type="datetime", nullable=true, unique=false)
     */
    protected $updatedAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4()->toString();
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
        //$this->extraFields = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();

        $this->enabled = false;
        $this->locked = false;
        $this->expired = false;
        $this->roles = [];
        $this->credentialsExpired = false;

        $this->courseGroupsAsMember = new ArrayCollection();
        $this->courseGroupsAsTutor = new ArrayCollection();
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

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->username;
    }

    public function setResourceNode(ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    public function getResourceNode(): ResourceNode
    {
        return $this->resourceNode;
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
     * @return ArrayCollection
     */
    public function getDropBoxReceivedFiles()
    {
        return $this->dropBoxReceivedFiles;
    }

    /**
     * @param ArrayCollection $value
     */
    public function setDropBoxSentFiles($value)
    {
        $this->dropBoxSentFiles = $value;
    }

    /**
     * @param ArrayCollection $value
     */
    public function setDropBoxReceivedFiles($value)
    {
        $this->dropBoxReceivedFiles = $value;
    }

    /**
     * @param ArrayCollection $courses
     */
    public function setCourses($courses)
    {
        $this->courses = $courses;
    }

    public function getCourses(): Collection
    {
        return $this->courses;
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
                        'htmlPattern' => '/^[a-z\-_0-9]+$/i', ]
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
                        'htmlPattern' => '/[0-9]{2}/', ]
                ),
            ]
            ;
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

    /**
     * @return ArrayCollection
     */
    public function getPortals()
    {
        return $this->portals;
    }

    /**
     * @param $portal
     */
    public function setPortal($portal)
    {
        $this->portals->add($portal);
    }

    /**
     * @param $value
     * @param (mixed|string|string[])[] $value
     */
    public function setPortals(array $value)
    {
        $this->portals = $value;
    }

    /**
     * @return ArrayCollection
     */
    public function getCurriculumItems()
    {
        return $this->curriculumItems;
    }

    /**
     * @param $items
     *
     * @return $this
     */
    public function setCurriculumItems(array $items)
    {
        $this->curriculumItems = $items;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsActive()
    {
        return 1 == $this->active;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getIsActive();
    }

    public function isEnabled()
    {
        return 1 == $this->getActive();
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
     * @param ArrayCollection $classes
     *
     * @return $this
     */
    public function setClasses($classes)
    {
        $this->classes = $classes;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getClasses()
    {
        return $this->classes;
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
     * Set firstname.
     *
     * @return User
     */
    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

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
     * Get authSource.
     *
     * @return string
     */
    public function getAuthSource()
    {
        return $this->authSource;
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
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
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
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return (int) $this->status;
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
     * Get officialCode.
     *
     * @return string
     */
    public function getOfficialCode()
    {
        return $this->officialCode;
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
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
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
     * Get address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
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
     * Get creatorId.
     *
     * @return int
     */
    public function getCreatorId()
    {
        return $this->creatorId;
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
     * Get competences.
     *
     * @return string
     */
    public function getCompetences()
    {
        return $this->competences;
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
     * Get diplomas.
     *
     * @return string
     */
    public function getDiplomas()
    {
        return $this->diplomas;
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
     * Get openarea.
     *
     * @return string
     */
    public function getOpenarea()
    {
        return $this->openarea;
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
     * Get teach.
     *
     * @return string
     */
    public function getTeach()
    {
        return $this->teach;
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
     * Get productions.
     *
     * @return string
     */
    public function getProductions()
    {
        return $this->productions;
    }

    /**
     * Set language.
     *
     * @param string $language
     *
     * @return User
     */
    public function setLanguage($language)
    {
        $this->language = $language;

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
     * Get registrationDate.
     *
     * @return \DateTime
     */
    public function getRegistrationDate()
    {
        return $this->registrationDate;
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
     * Get expirationDate.
     *
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
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
     * Get active.
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
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
     * Get openid.
     *
     * @return string
     */
    public function getOpenid()
    {
        return $this->openid;
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
     * Get theme.
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
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
     * Get hrDeptId.
     *
     * @return int
     */
    public function getHrDeptId()
    {
        return $this->hrDeptId;
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
     * @return string
     */
    public function getSlug()
    {
        return $this->getUsername();
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

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function setUsernameCanonical($usernameCanonical)
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    public function setEmailCanonical($emailCanonical)
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    /**
     * Set lastLogin.
     *
     * @param \DateTime $lastLogin
     *
     * @return User
     */
    public function setLastLogin(\DateTime $lastLogin = null)
    {
        $this->lastLogin = $lastLogin;

        return $this;
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
     * Get sessionCourseSubscription.
     *
     * @return ArrayCollection
     */
    public function getSessionCourseSubscriptions()
    {
        return $this->sessionCourseSubscriptions;
    }

    /**
     * @param $value
     * @param string[][] $value
     *
     * @return $this
     */
    public function setSessionCourseSubscriptions(array $value)
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
     *
     * @return User
     */
    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    /*public function isPasswordRequestNonExpired($ttl)
    {
        return $this->getPasswordRequestedAt() instanceof \DateTime &&
            $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }*/

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $password)
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
     *
     * @return User
     */
    public function setCredentialsExpireAt(\DateTime $date = null)
    {
        $this->credentialsExpireAt = $date;

        return $this;
    }

    public function addGroup($group)
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    /**
     * Sets the user groups.
     *
     * @param array $groups
     *
     * @return User
     */
    public function setGroups($groups)
    {
        foreach ($groups as $group) {
            $this->addGroup($group);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getFullname()
    {
        return sprintf('%s %s', $this->getFirstname(), $this->getLastname());
    }

    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return array
     */
    public function getGroupNames()
    {
        $names = [];
        foreach ($this->getGroups() as $group) {
            $names[] = $group->getName();
        }

        return $names;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasGroup($name)
    {
        return in_array($name, $this->getGroupNames());
    }

    public function removeGroup($group)
    {
        if ($this->getGroups()->contains($group)) {
            $this->getGroups()->removeElement($group);
        }

        return $this;
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function addRole($role)
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
     *
     * @return User
     */
    public function setCredentialsExpired($boolean)
    {
        $this->credentialsExpired = $boolean;

        return $this;
    }

    /**
     * @param $boolean
     *
     * @return $this
     */
    public function setEnabled($boolean)
    {
        $this->enabled = (bool) $boolean;

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
     *
     * @return User
     */
    public function setExpired($boolean)
    {
        $this->expired = (bool) $boolean;

        return $this;
    }

    /**
     * @return User
     */
    public function setExpiresAt(\DateTime $date)
    {
        $this->expiresAt = $date;

        return $this;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param $boolean
     *
     * @return $this
     */
    public function setLocked($boolean)
    {
        $this->locked = $boolean;

        return $this;
    }

    /**
     * @return $this
     */
    public function setRoles(array $roles)
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
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
     * @param $value
     * @param string[] $value
     *
     * @return $this
     */
    public function setAchievedSkills(array $value)
    {
        $this->achievedSkills = $value;

        return $this;
    }

    /**
     * Check if the user has the skill.
     *
     * @param Skill $skill The skill
     *
     * @return bool
     */
    public function hasSkill(Skill $skill)
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
     * @return bool
     */
    public function isProfileCompleted()
    {
        return $this->profileCompleted;
    }

    /**
     * @return User
     */
    public function setProfileCompleted($profileCompleted)
    {
        $this->profileCompleted = $profileCompleted;

        return $this;
    }

    /**
     * Sets the AccessUrl for the current user in memory.
     *
     * @return $this
     */
    public function setCurrentUrl(AccessUrl $url)
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
     * @return AccessUrl
     */
    public function getCurrentUrl()
    {
        return $this->currentUrl;
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
     *
     * @return $this
     */
    public function setSessionAsGeneralCoach($value)
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
    public function setCommentedUserSkills(array $commentedUserSkills)
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

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('ROLE_SUPER_ADMIN');
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

    public function getEmailCanonical()
    {
        return $this->emailCanonical;
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
    public function getTimezone()
    {
        return $this->timezone;
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
    public function getLocale()
    {
        return $this->locale;
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
     * @param \DateTime $dateOfBirth
     *
     * @return User
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
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
     * Retreives this user's related student sessions.
     *
     * @return Session[]
     */
    public function getStudentSessions()
    {
        return $this->getSessions(0);
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

    /**
     * Get the Admin for the current user.
     *
     */
    public function getAdmin(): Admin
    {
        return $this->admin;
    }

    /**
     * Sets the Admin for the current user.
     *
     */
    public function setAdmin(Admin $admin): User
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Sets the Gradebook Certificate's for the current user.
     *
     */
    public function getGradebookCertificates(): ArrayCollection
    {
        return $this->gradebookCertificates;
    }

    /**
     * Sets the Gradebook Certificate's for the current user.
     *
     */
    public function setGradebookCertificates(ArrayCollection $gradebookCertificates): User
    {
        $this->gradebookCertificates = $gradebookCertificates;

        return $this;
    }

    /**
     * Get the Gradebook Certificate's for the current user.
     *
     */
    public function getGradebookEvaluations(): ArrayCollection
    {
        return $this->gradebookEvaluations;
    }

    /**
     * Sets the Gradebook Evaluation's for the current user.
     *
     */
    public function setGradebookEvaluations(ArrayCollection $gradebookEvaluations): User
    {
        $this->gradebookEvaluations = $gradebookEvaluations;

        return $this;
    }

    /**
     * Get the Gradebook Evaluation's for the current user.
     *
     */
    public function getGradebookLinks(): ArrayCollection
    {
        return $this->gradebookLinks;
    }

    /**
     * Sets the Gradebook Link's for the current user.
     *
     */
    public function setGradebookLinks(ArrayCollection $gradebookLinks): User
    {
        $this->gradebookLinks = $gradebookLinks;

        return $this;
    }

    /**
     * Get the Gradebook Result's for the current user.
     *
     */
    public function getGradebookResults(): ArrayCollection
    {
        return $this->gradebookResults;
    }

    /**
     * Sets the Gradebook Result's for the current user.
     *
     */
    public function setGradebookResults(ArrayCollection $gradebookResults): User
    {
        $this->gradebookResults = $gradebookResults;

        return $this;
    }

    /**
     * Get the Gradebook Result Log's for the current user.
     *
     */
    public function getGradebookResultLogs(): ArrayCollection
    {
        return $this->gradebookResultLogs;
    }

    /**
     * Sets the Gradebook Result Log's for the current user.
     *
     */
    public function setGradebookResultLogs(ArrayCollection $gradebookResultLogs): User
    {
        $this->gradebookResultLogs = $gradebookResultLogs;

        return $this;
    }

    /**
     * Get the Gradebook Score Log's for the current user.
     *
     */
    public function getGradebookScoreLogs(): ArrayCollection
    {
        return $this->gradebookScoreLogs;
    }

    /**
     * Sets the Gradebook Score Log's for the current user.
     *
     */
    public function setGradebookScoreLogs(ArrayCollection $gradebookScoreLogs): User
    {
        $this->gradebookScoreLogs = $gradebookScoreLogs;

        return $this;
    }

    /**
     * Get the Sequence Value's for the current user.
     *
     */
    public function getSequenceValues(): ArrayCollection
    {
        return $this->sequenceValues;
    }

    /**
     * Sets the Sequence Value's for the current user.
     *
     */
    public function setSequenceValues(ArrayCollection $sequenceValues): User
    {
        $this->sequenceValues = $sequenceValues;

        return $this;
    }

    /**
     * Get the Templates's for the current user.
     *
     */
    public function getTemplates(): ArrayCollection
    {
        return $this->templates;
    }

    /**
     * Sets the Templates's for the current user.
     *
     */
    public function setTemplates(ArrayCollection $templates): User
    {
        $this->templates = $templates;

        return $this;
    }

    /**
     * Get the Track E Attemp's for the current user.
     *
     */
    public function getTrackEAttempts(): ArrayCollection
    {
        return $this->trackEAttempts;
    }

    /**
     * Sets the Track E Attemp's for the current user.
     *
     */
    public function setTrackEAttempts(ArrayCollection $trackEAttempts): User
    {
        $this->trackEAttempts = $trackEAttempts;
        return $this;
    }

    /**
     * Get the Track E CourseA ccess 's for the current user.
     *
     */
    public function getTrackECourseAccess(): ArrayCollection
    {
        return $this->trackECourseAccess;
    }

    /**
     * Sets the Track E CourseA ccess 's for the current user.
     *
     */
    public function setTrackECourseAccess(ArrayCollection $trackECourseAccess): User
    {
        $this->trackECourseAccess = $trackECourseAccess;

        return $this;
    }

    /**
     * Get the User Course Category's for the current user.
     *
     */
    public function getUserCourseCategorys(): ArrayCollection
    {
        return $this->userCourseCategorys;
    }

    /**
     * Sets the User Course Category's for the current user.
     *
     */
    public function setUserCourseCategorys(ArrayCollection $userCourseCategorys): User
    {
        $this->userCourseCategorys = $userCourseCategorys;

        return $this;
    }

    /**
     * Get the User Rel Course Vote's for the current user.
     *
     */
    public function getUserRelCourseVotes(): ArrayCollection
    {
        return $this->userRelCourseVotes;
    }

    /**
     * Sets the User Rel Course Vote's for the current user.
     *
     */
    public function setUserRelCourseVotes(ArrayCollection $userRelCourseVotes): User
    {
        $this->userRelCourseVotes = $userRelCourseVotes;

        return $this;
    }

    /**
     * Get the User Rel Tag's for the current user.
     *
     */
    public function getUserRelTags(): ArrayCollection
    {
        return $this->userRelTags;
    }

    /**
     * Sets the User Rel Tag's for the current user.
     *
     */
    public function setUserRelTags(ArrayCollection $userRelTags): User
    {
        $this->userRelTags = $userRelTags;

        return $this;
    }

    /**
     * Get the User Rel User's for the current user.
     *
     */
    public function getUserRelationships(): ArrayCollection
    {
        return $this->userRelationships;
    }

    /**
     * Sets the User Rel User's for the current user.
     *
     */
    public function setUserRelationships(ArrayCollection $userRelationships): User
    {
        $this->userRelationships = $userRelationships;

        return $this;
    }

    /**
     * Get the Track E Exercise Confirmation's for the current user.
     *
     */
    public function getTrackEExerciseConfirmations(): ArrayCollection
    {
        return $this->trackEExerciseConfirmations;
    }

    /**
     * Sets the Track E Exercise Confirmation's for the current user.
     *
     */
    public function setTrackEExerciseConfirmations(ArrayCollection $trackEExerciseConfirmations): User
    {
        $this->trackEExerciseConfirmations = $trackEExerciseConfirmations;

        return $this;
    }

    /**
     * Get the Gradebook Linkeval Log's for the current user.
     *
     */
    public function getGradebookLinkevalLogs(): ArrayCollection
    {
        return $this->gradebookLinkevalLogs;
    }

    /**
     * Sets the Gradebook Linkeval Log's for the current user.
     *
     */
    public function setGradebookLinkevalLogs(ArrayCollection $gradebookLinkevalLogs): User
    {
        $this->gradebookLinkevalLogs = $gradebookLinkevalLogs;
        return $this;
    }

    /**
     * Get the Course Attendance Result's for the current user.
     *
     */
    public function getCAttendanceResults(): ArrayCollection
    {
        return $this->cAttendanceResults;
    }

    /**
     * Sets the Course Attendance Result's for the current user.
     *
     */
    public function setCAttendanceResults(ArrayCollection $cAttendanceResults): User
    {
        $this->cAttendanceResults = $cAttendanceResults;
        return $this;
    }

    /**
     *  Gets the Course Attendance Sheet's for the current user.
     *
     */
    public function getCAttendanceSheets(): ArrayCollection
    {
        return $this->cAttendanceSheets;
    }

    /**
     * Sets the Course Attendance Sheet's for the current user.
     *
     */
    public function setCAttendanceSheets(ArrayCollection $cAttendanceSheets): User
    {
        $this->cAttendanceSheets = $cAttendanceSheets;
        return $this;
    }

    /**
     *  Gets the Course Blog Ratings's for the current user.
     *
     */
    public function getCBlogRatings(): ArrayCollection
    {
        return $this->cBlogRatings;
    }

    /**
     * Sets the Course Blog Ratings's for the current user.
     *
     */
    public function setCBlogRatings(ArrayCollection $cBlogRatings): User
    {
        $this->cBlogRatings = $cBlogRatings;
        return $this;
    }

    /**
     *  Gets the Course Blog Relation with the current user.
     *
     */
    public function getCBlogRelUsers(): ArrayCollection
    {
        return $this->cBlogRelUsers;
    }

    /**
     * Sets the Course Blog Relation with the current user.
     *
     */
    public function setCBlogRelUsers(ArrayCollection $cBlogRelUsers): User
    {
        $this->cBlogRelUsers = $cBlogRelUsers;
        return $this;
    }

    /**
     *  Gets the Course Blog Tak Relation with the current user.
     *
     */
    public function getCBlogTaskRelUsers(): ArrayCollection
    {
        return $this->cBlogTaskRelUsers;
    }

    /**
     * Sets the Course Blog Tak Relation with the current user.
     *
     */
    public function setCBlogTaskRelUsers(ArrayCollection $cBlogTaskRelUsers): User
    {
        $this->cBlogTaskRelUsers = $cBlogTaskRelUsers;
        return $this;
    }

    /**
     *  Gets the Course Chat Connected for the current user.
     *
     */
    public function getCChatConnected(): ArrayCollection
    {
        return $this->cChatConnected;
    }

    /**
     * Sets the Course Chat Connected for the current user.
     *
     */
    public function setCChatConnected(ArrayCollection $cChatConnected): User
    {
        $this->cChatConnected = $cChatConnected;
        return $this;
    }

    /**
     *  Gets the Course Dropbox Category's for the current user.
     *
     */
    public function getCDropboxCategorys(): ArrayCollection
    {
        return $this->cDropboxCategorys;
    }

    /**
     * Sets the Course Dropbox Category's for the current user.
     *
     */
    public function setCDropboxCategorys(ArrayCollection $cDropboxCategorys): User
    {
        $this->cDropboxCategorys = $cDropboxCategorys;
        return $this;
    }

    /**
     *  Gets the Course Dropbox Person's for the current user.
     *
     */
    public function getCDropboxPersons(): ArrayCollection
    {
        return $this->cDropboxPersons;
    }

    /**
     * Sets the Course Dropbox Person's for the current user.
     *
     */
    public function setCDropboxPersons(ArrayCollection $cDropboxPersons): User
    {
        $this->cDropboxPersons = $cDropboxPersons;
        return $this;
    }

    /**
     *  Gets the Course Forum Mailcue's for the current user.
     *
     */
    public function getCForumMailcues(): ArrayCollection
    {
        return $this->cForumMailcues;
    }

    /**
     * Sets the Course Forum Mailcue's for the current user.
     *
     */
    public function setCForumMailcues(ArrayCollection $cForumMailcues): User
    {
        $this->cForumMailcues = $cForumMailcues;
        return $this;
    }

    /**
     *  Gets the Course Forum Notification's for the current user.
     *
     */
    public function getCForumNotifications(): ArrayCollection
    {
        return $this->cForumNotifications;
    }

    /**
     * Sets the Course Forum Notification's for the current user.
     *
     */
    public function setCForumNotifications(ArrayCollection $cForumNotifications): User
    {
        $this->cForumNotifications = $cForumNotifications;
        return $this;
    }

    /**
     *  Gets the Course Forum Thread Qualify's for the current user.
     *
     */
    public function getCForumThreadQualifys(): ArrayCollection
    {
        return $this->cForumThreadQualifys;
    }

    /**
     * Sets the Course Forum Thread Qualify's for the current user.
     *
     */
    public function setCForumThreadQualifys(ArrayCollection $cForumThreadQualifys): User
    {
        $this->cForumThreadQualifys = $cForumThreadQualifys;
        return $this;
    }

    /**
     *  Gets the Course Forum Thread Qualify Log's for the current user.
     *
     */
    public function getCForumThreadQualifyLogs(): ArrayCollection
    {
        return $this->cForumThreadQualifyLogs;
    }

    /**
     * Sets the Course Forum Thread Qualify Log's for the current user.
     *
     */
    public function setCForumThreadQualifyLogs(ArrayCollection $cForumThreadQualifyLogs): User
    {
        $this->cForumThreadQualifyLogs = $cForumThreadQualifyLogs;
        return $this;
    }

    /**
     *  Gets the Course Lp View's for the current user.
     *
     */
    public function getCLpViews(): ArrayCollection
    {
        return $this->cLpViews;
    }

    /**
     * Sets the Course Lp View's for the current user.
     *
     */
    public function setCLpViews(ArrayCollection $cLpViews): User
    {
        $this->cLpViews = $cLpViews;
        return $this;
    }

    /**
     *  Gets the Course Notebook's for the current user.
     *
     */
    public function getCNotebooks(): ArrayCollection
    {
        return $this->cNotebooks;
    }

    /**
     * Sets the Course Notebook's for the current user.
     *
     */
    public function setCNotebooks(ArrayCollection $cNotebooks): User
    {
        $this->cNotebooks = $cNotebooks;
        return $this;
    }

    /**
     *  Gets the Course Online Connected for the current user.
     *
     */
    public function getCOnlineConnected(): ArrayCollection
    {
        return $this->cOnlineConnected;
    }

    /**
     * Sets the Course Online Connected for the current user.
     *
     */
    public function setCOnlineConnected(ArrayCollection $cOnlineConnected): User
    {
        $this->cOnlineConnected = $cOnlineConnected;
        return $this;
    }

    /**
     *  Gets the Course Role User's for the current user.
     *
     */
    public function getCRoleUsers(): ArrayCollection
    {
        return $this->cRoleUsers;
    }

    /**
     * Sets the Course Role User's for the current user.
     *
     */
    public function setCRoleUsers(ArrayCollection $cRoleUsers): User
    {
        $this->cRoleUsers = $cRoleUsers;
        return $this;
    }

    /**
     *  Gets the Course Student Publication's for the current user.
     *
     */
    public function getCStudentPublications(): ArrayCollection
    {
        return $this->cStudentPublications;
    }

    /**
     * Sets the Course Student Publication's for the current user.
     *
     */
    public function setCStudentPublications(ArrayCollection $cStudentPublications): User
    {
        $this->cStudentPublications = $cStudentPublications;
        return $this;
    }

    /**
     *  Gets the Course Student Publication Comment's for the current user.
     *
     */
    public function getCStudentPublicationComments(): ArrayCollection
    {
        return $this->cStudentPublicationComments;
    }

    /**
     * Sets the Course Student Publication Comment's for the current user.
     *
     */
    public function setCStudentPublicationComments(ArrayCollection $cStudentPublicationComments): User
    {
        $this->cStudentPublicationComments = $cStudentPublicationComments;
        return $this;
    }

    /**
     *  Gets the Course Student Publication Rel User's for the current user.
     *
     */
    public function getCStudentPublicationRelUser(): ArrayCollection
    {
        return $this->CStudentPublicationRelUser;
    }

    /**
     * Sets the Course Student Publication Rel User's for the current user.
     *
     */
    public function setCStudentPublicationRelUser(ArrayCollection $CStudentPublicationRelUser): User
    {
        $this->CStudentPublicationRelUser = $CStudentPublicationRelUser;
        return $this;
    }

    /**
     *  Gets the Wiki's for the current user.
     *
     */
    public function getCWiki(): ArrayCollection
    {
        return $this->cWiki;
    }

    /**
     * Sets the Wiki's for the current user.
     *
     */
    public function setCWiki(ArrayCollection $cWiki): User
    {
        $this->cWiki = $cWiki;
        return $this;
    }

    /**
     *  Gets the Wiki Mailcue's for the current user.
     *
     */
    public function getCWikiMailcues(): ArrayCollection
    {
        return $this->cWikiMailcues;
    }

    /**
     * Sets the Course Wiki Mailcue's for the current user.
     *
     */
    public function setCWikiMailcues(ArrayCollection $cWikiMailcues): User
    {
        $this->cWikiMailcues = $cWikiMailcues;
        return $this;
    }

    /**
     * Sets the Track E Access Complete for the current user.
     *
     */
    public function getTrackEAccessComplete(): TrackEAccessComplete
    {
        return $this->trackEAccessComplete;
    }

    /**
     * Get the Track E Access Complete for the current user.
     *
     */
    public function setTrackEAccessComplete(TrackEAccessComplete $trackEAccessComplete): User
    {
        $this->trackEAccessComplete = $trackEAccessComplete;
        return $this;
    }
}
