<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFAreaMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFAreaMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Area Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Single Area',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Dynamic Area',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Dynamic Area (closest subject)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Group Dynamic',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Single Area (wide)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Dynamic Area (wide)',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Single Area',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Dynamic Area',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'Dynamic Area (closest subject)',
        ),
        9 => array(
            'Id' => 3,
            'Label' => 'Group Dynamic',
        ),
        10 => array(
            'Id' => 4,
            'Label' => 'Dynamic Area (9 points)',
        ),
        11 => array(
            'Id' => 5,
            'Label' => 'Dynamic Area (21 points)',
        ),
        12 => array(
            'Id' => 6,
            'Label' => 'Dynamic Area (51 points)',
        ),
        13 => array(
            'Id' => 7,
            'Label' => 'Dynamic Area (51 points, 3D-tracking)',
        ),
        14 => array(
            'Id' => 8,
            'Label' => 'Auto-area',
        ),
        15 => array(
            'Id' => 9,
            'Label' => 'Dynamic Area (3D-tracking)',
        ),
        16 => array(
            'Id' => 10,
            'Label' => 'Single Area (wide)',
        ),
        17 => array(
            'Id' => 11,
            'Label' => 'Dynamic Area (wide)',
        ),
        18 => array(
            'Id' => 12,
            'Label' => 'Dynamic Area (wide, 3D-tracking)',
        ),
        19 => array(
            'Id' => 128,
            'Label' => 'Single',
        ),
        20 => array(
            'Id' => 129,
            'Label' => 'Auto (41 points)',
        ),
        21 => array(
            'Id' => 130,
            'Label' => 'Subject Tracking (41 points)',
        ),
        22 => array(
            'Id' => 131,
            'Label' => 'Face Priority (41 points)',
        ),
        23 => array(
            'Id' => 0,
            'Label' => 'Contrast-detect',
        ),
        24 => array(
            'Id' => 1,
            'Label' => 'Contrast-detect (normal area)',
        ),
        25 => array(
            'Id' => 2,
            'Label' => 'Contrast-detect (wide area)',
        ),
        26 => array(
            'Id' => 3,
            'Label' => 'Contrast-detect (face priority)',
        ),
        27 => array(
            'Id' => 4,
            'Label' => 'Contrast-detect (subject tracking)',
        ),
        28 => array(
            'Id' => 128,
            'Label' => 'Single',
        ),
        29 => array(
            'Id' => 129,
            'Label' => 'Auto (41 points)',
        ),
        30 => array(
            'Id' => 130,
            'Label' => 'Subject Tracking (41 points)',
        ),
        31 => array(
            'Id' => 131,
            'Label' => 'Face Priority (41 points)',
        ),
    );

    protected $Index = 'mixed';

}
