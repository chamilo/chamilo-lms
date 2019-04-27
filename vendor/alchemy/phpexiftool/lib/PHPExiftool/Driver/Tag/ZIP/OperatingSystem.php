<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ZIP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class OperatingSystem extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'OperatingSystem';

    protected $FullName = 'mixed';

    protected $GroupName = 'ZIP';

    protected $g0 = 'ZIP';

    protected $g1 = 'ZIP';

    protected $g2 = 'Other';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Operating System';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'FAT filesystem (MS-DOS, OS/2, NT/Win32)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Amiga',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'VMS (or OpenVMS)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Unix',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'VM/CMS',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Atari TOS',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'HPFS filesystem (OS/2, NT)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Macintosh',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Z-System',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'CP/M',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'TOPS-20',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'NTFS filesystem (NT)',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'QDOS',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Acorn RISCOS',
        ),
        14 => array(
            'Id' => 255,
            'Label' => 'unknown',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'MS-DOS',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 'OS/2',
        ),
        17 => array(
            'Id' => 2,
            'Label' => 'Win32',
        ),
        18 => array(
            'Id' => 3,
            'Label' => 'Unix',
        ),
    );

}
