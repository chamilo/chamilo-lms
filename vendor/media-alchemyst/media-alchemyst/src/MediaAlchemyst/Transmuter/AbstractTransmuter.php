<?php

/*
 * This file is part of Media-Alchemyst.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaAlchemyst\Transmuter;

use Imagine\Image\Box;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\SpecificationInterface;
use MediaAlchemyst\Exception\InvalidArgumentException;
use MediaVorus\Media\MediaInterface;
use Neutron\TemporaryFilesystem\Manager;
use Pimple;

abstract class AbstractTransmuter
{
    /**
     *
     * @var Pimple
     */
    protected $container;
    /** @var Manager */
    protected $tmpFileManager;

    const TMP_FILE_SCOPE = '_media_alchemyst_';

    public function __construct(Pimple $container, Manager $manager)
    {
        $this->container = $container;
        $this->tmpFileManager = $manager;
    }

    public function __destruct()
    {
        $this->container = null;
    }

    /**
     * Return the box for a spec
     *
     * @param Specification\Image $spec
     * @param integer             $width
     * @param integer             $height
     *
     * @return \Image\Box
     */
    protected function boxFromSize(Image $spec, $width, $height)
    {
        if ( ! $spec->getWidth() && ! $spec->getHeight()) {
            throw new InvalidArgumentException('The specification you provide must have width and height');
        }

        if ($spec->getWidth() >= $width && $spec->getHeight() >= $height) {
            return null;
        }

        if ($spec->getResizeMode() == Image::RESIZE_MODE_INBOUND_FIXEDRATIO) {

            $ratioOut = $spec->getWidth() / $spec->getHeight();
            $ratioIn = $width / $height;

            if ($ratioOut > $ratioIn) {

                $outHeight = round($spec->getHeight());
                $outWidth = round($ratioIn * $outHeight);
            } else {

                $outWidth = round($spec->getWidth());
                $outHeight = round($outWidth / $ratioIn);
            }

            return new Box($outWidth, $outHeight);
        }

        return new Box($spec->getWidth(), $spec->getHeight());
    }

    abstract public function execute(SpecificationInterface $spec, MediaInterface $source, $dest);
}
