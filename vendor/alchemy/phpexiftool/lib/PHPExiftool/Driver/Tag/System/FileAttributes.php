<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\System;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FileAttributes extends AbstractTag
{

    protected $Id = 'FileAttributes';

    protected $Name = 'FileAttributes';

    protected $FullName = 'Extra';

    protected $GroupName = 'System';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'File Attributes';

    protected $local_g1 = 'System';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        4096 => array(
            'Id' => 4096,
            'Label' => 'FIFO',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'Character',
        ),
        12288 => array(
            'Id' => 12288,
            'Label' => 'Mux Character',
        ),
        16384 => array(
            'Id' => 16384,
            'Label' => 'Directory',
        ),
        20480 => array(
            'Id' => 20480,
            'Label' => 'XENIX Named',
        ),
        24576 => array(
            'Id' => 24576,
            'Label' => 'Block',
        ),
        28672 => array(
            'Id' => 28672,
            'Label' => 'Mux Block',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'Regular',
        ),
        36864 => array(
            'Id' => 36864,
            'Label' => 'VxFS Compressed',
        ),
        40960 => array(
            'Id' => 40960,
            'Label' => 'Symbolic Link',
        ),
        45056 => array(
            'Id' => 45056,
            'Label' => 'Solaris Shadow Inode',
        ),
        49152 => array(
            'Id' => 49152,
            'Label' => 'Socket',
        ),
        53248 => array(
            'Id' => 53248,
            'Label' => 'Solaris Door',
        ),
        57344 => array(
            'Id' => 57344,
            'Label' => 'BSD Whiteout',
        ),
    );

}
