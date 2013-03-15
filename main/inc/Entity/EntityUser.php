<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * EntityUser
 *
 * @Table(name="user")
 * @Entity(repositoryClass="Entity\Repository\UserRepository")
 */
class EntityUser
{
    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $userId;

    /**
     * @var string
     *
     * @Column(name="lastname", type="string", length=60, precision=0, scale=0, nullable=true, unique=false)
     */
    private $lastname;

    /**
     * @var string
     *
     * @Column(name="firstname", type="string", length=60, precision=0, scale=0, nullable=true, unique=false)
     */
    private $firstname;

    /**
     * @var string
     *
     * @Column(name="username", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $username;

    /**
     * @var string
     *
     * @Column(name="password", type="string", length=50, precision=0, scale=0, nullable=false, unique=false)
     */
    private $password;

    /**
     * @var string
     *
     * @Column(name="auth_source", type="string", length=50, precision=0, scale=0, nullable=true, unique=false)
     */
    private $authSource;

    /**
     * @var string
     *
     * @Column(name="email", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $email;

    /**
     * @var boolean
     *
     * @Column(name="status", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var string
     *
     * @Column(name="official_code", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $officialCode;

    /**
     * @var string
     *
     * @Column(name="phone", type="string", length=30, precision=0, scale=0, nullable=true, unique=false)
     */
    private $phone;

    /**
     * @var string
     *
     * @Column(name="picture_uri", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $pictureUri;

    /**
     * @var integer
     *
     * @Column(name="creator_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $creatorId;

    /**
     * @var string
     *
     * @Column(name="competences", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $competences;

    /**
     * @var string
     *
     * @Column(name="diplomas", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $diplomas;

    /**
     * @var string
     *
     * @Column(name="openarea", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $openarea;

    /**
     * @var string
     *
     * @Column(name="teach", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $teach;

    /**
     * @var string
     *
     * @Column(name="productions", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $productions;

    /**
     * @var integer
     *
     * @Column(name="chatcall_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $chatcallUserId;

    /**
     * @var \DateTime
     *
     * @Column(name="chatcall_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $chatcallDate;

    /**
     * @var string
     *
     * @Column(name="chatcall_text", type="string", length=50, precision=0, scale=0, nullable=false, unique=false)
     */
    private $chatcallText;

    /**
     * @var string
     *
     * @Column(name="language", type="string", length=40, precision=0, scale=0, nullable=true, unique=false)
     */
    private $language;

    /**
     * @var \DateTime
     *
     * @Column(name="registration_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $registrationDate;

    /**
     * @var \DateTime
     *
     * @Column(name="expiration_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $expirationDate;

    /**
     * @var boolean
     *
     * @Column(name="active", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $active;

    /**
     * @var string
     *
     * @Column(name="openid", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $openid;

    /**
     * @var string
     *
     * @Column(name="theme", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $theme;

    /**
     * @var integer
     *
     * @Column(name="hr_dept_id", type="smallint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hrDeptId;

    /**
     * @OneToMany(targetEntity="EntityCourseRelUser", mappedBy="user")
     **/
    private $courses;

    /**
     * @OneToMany(targetEntity="EntityCItemProperty", mappedBy="user")
     **/
    private $items;


    /**
     * @OneToMany(targetEntity="EntityUsergroupRelUser", mappedBy="user")
     **/
    private $classes;

    /**
     *
     */
    public function __construct()
    {
        $this->courses = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->classes = new ArrayCollection();
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
        //return $this->lastname .', '. $this->firstname .' ('. $this->email .')';
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
     * @return EntityUser
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
