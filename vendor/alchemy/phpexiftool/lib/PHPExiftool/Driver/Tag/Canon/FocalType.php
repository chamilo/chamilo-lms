<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FocalType extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FocalType';

    protected $FullName = 'mixed';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Focal Type';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Fixed',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Zoom',
        ),
        2 => array(
            'Id' => 1,
            'Label' => 'Fixed',
        ),
        3 => array(
            'Id' => 2,
            'Label' => 'Zoom',
        ),
    );

}
