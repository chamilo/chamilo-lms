<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\MediaBundle\Entity;

use Sonata\MediaBundle\Entity\BaseGallery;
use Sonata\MediaBundle\Model\GalleryHasMediaInterface;

/**
 * Class Gallery.
 */
class Gallery extends BaseGallery
{
    /**
     * @var int
     */
    protected $id;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function addGalleryHasMedia(GalleryHasMediaInterface $galleryHasMedia)
    {
    }

    public function removeGalleryHasMedia(GalleryHasMediaInterface $galleryHasMedia)
    {
    }
}
