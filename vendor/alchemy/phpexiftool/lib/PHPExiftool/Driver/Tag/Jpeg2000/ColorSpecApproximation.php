<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Jpeg2000;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorSpecApproximation extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'ColorSpecApproximation';

    protected $FullName = 'Jpeg2000::ColorSpec';

    protected $GroupName = 'Jpeg2000';

    protected $g0 = 'Jpeg2000';

    protected $g1 = 'Jpeg2000';

    protected $g2 = 'Image';

    protected $Type = 'int8s';

    protected $Writable = false;

    protected $Description = 'Color Spec Approximation';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Not Specified',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Accurate',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Exceptional Quality',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Reasonable Quality',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Poor Quality',
        ),
    );

}
