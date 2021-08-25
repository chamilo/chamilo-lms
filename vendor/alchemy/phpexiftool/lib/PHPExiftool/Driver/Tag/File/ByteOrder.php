<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\File;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ByteOrder extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'ByteOrder';

    protected $FullName = 'DPX::Main';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Byte Order';

    protected $MaxLength = 4;

    protected $Values = array(
        'SDPX' => array(
            'Id' => 'SDPX',
            'Label' => 'Big-endian',
        ),
        'XPDS' => array(
            'Id' => 'XPDS',
            'Label' => 'Little-endian',
        ),
    );

}
