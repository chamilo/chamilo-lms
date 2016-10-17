<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IPTC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorRepresentation extends AbstractTag
{

    protected $Id = 60;

    protected $Name = 'ColorRepresentation';

    protected $FullName = 'IPTC::NewsPhoto';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Color Representation';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Image, Single Frame',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Monochrome, Single Frame',
        ),
        768 => array(
            'Id' => 768,
            'Label' => '3 Components, Single Frame',
        ),
        769 => array(
            'Id' => 769,
            'Label' => '3 Components, Frame Sequential in Multiple Objects',
        ),
        770 => array(
            'Id' => 770,
            'Label' => '3 Components, Frame Sequential in One Object',
        ),
        771 => array(
            'Id' => 771,
            'Label' => '3 Components, Line Sequential',
        ),
        772 => array(
            'Id' => 772,
            'Label' => '3 Components, Pixel Sequential',
        ),
        773 => array(
            'Id' => 773,
            'Label' => '3 Components, Special Interleaving',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => '4 Components, Single Frame',
        ),
        1025 => array(
            'Id' => 1025,
            'Label' => '4 Components, Frame Sequential in Multiple Objects',
        ),
        1026 => array(
            'Id' => 1026,
            'Label' => '4 Components, Frame Sequential in One Object',
        ),
        1027 => array(
            'Id' => 1027,
            'Label' => '4 Components, Line Sequential',
        ),
        1028 => array(
            'Id' => 1028,
            'Label' => '4 Components, Pixel Sequential',
        ),
        1029 => array(
            'Id' => 1029,
            'Label' => '4 Components, Special Interleaving',
        ),
    );

}
