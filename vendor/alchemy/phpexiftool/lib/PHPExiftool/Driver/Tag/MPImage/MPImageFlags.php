<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MPImage;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MPImageFlags extends AbstractTag
{

    protected $Id = '0.1';

    protected $Name = 'MPImageFlags';

    protected $FullName = 'MPF::MPImage';

    protected $GroupName = 'MPImage';

    protected $g0 = 'MPF';

    protected $g1 = 'MPImage';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'MP Image Flags';

    protected $Values = array(
        536870912 => array(
            'Id' => 536870912,
            'Label' => 'Representative image',
        ),
        1073741824 => array(
            'Id' => 1073741824,
            'Label' => 'Dependent child image',
        ),
        '2147483648' => array(
            'Id' => '2147483648',
            'Label' => 'Dependent parent image',
        ),
    );

}
