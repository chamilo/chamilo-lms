<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Apple;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HDRImageType extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'HDRImageType';

    protected $FullName = 'Apple::Main';

    protected $GroupName = 'Apple';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Apple';

    protected $g2 = 'Image';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'HDR Image Type';

    protected $flag_Permanent = true;

    protected $Values = array(
        3 => array(
            'Id' => 3,
            'Label' => 'HDR Image',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Original Image',
        ),
    );

}
