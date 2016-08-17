<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DaylightSavings extends AbstractTag
{

    protected $Id = 3;

    protected $Name = 'DaylightSavings';

    protected $FullName = 'Canon::TimeInfo';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Time';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Daylight Savings';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        60 => array(
            'Id' => 60,
            'Label' => 'On',
        ),
    );

}
