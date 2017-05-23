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
class ByteOrder extends AbstractTag
{

    protected $Id = '060e2b34.0101.0101.03010201.02000000';

    protected $Name = 'ByteOrder';

    protected $FullName = 'MXF::Main';

    protected $GroupName = 'MXF';

    protected $g0 = 'MXF';

    protected $g1 = 'MXF';

    protected $g2 = 'Video';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Byte Order';

    protected $Values = array(
        'II' => array(
            'Id' => 'II',
            'Label' => 'Little-endian (Intel, II)',
        ),
        'MM' => array(
            'Id' => 'MM',
            'Label' => 'Big-endian (Motorola, MM)',
        ),
    );

}
