<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseModule
 *
 * @ORM\Table(name="course_module")
 * @ORM\Entity
 */
class CourseModule
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255, nullable=false)
     */
    private $link;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=100, nullable=true)
     */
    private $image;

    /**
     * @var integer
     *
     * @ORM\Column(name="row_module", type="integer", nullable=false)
     */
    private $rowModule;

    /**
     * @var integer
     *
     * @ORM\Column(name="column_module", type="integer", nullable=false)
     */
    private $columnModule;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", length=20, nullable=false)
     */
    private $position;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
