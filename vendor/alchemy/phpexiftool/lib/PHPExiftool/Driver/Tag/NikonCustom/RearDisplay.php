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
class RearDisplay extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'RearDisplay';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Rear Display';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'ISO',
        ),
        1 => array(
            'Id' => 128,
            'Label' => 'Exposures Remaining',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'ISO',
        ),
        3 => array(
            'Id' => 64,
            'Label' => 'Exposures Remaining',
        ),
    );

}
