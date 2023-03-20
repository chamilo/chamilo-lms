<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Traits;

use ApiPlatform\Core\Annotation\ApiProperty;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

trait TimestampableTypedEntity
{
    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    #[ApiProperty]
    #[Groups(['timestampable_created:read'])]
    protected DateTime $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    #[ApiProperty]
    #[Groups(['timestampable_updated:read'])]
    protected DateTime $updatedAt;

    /**
     * Sets createdAt.
     *
     * @return $this
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Returns createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets updatedAt.
     *
     * @return $this
     */
    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Returns updatedAt.
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
