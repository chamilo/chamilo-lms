<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\EXE;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FileOS extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'FileOS';

    protected $FullName = 'EXE::PEVersion';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'File OS';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Win16',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'PM-16',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'PM-32',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Win32',
        ),
        65536 => array(
            'Id' => 65536,
            'Label' => 'DOS',
        ),
        65537 => array(
            'Id' => 65537,
            'Label' => 'Windows 16-bit',
        ),
        65540 => array(
            'Id' => 65540,
            'Label' => 'Windows 32-bit',
        ),
        131072 => array(
            'Id' => 131072,
            'Label' => 'OS/2 16-bit',
        ),
        131074 => array(
            'Id' => 131074,
            'Label' => 'OS/2 16-bit PM-16',
        ),
        196608 => array(
            'Id' => 196608,
            'Label' => 'OS/2 32-bit',
        ),
        196611 => array(
            'Id' => 196611,
            'Label' => 'OS/2 32-bit PM-32',
        ),
        262144 => array(
            'Id' => 262144,
            'Label' => 'Windows NT',
        ),
        262148 => array(
            'Id' => 262148,
            'Label' => 'Windows NT 32-bit',
        ),
    );

}
