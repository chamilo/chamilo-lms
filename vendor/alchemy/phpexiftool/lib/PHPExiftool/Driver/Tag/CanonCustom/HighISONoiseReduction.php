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
class HighISONoiseReduction extends AbstractTag
{

    protected $Id = 514;

    protected $Name = 'HighISONoiseReduction';

    protected $FullName = 'CanonCustom::Functions2';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'High ISO Noise Reduction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Low',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Strong',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
    );

    protected $Index = 'mixed';

}
