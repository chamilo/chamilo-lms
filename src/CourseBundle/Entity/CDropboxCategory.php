<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CourseBundle\Repository\CDropboxCategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CDropboxCategory.
 */
#[ORM\Table(name: 'c_dropbox_category')]
#[ORM\Index(columns: ['c_id'], name: 'course')]
#[ORM\Index(columns: ['session_id'], name: 'session_id')]
#[ORM\Entity(repositoryClass: CDropboxCategoryRepository::class)]
class CDropboxCategory
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\Column(name: 'c_id', type: 'integer')]
    protected int $cId;

    #[ORM\Column(name: 'cat_id', type: 'integer')]
    protected int $catId;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'text', nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'received', type: 'boolean', nullable: false)]
    protected bool $received;

    #[ORM\Column(name: 'sent', type: 'boolean', nullable: false)]
    protected bool $sent;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: false)]
    protected int $userId;

    #[ORM\Column(name: 'session_id', type: 'integer', nullable: false)]
    protected int $sessionId;

    public function getIid(): ?int
    {
        return $this->iid;
    }

    /**
     * Set title.
     *
     * @return CDropboxCategory
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set received.
     *
     * @return CDropboxCategory
     */
    public function setReceived(bool $received)
    {
        $this->received = $received;

        return $this;
    }

    /**
     * Get received.
     *
     * @return bool
     */
    public function getReceived()
    {
        return $this->received;
    }

    /**
     * Set sent.
     *
     * @return CDropboxCategory
     */
    public function setSent(bool $sent)
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * Get sent.
     *
     * @return bool
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Set userId.
     *
     * @return CDropboxCategory
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;

        return $this;
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
     * Set sessionId.
     *
     * @return CDropboxCategory
     */
    public function setSessionId(int $sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set catId.
     *
     * @return CDropboxCategory
     */
    public function setCatId(int $catId)
    {
        $this->catId = $catId;

        return $this;
    }

    /**
     * Get catId.
     *
     * @return int
     */
    public function getCatId()
    {
        return $this->catId;
    }

    /**
     * Set cId.
     *
     * @return CDropboxCategory
     */
    public function setCId(int $cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }
}
