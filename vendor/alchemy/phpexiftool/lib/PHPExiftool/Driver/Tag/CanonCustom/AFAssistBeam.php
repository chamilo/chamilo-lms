<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFAssistBeam extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFAssistBeam';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'AF Assist Beam';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Emits',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Does not emit',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'IR AF assist beam only',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Emits',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'Does not emit',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'Only ext. flash emits',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'IR AF assist beam only',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Emits',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Does not emit',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'Only ext. flash emits',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Emits',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Does not emit',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Only ext. flash emits',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Emits',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'Does not emit',
        ),
        15 => array(
            'Id' => 2,
            'Label' => 'Only ext. flash emits',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'Emits',
        ),
        17 => array(
            'Id' => 1,
            'Label' => 'Does not emit',
        ),
        18 => array(
            'Id' => 2,
            'Label' => 'Only ext. flash emits',
        ),
        19 => array(
            'Id' => 0,
            'Label' => 'Emits',
        ),
        20 => array(
            'Id' => 1,
            'Label' => 'Does not emit',
        ),
    );

    protected $Index = 'mixed';

}
