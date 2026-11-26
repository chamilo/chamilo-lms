<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\TrackELoginRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackELogin.
 */
#[ORM\Table(name: 'track_e_login')]
#[ORM\Index(name: 'login_user_id', columns: ['login_user_id'])]
#[ORM\Index(name: 'idx_track_e_login_date', columns: ['login_date'])]
#[ORM\Entity(repositoryClass: TrackELoginRepository::class)]
class TrackELogin
{
    #[ORM\Column(name: 'login_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $loginId;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'logins')]
    #[ORM\JoinColumn(name: 'login_user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Column(name: 'login_date', type: 'datetime', nullable: false)]
    protected DateTime $loginDate;

    #[ORM\Column(name: 'user_ip', type: 'string', length: 45, nullable: false)]
    protected string $userIp;

    #[ORM\Column(name: 'logout_date', type: 'datetime', nullable: true)]
    protected ?DateTime $logoutDate = null;

    /**
     * Access URL where the connection happened. Null for connections recorded before the column
     * existed on a platform with several URLs: they cannot be attributed.
     */
    #[ORM\Column(name: 'access_url_id', type: 'integer', nullable: true)]
    protected ?int $accessUrlId = null;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get loginDate.
     *
     * @return DateTime
     */
    public function getLoginDate()
    {
        return $this->loginDate;
    }

    public function setLoginDate(DateTime $loginDate): self
    {
        $this->loginDate = $loginDate;

        return $this;
    }

    /**
     * Get userIp.
     *
     * @return string
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    public function setUserIp(string $userIp): self
    {
        $this->userIp = $userIp;

        return $this;
    }

    public function getLogoutDate(): ?DateTime
    {
        return $this->logoutDate;
    }

    public function setLogoutDate(DateTime $logoutDate): self
    {
        $this->logoutDate = $logoutDate;

        return $this;
    }

    public function getAccessUrlId(): ?int
    {
        return $this->accessUrlId;
    }

    public function setAccessUrlId(?int $accessUrlId): self
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get loginId.
     *
     * @return int
     */
    public function getLoginId()
    {
        return $this->loginId;
    }
}
