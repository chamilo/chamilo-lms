<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\JFIF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ResolutionUnit extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'ResolutionUnit';

    protected $FullName = 'JFIF::Main';

    protected $GroupName = 'JFIF';

    protected $g0 = 'JFIF';

    protected $g1 = 'JFIF';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Resolution Unit';

    protected $flag_Mandatory = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'inches',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'cm',
        ),
    );

}
