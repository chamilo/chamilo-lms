<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GenGraphicsMode extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'GenGraphicsMode';

    protected $FullName = 'QuickTime::GenMediaInfo';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Video';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Gen Graphics Mode';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'srcCopy',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'srcOr',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'srcXor',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'srcBic',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'notSrcCopy',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'notSrcOr',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'notSrcXor',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'notSrcBic',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'patCopy',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'patOr',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'patXor',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'patBic',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'notPatCopy',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'notPatOr',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'notPatXor',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'notPatBic',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'blend',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'addPin',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'addOver',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'subPin',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'transparent',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'addMax',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'subOver',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'addMin',
        ),
        49 => array(
            'Id' => 49,
            'Label' => 'grayishTextOr',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'hilite',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'ditherCopy',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Alpha',
        ),
        257 => array(
            'Id' => 257,
            'Label' => 'White Alpha',
        ),
        258 => array(
            'Id' => 258,
            'Label' => 'Pre-multiplied Black Alpha',
        ),
        272 => array(
            'Id' => 272,
            'Label' => 'Component Alpha',
        ),
    );

}
