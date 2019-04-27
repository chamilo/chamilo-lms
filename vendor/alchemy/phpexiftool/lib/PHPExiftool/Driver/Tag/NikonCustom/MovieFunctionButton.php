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
class MovieFunctionButton extends AbstractTag
{

    protected $Id = '41.1';

    protected $Name = 'MovieFunctionButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Movie Function Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Power Aperture (open)',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Index Marking',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'View Photo Shooting Info',
        ),
    );

}
