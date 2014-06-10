<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseCategory
 *
 * @ORM\Table(name="course_category", uniqueConstraints={@ORM\UniqueConstraint(name="code", columns={"code"})}, indexes={@ORM\Index(name="parent_id", columns={"parent_id"}), @ORM\Index(name="tree_pos", columns={"tree_pos"})})
 * @ORM\Entity
 */
class CourseCategory
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=40, nullable=false)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="parent_id", type="string", length=40, nullable=true)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="tree_pos", type="integer", nullable=true)
     */
    private $treePos;

    /**
     * @var integer
     *
     * @ORM\Column(name="children_count", type="smallint", nullable=true)
     */
    private $childrenCount;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_course_child", type="string", length=100, nullable=true)
     */
    private $authCourseChild;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_cat_child", type="string", length=100, nullable=true)
     */
    private $authCatChild;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
