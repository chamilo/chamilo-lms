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
class AFAreaModeSetting extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFAreaModeSetting';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Area Mode Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Single Area',
        ),
        1 => array(
            'Id' => 16,
            'Label' => 'Dynamic Area',
        ),
        2 => array(
            'Id' => 32,
            'Label' => 'Closest Subject',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Single Area',
        ),
        4 => array(
            'Id' => 32,
            'Label' => 'Dynamic Area',
        ),
        5 => array(
            'Id' => 64,
            'Label' => 'Auto-area',
        ),
        6 => array(
            'Id' => 96,
            'Label' => '3D-tracking (11 points)',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Single Area',
        ),
        8 => array(
            'Id' => 64,
            'Label' => 'Dynamic Area',
        ),
        9 => array(
            'Id' => 128,
            'Label' => 'Auto-area',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Single Area',
        ),
        11 => array(
            'Id' => 32,
            'Label' => 'Dynamic Area',
        ),
        12 => array(
            'Id' => 64,
            'Label' => 'Auto-area',
        ),
        13 => array(
            'Id' => 96,
            'Label' => '3D-tracking (11 points)',
        ),
    );

}
