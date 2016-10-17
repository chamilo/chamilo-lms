<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExternalFlashBounce extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ExternalFlashBounce';

    protected $FullName = 'mixed';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'External Flash Bounce';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Bounce or Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Direct',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Yes',
        ),
    );

}
