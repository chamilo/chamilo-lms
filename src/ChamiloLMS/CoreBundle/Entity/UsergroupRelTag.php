<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsergroupRelTag
 *
 * @ORM\Table(name="usergroup_rel_tag", indexes={@ORM\Index(name="usergroup_rel_tag_usergroup_id", columns={"usergroup_id"}), @ORM\Index(name="usergroup_rel_tag_tag_id", columns={"tag_id"})})
 * @ORM\Entity
 */
class UsergroupRelTag
{
    /**
     * @var integer
     *
     * @ORM\Column(name="tag_id", type="integer", nullable=false)
     */
    private $tagId;

    /**
     * @var integer
     *
     * @ORM\Column(name="usergroup_id", type="integer", nullable=false)
     */
    private $usergroupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
