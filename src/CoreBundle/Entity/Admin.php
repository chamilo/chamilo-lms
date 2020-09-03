<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;

/**
 * Admin.
 * @ApiResource(
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      iri="http://schema.org/Person",
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 * )
 *
 * @ApiFilter(SearchFilter::class,
 *         properties={
 *             "user_id": "partial",
 *         }
 *     )
 * @ORM\Table(name="admin", uniqueConstraints={@ORM\UniqueConstraint(name="user_id", columns={"user_id"})})
 * @ORM\Entity
 */
class Admin
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\OneToOne (
     *     targetEntity="Chamilo\CoreBundle\Entity\User",
     *     inversedBy="admin"
     * )
     * @ORM\JoinColumn(
     *     name="user_id",
     *     referencedColumnName="id",
     *     onDelete="CASCADE")
     */
    protected $user;

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     * @deprecated Use user
     */
    protected $userId;

    /**
     * Set userId.
     * @param int $userId
     *
     * @return Admin
     * @deprecated Use setUser
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     * @deprecated Use getUser
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
