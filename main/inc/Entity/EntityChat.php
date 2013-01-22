<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityChat
 *
 * @Table(name="chat")
 * @Entity
 */
class EntityChat
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="from_user", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fromUser;

    /**
     * @var integer
     *
     * @Column(name="to_user", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $toUser;

    /**
     * @var string
     *
     * @Column(name="message", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $message;

    /**
     * @var \DateTime
     *
     * @Column(name="sent", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sent;

    /**
     * @var integer
     *
     * @Column(name="recd", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $recd;


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
     * Set fromUser
     *
     * @param integer $fromUser
     * @return EntityChat
     */
    public function setFromUser($fromUser)
    {
        $this->fromUser = $fromUser;

        return $this;
    }

    /**
     * Get fromUser
     *
     * @return integer 
     */
    public function getFromUser()
    {
        return $this->fromUser;
    }

    /**
     * Set toUser
     *
     * @param integer $toUser
     * @return EntityChat
     */
    public function setToUser($toUser)
    {
        $this->toUser = $toUser;

        return $this;
    }

    /**
     * Get toUser
     *
     * @return integer 
     */
    public function getToUser()
    {
        return $this->toUser;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return EntityChat
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set sent
     *
     * @param \DateTime $sent
     * @return EntityChat
     */
    public function setSent($sent)
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * Get sent
     *
     * @return \DateTime 
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Set recd
     *
     * @param integer $recd
     * @return EntityChat
     */
    public function setRecd($recd)
    {
        $this->recd = $recd;

        return $this;
    }

    /**
     * Get recd
     *
     * @return integer 
     */
    public function getRecd()
    {
        return $this->recd;
    }
}
