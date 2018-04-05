<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\TicketBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CategoryRelUser.
 *
 * @ORM\Table(name="ticket_category_rel_user")
 * @ORM\Entity
 */
class CategoryRelUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\TicketBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    protected $category;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
}
