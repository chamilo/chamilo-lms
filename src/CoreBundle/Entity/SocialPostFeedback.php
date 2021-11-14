<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(
 *     name="social_post_feedback",
 *     indexes={
 *         @Index(name="idx_social_post_uid_spid", columns={"social_post_id", "user_id"})
 *     }
 * )
 * @ORM\Entity()
 */
class SocialPostFeedback
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\SocialPost", inversedBy="feedbacks")
     * @ORM\JoinColumn(name="social_post_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected SocialPost $socialPost;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="socialPostsFeedbacks")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="liked", type="boolean", options={"default":false})
     */
    protected bool $liked;

    /**
     * @ORM\Column(name="disliked", type="boolean", options={"default":false})
     */
    protected bool $disliked;

    /**
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    protected DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSocialPost(): SocialPost
    {
        return $this->socialPost;
    }

    public function setSocialPost(SocialPost $socialPost): self
    {
        $this->socialPost = $socialPost;

        return $this;
    }

    public function isLiked(): bool
    {
        return $this->liked;
    }

    public function setLiked(bool $liked): self
    {
        $this->liked = $liked;

        return $this;
    }

    public function isDisliked(): bool
    {
        return $this->disliked;
    }

    public function setDisliked(bool $disliked): self
    {
        $this->disliked = $disliked;

        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
