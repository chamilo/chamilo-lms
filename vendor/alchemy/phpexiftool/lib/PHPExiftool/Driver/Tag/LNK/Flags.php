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
class Flags extends AbstractTag
{

    protected $Id = 20;

    protected $Name = 'Flags';

    protected $FullName = 'LNK::Main';

    protected $GroupName = 'LNK';

    protected $g0 = 'LNK';

    protected $g1 = 'LNK';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Flags';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'IDList',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'LinkInfo',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Description',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'RelativePath',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'WorkingDir',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'CommandArgs',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'IconFile',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Unicode',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'NoLinkInfo',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'ExpString',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'SeparateProc',
        ),
        4096 => array(
            'Id' => 4096,
            'Label' => 'DarwinID',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'RunAsUser',
        ),
        16384 => array(
            'Id' => 16384,
            'Label' => 'ExpIcon',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'NoPidAlias',
        ),
        131072 => array(
            'Id' => 131072,
            'Label' => 'RunWithShim',
        ),
        262144 => array(
            'Id' => 262144,
            'Label' => 'NoLinkTrack',
        ),
        524288 => array(
            'Id' => 524288,
            'Label' => 'TargetMetadata',
        ),
        1048576 => array(
            'Id' => 1048576,
            'Label' => 'NoLinkPathTracking',
        ),
        2097152 => array(
            'Id' => 2097152,
            'Label' => 'NoKnownFolderTracking',
        ),
        4194304 => array(
            'Id' => 4194304,
            'Label' => 'NoKnownFolderAlias',
        ),
        8388608 => array(
            'Id' => 8388608,
            'Label' => 'LinkToLink',
        ),
        16777216 => array(
            'Id' => 16777216,
            'Label' => 'UnaliasOnSave',
        ),
        33554432 => array(
            'Id' => 33554432,
            'Label' => 'PreferEnvPath',
        ),
        67108864 => array(
            'Id' => 67108864,
            'Label' => 'KeepLocalIDList',
        ),
    );

}
