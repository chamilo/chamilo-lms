<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FlashPix;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SpecialEffectsOpticalFilter extends AbstractTag
{

    protected $Id = 620757009;

    protected $Name = 'SpecialEffectsOpticalFilter';

    protected $FullName = 'FlashPix::ImageInfo';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Special Effects Optical Filter';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'None',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Colored',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Diffusion',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Multi-image',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Polarizing',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Split-field',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Star',
        ),
    );

}
