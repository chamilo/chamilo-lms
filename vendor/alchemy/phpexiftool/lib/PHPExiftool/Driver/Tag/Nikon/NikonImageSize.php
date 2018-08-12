<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class NikonImageSize extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'NikonImageSize';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Nikon Image Size';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Large',
        ),
        1 => array(
            'Id' => 8,
            'Label' => 'Medium',
        ),
        2 => array(
            'Id' => 16,
            'Label' => 'Small',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Large (10.0 M)',
        ),
        4 => array(
            'Id' => 16,
            'Label' => 'Medium (5.6 M)',
        ),
        5 => array(
            'Id' => 32,
            'Label' => 'Small (2.5 M)',
        ),
    );

}
