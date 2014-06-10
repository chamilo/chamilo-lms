<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsergroupRelUsergroup
 *
 * @ORM\Table(name="usergroup_rel_usergroup", indexes={@ORM\Index(name="usergroup_rel_usergroup_group_id", columns={"group_id"}), @ORM\Index(name="usergroup_rel_usergroup_subgroup_id", columns={"subgroup_id"}), @ORM\Index(name="usergroup_rel_usergroup_relation_type", columns={"relation_type"})})
 * @ORM\Entity
 */
class UsergroupRelUsergroup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="integer", nullable=false)
     */
    private $groupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="subgroup_id", type="integer", nullable=false)
     */
    private $subgroupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    private $relationType;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
