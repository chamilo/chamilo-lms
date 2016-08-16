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
class DisplaySize extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'DisplaySize';

    protected $FullName = 'QuickTime::Video';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Video';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Display Size';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Double Size',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Half Size',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Full Screen',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Current Size',
        ),
    );

}
