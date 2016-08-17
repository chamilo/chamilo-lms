<?php

/*
 * This file is part of Media-Alchemyst.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaAlchemyst\Specification;

use MediaAlchemyst\Exception\InvalidArgumentException;
use Imagine\Image\ImageInterface;

class Image extends AbstractSpecification
{
    protected $width;
    protected $height;
    protected $quality = 75;
    protected $resizeMode = self::RESIZE_MODE_INBOUND_FIXEDRATIO;
    protected $rotationAngle;
    protected $strip;
    protected $resolution_x = 72;
    protected $resolution_y = 72;
    protected $resolution_units = self::RESOLUTION_PIXELPERINCH;
    protected $flatten = false;
    protected $imageCodec = 'jpeg';

    const RESIZE_MODE_INBOUND = ImageInterface::THUMBNAIL_INSET;
    const RESIZE_MODE_INBOUND_FIXEDRATIO = 'inset_fixedRatio';
    const RESIZE_MODE_OUTBOUND = ImageInterface::THUMBNAIL_OUTBOUND;
    const RESOLUTION_PIXELPERINCH = 'ppi';
    const RESOLUTION_PIXELPERCENTIMETER = 'ppc';

    public function getType()
    {
        return self::TYPE_IMAGE;
    }

    public function setImageCodec($imageCodec)
    {
        $this->imageCodec = $imageCodec;
    }

    public function getImageCodec()
    {
        return $this->imageCodec;
    }

    public function setDimensions($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setResizeMode($mode)
    {
        if ( ! in_array($mode, array(self::RESIZE_MODE_INBOUND, self::RESIZE_MODE_OUTBOUND, self::RESIZE_MODE_INBOUND_FIXEDRATIO))) {
            throw new InvalidArgumentException('Invalid resize mode');
        }

        $this->resizeMode = $mode;
    }

    public function getResizeMode()
    {
        return $this->resizeMode;
    }

    public function setResolution($resolution_x, $resolution_y, $units = self::RESOLUTION_PIXELPERINCH)
    {
        if ($resolution_x <= 0 || $resolution_y <= 0) {
            throw new InvalidArgumentException('Resolution should be greater than 0');
        }
        if ( ! in_array($units, array(self::RESOLUTION_PIXELPERCENTIMETER, self::RESOLUTION_PIXELPERINCH))) {
            throw new InvalidArgumentException('Unknown resolution units');
        }

        $this->resolution_units = $units;
        $this->resolution_x = $resolution_x;
        $this->resolution_y = $resolution_y;

        return $this;
    }

    public function getResolutionUnit()
    {
        return $this->resolution_units;
    }

    public function getResolutionX()
    {
        return $this->resolution_x;
    }

    public function getResolutionY()
    {
        return $this->resolution_y;
    }

    public function setQuality($quality)
    {
        if ($quality < 0 || $quality > 100) {
            throw new InvalidArgumentException('Invalid quality value');
        }

        $this->quality = (int) $quality;
    }

    public function getQuality()
    {
        return $this->quality;
    }

    public function setRotationAngle($angle)
    {
        $this->rotationAngle = $angle;
    }

    public function getRotationAngle()
    {
        return $this->rotationAngle;
    }

    public function setStrip($boolean)
    {
        $this->strip = $boolean;
    }

    public function getStrip()
    {
        return $this->strip;
    }

    public function setFlatten($boolean)
    {
        $this->flatten = (Boolean) $boolean;
    }

    public function isFlatten()
    {
        return $this->flatten;
    }
}
