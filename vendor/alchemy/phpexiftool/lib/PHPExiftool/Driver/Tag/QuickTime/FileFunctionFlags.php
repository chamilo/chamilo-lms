<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FileFunctionFlags extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'FileFunctionFlags';

    protected $FullName = 'QuickTime::FileProf';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Video';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'File Function Flags';

    protected $Values = array(
        268435456 => array(
            'Id' => 268435456,
            'Label' => 'Fragmented',
        ),
        536870912 => array(
            'Id' => 536870912,
            'Label' => 'Additional tracks',
        ),
        1073741824 => array(
            'Id' => 1073741824,
            'Label' => 'Edited',
        ),
    );

}
