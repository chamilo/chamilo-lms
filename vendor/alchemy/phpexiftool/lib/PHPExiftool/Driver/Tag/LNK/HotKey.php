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
class HotKey extends AbstractTag
{

    protected $Id = 64;

    protected $Name = 'HotKey';

    protected $FullName = 'LNK::Main';

    protected $GroupName = 'LNK';

    protected $g0 = 'LNK';

    protected $g1 = 'LNK';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Hot Key';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        144 => array(
            'Id' => 144,
            'Label' => 'Num Lock',
        ),
        145 => array(
            'Id' => 145,
            'Label' => 'Scroll Lock',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Shift',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Control',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Alt',
        ),
        '0x30\'-\'0x39' => array(
            'Id' => '0x30\'-\'0x39',
            'Label' => '0-9',
        ),
        '0x41\'-\'0x5a' => array(
            'Id' => '0x41\'-\'0x5a',
            'Label' => 'A-Z',
        ),
        '0x70\'-\'0x87' => array(
            'Id' => '0x70\'-\'0x87',
            'Label' => 'F1-F24',
        ),
    );

}
