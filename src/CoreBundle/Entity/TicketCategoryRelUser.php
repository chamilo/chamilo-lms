<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CategoryRelUser.
 *
 * @ORM\Table(name="ticket_category_rel_user")
 * @ORM\Entity
 */
class TicketCategoryRelUser
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\TicketCategory")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    protected TicketCategory $category;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected TicketCategory $user;
}
