<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Entity;

use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
//use Sonata\UserBundle\Entity\BaseUser as BaseUser;
use FOS\UserBundle\Model\GroupInterface;
use FOS\UserBundle\Model\UserInterface;
use Sonata\UserBundle\Model\User as BaseUser;
//use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class User extends BaseUser
{
    public const COURSE_MANAGER = 1;
    public const TEACHER = 1;
    public const SESSION_ADMIN = 3;
    public const DRH = 4;
    public const STUDENT = 5;
    public const ANONYMOUS = 6;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    protected $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=100, nullable=false, unique=true)
     */
    //protected $username;

    /**
     * @var string
     *
     * * @ORM\Column(name="username_canonical", type="string", length=100, nullable=false, unique=true)
     */
    //protected $usernameCanonical;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false, unique=false)
     */
    //protected $email;

    /**
     * @var bool
     * @ORM\Column(name="locked", type="boolean")
     */
    //protected $locked;

    /**
     * @var bool
     * @ORM\Column(name="enabled", type="boolean")
     */
    //protected $enabled;

    /**
     * @var bool
     * @ORM\Column(name="expired", type="boolean")
     */
    //protected $expired;

    /**
     * @var bool
     * @ORM\Column(name="credentials_expired", type="boolean")
     */
    //protected $credentialsExpired;

    /**
     * @var \DateTime
     * @ORM\Column(name="credentials_expire_at", type="datetime", nullable=true, unique=false)
     */
    //protected $credentialsExpireAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="expires_at", type="datetime", nullable=true, unique=false)
     */
    //protected $expiresAt;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=60, nullable=true, unique=false)
     */
    protected $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=60, nullable=true, unique=false)
     */
    protected $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=30, nullable=true, unique=false)
     */
    protected $phone;

    /**
     * Vich\UploadableField(mapping="user_image", fileNameProperty="picture_uri").
     *
     * note This is not a mapped field of entity metadata, just a simple property.
     *
     * @var File
     */
    protected $imageFile;

    /**
     * @ORM\Column(type="string", length=255)
     */
    //protected $salt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true, unique=false)
     */
    //protected $lastLogin;

    /**
     * Random string sent to the user email address in order to verify it.
     *
     * @var string
     * @ORM\Column(name="confirmation_token", type="string", length=255, nullable=true)
     */
    //protected $confirmationToken;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true, unique=false)
     */
    //protected $passwordRequestedAt;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\CourseRelUser", mappedBy="user")
     */
    protected $courses;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CItemProperty", mappedBy="user")
     */
    //protected $items;

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
     * @ORM\Column(type="array")
     */
    //protected $roles;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\JuryMembers", mappedBy="user")
     */
    //protected $jurySubscriptions;

    /**
     * @ORM\ManyToMany(targetEntity="Chamilo\UserBundle\Entity\Group")
     * @ORM\JoinTable(name="fos_user_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

    //private $isActive;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\CurriculumItemRelUser", mappedBy="user").
     */
    protected $curriculumItems;

    /*
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\AccessUrlRelUser", mappedBy="user")
     *
     */
    protected $portals;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Session", mappedBy="generalCoach")
     */
    protected $sessionAsGeneralCoach;

    /**
     * @var ArrayCollection
     *                      ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\UserFieldValues", mappedBy="user", orphanRemoval=true, cascade={"persist"})
     */
    protected $extraFields;

    /**
     * ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceNode", mappedBy="creator").
     */
    protected $resourceNodes;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SessionRelCourseRelUser", mappedBy="user", cascade={"persist"})
     */
    protected $sessionCourseSubscriptions;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=false, unique=false)
     */
    //protected $password;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_source", type="string", length=50, nullable=true, unique=false)
     */
    private $authSource;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="official_code", type="string", length=40, nullable=true, unique=false)
     */
    private $officialCode;

    /**
     * @var string
     * @ORM\Column(name="picture_uri", type="string", length=250, nullable=true, unique=false)
     */
    private $pictureUri;

    /**
     * ORM\ManyToOne(targetEntity="Application\Sonata\MediaBundle\Entity\Media", cascade={"all"} ).
     *
     * @ORM\JoinColumn(name="picture_uri", referencedColumnName="id")
     */
    //protected $pictureUri;

    /**
     * @var int
     *
     * @ORM\Column(name="creator_id", type="integer", nullable=true, unique=false)
     */
    private $creatorId;

    /**
     * @var string
     *
     * @ORM\Column(name="competences", type="text", nullable=true, unique=false)
     */
    private $competences;

    /**
     * @var string
     *
     * @ORM\Column(name="diplomas", type="text", nullable=true, unique=false)
     */
    private $diplomas;

    /**
     * @var string
     *
     * @ORM\Column(name="openarea", type="text", nullable=true, unique=false)
     */
    private $openarea;

    /**
     * @var string
     *
     * @ORM\Column(name="teach", type="text", nullable=true, unique=false)
     */
    private $teach;

    /**
     * @var string
     *
     * @ORM\Column(name="productions", type="string", length=250, nullable=true, unique=false)
     */
    private $productions;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=40, nullable=true, unique=false)
     */
    private $language;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registration_date", type="datetime", nullable=false, unique=false)
     */
    private $registrationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration_date", type="datetime", nullable=true, unique=false)
     */
    private $expirationDate;

    /**
     * @var int
     *
     * @ORM\Column(name="active", type="boolean", nullable=false, unique=false)
     */
    private $active;

    /**
     * @var string
     *
     * @ORM\Column(name="openid", type="string", length=255, nullable=true, unique=false)
     */
    private $openid;

    /**
     * @var string
     *
     * @ORM\Column(name="theme", type="string", length=255, nullable=true, unique=false)
     */
    private $theme;

    /**
     * @var int
     *
     * @ORM\Column(name="hr_dept_id", type="smallint", nullable=true, unique=false)
     */
    private $hrDeptId;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->status = self::STUDENT;

        $this->salt = sha1(uniqid(null, true));
        $this->isActive = true;
        $this->active = 1;
        $this->registrationDate = new \DateTime();
        $this->authSource = 'platform';
        $this->courses = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->classes = new ArrayCollection();
        $this->curriculumItems = new ArrayCollection();
        $this->portals = new ArrayCollection();
        $this->dropBoxSentFiles = new ArrayCollection();
        $this->dropBoxReceivedFiles = new ArrayCollection();
        //$this->extraFields = new ArrayCollection();
        //$this->userId = 0;
        //$this->createdAt = new \DateTime();
        //$this->updatedAt = new \DateTime();

        $this->enabled = false;
        $this->locked = false;
        $this->expired = false;
        $this->roles = [];
        $this->credentialsExpired = false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUsername();
    }

    /**
     * Updates the id with the user_id.
     *
     *  @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        //parent::postPersist();
        // Updates the user_id field
        $user = $args->getEntity();
        $this->setUserId($user->getId());
        /*$em = $args->getEntityManager();
        $em->persist($user);
        $em->flush();*/
    }

    /**
     * @param int $userId
     */
    public function setId($userId)
    {
        $this->id = $userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        if (!empty($userId)) {
            $this->userId = $userId;
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEncoderName()
    {
        return "legacy_encoder";
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
     * @return ArrayCollection
     */
    public function getCourses()
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
     * {@inheritdoc}
     */
    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof User) {
            return false;
        }

        /*if ($this->password !== $user->getPassword()) {
            return false;
        }*/

        /*if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }*/

        /*if ($this->username !== $user->getUsername()) {
            return false;
        }*/

        return true;
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
     * @return ArrayCollection
     */
    public function getCurriculumItems()
    {
        return $this->curriculumItems;
    }

    /**
     * @param $items
     */
    public function setCurriculumItems($items)
    {
        $this->curriculumItems = $items;
    }

    /**
     * @return bool
     */
    public function getIsActive()
    {
        return $this->active == 1;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getIsActive();
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return ArrayCollection
     */
    /*public function getRolesObj()
    {
        return $this->roles;
    }*/

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
     * @todo don't use api_get_person_name
     *
     * @return string
     */
    public function getCompleteName()
    {
        return api_get_person_name($this->firstname, $this->lastname);
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

        return $this->getCompleteName().$classString;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set lastname.
     *
     * @param string $lastname
     *
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Set firstname.
     *
     * @param string $firstname
     *
     * @return User
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
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
     * @param int $status
     *
     * @return User
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
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
     * Set pictureUri.
     *
     * @param string $pictureUri
     *
     * @return User
     */
    public function setPictureUri($pictureUri)
    {
        $this->pictureUri = $pictureUri;

        return $this;
    }

    /**
     * Get pictureUri.
     *
     * @return Media
     */
    public function getPictureUri()
    {
        return $this->pictureUri;
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
     * @return Media
     */
    public function getAvatar()
    {
        return $this->getPictureUri();
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
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     */
    public function setImageFile(File $image)
    {
        $this->imageFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime('now');
        }
    }

    /**
     * @return File
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * @param string $imageName
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;
    }

    /**
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->getUsername();
    }

    /**
     * @param $slug
     *
     * @return User
     */
    public function setSlug($slug)
    {
        return $this->setUsername($slug);
    }

    /**
     * Set lastLogin.
     *
     * @return User
     */
    public function setLastLogin(\DateTime $lastLogin)
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
     * {@inheritdoc}
     */
    public function getExtraFields()
    {
        return $this->extraFields;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtraFields($extraFields)
    {
        $this->extraFields = new ArrayCollection();
        foreach ($extraFields as $extraField) {
            $this->addExtraFields($extraField);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    /*public function addExtraFields(ExtraFieldValues $extraFieldValue)
    {
        $extraFieldValue->setUser($this);
        $this->extraFields[] = $extraFieldValue;

        return $this;
    }*/

    /**
     * {@inheritdoc}
     */
    public function addExtraFields(ExtraFieldValues $extraFieldValue)
    {
        //if (!$this->hasExtraField($attribute)) {
        $extraFieldValue->setUser($this);
        $this->extraFields[] = $extraFieldValue;
        //}

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeExtraField(ExtraFieldValues $attribute)
    {
        //if ($this->hasExtraField($attribute)) {
        //$this->extraFields->removeElement($attribute);
        //$attribute->setUser($this);
        //}

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    /*public function hasExtraField($attribute)
    {
        if (!$this->extraFields) {
            return false;
        }
        return $this->extraFields->contains($attribute);
    }*/

    /**
     * {@inheritdoc}
     */
    public function hasExtraFieldByName($attributeName)
    {
        foreach ($this->extraFields as $attribute) {
            if ($attribute->getName() === $attributeName) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraFieldByName($attributeName)
    {
        foreach ($this->extraFields as $attribute) {
            if ($attribute->getName() === $attributeName) {
                return $attribute;
            }
        }

        return null;
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

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function isPasswordRequestNonExpired($ttl)
    {
        return $this->getPasswordRequestedAt() instanceof \DateTime &&
        $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }

    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Returns the creation date.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets the last update date.
     *
     * @return User
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Returns the last update date.
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
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
     * Sets the two-step verification code.
     *
     * @param string $twoStepVerificationCode
     *
     * @return User
     */
    public function setTwoStepVerificationCode($twoStepVerificationCode)
    {
        $this->twoStepVerificationCode = $twoStepVerificationCode;

        return $this;
    }

    /**
     * Returns the two-step verification code.
     *
     * @return string
     */
    public function getTwoStepVerificationCode()
    {
        return $this->twoStepVerificationCode;
    }

    /**
     * @param string $biography
     *
     * @return User
     */
    public function setBiography($biography)
    {
        $this->biography = $biography;

        return $this;
    }

    /**
     * @return string
     */
    public function getBiography()
    {
        return $this->biography;
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

    /**
     * @param string $facebookData
     *
     * @return User
     */
    public function setFacebookData($facebookData)
    {
        $this->facebookData = $facebookData;

        return $this;
    }

    /**
     * @return string
     */
    public function getFacebookData()
    {
        return $this->facebookData;
    }

    /**
     * @param string $facebookName
     *
     * @return User
     */
    public function setFacebookName($facebookName)
    {
        $this->facebookName = $facebookName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFacebookName()
    {
        return $this->facebookName;
    }

    /**
     * @param string $facebookUid
     *
     * @return User
     */
    public function setFacebookUid($facebookUid)
    {
        $this->facebookUid = $facebookUid;

        return $this;
    }

    /**
     * @return string
     */
    public function getFacebookUid()
    {
        return $this->facebookUid;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $gender
     *
     * @return User
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gplusData
     *
     * @return User
     */
    public function setGplusData($gplusData)
    {
        $this->gplusData = $gplusData;

        return $this;
    }

    /**
     * @return string
     */
    public function getGplusData()
    {
        return $this->gplusData;
    }

    /**
     * @param string $gplusName
     *
     * @return User
     */
    public function setGplusName($gplusName)
    {
        $this->gplusName = $gplusName;

        return $this;
    }

    /**
     * @return string
     */
    public function getGplusName()
    {
        return $this->gplusName;
    }

    /**
     * @param string $gplusUid
     *
     * @return User
     */
    public function setGplusUid($gplusUid)
    {
        $this->gplusUid = $gplusUid;

        return $this;
    }

    /**
     * @return string
     */
    public function getGplusUid()
    {
        return $this->gplusUid;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
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
     * @param string $twitterData
     *
     * @return User
     */
    public function setTwitterData($twitterData)
    {
        $this->twitterData = $twitterData;

        return $this;
    }

    /**
     * @return string
     */
    public function getTwitterData()
    {
        return $this->twitterData;
    }

    /**
     * @param string $twitterName
     *
     * @return User
     */
    public function setTwitterName($twitterName)
    {
        $this->twitterName = $twitterName;

        return $this;
    }

    /**
     * @return string
     */
    public function getTwitterName()
    {
        return $this->twitterName;
    }

    /**
     * @param string $twitterUid
     *
     * @return User
     */
    public function setTwitterUid($twitterUid)
    {
        $this->twitterUid = $twitterUid;

        return $this;
    }

    /**
     * @return string
     */
    public function getTwitterUid()
    {
        return $this->twitterUid;
    }

    /**
     * @param string $website
     *
     * @return User
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string $token
     *
     * @return User
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getFullname()
    {
        return sprintf('%s %s', $this->getFirstname(), $this->getLastname());
    }

    /**
     * @return array
     */
    public function getRealRoles()
    {
        return $this->roles;
    }

    /**
     * @return User
     */
    public function setRealRoles(array $roles)
    {
        $this->setRoles($roles);

        return $this;
    }

    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function getUsernameCanonical()
    {
        return $this->usernameCanonical;
    }

    public function getEmailCanonical()
    {
        return $this->emailCanonical;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
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
        $roles[] = static::ROLE_DEFAULT;

        return array_unique($roles);
    }

    /**
     * Never use this to check if this user has access to anything!
     *
     * Use the SecurityContext, or an implementation of AccessDecisionManager
     * instead, e.g.
     *
     *         $securityContext->isGranted('ROLE_USER');
     *
     * @param string $role
     *
     * @return bool
     */
    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    public function isAccountNonExpired()
    {
        if (true === $this->expired) {
            return false;
        }

        if (null !== $this->expiresAt && $this->expiresAt->getTimestamp() < time()) {
            return false;
        }

        return true;
    }

    public function isAccountNonLocked()
    {
        return !$this->locked;
    }

    public function isCredentialsNonExpired()
    {
        if (true === $this->credentialsExpired) {
            return false;
        }

        if (null !== $this->credentialsExpireAt && $this->credentialsExpireAt->getTimestamp() < time()) {
            return false;
        }

        return true;
    }

    public function isCredentialsExpired()
    {
        return !$this->isCredentialsNonExpired();
    }

    public function isExpired()
    {
        return !$this->isAccountNonExpired();
    }

    public function isLocked()
    {
        return !$this->isAccountNonLocked();
    }

    public function isSuperAdmin()
    {
        return $this->hasRole(static::ROLE_SUPER_ADMIN);
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

    public function setEmailCanonical($emailCanonical)
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    public function setEnabled($boolean)
    {
        $this->enabled = (bool) $boolean;

        return $this;
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
     * @param \DateTime $date
     *
     * @return User
     */
    public function setExpiresAt(\DateTime $date = null)
    {
        $this->expiresAt = $date;

        return $this;
    }

    public function setSuperAdmin($boolean)
    {
        if (true === $boolean) {
            $this->addRole(static::ROLE_SUPER_ADMIN);
        } else {
            $this->removeRole(static::ROLE_SUPER_ADMIN);
        }

        return $this;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;

        return $this;
    }

    public function setLocked($boolean)
    {
        $this->locked = $boolean;

        return $this;
    }

    public function setPasswordRequestedAt(\DateTime $date = null)
    {
        $this->passwordRequestedAt = $date;

        return $this;
    }

    public function setRoles(array $roles)
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * Gets the groups granted to the user.
     *
     * @return Collection
     */
    public function getGroups()
    {
        return $this->groups ?: $this->groups = new ArrayCollection();
    }

    public function getGroupNames()
    {
        $names = [];
        foreach ($this->getGroups() as $group) {
            $names[] = $group->getName();
        }

        return $names;
    }

    public function hasGroup($name)
    {
        return in_array($name, $this->getGroupNames());
    }

    public function addGroup(GroupInterface $group)
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    public function removeGroup(GroupInterface $group)
    {
        if ($this->getGroups()->contains($group)) {
            $this->getGroups()->removeElement($group);
        }

        return $this;
    }

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
     * Serializes the user.
     *
     * The serialized data have to contain the fields used by the equals method and the username.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize([
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->expired,
            $this->locked,
            $this->credentialsExpired,
            $this->enabled,
            $this->id,
        ]);
    }

    /**
     * Unserializes the user.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = \UnserializeApi::unserialize('not_allowed_classes', $serialized);
        // add a few extra elements in the array to ensure that we have enough keys when unserializing
        // older data which does not include all properties.
        $data = array_merge($data, array_fill(0, 2, null));

        list(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->expired,
            $this->locked,
            $this->credentialsExpired,
            $this->enabled,
            $this->id
            ) = $data;
    }
}
