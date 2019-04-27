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
class Rating extends AbstractTag
{

    protected $Id = 'rtng';

    protected $Name = 'Rating';

    protected $FullName = 'mixed';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'mixed';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Rating';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'none',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Explicit',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Clean',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Explicit (old)',
        ),
    );

}
