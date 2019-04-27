<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SelfTimerShotInterval extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'SelfTimerShotInterval';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Self Timer Shot Interval';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '0.5 s',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '1 s',
        ),
        32 => array(
            'Id' => 32,
            'Label' => '2 s',
        ),
        48 => array(
            'Id' => 48,
            'Label' => '3 s',
        ),
    );

}
