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
class FileFlags extends AbstractTag
{

    protected $Id = 7;

    protected $Name = 'FileFlags';

    protected $FullName = 'EXE::PEVersion';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'File Flags';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Debug',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Pre-release',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Patched',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Private build',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Info inferred',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Special build',
        ),
    );

}
