<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FocusRangeIndex extends AbstractTag
{

    protected $Id = '3.1';

    protected $Name = 'FocusRangeIndex';

    protected $FullName = 'Pentax::LensData';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Focus Range Index';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 5,
        ),
        1 => array(
            'Id' => 1,
            'Label' => 4,
        ),
        2 => array(
            'Id' => 2,
            'Label' => '6 (far)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '7 (very far)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 2,
        ),
        5 => array(
            'Id' => 5,
            'Label' => 3,
        ),
        6 => array(
            'Id' => 6,
            'Label' => '1 (close)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '0 (very close)',
        ),
    );

}
