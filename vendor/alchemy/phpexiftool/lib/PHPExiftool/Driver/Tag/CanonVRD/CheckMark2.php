<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonVRD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CheckMark2 extends AbstractTag
{

    protected $Id = 142;

    protected $Name = 'CheckMark2';

    protected $FullName = 'CanonVRD::Ver2';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Check Mark 2';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Clear',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 1,
        ),
        2 => array(
            'Id' => 2,
            'Label' => 2,
        ),
        3 => array(
            'Id' => 3,
            'Label' => 3,
        ),
        4 => array(
            'Id' => 4,
            'Label' => 4,
        ),
        5 => array(
            'Id' => 5,
            'Label' => 5,
        ),
    );

}
