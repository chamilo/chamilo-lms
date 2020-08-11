<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\LNK;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FileAttributes extends AbstractTag
{

    protected $Id = 24;

    protected $Name = 'FileAttributes';

    protected $FullName = 'LNK::Main';

    protected $GroupName = 'LNK';

    protected $g0 = 'LNK';

    protected $g1 = 'LNK';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'File Attributes';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Read-only',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Hidden',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'System',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Volume',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Directory',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Archive',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Encrypted?',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Normal',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Temporary',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Sparse',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Reparse point',
        ),
        2048 => array(
            'Id' => 2048,
            'Label' => 'Compressed',
        ),
        4096 => array(
            'Id' => 4096,
            'Label' => 'Offline',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'Not indexed',
        ),
        16384 => array(
            'Id' => 16384,
            'Label' => 'Encrypted',
        ),
    );

}
