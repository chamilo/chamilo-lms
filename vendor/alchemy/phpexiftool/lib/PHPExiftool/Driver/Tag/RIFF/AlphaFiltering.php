<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AlphaFiltering extends AbstractTag
{

    protected $Id = '0.1';

    protected $Name = 'AlphaFiltering';

    protected $FullName = 'RIFF::ALPH';

    protected $GroupName = 'RIFF';

    protected $g0 = 'RIFF';

    protected $g1 = 'RIFF';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Alpha Filtering';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'none',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Horizontal',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Vertical',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Gradient',
        ),
    );

}
