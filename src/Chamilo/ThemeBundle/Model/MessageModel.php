<?php
/**
 * MessageModel.php
 * avanzu-admin
 * Date: 23.02.14.
 */

namespace Chamilo\ThemeBundle\Model;

/**
 * Simple implementation of the MessageInterface.
 */
class MessageModel implements MessageInterface
{
    /**
     * Holds the sender.
     *
     * @var UserInterface
     */
    protected $from;

    /**
     * holds the Recipient.
     *
     * @var UserInterface
     */
    protected $to;

    /**
     * holds the date sent.
     *
     * @var \DateTime
     */
    protected $sentAt;

    /**
     * holds the subject.
     *
     * @var string
     */
    protected $subject;

    /**
     * Creates a new MessageModel object with the given values.
     *
     * SentAt will be set to the current DateTime when null is given.
     *
     * @param UserInterface $from
     * @param string        $subject
     * @param null          $sentAt
     * @param UserInterface $to
     */
    public function __construct(UserInterface $from = null, $subject = '', $sentAt = null, UserInterface $to = null)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->sentAt = $sentAt ?: new \DateTime();
        $this->from = $from;
    }

    /**
     * Set the sender.
     *
     * @param \Chamilo\ThemeBundle\Model\UserInterface $from
     *
     * @return $this
     */
    public function setFrom(UserInterface $from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get the Sender.
     *
     * @return \Chamilo\ThemeBundle\Model\UserInterface
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set the date sent.
     *
     * @return $this
     */
    public function setSentAt(\DateTime $sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * Get the date sent.
     *
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set the subject.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get the subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set the recipient.
     *
     * @param \Chamilo\ThemeBundle\Model\UserInterface $to
     *
     * @return $this
     */
    public function setTo(UserInterface $to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get the recipient.
     *
     * @return \Chamilo\ThemeBundle\Model\UserInterface
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Get the identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getSubject();
    }
}
