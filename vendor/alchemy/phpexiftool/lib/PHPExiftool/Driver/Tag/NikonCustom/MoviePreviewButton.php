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
class MoviePreviewButton extends AbstractTag
{

    protected $Id = '41.2';

    protected $Name = 'MoviePreviewButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Movie Preview Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Power Aperture (open)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Index Marking',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'View Photo Shooting Info',
        ),
    );

}
