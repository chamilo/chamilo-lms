<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class InternalFlash extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'InternalFlash';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Internal Flash';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'TTL',
        ),
        1 => array(
            'Id' => 64,
            'Label' => 'Manual',
        ),
        2 => array(
            'Id' => 128,
            'Label' => 'Repeating Flash',
        ),
        3 => array(
            'Id' => 192,
            'Label' => 'Commander Mode',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'TTL',
        ),
        5 => array(
            'Id' => 16,
            'Label' => 'Manual',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'TTL',
        ),
        7 => array(
            'Id' => 64,
            'Label' => 'Manual',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'TTL',
        ),
        9 => array(
            'Id' => 64,
            'Label' => 'Manual',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'TTL',
        ),
        11 => array(
            'Id' => 64,
            'Label' => 'Manual',
        ),
        12 => array(
            'Id' => 128,
            'Label' => 'Repeating Flash',
        ),
        13 => array(
            'Id' => 192,
            'Label' => 'Commander Mode',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'TTL',
        ),
        15 => array(
            'Id' => 64,
            'Label' => 'Manual',
        ),
        16 => array(
            'Id' => 128,
            'Label' => 'Repeating Flash',
        ),
        17 => array(
            'Id' => 192,
            'Label' => 'Commander Mode',
        ),
    );

}
