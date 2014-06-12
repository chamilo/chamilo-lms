<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExtLogEntries
 *
 * @ORM\Table(name="ext_log_entries")
 * @ORM\Entity
 */
class ExtLogEntries
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $action;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="logged_at", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $loggedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="object_id", type="string", length=64, precision=0, scale=0, nullable=true, unique=false)
     */
    private $objectId;

    /**
     * @var string
     *
     * @ORM\Column(name="object_class", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $objectClass;

    /**
     * @var integer
     *
     * @ORM\Column(name="version", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $data;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $username;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set action
     *
     * @param string $action
     * @return ExtLogEntries
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string 
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set loggedAt
     *
     * @param \DateTime $loggedAt
     * @return ExtLogEntries
     */
    public function setLoggedAt($loggedAt)
    {
        $this->loggedAt = $loggedAt;

        return $this;
    }

    /**
     * Get loggedAt
     *
     * @return \DateTime 
     */
    public function getLoggedAt()
    {
        return $this->loggedAt;
    }

    /**
     * Set objectId
     *
     * @param string $objectId
     * @return ExtLogEntries
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * Get objectId
     *
     * @return string 
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set objectClass
     *
     * @param string $objectClass
     * @return ExtLogEntries
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = $objectClass;

        return $this;
    }

    /**
     * Get objectClass
     *
     * @return string 
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return ExtLogEntries
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return integer 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set data
     *
     * @param string $data
     * @return ExtLogEntries
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string 
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return ExtLogEntries
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
}
