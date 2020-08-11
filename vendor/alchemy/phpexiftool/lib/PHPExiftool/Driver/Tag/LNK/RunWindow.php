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
class RunWindow extends AbstractTag
{

    protected $Id = 60;

    protected $Name = 'RunWindow';

    protected $FullName = 'LNK::Main';

    protected $GroupName = 'LNK';

    protected $g0 = 'LNK';

    protected $g1 = 'LNK';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Run Window';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Hide',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Normal',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Show Minimized',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Show Maximized',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Show No Activate',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Show',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Minimized',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Show Minimized No Activate',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Show NA',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Restore',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Show Default',
        ),
    );

}
