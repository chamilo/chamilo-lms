<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Ricoh;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AutoBracketing extends AbstractTag
{

    protected $Id = 4103;

    protected $Name = 'AutoBracketing';

    protected $FullName = 'Ricoh::Main';

    protected $GroupName = 'Ricoh';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Ricoh';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Auto Bracketing';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'AE',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'WB',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'DR',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Contrast',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'WB2',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Effect',
        ),
    );

}
