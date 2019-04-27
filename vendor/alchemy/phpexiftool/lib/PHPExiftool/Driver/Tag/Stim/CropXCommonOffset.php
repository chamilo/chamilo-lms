<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Stim;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CropXCommonOffset extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'CropXCommonOffset';

    protected $FullName = 'Stim::CropX';

    protected $GroupName = 'Stim';

    protected $g0 = 'Stim';

    protected $g1 = 'Stim';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Crop X Common Offset';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Common Offset Setting',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Individual Offset Setting',
        ),
    );

}
