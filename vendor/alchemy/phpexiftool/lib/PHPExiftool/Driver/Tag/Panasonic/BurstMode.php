<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BurstMode extends AbstractTag
{

    protected $Id = 42;

    protected $Name = 'BurstMode';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Burst Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Auto Exposure Bracketing (AEB)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Unlimited',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'White Balance Bracketing',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'On (with flash)',
        ),
    );

}
