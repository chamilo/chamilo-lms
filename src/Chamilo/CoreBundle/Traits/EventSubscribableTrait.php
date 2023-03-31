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
     * @ORM\Column(name="max_subscriptions", type="integer", options={"default": 0})
     */
    protected $maxSubscriptions = 0;

    public function getSubscriptionVisibility(): int
    {
        return $this->subscriptionVisibility;
    }

    public function setSubscriptionVisibility(int $subscriptionVisibility): self
    {
        $this->subscriptionVisibility = $subscriptionVisibility;

        return $this;
    }

    public function getMaxSubscriptions(): int
    {
        return $this->maxSubscriptions;
    }

    public function setMaxSubscriptions(int $maxSubscriptions): self
    {
        $this->maxSubscriptions = $maxSubscriptions;

        return $this;
    }
}
