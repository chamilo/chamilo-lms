<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\MediaBundle\Entity;

use Sonata\MediaBundle\Entity\BaseGalleryHasMedia as BaseGalleryHasMedia;

/**
 * Class GalleryHasMedia.
 *
 * @package Chamilo\MediaBundle\Entity
 */
class GalleryHasMedia extends BaseGalleryHasMedia
{
    /**
     * @var int
     */
    protected $id;

    /**
     * Get id.
     *
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }
}
