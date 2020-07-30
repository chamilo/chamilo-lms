<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MNG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class YMethod extends AbstractTag
{

    protected $Id = 17;

    protected $Name = 'YMethod';

    protected $FullName = 'MNG::MagnifyObject';

    protected $GroupName = 'MNG';

    protected $g0 = 'MNG';

    protected $g1 = 'MNG';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Y Method';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Magnification',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Pixel Replication',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Linear Interpolation',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Closest Pixel',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Color Linear Interpolation and Alpha Closest Pixel',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Color Closest Pixel and Alpha Linear Interpolation',
        ),
    );

}
