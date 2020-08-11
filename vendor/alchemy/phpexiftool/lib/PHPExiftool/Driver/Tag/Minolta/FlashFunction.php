<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FlashFunction extends AbstractTag
{

    protected $Id = 49;

    protected $Name = 'FlashFunction';

    protected $FullName = 'Minolta::WBInfoA100';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Flash Function';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No flash',
        ),
        768 => array(
            'Id' => 768,
            'Label' => 'Built-in flash',
        ),
        4613 => array(
            'Id' => 4613,
            'Label' => 'Manual',
        ),
        4622 => array(
            'Id' => 4622,
            'Label' => 'Strobe',
        ),
        4750 => array(
            'Id' => 4750,
            'Label' => 'Fill flash, Pre-flash TTL',
        ),
        4782 => array(
            'Id' => 4782,
            'Label' => 'Bounce flash',
        ),
        5134 => array(
            'Id' => 5134,
            'Label' => 'Rear sync, ADI',
        ),
        5262 => array(
            'Id' => 5262,
            'Label' => 'Fill flash, ADI',
        ),
        5504 => array(
            'Id' => 5504,
            'Label' => 'Wireless',
        ),
        6030 => array(
            'Id' => 6030,
            'Label' => 'HSS',
        ),
    );

}
