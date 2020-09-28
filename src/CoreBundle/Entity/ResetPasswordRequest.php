<?php

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;

/**
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\ResetPasswordRequestRepository")
 */
class ResetPasswordRequest implements ResetPasswordRequestInterface
{
    use UserTrait;
    use ResetPasswordRequestTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     */
    protected $user;

    public function __construct(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken)
    {
        $this->user = $user;
        $this->initialize($expiresAt, $selector, $hashedToken);
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ResetPasswordRequest
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
