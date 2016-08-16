<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MXF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ChannelStatusMode extends AbstractTag
{

    protected $Id = '060e2b34.0101.0105.04020501.02000000';

    protected $Name = 'ChannelStatusMode';

    protected $FullName = 'MXF::Main';

    protected $GroupName = 'MXF';

    protected $g0 = 'MXF';

    protected $g1 = 'MXF';

    protected $g2 = 'Video';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Channel Status Mode';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Channel Status Data',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AES3 Minimum',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AES3 Standard',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Fixed 24 Bytes in FixedChannelStatusData',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Stream of Data in MXF Header Metadata',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Stream of Data Multiplexed within MXF Body',
        ),
    );

}
