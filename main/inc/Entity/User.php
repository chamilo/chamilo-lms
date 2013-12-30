<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Symfony\Component\Validator\Constraints\DateTime;
use ChamiloLMS\Component\Auth;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="Entity\Repository\UserRepository")
 */
class User implements AdvancedUserInterface, \Serializable, EquatableInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=60, precision=0, scale=0, nullable=true, unique=false)
     */
    private $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=60, precision=0, scale=0, nullable=true, unique=false)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=50, precision=0, scale=0, nullable=false, unique=false)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_source", type="string", length=50, precision=0, scale=0, nullable=true, unique=false)
     */
    private $authSource;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $email;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="official_code", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $officialCode;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=30, precision=0, scale=0, nullable=true, unique=false)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="picture_uri", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $pictureUri;

    /**
     * @var integer
     *
     * @ORM\Column(name="creator_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $creatorId;

    /**
     * @var string
     *
     * @ORM\Column(name="competences", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $competences;

    /**
     * @var string
     *
     * @ORM\Column(name="diplomas", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $diplomas;

    /**
     * @var string
     *
     * @ORM\Column(name="openarea", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $openarea;

    /**
     * @var string
     *
     * @ORM\Column(name="teach", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $teach;

    /**
     * @var string
     *
     * @ORM\Column(name="productions", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $productions;

    /**
     * @var integer
     *
     * @ORM\Column(name="chatcall_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $chatcallUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="chatcall_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $chatcallDate;

    /**
     * @var string
     *
     * @ORM\Column(name="chatcall_text", type="string", length=50, precision=0, scale=0, nullable=false, unique=false)
     */
    private $chatcallText;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $language;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registration_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $registrationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $expirationDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $active;

    /**
     * @var string
     *
     * @ORM\Column(name="openid", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $openid;

    /**
     * @var string
     *
     * @ORM\Column(name="theme", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $theme;

    /**
     * @var integer
     *
     * @ORM\Column(name="hr_dept_id", type="smallint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hrDeptId;

    /**
     * @ORM\OneToMany(targetEntity="CourseRelUser", mappedBy="user")
     **/
    private $courses;

    /**
     * @ORM\OneToMany(targetEntity="CItemProperty", mappedBy="user")
     **/
    private $items;

    /**
     * @ORM\OneToMany(targetEntity="UsergroupRelUser", mappedBy="user")
     **/
    private $classes;

    /**
     * @ORM\OneToMany(targetEntity="CDropboxPost", mappedBy="user")
     **/
    private $dropBoxReceivedFiles;

    /**
     * @ORM\OneToMany(targetEntity="CDropboxFile", mappedBy="userSent")
     **/
    private $dropBoxSentFiles;


    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users")
     * @ORM\JoinTable(
     *          name="users_roles",
     *          joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="user_id")},
     *          inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")}
     *      )
     */
    private $roles;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $salt;

    private $isActive;

    /**
     * @ORM\OneToMany(targetEntity="CurriculumItemRelUser", mappedBy="user")
     **/
    private $curriculumItems;

    /**
     * @ORM\ManyToMany(targetEntity="AccessUrl")
     * @ORM\JoinTable(name="access_url_rel_user",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="user_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="access_url_id", referencedColumnName="id")}
     *      )
     */
    private $portals;

    /**
     *
     */
    public function __construct()
    {
        $this->salt = sha1(uniqid(null, true));
        $this->isActive = true;
        $this->registrationDate = new \DateTime();

        $this->courses = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->classes = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->curriculumItems = new ArrayCollection();
        $this->portals = new ArrayCollection();
        $this->dropBoxSentFiles = new ArrayCollection();
        $this->dropBoxReceivedFiles = new ArrayCollection();
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
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('firstname', new Assert\NotBlank());
        $metadata->addPropertyConstraint('lastname', new Assert\NotBlank());
        $metadata->addPropertyConstraint('email', new Assert\Email());

        $metadata->addConstraint(new UniqueEntity(array(
            'fields'  => 'username',
            'message' => 'This value is already used.',
        )));

        $metadata->addPropertyConstraint('username', new Assert\Length(array(
            'min'        => 2,
            'max'        => 50,
            'minMessage' => 'This value is too short. It should have {{ limit }} character or more.|This value is too short. It should have {{ limit }} characters or more.',
            'maxMessage' => 'This value is too long. It should have {{ limit }} character or less.|This value is too long. It should have {{ limit }} characters or less.',
        )));
    }

    /**
     * @inheritDoc
     */
    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

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
     * @inheritDoc
     */
    public function isAccountNonExpired()
    {
        return true;
        /*$now = new \DateTime();
        return $this->getExpirationDate() < $now;*/
    }

    /**
     * @inheritDoc
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled()
    {
        return $this->getActive() == 1;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
    }

    /**
     * This function is needed in order to pass roles to the security component
     * @inheritDoc
     */
    public function getRoles()
    {
        $roles = $this->roles->toArray();
        $courses = $this->getCourses();
        if (!empty($courses)) {
            /** @var CourseRelUser $course */
            foreach ($courses as $course) {
                $roles[] = new Auth\Role($this, $course->getStatus(), $course->getCId());
            }
        }
        return $roles;
    }

    /**
     * @param $role
     * @return $this
     */
    public function setRoles($role)
    {
        $this->roles->add($role);

        return $this;
    }

    /**
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        if (in_array($role, $this->getRoles())) {
            return true;
        }
        return false;
    }

    /**
     * @see https://github.com/symfony/symfony/issues/3691
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        /*
         * ! Don't serialize $roles field !
         */
        return \serialize(
            array(
                $this->userId,
                $this->username,
                $this->email,
                $this->salt,
                $this->password,
                $this->isActive
            )
        );
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list (
            $this->userId,
            $this->username,
            $this->email,
            $this->salt,
            $this->password,
            $this->isActive
        ) = \unserialize($serialized);
    }

    /**
     *
     * @return ArrayCollection
     */
    public function getRolesObj()
    {
        return $this->roles;
    }

    /**
     * Set salt
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
     * Get salt
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

    /**
     *
     */
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
     * @return string
     */
    public function getCompleteName()
    {
        return $this->lastname .', '. $this->firstname;
    }

    /**
     * Returns the list of classes for the user
     * @return string
     */
    public function getCompleteNameWithClasses()
    {
        $classSubscription = $this->getClasses();
        $classList = array();
        foreach ($classSubscription as $subscription) {
            $class = $subscription->getClass();
            $classList[] = $class->getName();
        }
        $classString = !empty($classList) ? ' ['.implode(', ', $classList).']' : null;

        return $this->getCompleteName().$classString;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set lastname
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
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set firstname
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
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set authSource
     *
     * @param string $authSource
     * @return User
     */
    public function setAuthSource($authSource)
    {
        $this->authSource = $authSource;

        return $this;
    }

    /**
     * Get authSource
     *
     * @return string
     */
    public function getAuthSource()
    {
        return $this->authSource;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return User
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set officialCode
     *
     * @param string $officialCode
     * @return User
     */
    public function setOfficialCode($officialCode)
    {
        $this->officialCode = $officialCode;

        return $this;
    }

    /**
     * Get officialCode
     *
     * @return string
     */
    public function getOfficialCode()
    {
        return $this->officialCode;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return User
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set pictureUri
     *
     * @param string $pictureUri
     * @return User
     */
    public function setPictureUri($pictureUri)
    {
        $this->pictureUri = $pictureUri;

        return $this;
    }

    /**
     * Get pictureUri
     *
     * @return string
     */
    public function getPictureUri()
    {
        return $this->pictureUri;
    }

    /**
     * Set creatorId
     *
     * @param integer $creatorId
     * @return User
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    /**
     * Get creatorId
     *
     * @return integer
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * Set competences
     *
     * @param string $competences
     * @return User
     */
    public function setCompetences($competences)
    {
        $this->competences = $competences;

        return $this;
    }

    /**
     * Get competences
     *
     * @return string
     */
    public function getCompetences()
    {
        return $this->competences;
    }

    /**
     * Set diplomas
     *
     * @param string $diplomas
     * @return User
     */
    public function setDiplomas($diplomas)
    {
        $this->diplomas = $diplomas;

        return $this;
    }

    /**
     * Get diplomas
     *
     * @return string
     */
    public function getDiplomas()
    {
        return $this->diplomas;
    }

    /**
     * Set openarea
     *
     * @param string $openarea
     * @return User
     */
    public function setOpenarea($openarea)
    {
        $this->openarea = $openarea;

        return $this;
    }

    /**
     * Get openarea
     *
     * @return string
     */
    public function getOpenarea()
    {
        return $this->openarea;
    }

    /**
     * Set teach
     *
     * @param string $teach
     * @return User
     */
    public function setTeach($teach)
    {
        $this->teach = $teach;

        return $this;
    }

    /**
     * Get teach
     *
     * @return string
     */
    public function getTeach()
    {
        return $this->teach;
    }

    /**
     * Set productions
     *
     * @param string $productions
     * @return User
     */
    public function setProductions($productions)
    {
        $this->productions = $productions;

        return $this;
    }

    /**
     * Get productions
     *
     * @return string
     */
    public function getProductions()
    {
        return $this->productions;
    }

    /**
     * Set chatcallUserId
     *
     * @param integer $chatcallUserId
     * @return User
     */
    public function setChatcallUserId($chatcallUserId)
    {
        $this->chatcallUserId = $chatcallUserId;

        return $this;
    }

    /**
     * Get chatcallUserId
     *
     * @return integer
     */
    public function getChatcallUserId()
    {
        return $this->chatcallUserId;
    }

    /**
     * Set chatcallDate
     *
     * @param \DateTime $chatcallDate
     * @return User
     */
    public function setChatcallDate($chatcallDate)
    {
        $this->chatcallDate = $chatcallDate;

        return $this;
    }

    /**
     * Get chatcallDate
     *
     * @return \DateTime
     */
    public function getChatcallDate()
    {
        return $this->chatcallDate;
    }

    /**
     * Set chatcallText
     *
     * @param string $chatcallText
     * @return User
     */
    public function setChatcallText($chatcallText)
    {
        $this->chatcallText = $chatcallText;

        return $this;
    }

    /**
     * Get chatcallText
     *
     * @return string
     */
    public function getChatcallText()
    {
        return $this->chatcallText;
    }

    /**
     * Set language
     *
     * @param string $language
     * @return User
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set registrationDate
     *
     * @param \DateTime $registrationDate
     * @return User
     */
    public function setRegistrationDate($registrationDate)
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    /**
     * Get registrationDate
     *
     * @return \DateTime
     */
    public function getRegistrationDate()
    {
        return $this->registrationDate;
    }

    /**
     * Set expirationDate
     *
     * @param \DateTime $expirationDate
     * @return User
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * Get expirationDate
     *
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return User
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set openid
     *
     * @param string $openid
     * @return User
     */
    public function setOpenid($openid)
    {
        $this->openid = $openid;

        return $this;
    }

    /**
     * Get openid
     *
     * @return string
     */
    public function getOpenid()
    {
        return $this->openid;
    }

    /**
     * Set theme
     *
     * @param string $theme
     * @return User
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get theme
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set hrDeptId
     *
     * @param integer $hrDeptId
     * @return User
     */
    public function setHrDeptId($hrDeptId)
    {
        $this->hrDeptId = $hrDeptId;

        return $this;
    }

    /**
     * Get hrDeptId
     *
     * @return integer
     */
    public function getHrDeptId()
    {
        return $this->hrDeptId;
    }
}
