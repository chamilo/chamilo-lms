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
class DynamicAFArea extends AbstractTag
{

    protected $Id = '1.4';

    protected $Name = 'DynamicAFArea';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Dynamic AF Area';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '9 Points',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '21 Points',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '51 Points',
        ),
        12 => array(
            'Id' => 12,
            'Label' => '51 Points (3D-tracking)',
        ),
    );

}
