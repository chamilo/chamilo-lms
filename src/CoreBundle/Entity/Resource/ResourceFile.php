<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="resource_file")
 */
class ResourceFile
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column()
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="hash", type="string", nullable=false)
     */
    protected $hash;

    /**
     * @Assert\NotBlank()
     * @var string
     *
     * @ORM\Column(name="original_filename", type="string", nullable=false)
     */
    protected $originalFilename;

    /**
     * @Assert\NotBlank()
     * @var string
     *
     * @ORM\Column(name="size", type="string", nullable=false)
     */
    protected $size;

    /**
     * @Assert\NotBlank()
     * @var string
     *
     * @ORM\Column(name="width", type="string", nullable=true)
     */
    protected $width;

    /**
     * @Assert\NotBlank()
     * @var string
     *
     * @ORM\Column(name="height", type="string", nullable=true)
     */
    protected $height;

    /**
     * @var string
     *
     * @ORM\Column(name="copyright", type="string", nullable=true)
     */
    protected $copyright;

    /**
     * @var string
     *
     * @ORM\Column(name="contentType", type="string", nullable=true)
     */
    protected $contentType;

    /**
     * @var string
     *
     * @ORM\Column(name="extension", type="string", nullable=false)
     */
    protected $extension;

    /**
     * @var ResourceNode
     *
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceNode", mappedBy="resourceFile")
     */
    protected $resourceNode;

    /**
     * @var string
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    protected $enabled;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }
}
