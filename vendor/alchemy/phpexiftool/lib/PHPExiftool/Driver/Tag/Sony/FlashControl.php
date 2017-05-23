<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FlashControl extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashControl';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Flash Control';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'ADI',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Pre-flash TTL',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Manual',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'ADI Flash',
        ),
        4 => array(
            'Id' => 2,
            'Label' => 'Pre-flash TTL',
        ),
    );

}
