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
class MultiSelectorShootMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MultiSelectorShootMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Multi Selector Shoot Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Select Center Focus Point',
        ),
        1 => array(
            'Id' => 64,
            'Label' => 'Highlight Active Focus Point',
        ),
        2 => array(
            'Id' => 128,
            'Label' => 'Not Used',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Select Center Focus Point (Reset)',
        ),
        4 => array(
            'Id' => 128,
            'Label' => 'Preset Focus Point (Pre)',
        ),
        5 => array(
            'Id' => 192,
            'Label' => 'Not Used (None)',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Select Center Focus Point',
        ),
        7 => array(
            'Id' => 64,
            'Label' => 'Highlight Active Focus Point',
        ),
        8 => array(
            'Id' => 128,
            'Label' => 'Not Used',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Select Center Focus Point (Reset)',
        ),
        10 => array(
            'Id' => 64,
            'Label' => 'Highlight Active Focus Point',
        ),
        11 => array(
            'Id' => 128,
            'Label' => 'Preset Focus Point (Pre)',
        ),
        12 => array(
            'Id' => 192,
            'Label' => 'Not Used (None)',
        ),
    );

}
