<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Doctrine\ORM\Mapping as ORM;

trait EventSubscribableTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="subscription_visibility", type="integer", options={"default": 0})
     */
    protected $subscriptionVisibility = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="subscription_item_id", type="integer", nullable=true)
     */
    protected $subscriptionItemId = null;

    public function getSubscriptionVisibility(): int
    {
        return $this->subscriptionVisibility;
    }

    public function setSubscriptionVisibility(int $subscriptionVisibility): self
    {
        $this->subscriptionVisibility = $subscriptionVisibility;

        return $this;
    }

    public function getSubscriptionItemId(): ?int
    {
        return $this->subscriptionItemId;
    }

    public function setSubscriptionItemId(?int $subscriptionItemId): self
    {
        $this->subscriptionItemId = $subscriptionItemId;

        return $this;
    }
}
