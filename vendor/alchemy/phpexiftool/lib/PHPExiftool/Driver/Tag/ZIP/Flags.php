<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ZIP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Flags extends AbstractTag
{

    protected $Id = 3;

    protected $Name = 'Flags';

    protected $FullName = 'ZIP::GZIP';

    protected $GroupName = 'ZIP';

    protected $g0 = 'ZIP';

    protected $g1 = 'ZIP';

    protected $g2 = 'Other';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Flags';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Text',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'CRC16',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'ExtraFields',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'FileName',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Comment',
        ),
    );

}
