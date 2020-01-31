<?php

namespace PhpCoveralls\Bundle\CoverallsBundle\Entity\Git;

use PhpCoveralls\Bundle\CoverallsBundle\Entity\Coveralls;

/**
 * Commit info.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class Commit extends Coveralls
{
    /**
     * Commit ID.
     *
     * @var null|string
     */
    protected $id;

    /**
     * Author name.
     *
     * @var null|string
     */
    protected $authorName;

    /**
     * Author email.
     *
     * @var null|string
     */
    protected $authorEmail;

    /**
     * Committer name.
     *
     * @var null|string
     */
    protected $committerName;

    /**
     * Committer email.
     *
     * @var null|string
     */
    protected $committerEmail;

    /**
     * Commit message.
     *
     * @var null|string
     */
    protected $message;

    // API

    /**
     * {@inheritdoc}
     *
     * @see \PhpCoveralls\Bundle\CoverallsBundle\Entity\ArrayConvertable::toArray()
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'author_name' => $this->authorName,
            'author_email' => $this->authorEmail,
            'committer_name' => $this->committerName,
            'committer_email' => $this->committerEmail,
            'message' => $this->message,
        ];
    }

    // accessor

    /**
     * Set commit ID.
     *
     * @param string $id
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Commit
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Return commit ID.
     *
     * @return null|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set author name.
     *
     * @param string $authorName
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Commit
     */
    public function setAuthorName($authorName)
    {
        $this->authorName = $authorName;

        return $this;
    }

    /**
     * Return author name.
     *
     * @return null|string
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * Set author email.
     *
     * @param string $authorEmail
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Commit
     */
    public function setAuthorEmail($authorEmail)
    {
        $this->authorEmail = $authorEmail;

        return $this;
    }

    /**
     * Return author email.
     *
     * @return null|string
     */
    public function getAuthorEmail()
    {
        return $this->authorEmail;
    }

    /**
     * Set committer name.
     *
     * @param string $committerName
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Commit
     */
    public function setCommitterName($committerName)
    {
        $this->committerName = $committerName;

        return $this;
    }

    /**
     * Return committer name.
     *
     * @return null|string
     */
    public function getCommitterName()
    {
        return $this->committerName;
    }

    /**
     * Set committer email.
     *
     * @param string $committerEmail
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Commit
     */
    public function setCommitterEmail($committerEmail)
    {
        $this->committerEmail = $committerEmail;

        return $this;
    }

    /**
     * Return committer email.
     *
     * @return null|string
     */
    public function getCommitterEmail()
    {
        return $this->committerEmail;
    }

    /**
     * Set commit message.
     *
     * @param string $message
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Commit
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Return commit message.
     *
     * @return null|string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
