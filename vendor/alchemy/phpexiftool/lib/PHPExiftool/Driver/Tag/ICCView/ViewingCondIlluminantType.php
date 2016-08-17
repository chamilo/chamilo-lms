<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ICCView;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ViewingCondIlluminantType extends AbstractTag
{

    protected $Id = 32;

    protected $Name = 'ViewingCondIlluminantType';

    protected $FullName = 'ICC_Profile::ViewingConditions';

    protected $GroupName = 'ICC-view';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-view';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Viewing Cond Illuminant Type';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'D50',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'D65',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'D93',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'F2',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'D55',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'A',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Equi-Power (E)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'F8',
        ),
    );

}
