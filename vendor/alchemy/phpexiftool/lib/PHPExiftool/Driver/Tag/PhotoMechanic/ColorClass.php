<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PhotoMechanic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorClass extends AbstractTag
{

    protected $Id = 222;

    protected $Name = 'ColorClass';

    protected $FullName = 'PhotoMechanic::SoftEdit';

    protected $GroupName = 'PhotoMechanic';

    protected $g0 = 'PhotoMechanic';

    protected $g1 = 'PhotoMechanic';

    protected $g2 = 'Image';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Color Class';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '0 (None)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1 (Winner)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '2 (Winner alt)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '3 (Superior)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '4 (Superior alt)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '5 (Typical)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '6 (Typical alt)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '7 (Extras)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '8 (Trash)',
        ),
    );

}
