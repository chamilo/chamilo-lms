<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="attempt_file",
 * )
 * @ORM\Entity
 */
class AttemptFile
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid")
     */
    protected Uuid $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\TrackEAttempt", inversedBy="files")
     * @ORM\JoinColumn(name="attempt_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[Assert\NotNull]
    protected TrackEAttempt $attempt;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Asset", cascade={"remove"} )
     * @ORM\JoinColumn(name="asset_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?Asset $asset;

    /**
     * @ORM\Column(name="comment", type="text", nullable=false)
     */
    protected string $comment;

    public function __construct()
    {
        $this->comment = '';
    }
}
