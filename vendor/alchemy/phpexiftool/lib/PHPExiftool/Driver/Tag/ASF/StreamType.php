<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ASF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class StreamType extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'StreamType';

    protected $FullName = 'ASF::StreamProperties';

    protected $GroupName = 'ASF';

    protected $g0 = 'ASF';

    protected $g1 = 'ASF';

    protected $g2 = 'Video';

    protected $Type = 'binary';

    protected $Writable = false;

    protected $Description = 'Stream Type';

    protected $MaxLength = 16;

    protected $Values = array(
        '35907DE0-E415-11CF-A917-00805F5C442B' => array(
            'Id' => '35907DE0-E415-11CF-A917-00805F5C442B',
            'Label' => 'Degradable JPEG',
        ),
        '3AFB65E2-47EF-40F2-AC2C-70A90D71D343' => array(
            'Id' => '3AFB65E2-47EF-40F2-AC2C-70A90D71D343',
            'Label' => 'Binary',
        ),
        '59DACFC0-59E6-11D0-A3AC-00A0C90348F6' => array(
            'Id' => '59DACFC0-59E6-11D0-A3AC-00A0C90348F6',
            'Label' => 'Command',
        ),
        '91BD222C-F21C-497A-8B6D-5AA86BFC0185' => array(
            'Id' => '91BD222C-F21C-497A-8B6D-5AA86BFC0185',
            'Label' => 'File Transfer',
        ),
        'B61BE100-5B4E-11CF-A8FD-00805F5C442B' => array(
            'Id' => 'B61BE100-5B4E-11CF-A8FD-00805F5C442B',
            'Label' => 'JFIF',
        ),
        'BC19EFC0-5B4D-11CF-A8FD-00805F5C442B' => array(
            'Id' => 'BC19EFC0-5B4D-11CF-A8FD-00805F5C442B',
            'Label' => 'Video',
        ),
        'F8699E40-5B4D-11CF-A8FD-00805F5C442B' => array(
            'Id' => 'F8699E40-5B4D-11CF-A8FD-00805F5C442B',
            'Label' => 'Audio',
        ),
    );

}
