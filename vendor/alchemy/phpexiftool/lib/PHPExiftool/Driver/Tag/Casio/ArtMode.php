<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ArtMode extends AbstractTag
{

    protected $Id = 12315;

    protected $Name = 'ArtMode';

    protected $FullName = 'Casio::Type2';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Art Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Silent Movie',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'HDR',
        ),
        45 => array(
            'Id' => 45,
            'Label' => 'Premium Auto',
        ),
        47 => array(
            'Id' => 47,
            'Label' => 'Painting',
        ),
        49 => array(
            'Id' => 49,
            'Label' => 'Crayon Drawing',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'Panorama',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Art HDR',
        ),
        62 => array(
            'Id' => 62,
            'Label' => 'High Speed Night Shot',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Monochrome',
        ),
        67 => array(
            'Id' => 67,
            'Label' => 'Toy Camera',
        ),
        68 => array(
            'Id' => 68,
            'Label' => 'Pop Art',
        ),
        69 => array(
            'Id' => 69,
            'Label' => 'Light Tone',
        ),
    );

}
